<?php
/**
 * Database Manager for ContentPoll AI
 *
 * Handles all database operations including table creation, schema migrations,
 * and upgrades. Uses singleton pattern to ensure consistent state.
 *
 * Why not activation hook?
 * - register_activation_hook fires on file changes (version bumps, edits)
 * - This caused vote data loss during updates due to re-triggered migrations
 * - Running migrations on plugins_loaded with option flag is more reliable
 *
 * @package ContentPoll
 * @since 0.7.5
 */

declare(strict_types=1);

namespace ContentPoll\Database;

/**
 * Manages database schema and migrations for the plugin.
 */
class DatabaseManager {

	/**
	 * Singleton instance.
	 *
	 * @var DatabaseManager|null
	 */
	private static ?DatabaseManager $instance = null;

	/**
	 * Database version for tracking schema changes.
	 *
	 * @var string
	 */
	private const DB_VERSION = '1.1.0';

	/**
	 * Option key for tracking database version.
	 *
	 * @var string
	 */
	private const DB_VERSION_OPTION = 'content_poll_db_version';

	/**
	 * Legacy option key for migration tracking (deprecated).
	 *
	 * @var string
	 */
	private const LEGACY_MIGRATION_OPTION = 'content_poll_poll_id_migrated';

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Table name with prefix.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Private constructor (singleton pattern).
	 */
	private function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'vote_block_submissions';
	}

	/**
	 * Get singleton instance.
	 *
	 * @return DatabaseManager
	 */
	public static function instance(): DatabaseManager {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize database: create table or run migrations if needed.
	 *
	 * This method is safe to call multiple times - it checks the stored
	 * database version and only runs migrations when needed.
	 *
	 * @return void
	 */
	public function initialize(): void {
		// Skip migrations during PHPUnit tests to prevent data loss.
		// Tests should use isolated test database or explicit truncation.
		if ( defined( 'PHPUNIT_COMPOSER_INSTALL' ) || defined( 'PHPUNIT_TEST' ) ) {
			// Only ensure table exists, skip migrations.
			$this->ensure_table_exists_without_migration();
			return;
		}

		$current_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		// Fresh install or schema needs creation/update.
		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			$this->ensure_table_exists();
			$this->run_migrations( $current_version );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );

			// Clean up legacy migration flag if present.
			if ( get_option( self::LEGACY_MIGRATION_OPTION, false ) ) {
				delete_option( self::LEGACY_MIGRATION_OPTION );
			}
		}
	}

	/**
	 * Ensure the submissions table exists with current schema.
	 *
	 * Creates table if missing, or verifies required columns exist.
	 *
	 * @return void
	 */
	private function ensure_table_exists(): void {
		$table_exists = $this->wpdb->get_var(
			$this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name )
		);

		if ( $table_exists !== $this->table_name ) {
			$this->create_table();
			return;
		}

		// Table exists, ensure it has current schema.
		$this->ensure_poll_id_column();
	}

	/**
	 * Ensure table exists without running migrations (for tests).
	 *
	 * @return void
	 */
	private function ensure_table_exists_without_migration(): void {
		$table_exists = $this->wpdb->get_var(
			$this->wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name )
		);

		if ( $table_exists !== $this->table_name ) {
			$this->create_table();
		}
	}

	/**
	 * Create the submissions table with current schema.
	 *
	 * Fresh install: creates table with poll_id as canonical identifier.
	 *
	 * @return void
	 */
	private function create_table(): void {
		$charset = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			poll_id VARCHAR(64) NOT NULL,
			block_id VARCHAR(64) NOT NULL,
			post_id BIGINT UNSIGNED NOT NULL,
			option_index TINYINT UNSIGNED NOT NULL,
			hashed_token CHAR(64) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_poll_token (poll_id, hashed_token),
			KEY idx_poll_option (poll_id, option_index),
			KEY idx_block_option (block_id, option_index)
		) $charset";

		$this->wpdb->query( $sql );
	}

	/**
	 * Ensure poll_id column exists (migration from old schema).
	 *
	 * Old schema used block_id as primary identifier.
	 * New schema uses poll_id (same value, clearer naming).
	 *
	 * @return void
	 */
	private function ensure_poll_id_column(): void {
		$columns = $this->wpdb->get_col(
			"SHOW COLUMNS FROM {$this->table_name} LIKE 'poll_id'"
		);

		if ( empty( $columns ) ) {
			// Add poll_id column with temporary empty default.
			$this->wpdb->query(
				"ALTER TABLE {$this->table_name} ADD COLUMN poll_id VARCHAR(64) NOT NULL DEFAULT '' AFTER id"
			);

			// Backfill poll_id from existing block_id for all rows.
			$this->wpdb->query(
				"UPDATE {$this->table_name} SET poll_id = block_id WHERE poll_id = ''"
			);
		}
	}

	/**
	 * Run schema migrations based on current database version.
	 *
	 * Each migration checks if it's needed before running (idempotent).
	 *
	 * @param string $from_version Current database version.
	 * @return void
	 */
	private function run_migrations( string $from_version ): void {
		// Migration 1.1.0: Add poll_id column and switch unique constraints.
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			$this->migrate_to_poll_id_indexes();
		}
	}

	/**
	 * Migration: Switch from block_id to poll_id unique constraint.
	 *
	 * Steps:
	 * 1. Ensure poll_id column exists (handled by ensure_table_exists)
	 * 2. Drop old unique constraint (uniq_block_token)
	 * 3. Add new unique constraint (uniq_poll_token)
	 * 4. Add new index (idx_poll_option)
	 *
	 * Each step checks if operation is needed (safe to re-run).
	 *
	 * @return void
	 */
	private function migrate_to_poll_id_indexes(): void {
		// Step 1: Drop old unique constraint if it exists.
		$old_indexes = $this->wpdb->get_results(
			"SHOW INDEX FROM {$this->table_name} WHERE Key_name = 'uniq_block_token'"
		);
		if ( ! empty( $old_indexes ) ) {
			$this->wpdb->query(
				"ALTER TABLE {$this->table_name} DROP INDEX uniq_block_token"
			);
		}

		// Step 2: Add new unique constraint if missing.
		$new_indexes = $this->wpdb->get_results(
			"SHOW INDEX FROM {$this->table_name} WHERE Key_name = 'uniq_poll_token'"
		);
		if ( empty( $new_indexes ) ) {
			$this->wpdb->query(
				"ALTER TABLE {$this->table_name} ADD UNIQUE KEY uniq_poll_token (poll_id, hashed_token)"
			);
		}

		// Step 3: Add poll_option index if missing.
		$poll_option_idx = $this->wpdb->get_results(
			"SHOW INDEX FROM {$this->table_name} WHERE Key_name = 'idx_poll_option'"
		);
		if ( empty( $poll_option_idx ) ) {
			$this->wpdb->query(
				"ALTER TABLE {$this->table_name} ADD KEY idx_poll_option (poll_id, option_index)"
			);
		}
	}

	/**
	 * Get table name with prefix.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		return $this->table_name;
	}

	/**
	 * Get current database version.
	 *
	 * @return string
	 */
	public function get_db_version(): string {
		return get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Prevent cloning (singleton pattern).
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization (singleton pattern).
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
