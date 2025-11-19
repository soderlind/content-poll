<?php

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\AbstractNode;
use ContentPoll\AI\Flow\NodeInterface;
use ContentPoll\Services\AISuggestionService;

final class ValidatePollNode extends AbstractNode {
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
