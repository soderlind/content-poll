<?php

declare(strict_types=1);

namespace ContentPoll\AI\Flow;

interface NodeInterface {
	public function run( \stdClass $shared ): ?NodeInterface;
}
