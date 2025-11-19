<?php

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

abstract class AbstractNode implements NodeInterface {
	private ?NodeInterface $next = null;

	public function next( NodeInterface $node ): self {
		$this->next = $node;
		return $this;
	}

	protected function nextNode(): ?NodeInterface {
		return $this->next;
	}
}
