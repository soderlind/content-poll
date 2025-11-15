<?php

declare(strict_types=1);

namespace ContentPoll\Services;

use ContentPoll\Admin\SettingsPage;

class AISuggestionService {
	private const PROMPT_TEMPLATE = "Based on the following content, first infer the language of the content, then suggest one poll question and 4-6 voting options in that same language. Return only valid JSON in this exact format: {\"question\": \"...\", \"options\": [\"...\", \"...\"]}. Do not include any text outside the JSON.\n\nContent:\n%s";
	/**
	 * Generate a suggestion (question + option list) from post content.
	 * Uses configured AI provider (heuristic or OpenAI).
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>} 2-6 options.
	 */
	public function suggest( string $content ): array {
		$provider = SettingsPage::get_ai_provider();
		$text     = strip_tags( $content );
		$text     = mb_substr( $text, 0, 1000 ); // Limit to first 1000 chars

		switch ( $provider ) {
			case 'openai':
				$result = $this->suggest_openai( $text );
				break;
			case 'anthropic':
				$result = $this->suggest_anthropic( $text );
				break;
			case 'gemini':
				$result = $this->suggest_gemini( $text );
				break;
			case 'ollama':
				$result = $this->suggest_ollama( $text );
				break;
			case 'grok':
				$result = $this->suggest_grok( $text );
				break;
			default:
				$result = [];
		}

		// Fallback to heuristic if AI fails (empty result)
		if ( empty( $result ) ) {
			$result = $this->suggest_heuristic( $content );
		}
		return $this->normalize_suggestion( $result, $content );
	}

	/**
	 * Ensure returned structure always contains 'question' and 'options'.
	 * Defensive in case provider-specific handlers return partial data.
	 *
	 * @param array $data Raw suggestion data.
	 * @param string $content Original content (may be reused for heuristic fallback).
	 * @return array{question:string,options:array<int,string>}
	 */
	private function normalize_suggestion( array $data, string $content ): array {
		if ( ! isset( $data[ 'question' ] ) || ! is_string( $data[ 'question' ] ) ) {
			// Regenerate via heuristic to guarantee a question.
			$data = $this->suggest_heuristic( $content );
		}
		if ( ! isset( $data[ 'options' ] ) || ! is_array( $data[ 'options' ] ) ) {
			$data[ 'options' ] = $this->suggest_heuristic( $content )[ 'options' ];
		}
		// Enforce min 2, max 6 options.
		$data[ 'options' ] = array_values( array_filter( $data[ 'options' ], fn( $o ) => is_string( $o ) && $o !== '' ) );
		if ( count( $data[ 'options' ] ) < 2 ) {
			$data[ 'options' ][] = 'Option';
		}
		if ( count( $data[ 'options' ] ) > 6 ) {
			$data[ 'options' ] = array_slice( $data[ 'options' ], 0, 6 );
		}
		return [
			'question' => $data[ 'question' ],
			'options'  => $data[ 'options' ],
		];
	}

