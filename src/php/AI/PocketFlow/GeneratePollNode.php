<?php

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\AI\LLMClient;
use ContentPoll\Services\AISuggestionService;

final class GeneratePollNode extends AbstractNode {
	public function __construct( private LLMClient $client ) {}

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
