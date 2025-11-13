<?php

declare(strict_types=1);

namespace ContentPoll\Security;

/**
 * Helper for nonce generation / verification tied to WordPress REST context.
 */
class SecurityHelper {
	/**
	 * Create a nonce for REST requests.
	 */
	public static function create_nonce(): string {
		return wp_create_nonce( 'wp_rest' );
	}

	/**
	 * Verify a REST nonce.
	 *
	 * @param string|null $nonce Nonce string.
	 * @return bool True if valid.
	 */
	public static function verify_nonce( ?string $nonce ): bool {
		if ( ! $nonce ) {
			return false;
		}
		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}
}
