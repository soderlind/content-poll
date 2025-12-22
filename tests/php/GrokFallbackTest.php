<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\Services\AISuggestionService;

final class GrokFallbackTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		global $content_poll_test_options;
		$content_poll_test_options = [];
	}

	protected function tearDown(): void {
		global $content_poll_test_options;
		$content_poll_test_options = [];
		parent::tearDown();
	}

	public function testGrokWithoutKeyFallsBackToHeuristic(): void {
		global $content_poll_test_options;
		// Simulate settings selecting Grok provider with no API key provided.
		$content_poll_test_options[ 'content_poll_options' ] = [
			'ai_provider' => 'grok',
			'grok_key'    => '',
			'grok_model'  => 'grok-2',
		];

		$svc     = new AISuggestionService();
		$content = 'Sample article about sustainable energy technology advances and innovation.';
		$res     = $svc->suggest( $content );

		// Expect a normalized heuristic-like structure (question + >=2 options)
		$this->assertArrayHasKey( 'question', $res );
		$this->assertArrayHasKey( 'options', $res );
		$this->assertIsString( $res[ 'question' ] );
		$this->assertIsArray( $res[ 'options' ] );
		$this->assertGreaterThanOrEqual( 2, count( $res[ 'options' ] ) );
	}
}
