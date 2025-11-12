<?php
// Minimal bootstrap for unit tests (without full WP).
require_once __DIR__ . '/../vendor/autoload.php';

// Mock WordPress functions used by SettingsPage getters
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}
