<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes the custom database table and any plugin options.
 *
 * @package ContentVote
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'vote_block_submissions';

// Use wpdb::prepare() with %i placeholder for table name (WordPress 6.2+)
// Fallback to direct query with sanitized prefix for older WP versions
if ( method_exists( $wpdb, 'prepare' ) && version_compare( $GLOBALS[ 'wp_version' ], '6.2', '>=' ) ) {
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
} else {
	// For WP < 6.2, use esc_sql on the table name
	$wpdb->query( "DROP TABLE IF EXISTS " . esc_sql( $table_name ) );
}

// Clean up any stored options (none currently, but留 for future use)
if ( function_exists( 'delete_option' ) ) {
	delete_option( 'content_vote_settings' );
}
