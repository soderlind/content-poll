<?php
/**
 * PHPUnit Bootstrap for Content Poll.
 *
 * Uses Brain Monkey for mocking WordPress functions.
 * Does NOT load WordPress core to avoid plugin autoloader conflicts.
 *
 * IMPORTANT: Since we define WordPress function stubs here, tests should NOT
 * try to redefine them with Brain Monkey's Functions\when(). Instead, tests
 * can use global variables to control behavior.
 *
 * @package ContentPoll\Tests
 */

declare(strict_types=1);

// Define constant to prevent migrations during tests
if ( ! defined( 'PHPUNIT_TEST' ) ) {
	define( 'PHPUNIT_TEST', true );
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define WordPress constants that may be needed
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', '/tmp/wordpress/wp-content' );
}
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

/**
 * Simple WP_Error class if not defined.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals, PEAR.NamingConventions
		public $errors = [];
		public $error_data = [];

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty( $code ) ) {
				return;
			}
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		public function get_error_codes() {
			return array_keys( $this->errors );
		}

		public function get_error_code() {
			$codes = $this->get_error_codes();
			return empty( $codes ) ? '' : $codes[ 0 ];
		}

		public function get_error_messages( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return $this->errors[ $code ] ?? [];
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			$messages = $this->get_error_messages( $code );
			return empty( $messages ) ? '' : $messages[ 0 ];
		}

		public function get_error_data( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			return $this->error_data[ $code ] ?? null;
		}

		public function add( $code, $message, $data = '' ) {
			$this->errors[ $code ][] = $message;
			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}
	}
}

/**
 * Global test data store for mocking WordPress behavior.
 * Tests can set these values to control function behavior.
 */
global $content_poll_test_options, $content_poll_test_transients, $content_poll_test_hooks;
$content_poll_test_options    = [];
$content_poll_test_transients = [];
$content_poll_test_hooks      = [];

/**
 * WordPress function stubs for unit testing.
 */

// Helper functions
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $thing instanceof \WP_Error;
	}
}

// Translation functions
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $text;
	}
}

// Escaping functions
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_js' ) ) {
	function esc_js( $text ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return addslashes( (string) $text );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return $data;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return htmlspecialchars( strip_tags( (string) $str ), ENT_QUOTES, 'UTF-8' );
	}
}

// Options API - uses global for test control
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_options;
		if ( is_array( $content_poll_test_options ) && array_key_exists( $option, $content_poll_test_options ) ) {
			return $content_poll_test_options[ $option ];
		}
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_options;
		$content_poll_test_options[ $option ] = $value;
		return true;
	}
}

// Transient functions - use global for test control
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_transients;
		$content_poll_test_transients[ $key ] = [
			'value'   => $value,
			'expires' => $expiration > 0 ? time() + (int) $expiration : PHP_INT_MAX,
		];
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_transients;
		if ( ! isset( $content_poll_test_transients[ $key ] ) ) {
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
		unset( $content_poll_test_transients[ $key ] );
		return true;
	}
}

// Capability check
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return false;
	}
}

// Hooks - store callbacks for testing
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_hooks;
		$content_poll_test_hooks[ 'actions' ][ $tag ][] = [
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_hooks;
		$content_poll_test_hooks[ 'filters' ][ $tag ][] = [
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
		return true;
	}
}

if ( ! function_exists( 'has_action' ) ) {
	function has_action( $tag, $callback = false ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		global $content_poll_test_hooks;
		if ( ! isset( $content_poll_test_hooks[ 'actions' ][ $tag ] ) ) {
			return false;
		}
		if ( $callback === false ) {
			return true;
		}
		foreach ( $content_poll_test_hooks[ 'actions' ][ $tag ] as $hook ) {
			if ( $hook[ 'callback' ] === $callback ) {
				return $hook[ 'priority' ];
			}
		}
		return false;
	}
}

// HTTP API
if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = [] ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return new \WP_Error( 'http_request_not_mocked', 'HTTP request not mocked in tests' );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = [] ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		return new \WP_Error( 'http_request_not_mocked', 'HTTP request not mocked in tests' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response[ 'response' ][ 'code' ] ?? '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return $response[ 'body' ] ?? '';
	}
}

