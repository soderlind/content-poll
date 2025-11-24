<?php

declare(strict_types=1);

namespace ContentPoll\Admin;

use ContentPoll\Services\VoteAnalyticsService;
use ContentPoll\Admin\PollsListTable;

class SettingsPage {
	private string $option_group = 'content_poll_settings';
	private string $option_name = 'content_poll_options';

	/**
	 * Map of setting keys to their environment variable and constant names.
	 *
	 * @var array<string, array{env: string, const: string, default: mixed}>
	 */
	private static array $config_map = [
		'ai_provider'       => [ 'env' => 'CONTENT_POLL_AI_PROVIDER', 'const' => 'CONTENT_POLL_AI_PROVIDER', 'default' => 'heuristic' ],
		'openai_type'       => [ 'env' => 'CONTENT_POLL_OPENAI_TYPE', 'const' => 'CONTENT_POLL_OPENAI_TYPE', 'default' => 'openai' ],
		'openai_key'        => [ 'env' => 'CONTENT_POLL_OPENAI_KEY', 'const' => 'CONTENT_POLL_OPENAI_KEY', 'default' => '' ],
		'openai_model'      => [ 'env' => 'CONTENT_POLL_OPENAI_MODEL', 'const' => 'CONTENT_POLL_OPENAI_MODEL', 'default' => 'gpt-3.5-turbo' ],
		'azure_endpoint'    => [ 'env' => 'CONTENT_POLL_AZURE_ENDPOINT', 'const' => 'CONTENT_POLL_AZURE_ENDPOINT', 'default' => '' ],
		'azure_api_version' => [ 'env' => 'CONTENT_POLL_AZURE_API_VERSION', 'const' => 'CONTENT_POLL_AZURE_API_VERSION', 'default' => '2024-02-15-preview' ],
		'anthropic_key'     => [ 'env' => 'CONTENT_POLL_ANTHROPIC_KEY', 'const' => 'CONTENT_POLL_ANTHROPIC_KEY', 'default' => '' ],
		'anthropic_model'   => [ 'env' => 'CONTENT_POLL_ANTHROPIC_MODEL', 'const' => 'CONTENT_POLL_ANTHROPIC_MODEL', 'default' => 'claude-3-5-sonnet-20241022' ],
		'gemini_key'        => [ 'env' => 'CONTENT_POLL_GEMINI_KEY', 'const' => 'CONTENT_POLL_GEMINI_KEY', 'default' => '' ],
		'gemini_model'      => [ 'env' => 'CONTENT_POLL_GEMINI_MODEL', 'const' => 'CONTENT_POLL_GEMINI_MODEL', 'default' => 'gemini-1.5-flash' ],
		'ollama_endpoint'   => [ 'env' => 'CONTENT_POLL_OLLAMA_ENDPOINT', 'const' => 'CONTENT_POLL_OLLAMA_ENDPOINT', 'default' => 'http://localhost:11434' ],
		'ollama_model'      => [ 'env' => 'CONTENT_POLL_OLLAMA_MODEL', 'const' => 'CONTENT_POLL_OLLAMA_MODEL', 'default' => 'llama3.2' ],
		'grok_key'          => [ 'env' => 'CONTENT_POLL_GROK_KEY', 'const' => 'CONTENT_POLL_GROK_KEY', 'default' => '' ],
		'grok_model'        => [ 'env' => 'CONTENT_POLL_GROK_MODEL', 'const' => 'CONTENT_POLL_GROK_MODEL', 'default' => 'grok-2' ],
	];

