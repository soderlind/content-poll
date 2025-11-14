<?php
// Test bootstrap: attempt to load a real WordPress installation so core
// localization functions like __() are available. Falls back to lightweight
// stubs when WordPress is not present.

// Define constant to prevent migrations during tests
if ( ! defined( 'PHPUNIT_TEST' ) ) {
	define( 'PHPUNIT_TEST', true );
}

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
	// Only truncate if using a separate test database (prefix: test_).
	// This prevents accidentally wiping production data during development tests.
	global $wpdb;
	if ( isset( $wpdb ) ) {
		$table = $wpdb->prefix . 'vote_block_submissions';
		// Safety check: only truncate if table prefix indicates test database
		$isTestDatabase = (
			strpos( $wpdb->prefix, 'test_' ) === 0 ||
			strpos( $wpdb->prefix, 'phpunit_' ) === 0 ||
			getenv( 'TRUNCATE_TEST_DATA' ) === 'true'
		);

		if ( $isTestDatabase && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
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
	// Simple transient emulation for caching tests.
	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( $key, $value, $expiration ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			global $content_poll_test_transients;
			if ( ! is_array( $content_poll_test_transients ) ) {
				$content_poll_test_transients = [];
			}
			$content_poll_test_transients[ $key ] = [ 'value' => $value, 'expires' => time() + (int) $expiration ];
			return true;
		}
	}
	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			global $content_poll_test_transients;
			if ( empty( $content_poll_test_transients[ $key ] ) ) {
				return false;
			}
			$entry = $content_poll_test_transients[ $key ];
			if ( $entry[ 'expires' ] < time() ) {
				unset( $content_poll_test_transients[ $key ] );
				return false;
			}
			return $entry[ 'value' ];
		}
	}
	if ( ! function_exists( 'delete_transient' ) ) {
		function delete_transient( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			global $content_poll_test_transients;
			if ( isset( $content_poll_test_transients[ $key ] ) ) {
				unset( $content_poll_test_transients[ $key ] );
			}
			return true;
		}
	}
}

