<?php
/**
 * LLM Client for OpenAI and Azure OpenAI API communication.
 *
 * @package ContentPoll\AI
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI;

use ContentPoll\Admin\SettingsPage;
use RuntimeException;

/**
 * Client wrapper for sending chat completions to OpenAI or Azure OpenAI.
 *
 * Abstracts the differences between OpenAI and Azure OpenAI APIs,
 * providing a unified interface for the PocketFlow nodes to call LLMs.
 *
 * @since 0.8.4
 */
class LLMClient {
	/**
	 * Send a chat completion request to the configured LLM provider.
	 *
	 * Supports both OpenAI and Azure OpenAI endpoints. The provider type
	 * is determined by the plugin settings (openai_type).
	 *
	 * @since 0.8.4
	 *
	 * @param array<int,array{role:string,content:string}> $messages Array of message objects with role and content.
	 * @param array<string,mixed>                          $options  Optional settings:
	 *                                                               - 'model': Override the configured model/deployment.
	 *                                                               - 'temperature': Sampling temperature (default: 0.7).
	 *                                                               - 'max_tokens': Maximum tokens in response (default: 200).
	 *
	 * @return string The assistant's response content.
	 *
	 * @throws RuntimeException If configuration is incomplete or API returns an error.
	 */
	public function chat( array $messages, array $options = [] ): string {
		$type  = SettingsPage::get_openai_type();
		$model = $options[ 'model' ] ?? SettingsPage::get_openai_model();
		$key   = SettingsPage::get_openai_key();

		if ( empty( $key ) || empty( $model ) ) {
			throw new RuntimeException( 'OpenAI/Azure configuration is incomplete.' );
		}

		$body = [
			'messages'    => $messages,
			'temperature' => $options[ 'temperature' ] ?? 0.7,
			'max_tokens'  => $options[ 'max_tokens' ] ?? 200,
		];

		if ( $type === 'azure' ) {
			$endpoint    = SettingsPage::get_azure_endpoint();
			$api_version = SettingsPage::get_azure_api_version();
			if ( empty( $endpoint ) ) {
				throw new RuntimeException( 'Azure OpenAI endpoint is not configured.' );
			}

			$url = rtrim( $endpoint, '/' ) . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $api_version;

			$response = wp_remote_post( $url, [
				'headers' => [
					'Content-Type' => 'application/json',
					'api-key'      => $key,
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 10,
			] );
		} else {
			$body[ 'model' ] = $model;

			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $key,
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 10,
			] );
		}

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $response->get_error_message() );
		}

		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( isset( $data[ 'error' ] ) ) {
			$message = $data[ 'error' ][ 'message' ] ?? 'Unknown API error';
			throw new RuntimeException( $message );
		}

		$content = $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ] ?? '';
		if ( ! is_string( $content ) || $content === '' ) {
			throw new RuntimeException( 'Empty response from model.' );
		}

		return $content;
	}
}
