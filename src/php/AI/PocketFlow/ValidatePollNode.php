<?php
/**
 * Node for validating and normalizing poll JSON responses.
 *
 * @package ContentPoll\AI\PocketFlow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\Services\AISuggestionService;

/**
 * Validates and normalizes the raw poll JSON from the LLM.
 *
 * This is the final node in the PocketFlow poll generation pipeline.
 * It parses the raw JSON response, validates the structure, and ensures
 * the poll has a valid question and 2-6 options.
 *
 * Input (shared):
 * - raw_poll_response: string - Raw JSON from GeneratePollNode.
 *
 * Output (shared):
 * - final_poll: array|null - Normalized poll with 'question' and 'options' keys,
 *                            or null if validation fails.
 *
 * @since 0.8.4
 */
final class ValidatePollNode extends AbstractNode {
	/**
	 * Validate and normalize the poll JSON response.
	 *
	 * Uses AISuggestionService's parse_poll_json method via reflection
	 * to leverage existing robust JSON parsing logic.
	 *
	 * @since 0.8.4
	 *
	 * @param \stdClass $shared Shared state with raw_poll_response input.
	 *
	 * @return NodeInterface|null The next node in the chain (always null for this terminal node).
	 */
	public function run( \stdClass $shared ): ?NodeInterface {
		$raw = (string) ( $shared->raw_poll_response ?? '' );

		if ( $raw === '' ) {
			return $this->nextNode();
		}

		$service = new AISuggestionService();

		$ref    = new \ReflectionClass( AISuggestionService::class);
		$method = $ref->getMethod( 'parse_poll_json' );
		$method->setAccessible( true );

		/** @var array $parsed */
		$parsed = $method->invoke( $service, $raw );

		if ( ! is_array( $parsed ) ||
			! isset( $parsed[ 'question' ], $parsed[ 'options' ] ) ||
			! is_string( $parsed[ 'question' ] ) ||
			! is_array( $parsed[ 'options' ] )
		) {
			return $this->nextNode();
		}

		$options = array_values( array_filter(
			$parsed[ 'options' ],
			static fn( $o ) => is_string( $o ) && $o !== ''
		) );

		if ( count( $options ) < 2 ) {
			$options[] = 'Option';
		}
		if ( count( $options ) > 6 ) {
			$options = array_slice( $options, 0, 6 );
		}

		$shared->final_poll = [
			'question' => $parsed[ 'question' ],
			'options'  => $options,
		];

		return $this->nextNode();
	}
}
