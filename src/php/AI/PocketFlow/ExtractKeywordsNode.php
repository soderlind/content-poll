<?php
/**
 * Node for extracting keywords/topics from content.
 *
 * @package ContentPoll\AI\PocketFlow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\AI\LLMClient;

/**
 * Extracts key topics from post content using an LLM.
 *
 * This is the first node in the PocketFlow poll generation pipeline.
 * It analyzes content and extracts 3-5 concise topics that summarize
 * what the content is about, enabling topic-aware poll generation.
 *
 * Input (shared):
 * - content_excerpt: string - The post content to analyze.
 *
 * Output (shared):
 * - topics: array - Array of extracted topic strings (3-5 items).
 *
 * @since 0.8.4
 */
final class ExtractKeywordsNode extends AbstractNode {
	/**
	 * LLM client for API calls.
	 *
	 * @var LLMClient
	 */
	private LLMClient $client;

	/**
	 * Create the node with an LLM client.
	 *
	 * @since 0.8.4
	 *
	 * @param LLMClient $client The LLM client for API communication.
	 */
	public function __construct( LLMClient $client ) {
		$this->client = $client;
	}

	/**
	 * Extract topics from content using the LLM.
	 *
	 * @since 0.8.4
	 *
	 * @param \stdClass $shared Shared state with content_excerpt input.
	 *
	 * @return NodeInterface|null The next node in the chain.
	 */
	public function run( \stdClass $shared ): ?NodeInterface {
		$content = (string) ( $shared->content_excerpt ?? '' );

		if ( $content === '' ) {
			$shared->topics = [];
			return $this->nextNode();
		}

		$messages = [
			[
				'role'    => 'system',
				'content' => 'You extract key topics from content and respond only with JSON.',
			],
			[
				'role'    => 'user',
				'content' => sprintf(
					"Analyze the following content.\n\nContent:\n%s\n\nFirst, infer the primary language of the content.\nThen, return 3 to 5 concise topics or keywords that best summarize what the content is about.\n\nReturn only valid JSON in this exact format (no extra text):\n[\"topic one\", \"topic two\", \"topic three\"]",
					$content
				),
			],
		];

		try {
			$raw = $this->client->chat( $messages, [
				'temperature' => 0.3,
				'max_tokens'  => 200,
			] );

			$topics = json_decode( $raw, true );
			if ( ! is_array( $topics ) ) {
				$shared->topics = [];
				return $this->nextNode();
			}

			$topics = array_values( array_filter(
				$topics,
				static fn( $t ) => is_string( $t ) && $t !== ''
			) );
			$topics = array_slice( $topics, 0, 5 );

			$shared->topics = $topics;
		} catch (\Throwable $e) {
			error_log( 'ContentPoll PocketFlow ExtractTopics Error: ' . $e->getMessage() );
			$shared->topics = [];
		}

		return $this->nextNode();
	}
}
