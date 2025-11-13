<?php

declare(strict_types=1);

namespace ContentPoll\Admin;

class SettingsPage {
	private string $option_group = 'content_poll_settings';
	private string $option_name = 'content_poll_options';

	public function __construct() {
		$this->option_group = 'content_poll_options_group';
		$this->option_name  = 'content_poll_options';
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
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

		$sanitized[ 'ai_provider' ] = isset( $input[ 'ai_provider' ] ) && in_array( $input[ 'ai_provider' ], [ 'heuristic', 'openai', 'anthropic', 'gemini', 'ollama' ], true )
			? $input[ 'ai_provider' ]
			: 'heuristic';

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

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->option_group );
				do_settings_sections( 'content-poll-settings' );
				submit_button( __( 'Save Settings', 'content-poll' ) );
				?>
			</form>
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
		$options = get_option( $this->option_name, [
			'ai_provider' => 'heuristic',
		] );
		$current = $options[ 'ai_provider' ] ?? 'heuristic';
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[ai_provider]" id="ai_provider">
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
		</select>
		<p class="description">
			<?php esc_html_e( 'Heuristic AI uses built-in logic without API calls. Other options require API keys or local installation.', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_openai_type_field(): void {
		$options = get_option( $this->option_name, [
			'openai_type' => 'openai',
		] );
		$current = $options[ 'openai_type' ] ?? 'openai';
		?>
		<select name="<?php echo esc_attr( $this->option_name ); ?>[openai_type]" id="openai_type">
			<option value="openai" <?php selected( $current, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI', 'content-poll' ); ?>
			</option>
			<option value="azure" <?php selected( $current, 'azure' ); ?>>
				<?php esc_html_e( 'Azure OpenAI', 'content-poll' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose between standard OpenAI API or Azure OpenAI Service', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_openai_key_field(): void {
		$options = get_option( $this->option_name, [
			'openai_key' => '',
		] );
		$value   = $options[ 'openai_key' ] ?? '';
		?>
		<input type="password" name="<?php echo esc_attr( $this->option_name ); ?>[openai_key]" id="openai_key"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
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
		$options = get_option( $this->option_name, [
			'openai_model' => 'gpt-3.5-turbo',
		] );
		$value   = $options[ 'openai_model' ] ?? 'gpt-3.5-turbo';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[openai_model]" id="openai_model"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="gpt-3.5-turbo" />
		<p class="description">
			<?php esc_html_e( 'OpenAI: gpt-3.5-turbo, gpt-4, etc. Anthropic: claude-3-5-sonnet-20241022, etc. Gemini: gemini-1.5-flash, etc. Ollama: llama3.2, mistral, etc.', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_azure_endpoint_field(): void {
		$options = get_option( $this->option_name, [
			'azure_endpoint' => '',
		] );
		$value   = $options[ 'azure_endpoint' ] ?? '';
		?>
		<input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[azure_endpoint]" id="azure_endpoint"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text"
			placeholder="https://your-resource.openai.azure.com" />
		<p class="description">
			<?php esc_html_e( 'Your Azure OpenAI resource endpoint URL', 'content-poll' ); ?>
		</p>
		<?php
	}

	public function render_azure_api_version_field(): void {
		$options = get_option( $this->option_name, [
			'azure_api_version' => '2024-02-15-preview',
		] );
		$value   = $options[ 'azure_api_version' ] ?? '2024-02-15-preview';
		?>
		<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[azure_api_version]" id="azure_api_version"
			value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="2024-02-15-preview" />
		<p class="description">
			<?php esc_html_e( 'Azure OpenAI API version (e.g., 2024-02-15-preview)', 'content-poll' ); ?>
		</p>
		<?php
	}

	public static function get_ai_provider(): string {
		$options = get_option( 'content_poll_options', [ 'ai_provider' => 'heuristic' ] );
		return $options[ 'ai_provider' ] ?? 'heuristic';
	}

	public static function get_openai_type(): string {
		$options = get_option( 'content_poll_options', [ 'openai_type' => 'openai' ] );
		return $options[ 'openai_type' ] ?? 'openai';
	}

	public static function get_openai_key(): string {
		$options = get_option( 'content_poll_options', [ 'openai_key' => '' ] );
		return $options[ 'openai_key' ] ?? '';
	}

	public static function get_openai_model(): string {
		$options = get_option( 'content_poll_options', [ 'openai_model' => 'gpt-3.5-turbo' ] );
		return $options[ 'openai_model' ] ?? 'gpt-3.5-turbo';
	}

	public static function get_azure_endpoint(): string {
		$options = get_option( 'content_poll_options', [ 'azure_endpoint' => '' ] );
		return $options[ 'azure_endpoint' ] ?? '';
	}

	public static function get_azure_api_version(): string {
		$options = get_option( 'content_poll_options', [ 'azure_api_version' => '2024-02-15-preview' ] );
		return $options[ 'azure_api_version' ] ?? '2024-02-15-preview';
	}

	public static function get_anthropic_key(): string {
		$options = get_option( 'content_poll_options', [ 'anthropic_key' => '' ] );
		return $options[ 'anthropic_key' ] ?? '';
	}

	public static function get_anthropic_model(): string {
		$options = get_option( 'content_poll_options', [ 'anthropic_model' => 'claude-3-5-sonnet-20241022' ] );
		return $options[ 'anthropic_model' ] ?? 'claude-3-5-sonnet-20241022';
	}

	public static function get_gemini_key(): string {
		$options = get_option( 'content_poll_options', [ 'gemini_key' => '' ] );
		return $options[ 'gemini_key' ] ?? '';
	}

	public static function get_gemini_model(): string {
		$options = get_option( 'content_poll_options', [ 'gemini_model' => 'gemini-1.5-flash' ] );
		return $options[ 'gemini_model' ] ?? 'gemini-1.5-flash';
	}

	public static function get_ollama_endpoint(): string {
		$options = get_option( 'content_poll_options', [ 'ollama_endpoint' => 'http://localhost:11434' ] );
		return $options[ 'ollama_endpoint' ] ?? 'http://localhost:11434';
	}

	public static function get_ollama_model(): string {
		$options = get_option( 'content_poll_options', [ 'ollama_model' => 'llama3.2' ] );
		return $options[ 'ollama_model' ] ?? 'llama3.2';
	}
}
