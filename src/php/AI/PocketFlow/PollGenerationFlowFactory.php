<?php

declare(strict_types=1);

namespace ContentPoll\AI\PocketFlow;

use ContentPoll\AI\Flow\Flow;
use ContentPoll\AI\LLMClient;

final class PollGenerationFlowFactory {
	public static function create( LLMClient $client ): Flow {
		$extract  = new ExtractKeywordsNode( $client );
		$generate = new GeneratePollNode( $client );
		$validate = new ValidatePollNode();

		$extract->next( $generate );
		$generate->next( $validate );

		return new Flow( $extract );
	}
}
