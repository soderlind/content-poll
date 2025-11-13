<?php
// Test bootstrap: attempt to load a real WordPress installation so core
// localization functions like __() are available. Falls back to lightweight
// stubs when WordPress is not present.

require_once __DIR__ . '/../vendor/autoload.php';

$wpRoot = getenv( 'WP_ROOT' ) ?: '/Users/persoderlind/Sites/plugins/app/public';

if ( is_dir( $wpRoot ) && file_exists( $wpRoot . '/wp-load.php' ) ) {
	// Provide minimal server vars to satisfy WP in CLI context.
	$_SERVER[ 'HTTP_HOST' ]      = $_SERVER[ 'HTTP_HOST' ] ?? 'plugins.local';
	$_SERVER[ 'SERVER_NAME' ]    = $_SERVER[ 'SERVER_NAME' ] ?? 'plugins.local';
	$_SERVER[ 'REQUEST_METHOD' ] = $_SERVER[ 'REQUEST_METHOD' ] ?? 'GET';
	$_SERVER[ 'REMOTE_ADDR' ]    = $_SERVER[ 'REMOTE_ADDR' ] ?? '127.0.0.1';
	$_SERVER[ 'REQUEST_URI' ]    = $_SERVER[ 'REQUEST_URI' ] ?? '/';
	// Define home/site URLs if not set to reduce notices.
	if ( ! defined( 'WP_HOME' ) ) {
		define( 'WP_HOME', 'http://plugins.local' );
	}
	if ( ! defined( 'WP_SITEURL' ) ) {
		define( 'WP_SITEURL', 'http://plugins.local' );
	}
	// Prevent theme loading / headers noise during tests.
	if ( ! defined( 'WP_USE_THEMES' ) ) {
		define( 'WP_USE_THEMES', false );
	}
	// Load WordPress core. This will define __(), get_option(), etc.
	require_once $wpRoot . '/wp-load.php';
	// Reset custom vote table between test runs for isolation.
	global $wpdb;
	if ( isset( $wpdb ) ) {
		$table = $wpdb->prefix . 'vote_block_submissions';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$wpdb->query( 'TRUNCATE TABLE ' . $table );
		}
	}
} else {
	// Minimal mocks when WordPress is unavailable.
	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $option, $default = false ) {
			return $default;
		}
	}
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			return $text;
		}
	}
}

