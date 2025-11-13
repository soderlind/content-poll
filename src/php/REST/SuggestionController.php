<?php

declare(strict_types=1);

namespace ContentPoll\REST;

use ContentPoll\Services\AISuggestionService;

/**
 * REST controller to generate AI-assisted poll suggestions based on post content.
 */
class SuggestionController {
	private string $namespace = 'content-poll/v1';

	/**
	 * Register suggestion endpoint restricted to editors/authors.
	 */
	public function register(): void {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/suggest', [
				'methods'             => 'GET',
				'callback'            => [ $this, 'suggest' ],
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'postId' => [ 'type' => 'integer', 'required' => true ],
				],
			] );
		} );
	}

	/**
	 * Produce suggestion payload (question + options) using AI provider or fallback heuristic.
	 *
	 * @param \WP_REST_Request $request Request with postId parameter.
	 * @return array|\WP_Error Suggestion data or error.
	 */
	public function suggest( $request ) {
		$post_id = (int) $request->get_param( 'postId' );
		if ( $post_id <= 0 ) {
			return $this->error( 'invalid_post', 'Invalid post id', 400 );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error( 'not_found', 'Post not found', 404 );
		}
		$service = new AISuggestionService();
		return $service->suggest( $post->post_content ?? '' );
	}

	/**
	 * Standardized error response helper.
	 *
	 * @param string $code Error code.
	 * @param string $message Message.
	 * @param int    $status HTTP status.
	 * @return \WP_Error|array
	 */
	private function error( string $code, string $message, int $status ) {
		if ( class_exists( 'WP_Error' ) ) {
			return new \WP_Error( $code, $message, [ 'status' => $status ] );
		}
		return [ 'error' => true, 'code' => $code, 'message' => $message, 'status' => $status ];
	}
}
