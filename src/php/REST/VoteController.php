<?php

declare(strict_types=1);

namespace ContentVote\REST;

use ContentVote\Services\VoteStorageService;
use ContentVote\Security\SecurityHelper;

class VoteController {
	private string $namespace = 'content-vote/v1';

	public function register(): void {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return; // Non-WordPress execution safety.
		}
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/block/(?P<blockId>[a-zA-Z0-9_-]+)/vote', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_vote' ],
				'permission_callback' => '__return_true', // Public vote, but we enforce nonce below.
				'args'                => [
					'optionIndex' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return $value >= 0 && $value <= 5;
						}
					],
				],
			] );
			// Debug-only reset endpoint
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				register_rest_route( $this->namespace, '/block/(?P<blockId>[a-zA-Z0-9_-]+)/reset', [
					'methods'             => 'POST',
					'callback'            => [ $this, 'handle_reset' ],
					'permission_callback' => '__return_true',
				] );
			}
		} );
	}

	/**
	 * Handle incoming vote request with security validation.
	 * 
	 * @param \WP_REST_Request $request REST request object
	 * @return array|\WP_Error Vote results or error
	 */
	public function handle_vote( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! SecurityHelper::verify_nonce( $nonce ) ) {
			return $this->error( 'invalid_nonce', 'Nonce validation failed', 403 );
		}
		// Validate AUTH_KEY is properly configured for secure hashing.
		if ( ! defined( 'AUTH_KEY' ) || AUTH_KEY === 'put your unique phrase here' || empty( AUTH_KEY ) ) {
			return $this->error( 'config_error', 'WordPress AUTH_KEY must be configured for vote security.', 500 );
		}
		$block_id = sanitize_text_field( $request[ 'blockId' ] );
		// Validate block ID length (UUIDs are 36 chars; allow some flexibility).
		if ( strlen( $block_id ) > 64 || strlen( $block_id ) < 8 ) {
			return $this->error( 'invalid_block_id', 'Block ID format invalid.', 400 );
		}
		$option_index = (int) $request->get_param( 'optionIndex' );
		$post_id      = (int) $request->get_param( 'postId' ); // Provided by client or resolved front-end.
		if ( $post_id <= 0 ) {
			$post_id = 0; // Will still store; improvement later.
		}
		$token   = $this->get_or_create_token();
		$hashed  = hash( 'sha256', $token . AUTH_KEY );
		$service = new VoteStorageService();
		$result  = $service->record_vote( $block_id, $post_id, $option_index, $hashed );
		if ( isset( $result[ 'error' ] ) && $result[ 'error' ] ) {
			return $this->error( $result[ 'code' ], $result[ 'message' ], $result[ 'status' ] );
		}
		return $result;
	}

	/**
	 * Get or create anonymous voter token with secure cookie.
	 * 
	 * @return string Unique voter token
	 */
	private function get_or_create_token(): string {
		if ( isset( $_COOKIE[ 'content_vote_token' ] ) ) {
			return sanitize_text_field( $_COOKIE[ 'content_vote_token' ] );
		}
		$token = bin2hex( random_bytes( 16 ) );
		// Set cookie with SameSite attribute for CSRF protection.
		$secure  = is_ssl();
		$options = [
			'expires'  => time() + ( 3600 * 24 * 365 ),
			'path'     => COOKIEPATH ?: '/',
			'domain'   => '',
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		];
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie( 'content_vote_token', $token, $options );
		} else {
			// PHP < 7.3 fallback (append samesite to path).
			setcookie( 'content_vote_token', $token, $options[ 'expires' ], $options[ 'path' ] . '; samesite=' . $options[ 'samesite' ], $options[ 'domain' ], $options[ 'secure' ], $options[ 'httponly' ] );
		}
		return $token;
	}

	/**
	 * Handle reset request (debug only).
	 * Clears the voter token cookie to allow re-voting.
	 * 
	 * @param \WP_REST_Request $request REST request object
	 * @return array Reset confirmation
	 */
	public function handle_reset( $request ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return $this->error( 'forbidden', 'Reset only available in debug mode', 403 );
		}
		// Clear the voter token cookie
		$options = [
			'expires'  => time() - 3600,
			'path'     => COOKIEPATH ?: '/',
			'domain'   => '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		];
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie( 'content_vote_token', '', $options );
		} else {
			setcookie( 'content_vote_token', '', $options[ 'expires' ], $options[ 'path' ] . '; samesite=' . $options[ 'samesite' ], $options[ 'domain' ], $options[ 'secure' ], $options[ 'httponly' ] );
		}
		return [ 'success' => true, 'message' => 'Vote reset successfully' ];
	}

	private function error( string $code, string $message, int $status ) {
		if ( class_exists( 'WP_Error' ) ) {
			return new \WP_Error( $code, $message, [ 'status' => $status ] );
		}
		return [ 'error' => true, 'code' => $code, 'message' => $message, 'status' => $status ];
	}
}
