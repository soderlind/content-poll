<?php
/**
 * Flow executor for chained node pipelines.
 *
 * @package ContentPoll\AI\Flow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

/**
 * Executes a chain of nodes in sequence.
 *
 * The Flow class orchestrates multi-step AI workflows by running nodes
 * one after another. Each node can read from and write to a shared state
 * object, enabling data to flow through the pipeline.
 *
 * Inspired by PocketFlow's minimal flow architecture.
 *
 * @since 0.8.4
 */
final class Flow {
	/**
	 * The first node in the execution chain.
	 *
	 * @var NodeInterface
	 */
	private NodeInterface $startNode;

	/**
	 * Create a new Flow with the given starting node.
	 *
	 * @since 0.8.4
	 *
	 * @param NodeInterface $startNode The first node to execute.
	 */
	public function __construct( NodeInterface $startNode ) {
		$this->startNode = $startNode;
	}

	/**
	 * Execute the flow from start to end.
	 *
	 * Runs each node in sequence until a node returns null,
	 * signaling the end of the flow.
	 *
	 * @since 0.8.4
	 *
	 * @param \stdClass $shared Shared state object passed to all nodes.
	 *                          Nodes use this to pass data between steps.
	 *
	 * @return void
	 */
	public function run( \stdClass $shared ): void {
		$current = $this->startNode;

		while ( null !== $current ) {
			$current = $current->run( $shared );
		}
	}
}
