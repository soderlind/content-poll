<?php
/**
 * Abstract base class for Flow nodes.
 *
 * @package ContentPoll\AI\Flow
 * @since   0.8.4
 */

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

/**
 * Abstract base class providing node chaining functionality.
 *
 * Concrete nodes extend this class and implement the `run()` method.
 * Use `next()` to chain nodes together and `nextNode()` to retrieve
 * the successor for flow continuation.
 *
 * @since 0.8.4
 */
abstract class AbstractNode implements NodeInterface {
	/**
	 * The next node in the chain.
	 *
	 * @var NodeInterface|null
	 */
	private ?NodeInterface $next = null;

	/**
	 * Set the next node to execute after this one.
	 *
	 * @since 0.8.4
	 *
	 * @param NodeInterface $node The successor node.
	 *
	 * @return self Fluent interface for chaining.
	 */
	public function next( NodeInterface $node ): self {
		$this->next = $node;
		return $this;
	}

	/**
	 * Get the next node in the chain.
	 *
	 * @since 0.8.4
	 *
	 * @return NodeInterface|null The next node, or null if this is the last node.
	 */
	protected function nextNode(): ?NodeInterface {
		return $this->next;
	}
}
