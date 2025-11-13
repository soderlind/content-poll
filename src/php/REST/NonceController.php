<?php

declare(strict_types=1);

namespace ContentPoll\REST;

use ContentPoll\Security\SecurityHelper;

/**
 * REST controller for providing a fresh nonce value to the client.
 */
class NonceController {
	private string $namespace = 'content-poll/v1';

	/**
	 * Register the nonce endpoint.
	 */
	public function register(): void {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/nonce', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_nonce' ],
				'permission_callback' => '__return_true', // Public nonce for vote action only.
			] );
		} );
	}

	/**
	 * Return a newly created nonce for vote requests.
	 *
	 * @param \WP_REST_Request $request Request object (unused).
	 * @return array{nonce:string}
	 */
	public function get_nonce( $request ) {
		return [ 'nonce' => SecurityHelper::create_nonce() ];
	}
}
