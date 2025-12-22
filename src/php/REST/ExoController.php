<?php
/**
 * REST controller for Exo AI provider endpoints.
 *
 * Provides endpoints to check Exo health and list available models
 * for the settings page dynamic UI.
 *
 * @package ContentPoll\REST
 * @since   0.9.3
 */

declare(strict_types=1);

namespace ContentPoll\REST;

/**
 * REST controller for Exo health check and model listing.
 */
class ExoController {
	private string $namespace = 'content-poll/v1';

	/**
	 * Register Exo endpoints.
	 */
	public function register(): void {
		add_action( 'rest_api_init', function () {
			register_rest_route( $this->namespace, '/exo-health', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'check_health' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'endpoint' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					],
				],
			] );

			register_rest_route( $this->namespace, '/exo-models', [
				'methods'             => 'POST',
				'callback'            => [ $this, 'list_models' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'endpoint' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'esc_url_raw',
					],
				],
			] );
		} );
	}

	/**
	 * Check if Exo is running at the given endpoint.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array Response with status.
	 */
	public function check_health( $request ): array {
		$endpoint = $request->get_param( 'endpoint' );

		if ( empty( $endpoint ) ) {
			return [
				'status'  => 'error',
				'message' => 'Endpoint is required',
			];
		}

		$url = rtrim( $endpoint, '/' ) . '/v1/models';

		$response = wp_remote_get( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'status'  => 'error',
				'message' => $response->get_error_message(),
			];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return [
				'status'  => 'error',
				'message' => sprintf( 'HTTP %d', $code ),
			];
		}

		return [
			'status'  => 'ok',
			'message' => 'Connected to Exo',
		];
	}

	/**
	 * List running models from Exo.
	 *
	 * Queries the /state endpoint to find only models that are currently
	 * loaded and running, then cross-references with /v1/models to get
	 * the correct short IDs.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return array Response with models list.
	 */
	public function list_models( $request ): array {
		$endpoint = $request->get_param( 'endpoint' );

		if ( empty( $endpoint ) ) {
			return [
				'models' => [],
				'error'  => 'Endpoint is required',
			];
		}

		$base_url = rtrim( $endpoint, '/' );

		// First, get the model ID mapping from /v1/models
		$models_url      = $base_url . '/v1/models';
		$models_response = wp_remote_get( $models_url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 10,
		] );

		$model_id_map = []; // hugging_face_id => short_id
		if ( ! is_wp_error( $models_response ) ) {
			$models_body = wp_remote_retrieve_body( $models_response );
			$models_data = json_decode( $models_body, true );
			if ( is_array( $models_data ) && isset( $models_data['data'] ) ) {
				foreach ( $models_data['data'] as $model ) {
					$hf_id    = $model['hugging_face_id'] ?? '';
					$short_id = $model['id'] ?? '';
					if ( ! empty( $hf_id ) && ! empty( $short_id ) ) {
						$model_id_map[ $hf_id ] = $short_id;
					}
				}
			}
		}

		// Query /state endpoint to get running models
		$state_url = $base_url . '/state';

		$response = wp_remote_get( $state_url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return [
				'models' => [],
				'error'  => $response->get_error_message(),
			];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return [
				'models' => [],
				'error'  => sprintf( 'HTTP %d: Unable to connect to Exo', $code ),
			];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || ! isset( $data['instances'] ) ) {
			return [
				'models' => [],
				'error'  => 'Invalid response from Exo state endpoint',
			];
		}

		// Extract running models from instances
		$models    = [];
		$seen_ids  = [];
		$instances = $data['instances'] ?? [];

		foreach ( $instances as $instance ) {
			// Handle MlxRingInstance format
			$ring_instance = $instance['MlxRingInstance'] ?? $instance;
			$assignments   = $ring_instance['shardAssignments'] ?? [];

			// Get model info from shard metadata
			$runner_to_shard = $assignments['runnerToShard'] ?? [];
			foreach ( $runner_to_shard as $runner_data ) {
				$shard_meta = $runner_data['PipelineShardMetadata'] ?? $runner_data;
				$model_meta = $shard_meta['modelMeta'] ?? [];

				$full_model_id = $model_meta['modelId'] ?? '';
				$model_name    = $model_meta['prettyName'] ?? $full_model_id;

				if ( ! empty( $full_model_id ) && ! isset( $seen_ids[ $full_model_id ] ) ) {
					// Look up short ID from model mapping
					$short_id = $model_id_map[ $full_model_id ] ?? $this->fallback_short_id( $full_model_id );

					$models[] = [
						'id'   => $short_id,
						'name' => $model_name,
					];
					$seen_ids[ $full_model_id ] = true;
				}
			}
		}

		if ( empty( $models ) ) {
			return [
				'models' => [],
				'error'  => 'No running models found. Start a model in Exo first.',
			];
		}

		return [
			'models' => $models,
		];
	}

	/**
	 * Fallback conversion from full model ID to short format.
	 *
	 * Used when /v1/models lookup fails. Applies common naming patterns.
	 *
	 * @param string $model_id Full model ID (e.g., "mlx-community/Llama-3.2-3B-Instruct-8bit").
	 * @return string Best-effort short model ID.
	 */
	private function fallback_short_id( string $model_id ): string {
		// Remove mlx-community/ prefix
		$name = basename( $model_id );

		// Common transformations to match Exo short ID format
		$short = strtolower( $name );
		$short = str_replace( '-instruct', '', $short );
		$short = str_replace( 'meta-', '', $short );

		return $short;
	}
}
