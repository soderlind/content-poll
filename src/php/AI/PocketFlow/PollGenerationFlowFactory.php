<?php
/**
 * Factory for creating the poll generation Flow.
 *
 * @package ContentPoll\AI\PocketFlow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\Flow;
use ContentPoll\AI\LLMClient;

/**
 * Factory class for assembling the poll generation pipeline.
 *
 * Creates and wires together the three-node PocketFlow pipeline:
 * 1. ExtractKeywordsNode - Extracts topics from content.
 * 2. GeneratePollNode - Generates poll question and options.
 * 3. ValidatePollNode - Validates and normalizes the JSON response.
 *
 * @since 0.8.4
 */
final class PollGenerationFlowFactory {
	/**
	 * Create a configured poll generation Flow.
	 *
	 * Assembles the node chain and returns a ready-to-run Flow instance.
	 *
	 * Usage:
	 * ```php
	 * $client = new LLMClient();
	 * $flow   = PollGenerationFlowFactory::create( $client );
	 * $shared = new \stdClass();
	 * $shared->content_excerpt = 'Your post content here...';
	 * $flow->run( $shared );
	 * // $shared->final_poll contains the result.
	 * ```
	 *
	 * @since 0.8.4
	 *
	 * @param LLMClient $client The LLM client for API communication.
	 *
	 * @return Flow Configured Flow ready for execution.
	 */
	public static function create( LLMClient $client ): Flow {
		$extract  = new ExtractKeywordsNode( $client );
		$generate = new GeneratePollNode( $client );
		$validate = new ValidatePollNode();

		$extract->next( $generate );
		$generate->next( $validate );

		return new Flow( $extract );
	}
}