	/**
	 * Generate OpenAI-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_openai( string $text ): array {
		$api_key = SettingsPage::get_openai_key();
		$model   = SettingsPage::get_openai_model();
		$type    = SettingsPage::get_openai_type();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$prompt = sprintf( self::PROMPT_TEMPLATE, $text );

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
			error_log( 'ContentPoll AI OpenAI Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses (invalid model, auth issues, etc.)
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'ContentPoll AI OpenAI API Error: ' . $error_message );

			// Store transient for admin notice
			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_poll_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ] ) ) {
			return [];
		}

		$content_text = $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ];

		$parsed = $this->parse_poll_json( $content_text );
		if ( ! empty( $parsed ) ) {
			return $parsed;
		}

		return [];
	}

	/**
	 * Generate Anthropic Claude-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_anthropic( string $text ): array {
		$api_key = SettingsPage::get_anthropic_key();
		$model   = SettingsPage::get_anthropic_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$prompt = sprintf( self::PROMPT_TEMPLATE, $text );

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
			error_log( 'ContentPoll AI Anthropic Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'ContentPoll AI Anthropic API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_poll_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'content' ][ 0 ][ 'text' ] ) ) {
			return [];
		}

		$content_text = $data[ 'content' ][ 0 ][ 'text' ];

		$parsed = $this->parse_poll_json( $content_text );
		if ( ! empty( $parsed ) ) {
			return $parsed;
		}

		return [];
	}

	/**
	 * Generate Google Gemini-based suggestion.
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_gemini( string $text ): array {
		$api_key = SettingsPage::get_gemini_key();
		$model   = SettingsPage::get_gemini_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$prompt = sprintf( self::PROMPT_TEMPLATE, $text );

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
			error_log( 'ContentPoll AI Gemini Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for API error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'ContentPoll AI Gemini API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_poll_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'candidates' ][ 0 ][ 'content' ][ 'parts' ][ 0 ][ 'text' ] ) ) {
			return [];
		}

		$content_text = $data[ 'candidates' ][ 0 ][ 'content' ][ 'parts' ][ 0 ][ 'text' ];

		$parsed = $this->parse_poll_json( $content_text );
		if ( ! empty( $parsed ) ) {
			return $parsed;
		}

		return [];
	}

	/**
	 * Generate Ollama-based suggestion (self-hosted).
	 * @param string $content Raw post content.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_ollama( string $text ): array {
		$endpoint = SettingsPage::get_ollama_endpoint();
		$model    = SettingsPage::get_ollama_model();

		if ( empty( $endpoint ) || empty( $model ) ) {
			return [];
		}

		$prompt = sprintf( self::PROMPT_TEMPLATE, $text );

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
			error_log( 'ContentPoll AI Ollama Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check for error responses
		if ( isset( $data[ 'error' ] ) ) {
			$error_message = is_string( $data[ 'error' ] ) ? $data[ 'error' ] : ( $data[ 'error' ][ 'message' ] ?? 'Unknown error' );
			error_log( 'ContentPoll AI Ollama API Error: ' . $error_message );

			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_poll_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'response' ] ) ) {
			return [];
		}

		$content_text = $data[ 'response' ];

		$parsed = $this->parse_poll_json( $content_text );
		if ( ! empty( $parsed ) ) {
			return $parsed;
		}

		return [];
	}

	/**
	 * Generate Grok (xAI) based suggestion.
	 * @param string $text Content excerpt.
	 * @return array{question:string,options:array<int,string>}|array Empty array on failure.
	 */
	private function suggest_grok( string $text ): array {
		$api_key = SettingsPage::get_grok_key();
		$model   = SettingsPage::get_grok_model();

		if ( empty( $api_key ) || empty( $model ) ) {
			return [];
		}

		$prompt = sprintf( self::PROMPT_TEMPLATE, $text );

		$response = wp_remote_post( 'https://api.x.ai/v1/chat/completions', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( [
				'model'       => $model,
				'messages'    => [
					[ 'role' => 'system', 'content' => 'You generate poll questions and voting options. Always respond with valid JSON.' ],
					[ 'role' => 'user', 'content' => $prompt ],
				],
				'temperature' => 0.7,
				'max_tokens'  => 200,
			] ),
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'ContentPoll AI Grok Error: ' . $response->get_error_message() );
			return [];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data[ 'error' ] ) ) {
			$error_message = $data[ 'error' ][ 'message' ] ?? 'Unknown error';
			error_log( 'ContentPoll AI Grok API Error: ' . $error_message );
			if ( current_user_can( 'manage_options' ) ) {
				set_transient( 'content_poll_ai_error', $error_message, 300 );
			}
			return [];
		}

		if ( ! isset( $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ] ) ) {
			return [];
		}

		$content_text = $data[ 'choices' ][ 0 ][ 'message' ][ 'content' ];

		$parsed = $this->parse_poll_json( $content_text );
		if ( ! empty( $parsed ) ) {
			return $parsed;
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
				'question' => __( 'What is your opinion of this content?', 'content-poll' ),
				'options'  => [ 'Great', 'Informative', 'Neutral', 'Confusing' ],
			];
		}
		$stem = $top[ 0 ];
		/* translators: %s: extracted keyword from content used to form the poll question */
		$question = sprintf( __( 'Your view on "%s"?', 'content-poll' ), ucfirst( $stem ) );
		// Build option phrases.
		$options = [];
		foreach ( $top as $i => $word ) {
			$options[] = ucfirst( $word );
		}
		$count = count( $options );
		if ( $count < 2 ) {
			$options[] = __( 'Unsure', 'content-poll' );
		}
		if ( $count > 6 ) {
			$options = array_slice( $options, 0, 6 );
		}
		return [ 'question' => $question, 'options' => $options ];
	}


	/**
	 * Parse provider raw response chunk for poll JSON and sanitize.
	 *
	 * @param string $raw Provider response content.
	 * @return array{question:string,options:array<int,string>}|array
	 */
	private function parse_poll_json( string $raw ): array {
		$raw = trim( $raw );
		// 1. Try direct decode (model returns pure JSON)
		$direct = json_decode( $raw, true );
		if ( is_array( $direct ) && isset( $direct[ 'question' ], $direct[ 'options' ] ) && is_array( $direct[ 'options' ] ) ) {
			return [
				'question' => sanitize_text_field( $direct[ 'question' ] ),
				'options'  => array_map( 'sanitize_text_field', array_slice( $direct[ 'options' ], 0, 6 ) ),
			];
		}
		// 2. Locate first '{' and last '}' to capture full object
		$start = strpos( $raw, '{' );
		$end   = strrpos( $raw, '}' );
		if ( $start !== false && $end !== false && $end > $start ) {
			$candidate = substr( $raw, $start, $end - $start + 1 );
			$decoded   = json_decode( $candidate, true );
			if ( is_array( $decoded ) && isset( $decoded[ 'question' ], $decoded[ 'options' ] ) && is_array( $decoded[ 'options' ] ) ) {
				return [
					'question' => sanitize_text_field( $decoded[ 'question' ] ),
					'options'  => array_map( 'sanitize_text_field', array_slice( $decoded[ 'options' ], 0, 6 ) ),
				];
			}
		}
		// 3. Fallback: scan all non-nested simple object patterns and try each
		if ( preg_match_all( '/\{[^{}]*\}/', $raw, $all ) ) {
			foreach ( $all[ 0 ] as $fragment ) {
				$decoded = json_decode( $fragment, true );
				if ( is_array( $decoded ) && isset( $decoded[ 'question' ], $decoded[ 'options' ] ) && is_array( $decoded[ 'options' ] ) ) {
					return [
						'question' => sanitize_text_field( $decoded[ 'question' ] ),
						'options'  => array_map( 'sanitize_text_field', array_slice( $decoded[ 'options' ], 0, 6 ) ),
					];
				}
			}
		}
		return [];
	}
}
