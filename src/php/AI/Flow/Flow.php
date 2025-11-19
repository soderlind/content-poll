<?php

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

final class Flow {
	private NodeInterface $startNode;

	public function __construct( NodeInterface $startNode ) {
		$this->startNode = $startNode;
	}

	public function run( \stdClass $shared ): void {
		$current = $this->startNode;

		while ( null !== $current ) {
			$current = $current->run( $shared );
		}
	}
}
