<?php
/**
 * Tests for Exo AI provider integration.
 *
 * @package ContentPoll\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\Services\AISuggestionService;

final class ExoProviderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// Reset test options before each test
		global $content_poll_test_options;
		$content_poll_test_options = [];
	}

	protected function tearDown(): void {
		global $content_poll_test_options;
		$content_poll_test_options = [];
		parent::tearDown();
	}

	/**
	 * Test that Exo provider without endpoint falls back to heuristic.
	 */
	public function testExoWithoutEndpointFallsBackToHeuristic(): void {
		global $content_poll_test_options;
		// Set options via global to control get_option() behavior
		$content_poll_test_options[ 'content_poll_options' ] = [
			'ai_provider'  => 'exo',
			'exo_endpoint' => '',
			'exo_model'    => '',
		];

		$svc     = new AISuggestionService();
		$content = 'Sample article about distributed AI inference and local model deployment.';
		$res     = $svc->suggest( $content );

		// Expect a normalized heuristic-like structure (question + >=2 options)
		$this->assertArrayHasKey( 'question', $res );
		$this->assertArrayHasKey( 'options', $res );
		$this->assertIsString( $res[ 'question' ] );
		$this->assertIsArray( $res[ 'options' ] );
		$this->assertGreaterThanOrEqual( 2, count( $res[ 'options' ] ) );
	}

	/**
	 * Test that Exo provider without model falls back to heuristic.
	 */
	public function testExoWithoutModelFallsBackToHeuristic(): void {
		global $content_poll_test_options;
		// Set options via global with endpoint but no model
		$content_poll_test_options[ 'content_poll_options' ] = [
			'ai_provider'  => 'exo',
			'exo_endpoint' => 'http://localhost:8000',
			'exo_model'    => '',
		];

		$svc     = new AISuggestionService();
		$content = 'Article about machine learning and neural networks.';
		$res     = $svc->suggest( $content );

		// Should fall back to heuristic since model is empty
		$this->assertArrayHasKey( 'question', $res );
		$this->assertArrayHasKey( 'options', $res );
		$this->assertIsString( $res[ 'question' ] );
		$this->assertIsArray( $res[ 'options' ] );
		$this->assertGreaterThanOrEqual( 2, count( $res[ 'options' ] ) );
	}

	/**
	 * Test SSE response parsing helper.
	 */
	public function testParseExoSseResponse(): void {
		$service = new AISuggestionService();

		// Use reflection to access private method
		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'parse_exo_sse_response' );
		$method->setAccessible( true );

		// Test SSE format with delta content
		$sse_body = <<<'SSE'
data: {"choices":[{"delta":{"role":"assistant"}}]}

data: {"choices":[{"delta":{"content":"{"}}]}

data: {"choices":[{"delta":{"content":"\"question\":"}}]}

data: {"choices":[{"delta":{"content":"\"Test?\""}}]}

data: {"choices":[{"delta":{"content":"}"}}]}

data: [DONE]
SSE;

		$result = $method->invoke( $service, $sse_body );
		$this->assertStringContainsString( '"question":', $result );
		$this->assertStringContainsString( '"Test?"', $result );
	}

	/**
	 * Test SSE response parsing with full message format.
	 */
	public function testParseExoSseResponseFullMessage(): void {
		$service = new AISuggestionService();

		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'parse_exo_sse_response' );
		$method->setAccessible( true );

		// Test full message format (non-streaming)
		$sse_body = 'data: {"choices":[{"message":{"content":"{\"question\":\"What is AI?\",\"options\":[\"A\",\"B\",\"C\"]}"}}]}';

		$result = $method->invoke( $service, $sse_body );
		$this->assertStringContainsString( '"question":', $result );
		$this->assertStringContainsString( '"What is AI?"', $result );
	}

	/**
	 * Test that empty SSE body returns empty string.
	 */
	public function testParseExoSseResponseEmpty(): void {
		$service = new AISuggestionService();

		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'parse_exo_sse_response' );
		$method->setAccessible( true );

		$result = $method->invoke( $service, '' );
		$this->assertSame( '', $result );
	}
}
