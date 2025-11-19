<?php

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\AI\LLMClient;

final class ExtractKeywordsNode extends AbstractNode {
	public function __construct( private LLMClient $client ) {}

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
