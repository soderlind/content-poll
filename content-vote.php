<?php
/**
 * Plugin Name: Content Vote
 * Description: Ask readers to vote on questions about your content. AI suggests relevant questions by analyzing your page. Beautiful card interface, real-time results.
 * Version: 0.3.0
 * Author: Per Soderlind
 * Author URI: https://soderlind.no
 * Plugin URI: https://github.com/soderlind/content-vote
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Text Domain: content-vote
 */

declare(strict_types=1);
// Composer autoload (optional until "composer install" is run).
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Direct requires (can be removed once full composer autoload adopted for these classes).
$cv_base = __DIR__ . '/src/php';

// Activation: create custom table.
if ( function_exists( 'register_activation_hook' ) ) {
	register_activation_hook( __FILE__, function () {
		global $wpdb;
		$table = $wpdb->prefix . 'vote_block_submissions';
		// Only create if not exists.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			$charset = $wpdb->get_charset_collate();
			$sql     = "CREATE TABLE $table (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				block_id VARCHAR(64) NOT NULL,
				post_id BIGINT UNSIGNED NOT NULL,
				option_index TINYINT UNSIGNED NOT NULL,
				hashed_token CHAR(64) NOT NULL,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_block_token (block_id, hashed_token),
				KEY idx_block_option (block_id, option_index)
			) $charset";
			$wpdb->query( $sql );
		}
	} );
}

// Inject CSP nonce attribute into enqueued script tags if a server-provided nonce is available.
// This helps satisfy strict Content Security Policy configurations that require a nonce on every script tag.
// You can expose a nonce via:
// 1. Setting $_SERVER['CONTENT_SECURITY_POLICY_NONCE'] in your mu-plugin or server layer.
// 2. Defining a constant CONTENT_VOTE_CSP_NONCE.
// 3. Adding a filter: add_filter('content_vote_csp_nonce', fn() => 'your-nonce');
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
		if ( strpos( $tag, ' nonce=' ) !== false ) {
			return $tag;
		}
		$csp_nonce = '';
		if ( isset( $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ] ) ) {
			$csp_nonce = (string) $_SERVER[ 'CONTENT_SECURITY_POLICY_NONCE' ];
		}
		if ( ! $csp_nonce && defined( 'CONTENT_VOTE_CSP_NONCE' ) ) {
			$csp_nonce = (string) CONTENT_VOTE_CSP_NONCE;
		}
		if ( function_exists( 'apply_filters' ) ) {
			$csp_nonce = apply_filters( 'content_vote_csp_nonce', $csp_nonce, $handle, $src );
		}
		if ( $csp_nonce ) {
			$escaped = function_exists( 'esc_attr' ) ? esc_attr( $csp_nonce ) : htmlspecialchars( $csp_nonce, ENT_QUOTES, 'UTF-8' );
			$tag     = preg_replace( '/<script\b/', '<script nonce="' . $escaped . '"', $tag, 1 );
		}
		return $tag;
	}, 10, 3 );
}

// Basic vote storage service placeholder.
require_once $cv_base . '/Services/VoteStorageService.php';
require_once $cv_base . '/Security/SecurityHelper.php';
require_once $cv_base . '/REST/VoteController.php';
require_once $cv_base . '/REST/NonceController.php';
require_once $cv_base . '/REST/ResultsController.php';
require_once $cv_base . '/Services/AISuggestionService.php';
require_once $cv_base . '/REST/SuggestionController.php';
require_once $cv_base . '/Blocks/VoteBlock.php';
require_once $cv_base . '/Admin/SettingsPage.php';

if ( function_exists( 'add_action' ) ) {
	add_action( 'plugins_loaded', function () {
		// Load translations.
		if ( function_exists( 'load_plugin_textdomain' ) && function_exists( 'plugin_basename' ) ) {
			load_plugin_textdomain( 'content-vote', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
		// Register REST routes.
		( new \ContentVote\REST\VoteController() )->register();
		( new \ContentVote\REST\NonceController() )->register();
		( new \ContentVote\REST\ResultsController() )->register();
		( new \ContentVote\REST\SuggestionController() )->register();
		// Register block with VoteBlock class for proper render callback and script enqueuing
		( new \ContentVote\Blocks\VoteBlock() )->register();
		// Initialize settings page (constructor hooks into WordPress actions)
		new \ContentVote\Admin\SettingsPage();
	} );
	// Removed inline localization to comply with strict CSP (no inline scripts).
	// Block editor JS now derives postId via wp.data.select('core/editor').getCurrentPostId()
	// and nonce from wpApiSettings.nonce (core REST setup) without plugin-added inline script.
}

