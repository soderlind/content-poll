<?php

declare(strict_types=1);

namespace ContentVote\REST;

use ContentVote\Services\VoteStorageService;
use ContentVote\Security\SecurityHelper;

class ResultsController {
	private string $namespace = 'content-vote/v1';

	public function register(): void {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/block/(?P<blockId>[a-zA-Z0-9_-]+)/results', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_results' ],
				'permission_callback' => '__return_true',
				'args'                => [
						'blockId' => [ 'type' => 'string', 'required' => true ],
					],
			] );
		} );
	}

	public function get_results( $request ) {
		$block_id = sanitize_text_field( $request[ 'blockId' ] );
		$service  = new VoteStorageService();
		$agg      = $service->get_aggregate( $block_id );

		// Add user's vote if they have voted
		if ( isset( $_COOKIE[ 'content_vote_token' ] ) && defined( 'AUTH_KEY' ) ) {
			$token        = sanitize_text_field( $_COOKIE[ 'content_vote_token' ] );
			$hashed_token = hash( 'sha256', $token . AUTH_KEY );
			$user_vote    = $service->get_user_vote( $block_id, $hashed_token );
			if ( $user_vote !== null ) {
				$agg[ 'userVote' ] = $user_vote;
			}
		}

		// Hide results until first vote: front-end can check totalVotes; here we just pass data.
		return $agg;
	}
}
