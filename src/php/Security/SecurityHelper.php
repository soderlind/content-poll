<?php

declare(strict_types=1);

namespace ContentVote\Security;

class SecurityHelper {
	public static function create_nonce(): string {
		if ( function_exists( 'wp_create_nonce' ) ) {
			// Use 'wp_rest' action for WordPress REST API compatibility
			return wp_create_nonce( 'wp_rest' );
		}
		return bin2hex( random_bytes( 12 ) );
	}

	public static function verify_nonce( ?string $nonce ): bool {
		if ( ! $nonce ) {
			return false;
		}
		if ( function_exists( 'wp_verify_nonce' ) ) {
			// Verify with 'wp_rest' action for WordPress REST API compatibility
			return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
		}
		// Fallback non-WordPress context always false.
		return false;
	}
}
