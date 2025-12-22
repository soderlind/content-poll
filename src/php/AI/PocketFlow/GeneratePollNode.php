<?php
/**
 * Node for generating poll question and options from content.
 *
 * @package ContentPoll\AI\PocketFlow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\AI\LLMClient;
use ContentPoll\Services\AISuggestionService;

/**
 * Generates a poll question and options using an LLM.
 *
 * This is the second node in the PocketFlow poll generation pipeline.
 * It uses the extracted topics (if available) to generate a contextually
 * relevant poll with a question and 4-6 voting options.
 *
 * Input (shared):
 * - content_excerpt: string - The post content for context.
 * - topics: array - Optional topics from ExtractKeywordsNode.
 *
 * Output (shared):
 * - raw_poll_response: string - Raw JSON response from the LLM.
 *
 * @since 0.8.4
 */
final class GeneratePollNode extends AbstractNode {
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
	 * Generate poll question and options using the LLM.
	 *
	 * @since 0.8.4
	 *
	 * @param \stdClass $shared Shared state with content_excerpt and optional topics.
	 *
	 * @return NodeInterface|null The next node in the chain.
	 */
	public function run( \stdClass $shared ): ?NodeInterface {
		$content = (string) ( $shared->content_excerpt ?? '' );
		$topics  = is_array( $shared->topics ?? null ) ? $shared->topics : [];

		if ( ! empty( $topics ) ) {
			$topics_text = implode( ', ', $topics );
			$prompt      = sprintf(
				AISuggestionService::PROMPT_TEMPLATE_TOPIC_AWARE,
				$content,
				$topics_text
			);
		} else {
			$prompt = sprintf(
				AISuggestionService::PROMPT_TEMPLATE,
				$content
			);
		}

		$messages = [
			[
				'role'    => 'system',
				'content' => 'You generate poll questions and voting options based on content. Always respond with valid JSON.',
			],
			[
				'role'    => 'user',
				'content' => $prompt,
			],
		];

		try {
			$shared->raw_poll_response = $this->client->chat( $messages, [
				'temperature' => 0.7,
				'max_tokens'  => 200,
			] );
		} catch (\Throwable $e) {
			error_log( 'ContentPoll PocketFlow GeneratePoll Error: ' . $e->getMessage() );
			$shared->raw_poll_response = '';
		}

		return $this->nextNode();
	}
}
