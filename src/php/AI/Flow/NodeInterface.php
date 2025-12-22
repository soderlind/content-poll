<?php
/**
 * Node interface for the Flow execution system.
 *
 * @package ContentPoll\AI\Flow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

/**
 * Interface for executable nodes in a Flow pipeline.
 *
 * Each node performs a specific task and optionally returns the next node
 * to execute, enabling a chain-of-responsibility pattern for multi-step
 * AI workflows.
 *
 * @since 0.8.4
 */
interface NodeInterface {
	/**
	 * Execute this node's logic.
	 *
	 * @since 0.8.4
	 *
	 * @param \stdClass $shared Shared state object passed between nodes.
	 *                          Nodes read inputs and write outputs to this object.
	 *
	 * @return NodeInterface|null The next node to execute, or null to end the flow.
	 */
	public function run( \stdClass $shared ): ?NodeInterface;
}
