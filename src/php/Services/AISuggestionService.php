<?php

declare(strict_types=1);

namespace ContentVote\Services;

use ContentVote\Admin\SettingsPage;

class AISuggestionService {
	/**
	 * Generate a suggestion (question + option list) from post content.
	 * Uses configured AI provider (heuristic or OpenAI).
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>} 2-6 options.
	 */
	public function suggest( string $content ): array {
		$provider = SettingsPage::get_ai_provider();

		switch ( $provider ) {
			case 'openai':
				$result = $this->suggest_openai( $content );
				break;
			case 'anthropic':
				$result = $this->suggest_anthropic( $content );
				break;
			case 'gemini':
				$result = $this->suggest_gemini( $content );
				break;
			case 'ollama':
				$result = $this->suggest_ollama( $content );
				break;
			default:
				$result = [];
		}

		// Fallback to heuristic if AI fails
		if ( ! empty( $result ) ) {
			return $result;
		}

		return $this->suggest_heuristic( $content );
	}

	/**
	 * Generate OpenAI-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_openai( string $content ): array {
		$api_key = SettingsPage::get_openai_key();
		$model   = SettingsPage::get_openai_model();
		$type    = SettingsPage::get_openai_type();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$text   = strip_tags( $content );
		$text   = mb_substr( $text, 0, 1000 ); // Limit to first 1000 chars
		$prompt = "Based on the following content, suggest one poll question and 4-6 voting options. Return JSON format: {\"question\": \"...\", \"options\": [\"...\", \"...\"]}.\n\nContent:\n" . $text;

		// Build request based on type (OpenAI or Azure)
		if ( $type === 'azure' ) {
			$endpoint    = SettingsPage::get_azure_endpoint();
			$api_version = SettingsPage::get_azure_api_version();

			if ( empty( $endpoint ) ) {
				return [];
			}

			// Azure OpenAI endpoint format
			$url = rtrim( $endpoint, '/' ) . '/openai/deployments/' . $model . '/chat/completions?api-version=' . $api_version;

			$response = wp_remote_post( $url, [
				'headers' => [
					'Content-Type' => 'application/json',
					'api-key'      => $api_key,
				],
				'body'    => wp_json_encode( [
					'messages'    => [
						[
							'role'    => 'system',
							'content' => 'You are a helpful assistant that generates poll questions and voting options based on content. Always respond with valid JSON.',
						],
						[
							'role'    => 'user',
							'content' => $prompt,
						],
					],
					'temperature' => 0.7,
					'max_tokens'  => 200,
				] ),
				'timeout' => 10,
			] );
		} else {
			// Standard OpenAI endpoint
			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body'    => wp_json_encode( [
					'model'       => $model,
					'messages'    => [
						[
							'role'    => 'system',
							'content' => 'You are a helpful assistant that generates poll questions and voting options based on content. Always respond with valid JSON.',
						],
						[
							'role'    => 'user',
							'content' => $prompt,
						],
					],
					'temperature' => 0.7,
					'max_tokens'  => 200,
				] ),
				'timeout' => 10,
			] );
		}

		if ( is_wp_error( $response ) ) {
			error_log( 'Content Vote OpenAI Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses (invalid model, auth issues, etc.)
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'Content Vote OpenAI API Error: ' . $error_message );

			// Store transient for admin notice
			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_vote_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ] ) ) {
			return [];
		}

		$content_text = $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ];

		// Try to extract JSON from the response
		if ( preg_match( '/\{[^}]+\}/', $content_text, $matches ) ) {
			$json = json_decode( $matches[ 0 ], true );
			if ( isset( $json[ 'question' ], $json[ 'options' ] ) && is_array( $json[ 'options' ] ) ) {
				return [
					'question' => sanitize_text_field( $json[ 'question' ] ),
					'options'  => array_map( 'sanitize_text_field', array_slice( $json[ 'options' ], 0, 6 ) ),
				];
			}
		}

		return [];
	}

	/**
	 * Generate Anthropic Claude-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_anthropic( string $content ): array {
		$api_key = SettingsPage::get_anthropic_key();
		$model   = SettingsPage::get_anthropic_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$text   = strip_tags( $content );
		$text   = mb_substr( $text, 0, 1000 );
		$prompt = "Based on the following content, suggest one poll question and 4-6 voting options. Return JSON format: {\"question\": \"...\", \"options\": [\"...\", \"...\"]}.\n\nContent:\n" . $text;

		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
			'headers' => [
				'Content-Type'      => 'application/json',
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
			],
			'body'    => wp_json_encode( [
				'model'      => $model,
				'max_tokens' => 1024,
				'messages'   => [
					[
						'role'    => 'user',
						'content' => $prompt,
					],
				],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'Content Vote Anthropic Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'Content Vote Anthropic API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_vote_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'content' ][ 0 ][ 'text' ] ) ) {
			return [];
		}

		$content_text = $data[ 'content' ][ 0 ][ 'text' ];

		// Try to extract JSON from the response
		if ( preg_match( '/\{[^}]+\}/', $content_text, $matches ) ) {
			$json = json_decode( $matches[ 0 ], true );
			if ( isset( $json[ 'question' ], $json[ 'options' ] ) && is_array( $json[ 'options' ] ) ) {
				return [
					'question' => sanitize_text_field( $json[ 'question' ] ),
					'options'  => array_map( 'sanitize_text_field', array_slice( $json[ 'options' ], 0, 6 ) ),
				];
			}
		}

		return [];
	}

	/**
	 * Generate Google Gemini-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_gemini( string $content ): array {
		$api_key = SettingsPage::get_gemini_key();
		$model   = SettingsPage::get_gemini_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$text   = strip_tags( $content );
		$text   = mb_substr( $text, 0, 1000 );
		$prompt = "Based on the following content, suggest one poll question and 4-6 voting options. Return JSON format: {\"question\": \"...\", \"options\": [\"...\", \"...\"]}.\n\nContent:\n" . $text;

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;

		$response = wp_remote_post( $url, [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( [
				'contents' => [
					[
						'parts' => [
							[
								'text' => $prompt,
							],
						],
					],
				],
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'Content Vote Gemini Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'Content Vote Gemini API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_vote_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'candidates' ][ 0 ][ 'content' ][ 'parts' ][ 0 ][ 'text' ] ) ) {
			return [];
		}

		$content_text = $data[ 'candidates' ][ 0 ][ 'content' ][ 'parts' ][ 0 ][ 'text' ];

		// Try to extract JSON from the response
		if ( preg_match( '/\{[^}]+\}/', $content_text, $matches ) ) {
			$json = json_decode( $matches[ 0 ], true );
			if ( isset( $json[ 'question' ], $json[ 'options' ] ) && is_array( $json[ 'options' ] ) ) {
				return [
					'question' => sanitize_text_field( $json[ 'question' ] ),
					'options'  => array_map( 'sanitize_text_field', array_slice( $json[ 'options' ], 0, 6 ) ),
				];
			}
		}

		return [];
	}

	/**
	 * Generate Ollama-based suggestion (self-hosted).
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_ollama( string $content ): array {
		$endpoint = SettingsPage::get_ollama_endpoint();
		$model    = SettingsPage::get_ollama_model();

		if ( empty( $endpoint ) || empty( $model ) ) {
			return [];
		}

		$text   = strip_tags( $content );
		$text   = mb_substr( $text, 0, 1000 );
		$prompt = "Based on the following content, suggest one poll question and 4-6 voting options. Return JSON format: {\"question\": \"...\", \"options\": [\"...\", \"...\"]}.\n\nContent:\n" . $text;

		$url = rtrim( $endpoint, '/' ) . '/api/generate';

		$response = wp_remote_post( $url, [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( [
				'model'  => $model,
				'prompt' => $prompt,
				'stream' => false,
			] ),
			'timeout' => 30, // Ollama might be slower
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'Content Vote Ollama Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = is_string( $data[ 'error' ] ) ? $data[ 'error' ] : ( $data[ 'error' ][ 'message' ] ?? 'Unknown error' );
			error_log( 'Content Vote Ollama API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_vote_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'response' ] ) ) {
			return [];
		}

		$content_text = $data[ 'response' ];

		// Try to extract JSON from the response
		if ( preg_match( '/\{[^}]+\}/', $content_text, $matches ) ) {
			$json = json_decode( $matches[ 0 ], true );
			if ( isset( $json[ 'question' ], $json[ 'options' ] ) && is_array( $json[ 'options' ] ) ) {
				return [
					'question' => sanitize_text_field( $json[ 'question' ] ),
					'options'  => array_map( 'sanitize_text_field', array_slice( $json[ 'options' ], 0, 6 ) ),
				];
			}
		}

		return [];
	}

	/**
	 * Generate a heuristic suggestion (question + option list) from post content.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>} 2-6 options.
	 */
	private function suggest_heuristic( string $content ): array {
		$text = strip_tags( $content );
		// Basic tokenization.
		$tokens = preg_split( '/[^\p{L}\p{N}]+/u', mb_strtolower( $text ) );
		$freq   = [];
		foreach ( $tokens as $t ) {
			if ( $t === '' || mb_strlen( $t ) < 4 ) {
				continue;
			}
			$freq[ $t ] = ( $freq[ $t ] ?? 0 ) + 1;
		}
		arsort( $freq );
		$top = array_slice( array_keys( $freq ), 0, 6 );
		if ( empty( $top ) ) {
			return [
				'question' => __( 'What is your opinion of this content?', 'content-vote' ),
				'options'  => [ 'Great', 'Informative', 'Neutral', 'Confusing' ],
			];
		}
		$stem     = $top[ 0 ];
		$question = sprintf( __( 'Your view on "%s"?', 'content-vote' ), ucfirst( $stem ) );
		// Build option phrases.
		$options = [];
		foreach ( $top as $i => $word ) {
			$options[] = ucfirst( $word );
		}
		$count = count( $options );
		if ( $count < 2 ) {
			$options[] = __( 'Unsure', 'content-vote' );
		}
		if ( $count > 6 ) {
			$options = array_slice( $options, 0, 6 );
		}
		return [ 'question' => $question, 'options' => $options ];
	}
}