	public function __construct() {
		$this->option_group = 'content_poll_options_group';
		$this->option_name  = 'content_poll_options';
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'load-settings_page_content-poll-settings', [ $this, 'add_screen_options' ] );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 10, 3 );

		// Add admin columns
		// Add list table columns for Posts and Pages
		// Primary dynamic filters for columns
		add_filter( 'manage_edit-post_columns', [ $this, 'add_votes_column' ] );
		add_filter( 'manage_edit-page_columns', [ $this, 'add_votes_column' ] );
		// Fallback legacy filters (ensure compatibility)
		add_filter( 'manage_posts_columns', [ $this, 'add_votes_column' ] );
		add_filter( 'manage_pages_columns', [ $this, 'add_votes_column' ] );
		// Row rendering actions
		add_action( 'manage_posts_custom_column', [ $this, 'render_votes_column' ], 10, 2 );
		add_action( 'manage_pages_custom_column', [ $this, 'render_votes_column' ], 10, 2 );
	}
	public function add_settings_page(): void {
		add_options_page(
			__( 'ContentPoll Settings', 'content-poll' ),
			__( 'ContentPoll AI', 'content-poll' ),
			'manage_options',
			'content-poll-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	public function add_screen_options(): void {
		add_screen_option( 'per_page', [
			'label'   => __( 'Poll Posts per page', 'content-poll' ),
			'default' => 20,
			'option'  => 'content_poll_polls_per_page',
		] );
	}

	public function set_screen_option( $status, $option, $value ) {
		if ( $option === 'content_poll_polls_per_page' ) {
			return (int) $value;
		}
		return $status;
	}

	public function register_settings(): void {
		register_setting( $this->option_group, $this->option_name, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_settings' ],
			'default'           => [
				'ai_provider'       => 'heuristic',
				'openai_type'       => 'openai',
				'openai_key'        => '',
				'openai_model'      => 'gpt-3.5-turbo',
				'azure_endpoint'    => '',
				'azure_deployment'  => '',
				'azure_api_version' => '2024-02-15-preview',
				'anthropic_key'     => '',
				'anthropic_model'   => 'claude-3-5-sonnet-20241022',
				'gemini_key'        => '',
				'gemini_model'      => 'gemini-1.5-flash',
				'ollama_endpoint'   => 'http://localhost:11434',
				'ollama_model'      => 'llama3.2',
				'grok_key'          => '',
				'grok_model'        => 'grok-2',
			],
		] );

		add_settings_section(
			'content_poll_ai_section',
			__( 'AI Suggestion Settings', 'content-poll' ),
			[ $this, 'render_ai_section_description' ],
			'content-poll-settings'
		);

		add_settings_field(
			'ai_provider',
			__( 'AI Provider', 'content-poll' ),
			[ $this, 'render_ai_provider_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);

		add_settings_field(
			'openai_type',
			__( 'OpenAI Type', 'content-poll' ),
			[ $this, 'render_openai_type_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);

		add_settings_field(
			'openai_key',
			__( 'API Key', 'content-poll' ),
			[ $this, 'render_openai_key_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);

		add_settings_field(
			'openai_model',
			__( 'Model / Deployment', 'content-poll' ),
			[ $this, 'render_openai_model_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);

		add_settings_field(
			'azure_endpoint',
			__( 'Azure OpenAI Endpoint', 'content-poll' ),
			[ $this, 'render_azure_endpoint_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);

		add_settings_field(
			'azure_api_version',
			__( 'Azure API Version', 'content-poll' ),
			[ $this, 'render_azure_api_version_field' ],
			'content-poll-settings',
			'content_poll_ai_section'
		);
	}

	public function sanitize_settings( $input ): array {
		$sanitized = [];

		$sanitized[ 'ai_provider' ] = isset( $input[ 'ai_provider' ] ) && in_array( $input[ 'ai_provider' ], [ 'heuristic', 'openai', 'anthropic', 'gemini', 'ollama', 'grok' ], true )
			? $input[ 'ai_provider' ]
			: 'heuristic';
		$sanitized[ 'grok_key' ]    = isset( $input[ 'grok_key' ] ) ? sanitize_text_field( $input[ 'grok_key' ] ) : '';
		$sanitized[ 'grok_model' ]  = isset( $input[ 'grok_model' ] ) ? sanitize_text_field( $input[ 'grok_model' ] ) : 'grok-2';

		$sanitized[ 'openai_type' ] = isset( $input[ 'openai_type' ] ) && in_array( $input[ 'openai_type' ], [ 'openai', 'azure' ], true )
			? $input[ 'openai_type' ]
			: 'openai';

		$sanitized[ 'openai_key' ] = isset( $input[ 'openai_key' ] ) ? sanitize_text_field( $input[ 'openai_key' ] ) : '';

		$sanitized[ 'openai_model' ] = isset( $input[ 'openai_model' ] ) ? sanitize_text_field( $input[ 'openai_model' ] ) : 'gpt-3.5-turbo';

		$sanitized[ 'azure_endpoint' ] = isset( $input[ 'azure_endpoint' ] ) ? esc_url_raw( $input[ 'azure_endpoint' ] ) : '';

		$sanitized[ 'azure_deployment' ] = isset( $input[ 'azure_deployment' ] ) ? sanitize_text_field( $input[ 'azure_deployment' ] ) : '';

		$sanitized[ 'azure_api_version' ] = isset( $input[ 'azure_api_version' ] ) ? sanitize_text_field( $input[ 'azure_api_version' ] ) : '2024-02-15-preview';

		$sanitized[ 'anthropic_key' ] = isset( $input[ 'anthropic_key' ] ) ? sanitize_text_field( $input[ 'anthropic_key' ] ) : '';

		$sanitized[ 'anthropic_model' ] = isset( $input[ 'anthropic_model' ] ) ? sanitize_text_field( $input[ 'anthropic_model' ] ) : 'claude-3-5-sonnet-20241022';

		$sanitized[ 'gemini_key' ] = isset( $input[ 'gemini_key' ] ) ? sanitize_text_field( $input[ 'gemini_key' ] ) : '';

		$sanitized[ 'gemini_model' ] = isset( $input[ 'gemini_model' ] ) ? sanitize_text_field( $input[ 'gemini_model' ] ) : 'gemini-1.5-flash';

		$sanitized[ 'ollama_endpoint' ] = isset( $input[ 'ollama_endpoint' ] ) ? esc_url_raw( $input[ 'ollama_endpoint' ] ) : 'http://localhost:11434';

		$sanitized[ 'ollama_model' ] = isset( $input[ 'ollama_model' ] ) ? sanitize_text_field( $input[ 'ollama_model' ] ) : 'llama3.2';

		// Validate AI provider configuration when non-heuristic provider is selected
		if ( $sanitized[ 'ai_provider' ] !== 'heuristic' ) {
			$this->validate_ai_configuration( $sanitized );
		}

		return $sanitized;
	}

	/**
	 * Validate AI configuration by attempting a test request
	 */
	private function validate_ai_configuration( array $settings ): void {
		$provider     = $settings[ 'ai_provider' ];
		$test_content = 'This is a test article about technology and innovation.';

		$error = null;

		switch ( $provider ) {
			case 'openai':
				$error = $this->test_openai( $settings );
				break;
			case 'anthropic':
				$error = $this->test_anthropic( $settings );
				break;
			case 'gemini':
				$error = $this->test_gemini( $settings );
				break;
			case 'ollama':
				$error = $this->test_ollama( $settings );
				break;
			case 'grok':
				$error = $this->test_grok( $settings );
				break;
		}

		if ( $error ) {
			// Dedupe: ensure we only add this error once per request.
			$existing = get_settings_errors();
			foreach ( $existing as $ex ) {
				if ( isset( $ex[ 'code' ] ) && $ex[ 'code' ] === 'ai_validation_error' ) {
					return;
				}
			}
			add_settings_error(
				'content_poll_messages',
				'ai_validation_error',
				/* translators: %s: error message returned from validating the AI provider configuration */
				sprintf( __( 'AI Configuration Warning: %s', 'content-poll' ), $error ),
				'error'
			);
		}
	}

	private function test_openai( array $settings ): ?string {
		$api_key = $settings[ 'openai_key' ];
		$model   = $settings[ 'openai_model' ];
		$type    = $settings[ 'openai_type' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null; // Don't validate if credentials not provided
		}

		if ( $type === 'azure' ) {
			$endpoint = $settings[ 'azure_endpoint' ];
			if ( empty( $endpoint ) ) {
				return null;
			}
			$url     = rtrim( $endpoint, '/' ) . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $settings[ 'azure_api_version' ];
			$headers = [
				'Content-Type' => 'application/json',
				'api-key'      => $api_key,
			];
		} else {
			$url     = 'https://api.openai.com/v1/chat/completions';
			$headers = [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			];
		}

		$response = wp_remote_post( $url, [
			'headers' => $headers,
			'body'    => wp_json_encode( [
				'model'      => $model,
				'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
				'max_tokens' => 5,
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_anthropic( array $settings ): ?string {
		$api_key = $settings[ 'anthropic_key' ];
		$model   = $settings[ 'anthropic_model' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'headers' => [
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'max_tokens' => 5,
				'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_gemini( array $settings ): ?string {
		$api_key = $settings[ 'gemini_key' ];
		$model   = $settings[ 'gemini_model' ];

		if ( empty( $api_key ) || empty( $model ) ) {
			return null;
		}

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'contents' => [ [ 'parts' => [ [ 'text' => 'test' ] ] ] ],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	private function test_ollama( array $settings ): ?string {
		$endpoint = $settings[ 'ollama_endpoint' ];
		$model    = $settings[ 'ollama_model' ];

		if ( empty( $endpoint ) || empty( $model ) ) {
			return null;
		}

		$url = rtrim( $endpoint, '/' ) . '/api/generate';

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'model'  => $model,
				'prompt' => 'test',
				'stream' => false,
			] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return is_string( $data[ 'error' ] ) ? $data[ 'error' ] : ( $data[ 'error' ][ 'message' ] ?? 'Unknown error' );
		}

		return null;
	}

	private function test_grok( array $settings ): ?string {
		$api_key = $settings[ 'grok_key' ] ?? '';
		$model   = $settings[ 'grok_model' ] ?? 'grok-2';

		if ( empty( $api_key ) || empty( $model ) ) {
			return null; // Don't validate if credentials not provided
		}

		$url     = 'https://api.x.ai/v1/chat/completions';
		$headers = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		];
		$payload = [
			'model'      => $model,
			'messages'   => [ [ 'role' => 'user', 'content' => 'test' ] ],
			'max_tokens' => 5,
		];

		$response = wp_remote_post( $url, [
			'headers' => $headers,
			'body'    => wp_json_encode( $payload ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			return $data[ 'error' ][ 'message' ] ?? 'Unknown error';
		}

		return null;
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Determine active tab
		$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'analytics';
		$valid_tabs = [ 'analytics', 'settings' ];
		if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
			$active_tab = 'analytics';
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=content-poll-settings&tab=analytics"
					class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Analytics', 'content-poll' ); ?>
				</a>
				<a href="?page=content-poll-settings&tab=settings"
					class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'AI Settings', 'content-poll' ); ?>
				</a>
			</h2>

			<?php if ( $active_tab === 'analytics' ) : ?>
				<?php $this->render_analytics_tab(); ?>
			<?php elseif ( $active_tab === 'settings' ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $this->option_group );
					do_settings_sections( 'content-poll-settings' );
					submit_button( __( 'Save Settings', 'content-poll' ) );
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_analytics_tab(): void {
		$analytics  = new VoteAnalyticsService();
		$list_table = null;
		if ( class_exists( '\\ContentPoll\\Admin\\PollsListTable' ) ) {
			$list_table = new PollsListTable( $analytics );
		}

		// Handle orphan poll deletion request.
		if ( isset( $_GET[ 'content_poll_delete_orphan' ] ) && current_user_can( 'manage_options' ) ) {
			$block_to_delete = sanitize_text_field( (string) $_GET[ 'content_poll_delete_orphan' ] );
			$nonce           = $_GET[ '_wpnonce' ] ?? '';
			if ( $block_to_delete && $nonce && wp_verify_nonce( $nonce, 'content_poll_delete_orphan_' . $block_to_delete ) ) {
				$deleted = $analytics->delete_block_votes( $block_to_delete );
				if ( $deleted > 0 ) {
					add_settings_error(
						'content_poll_messages',
						'content_poll_orphan_deleted',
						/* translators: %d: number of rows deleted */
						sprintf( __( 'Deleted %d orphan vote record(s).', 'content-poll' ), $deleted ),
						'success'
					);
				} else {
					add_settings_error(
						'content_poll_messages',
						'content_poll_orphan_none',
						__( 'No orphan records matched for deletion.', 'content-poll' ),
						'info'
					);
				}
			}
		}
		// Display any settings messages (for deletion feedback).
		settings_errors( 'content_poll_messages' );

		// Handle post detail view
		$viewing_post_id = isset( $_GET[ 'post_id' ] ) ? absint( $_GET[ 'post_id' ] ) : 0;

		if ( $viewing_post_id > 0 ) {
			$this->render_post_detail( $analytics, $viewing_post_id );
			return;
		}

		// Summary metrics
		$total_votes   = $analytics->get_total_votes();
		$total_polls   = $analytics->get_total_polls();
		$avg_votes     = $analytics->get_average_votes_per_poll();
		$top_polls     = $analytics->get_top_polls( 5 );
		$recent        = $analytics->get_recent_activity( 5 );
		$posts_summary = $analytics->get_posts_summary();
		$orphans       = $analytics->detect_orphan_block_ids();

		?>
		<div class="content-poll-analytics" style="margin-top: 20px;">

			<!-- Summary Cards -->
			<div
				style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<div class="postbox" style="padding: 20px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Total Votes', 'content-poll' ); ?></h3>
					<p style="font-size: 32px; font-weight: bold; margin: 0;">
						<?php echo esc_html( number_format_i18n( $total_votes ) ); ?>
					</p>
				</div>
				<div class="postbox" style="padding: 20px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Total Polls', 'content-poll' ); ?></h3>
					<p style="font-size: 32px; font-weight: bold; margin: 0;">
						<?php echo esc_html( number_format_i18n( $total_polls ) ); ?>
					</p>
				</div>
				<div class="postbox" style="padding: 20px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'Average Votes/Poll', 'content-poll' ); ?></h3>
					<p style="font-size: 32px; font-weight: bold; margin: 0;">
						<?php echo esc_html( number_format_i18n( $avg_votes, 1 ) ); ?>
					</p>
				</div>
			</div>

			<?php if ( empty( $posts_summary ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No votes recorded yet. Add poll blocks to your posts and pages to start collecting votes.', 'content-poll' ); ?>
					</p>
				</div>
			<?php else : ?>

				<?php if ( ! empty( $orphans ) ) : ?>
					<div class="postbox" style="padding: 20px; margin-bottom: 20px; border-left:4px solid #d63638;">
						<h2 style="margin-top: 0; color:#d63638;">
							<?php esc_html_e( 'Orphan Poll Data (No Matching Blocks)', 'content-poll' ); ?>
						</h2>
						<p><?php esc_html_e( 'These vote records belong to poll IDs that no longer appear in any post or page content. You can safely delete them if you no longer need the historical data.', 'content-poll' ); ?>
						</p>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Poll ID', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Approximate Votes', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'content-poll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $orphans as $row ) :
									$poll_id    = $row[ 'poll_id' ];
									$vote_count = (int) $row[ 'approx_vote_count' ];
									$short_id   = substr( $poll_id, 0, 8 ) . '...';
									?>
									<tr>
										<td>
											<code><?php echo esc_html( $short_id ); ?></code>
										</td>
										<td><?php echo esc_html( number_format_i18n( $vote_count ) ); ?></td>
										<td>
											<a href="<?php echo esc_url( wp_nonce_url( '?page=content-poll-settings&tab=analytics&content_poll_delete_orphan=' . urlencode( $poll_id ), 'content_poll_delete_orphan_' . $poll_id ) ); ?>"
												class="button button-small"
												onclick="return confirm('<?php esc_attr_e( 'Delete all vote records for this orphan poll? This cannot be undone.', 'content-poll' ); ?>');">
												<?php esc_html_e( 'Delete Data', 'content-poll' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- Posts Summary List Table -->
				<div class="postbox" style="padding: 20px; margin-bottom: 20px;">
					<h2 style="margin-top: 0;"><?php esc_html_e( 'Posts with Polls', 'content-poll' ); ?></h2>
					<?php if ( $list_table ) : ?>
						<form method="get">
							<input type="hidden" name="page" value="content-poll-settings" />
							<input type="hidden" name="tab" value="analytics" />
							<?php $list_table->prepare_items();
							$list_table->display(); ?>
						</form>
					<?php else : ?>
						<p><?php esc_html_e( 'List table unavailable.', 'content-poll' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Top Polls -->
				<?php if ( ! empty( $top_polls ) ) : ?>
					<div class="postbox" style="padding: 20px; margin-bottom: 20px;">
						<h2 style="margin-top: 0;"><?php esc_html_e( 'Top Polls by Votes', 'content-poll' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Poll Question', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Total Votes', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Last Vote', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'content-poll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $top_polls as $poll ) :
									$attrs     = $analytics->get_block_attributes( (int) $poll->post_id, $poll->poll_id );
									$is_orphan = ! $attrs;
									$question  = $attrs ? $attrs[ 'question' ] : __( 'Orphan Poll (block removed)', 'content-poll' );
									$short_id  = substr( $poll->poll_id, 0, 8 ) . '...';
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( $question ); ?></strong>
											<?php
											/* translators: %s: truncated poll identifier */
											$poll_id_label = sprintf( __( 'Poll ID: %s', 'content-poll' ), $short_id );
											?>
											<br><small><?php echo esc_html( $poll_id_label ); ?><?php if ( $is_orphan ) : ?>
													• <span
														style="color:#d63638; font-weight:600;"><?php esc_html_e( 'Orphan', 'content-poll' ); ?></span><?php endif; ?></small>
										</td>
										<td><?php echo esc_html( number_format_i18n( (int) $poll->total_votes ) ); ?></td>
										<td><?php echo esc_html( $poll->last_vote ? human_time_diff( strtotime( $poll->last_vote ), time() ) . ' ' . __( 'ago', 'content-poll' ) : '-' ); ?>
										</td>
										<td>
											<?php if ( $is_orphan ) : ?>
												<a href="<?php echo esc_url( wp_nonce_url( '?page=content-poll-settings&tab=analytics&content_poll_delete_orphan=' . urlencode( $poll->poll_id ), 'content_poll_delete_orphan_' . $poll->poll_id ) ); ?>"
													class="button button-small"
													onclick="return confirm('<?php esc_attr_e( 'Delete all vote records for this orphan poll? This cannot be undone.', 'content-poll' ); ?>');">
													<?php esc_html_e( 'Delete Data', 'content-poll' ); ?>
												</a>
											<?php else : ?>
												<a href="<?php echo esc_url( '?page=content-poll-settings&tab=analytics&post_id=' . (int) $poll->post_id ); ?>"
													class="button button-small">
													<?php esc_html_e( 'View Details', 'content-poll' ); ?>
												</a>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

			<?php endif; ?>
		</div>
		<?php
	}

	private function render_post_detail( VoteAnalyticsService $analytics, int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			?>
			<div class="notice notice-error">
				<p><?php esc_html_e( 'Post not found.', 'content-poll' ); ?></p>
			</div>
			<p>
				<a href="?page=content-poll-settings&tab=analytics" class="button">
					<?php esc_html_e( '← Back to Analytics', 'content-poll' ); ?>
				</a>
			</p>
			<?php
			return;
		}

		$blocks      = $analytics->get_post_block_totals( $post_id );
		$block_attrs = $analytics->get_post_block_attributes( $post_id );

		?>
		<div class="content-poll-post-detail" style="margin-top: 20px;">
			<p>
				<a href="?page=content-poll-settings&tab=analytics" class="button">
					<?php esc_html_e( '← Back to Analytics', 'content-poll' ); ?>
				</a>
			</p>

			<h2>
				<?php echo esc_html( $post->post_title ?: __( '(No title)', 'content-poll' ) ); ?>
				<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button button-small"
					style="margin-left: 10px;">
					<?php esc_html_e( 'Edit Post', 'content-poll' ); ?>
				</a>
			</h2>

			<?php if ( empty( $blocks ) ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'No votes recorded for polls on this post yet.', 'content-poll' ); ?></p>
				</div>
			<?php else : ?>
				<?php foreach ( $blocks as $block ) :
					$attrs     = $block_attrs[ $block->poll_id ] ?? null;
					$question  = $attrs ? $attrs[ 'question' ] : __( 'Untitled Poll', 'content-poll' );
					$options   = $attrs ? $attrs[ 'options' ] : [];
					$breakdown = $analytics->get_block_option_breakdown( $block->poll_id );
					?>
					<div class="postbox" style="padding: 20px; margin-bottom: 20px;">
						<h3 style="margin-top: 0;"><?php echo esc_html( $question ); ?></h3>
						<p>
							<strong><?php esc_html_e( 'Total Votes:', 'content-poll' ); ?></strong>
							<?php echo esc_html( number_format_i18n( (int) $block->total_votes ) ); ?>
							&nbsp;|&nbsp;
							<strong><?php esc_html_e( 'Last Vote:', 'content-poll' ); ?></strong>
							<?php echo esc_html( $block->last_vote ? human_time_diff( strtotime( $block->last_vote ), time() ) . ' ' . __( 'ago', 'content-poll' ) : '-' ); ?>
						</p>

						<table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
							<thead>
								<tr>
									<th style="width: 50%;"><?php esc_html_e( 'Option', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Votes', 'content-poll' ); ?></th>
									<th><?php esc_html_e( 'Percentage', 'content-poll' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$option_count = count( $options );
								for ( $i = 0; $i < $option_count; $i++ ) :
									$label = $options[ $i ] ?? __( 'Option', 'content-poll' ) . ' ' . ( $i + 1 );
									$votes = $breakdown[ 'counts' ][ $i ] ?? 0;
									$pct   = $breakdown[ 'percentages' ][ $i ] ?? 0;
									?>
									<tr>
										<td>
											<strong><?php echo esc_html( chr( 65 + $i ) ); ?>.</strong>
											<?php echo esc_html( $label ); ?>
										</td>
										<td><?php echo esc_html( number_format_i18n( $votes ) ); ?></td>
										<td>
											<?php echo esc_html( number_format_i18n( $pct, 1 ) . '%' ); ?>
											<div
												style="background: #ddd; height: 20px; border-radius: 3px; overflow: hidden; margin-top: 5px;">
												<div style="background: #2271b1; height: 100%; width: <?php echo esc_attr( $pct ); ?>%;">
												</div>
											</div>
										</td>
									</tr>
								<?php endfor; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_ai_section_description(): void {
		?>
		<p><?php esc_html_e( 'Configure the AI service used for generating vote option suggestions.', 'content-poll' ); ?></p>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const providerSelect = document.getElementById('ai_provider');
				const typeSelect = document.getElementById('openai_type');
				const azureFields = document.querySelectorAll('#azure_endpoint, #azure_api_version');
				const apiKeyField = document.getElementById('openai_key');
				const modelField = document.getElementById('openai_model');
				const modelLabel = modelField ? modelField.closest('tr').querySelector('th label') : null;

				function updateFieldVisibility() {
					const provider = providerSelect.value;
					const isOpenAI = provider === 'openai';
					const isAzure = typeSelect.value === 'azure';
					const isAnthropic = provider === 'anthropic';
					const isGemini = provider === 'gemini';
					const isOllama = provider === 'ollama';
					const isGrok = provider === 'grok';

					// Show/hide OpenAI type selector
					if (typeSelect) {
						typeSelect.closest('tr').style.display = isOpenAI ? '' : 'none';
					}

					// Show/hide API Key field based on provider
					if (apiKeyField) {
						const keyLabel = apiKeyField.closest('tr').querySelector('th label');
						apiKeyField.closest('tr').style.display = (provider !== 'heuristic' && provider !== 'ollama') ? '' : 'none';

						// Update API key label and description
						if (keyLabel) {
							if (isAnthropic) {
								keyLabel.textContent = 'Anthropic API Key';
							} else if (isGemini) {
								keyLabel.textContent = 'Google AI API Key';
							} else if (isGrok) {
								keyLabel.textContent = 'xAI API Key';
							} else {
								keyLabel.textContent = 'API Key';
							}
						}
					}

					// Show/hide Model field
					if (modelField) {
						modelField.closest('tr').style.display = (provider !== 'heuristic') ? '' : 'none';
					}

					// Show/hide Azure-specific fields
					azureFields.forEach(field => {
						field.closest('tr').style.display = (isOpenAI && isAzure) ? '' : 'none';
					});

					// Update model label based on provider
					if (modelLabel) {
						if (isAzure) {
							modelLabel.textContent = 'Deployment Name';
						} else if (isOllama) {
							modelLabel.textContent = 'Model Name';
						} else {
							modelLabel.textContent = 'Model';
						}
					}
				}

				providerSelect.addEventListener('change', updateFieldVisibility);
				if (typeSelect) {
					typeSelect.addEventListener('change', updateFieldVisibility);
				}
				updateFieldVisibility();
			});
		</script>
		<?php
	}

	public function render_ai_provider_field(): void {
		$current     = self::get_ai_provider();
		$is_external = self::is_externally_defined( 'ai_provider' );
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]" id="ai_provider" <?php echo $is_external ? 'disabled' : ''; ?>>
			<option value="heuristic" <?php selected( $current, 'heuristic' ); ?>>
				<?php esc_html_e( 'Heuristic AI (Default)', 'content-poll' ); ?>
			</option>
			<option value="openai" <?php selected( $current, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI', 'content-poll' ); ?>
			</option>
			<option value="anthropic" <?php selected( $current, 'anthropic' ); ?>>
				<?php esc_html_e( 'Anthropic Claude', 'content-poll' ); ?>
			</option>
			<option value="gemini" <?php selected( $current, 'gemini' ); ?>>
				<?php esc_html_e( 'Google Gemini', 'content-poll' ); ?>
			</option>
			<option value="ollama" <?php selected( $current, 'ollama' ); ?>>
				<?php esc_html_e( 'Ollama (Self-Hosted)', 'content-poll' ); ?>
			</option>
			<option value="grok" <?php selected( $current, 'grok' ); ?>>
				<?php esc_html_e( 'Grok (xAI)', 'content-poll' ); ?>
			</option>
		</select>
		<?php if ( $is_external ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]"
				value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<?php $this->render_external_indicator( 'ai_provider' ); ?>
		<p class="description">
			<?php esc_html_e( 'Heuristic AI uses built-in logic without API calls. Other options require API keys or local installation.', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_openai_type_field(): void {
		$current     = self::get_openai_type();
		$is_external = self::is_externally_defined( 'openai_type' );
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[openai_type]" id="openai_type" <?php echo $is_external ? 'disabled' : ''; ?>>
			<option value="openai" <?php selected( $current, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI', 'content-poll' ); ?>
			</option>
			<option value="azure" <?php selected( $current, 'azure' ); ?>>
				<?php esc_html_e( 'Azure OpenAI', 'content-poll' ); ?>
			</option>
		</select>
		<?php if ( $is_external ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name ); ?>[openai_type]"
				value="<?php echo esc_attr( $current ); ?>" />
		<?php endif; ?>
		<?php $this->render_external_indicator( 'openai_type' ); ?>
		<p class="description">
			<?php esc_html_e( 'Choose between standard OpenAI API or Azure OpenAI Service', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_openai_key_field(): void {
		$value       = self::get_openai_key();
		$is_external = self::is_externally_defined( 'openai_key' );
		$display_val = $is_external && $value !== '' ? '••••••••••••••••' : $value;
		?>
		<input type="password" name="<?php echo esc_attr( $this->option_name ); ?>[openai_key]" id="openai_key"
			value="<?php echo esc_attr( $is_external ? '' : $value ); ?>" class="regular-text" <?php echo $is_external ? 'readonly placeholder="' . esc_attr( $display_val ) . '"' : ''; ?> />
		<?php $this->render_external_indicator( 'openai_key' ); ?>
		<p class="description">
			<?php esc_html_e( 'API key from', 'content-poll' ); ?>
			<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>,
			<a href="https://console.anthropic.com/" target="_blank">Anthropic</a>,
			<?php esc_html_e( 'or', 'content-poll' ); ?>
			<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
		</p>
		<?php
	}

	public function render_openai_model_field(): void {
		$value       = self::get_openai_model();
		$is_external = self::is_externally_defined( 'openai_model' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[openai_model]" id="openai_model"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="gpt-3.5-turbo" <?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'openai_model' ); ?>
		<p class="description">
			<?php esc_html_e( 'OpenAI: gpt-3.5-turbo, gpt-4, etc. Anthropic: claude-3-5-sonnet-20241022, etc. Gemini: gemini-1.5-flash, etc. Ollama: llama3.2, mistral, etc.', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_azure_endpoint_field(): void {
		$value       = self::get_azure_endpoint();
		$is_external = self::is_externally_defined( 'azure_endpoint' );
		?>
		<input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[azure_endpoint]" id="azure_endpoint"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://your-resource.openai.azure.com"
			<?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'azure_endpoint' ); ?>
		<p class="description">
			<?php esc_html_e( 'Your Azure OpenAI resource endpoint URL', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_azure_api_version_field(): void {
		$value       = self::get_azure_api_version();
		$is_external = self::is_externally_defined( 'azure_api_version' );
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[azure_api_version]" id="azure_api_version"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="2024-02-15-preview" <?php echo $is_external ? 'readonly' : ''; ?> />
		<?php $this->render_external_indicator( 'azure_api_version' ); ?>
		<p class="description">
			<?php esc_html_e( 'Azure OpenAI API version (e.g., 2024-02-15-preview)', 'content-poll' ); ?>
		</p>
		<?php
	}

	/**
	 * Resolve a configuration value with priority: constant > env var > database > default.
	 *
	 * @param string $key The setting key (e.g., 'openai_key').
	 * @return string The resolved value.
	 */
	private static function resolve_config( string $key ): string {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return '';
		}

		// Priority 1: PHP constant (wp-config.php)
		if ( defined( $config[ 'const' ] ) ) {
			return (string) constant( $config[ 'const' ] );
		}

		// Priority 2: Environment variable
		$env_value = getenv( $config[ 'env' ] );
		if ( $env_value !== false && $env_value !== '' ) {
			return $env_value;
		}

		// Priority 3: Database option
		$options = get_option( 'content_poll_options', [] );
		if ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) {
			return (string) $options[ $key ];
		}

		// Priority 4: Default value
		return (string) $config[ 'default' ];
	}

	/**
	 * Check if a setting is defined via environment variable or constant.
	 *
	 * @param string $key The setting key.
	 * @return bool True if externally defined.
	 */
	public static function is_externally_defined( string $key ): bool {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return false;
		}

		if ( defined( $config[ 'const' ] ) ) {
			return true;
		}

		$env_value = getenv( $config[ 'env' ] );
		return $env_value !== false && $env_value !== '';
	}

	/**
	 * Get the source of a setting value.
	 *
	 * @param string $key The setting key.
	 * @return string 'constant', 'env', 'database', or 'default'.
	 */
	public static function get_config_source( string $key ): string {
		$config = self::$config_map[ $key ] ?? null;
		if ( ! $config ) {
			return 'default';
		}

		if ( defined( $config[ 'const' ] ) ) {
			return 'constant';
		}

		$env_value = getenv( $config[ 'env' ] );
		if ( $env_value !== false && $env_value !== '' ) {
			return 'env';
		}

		$options = get_option( 'content_poll_options', [] );
		if ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) {
			return 'database';
		}

		return 'default';
	}

	/**
	 * Render the "set via" indicator for externally defined settings.
	 *
	 * @param string $key The setting key.
	 */
	private function render_external_indicator( string $key ): void {
		if ( ! self::is_externally_defined( $key ) ) {
			return;
		}
		$source = self::get_config_source( $key );
		$label  = $source === 'constant'
			? __( 'wp-config.php constant', 'content-poll' )
			: __( 'environment variable', 'content-poll' );
		?>
		<span class="description" style="color: #2271b1; font-weight: 500; margin-left: 8px;">
			<?php
			/* translators: %s: source of the setting (constant or env) */
			printf( esc_html__( '(Set via %s)', 'content-poll' ), esc_html( $label ) );
			?>
		</span>
		<?php
	}

	public static function get_ai_provider(): string {
		return self::resolve_config( 'ai_provider' );
	}

	public static function get_openai_type(): string {
		return self::resolve_config( 'openai_type' );
	}

	public static function get_openai_key(): string {
		return self::resolve_config( 'openai_key' );
	}

	public static function get_openai_model(): string {
		return self::resolve_config( 'openai_model' );
	}

	public static function get_azure_endpoint(): string {
		return self::resolve_config( 'azure_endpoint' );
	}

	public static function get_azure_api_version(): string {
		return self::resolve_config( 'azure_api_version' );
	}

	public static function get_anthropic_key(): string {
		return self::resolve_config( 'anthropic_key' );
	}

	public static function get_anthropic_model(): string {
		return self::resolve_config( 'anthropic_model' );
	}

	public static function get_gemini_key(): string {
		return self::resolve_config( 'gemini_key' );
	}

	public static function get_gemini_model(): string {
		return self::resolve_config( 'gemini_model' );
	}

	public static function get_ollama_endpoint(): string {
		return self::resolve_config( 'ollama_endpoint' );
	}

	public static function get_ollama_model(): string {
		return self::resolve_config( 'ollama_model' );
	}

	public static function get_grok_key(): string {
		return self::resolve_config( 'grok_key' );
	}

	public static function get_grok_model(): string {
		return self::resolve_config( 'grok_model' );
	}

	/**
	 * Add votes column to post/page admin list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_votes_column( array $columns ): array {
		// Insert before comments column if it exists, otherwise append
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			if ( $key === 'comments' ) {
				$new_columns[ 'content_poll_votes' ] = __( 'Poll Votes', 'content-poll' );
			}
			$new_columns[ $key ] = $label;
		}
		// If comments column doesn't exist, append
		if ( ! isset( $new_columns[ 'content_poll_votes' ] ) ) {
			$new_columns[ 'content_poll_votes' ] = __( 'Poll Votes', 'content-poll' );
		}
		return $new_columns;
	}

	/**
	 * Render votes column content.
	 *
	 * @param string $column_name Column identifier.
	 * @param int    $post_id     Post ID.
	 */
	public function render_votes_column( string $column_name, int $post_id ): void {
		if ( $column_name !== 'content_poll_votes' ) {
			return;
		}

		$analytics   = new VoteAnalyticsService();
		$total_votes = $analytics->get_post_total_votes( $post_id );

		if ( $total_votes > 0 ) {
			?>
			<strong><?php echo esc_html( number_format_i18n( $total_votes ) ); ?></strong>
			<br>
			<a
				href="<?php echo esc_url( admin_url( 'options-general.php?page=content-poll-settings&tab=analytics&post_id=' . $post_id ) ); ?>">
				<?php esc_html_e( 'View Analytics', 'content-poll' ); ?>
			</a>
			<?php
		} else {
			echo '<span style="color: #999;">—</span>';
		}
	}
}
