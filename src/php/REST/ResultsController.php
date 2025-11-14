<?php

declare(strict_types=1);

namespace ContentPoll\REST;

use ContentPoll\Services\VoteStorageService;
use ContentPoll\Security\SecurityHelper;

/**
 * REST controller for aggregated vote results per block instance.
 */
class ResultsController {
	private string $namespace = 'content-poll/v1';

	/**
	 * Register the results endpoint returning counts & percentages.
	 */
	public function register(): void {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/block/(?P<pollId>[a-zA-Z0-9_-]+)/results', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_results' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'pollId' => [ 'type' => 'string', 'required' => true ],
				],
			] );
		} );
	}

	/**
	 * Build and return aggregate data for a block.
	 * Adds userVote when a matching hashed token is found.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array Aggregate payload.
	 */
	public function get_results( $request ) {
		$poll_id      = sanitize_text_field( (string) $request->get_param( 'pollId' ) );
		$block_id     = sanitize_text_field( (string) $request->get_param( 'blockId' ) );
		$effective_id = $poll_id !== '' ? $poll_id : $block_id;
		$service      = new VoteStorageService();
		$agg          = $service->get_aggregate( $effective_id );

		// Add user's vote if they have voted
		if ( isset( $_COOKIE[ 'content_poll_token' ] ) && defined( 'AUTH_KEY' ) ) {
			$token        = sanitize_text_field( $_COOKIE[ 'content_poll_token' ] );
			$hashed_token = hash( 'sha256', $token . AUTH_KEY );
			$user_vote    = $service->get_user_vote( $effective_id, $hashed_token );
			if ( $user_vote !== null ) {
				$agg[ 'userVote' ] = $user_vote;
			}
		}

		// Front-end decides visibility (e.g., hide until first vote using totalVotes).
		return $agg;
	}
}
