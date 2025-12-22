<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\Services\AISuggestionService;

final class AISuggestionServiceTest extends TestCase {

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

	public function testSuggestReturnsFallbackOnEmptyContent(): void {
		$svc = new AISuggestionService();
		$res = $svc->suggest( '' );
		$this->assertTrue( isset( $res[ 'question' ] ) );
		$this->assertTrue( count( $res[ 'options' ] ) >= 2 );
	}

	public function testSuggestExtractsKeywords(): void {
		$content = 'Mountains fjords hiking fjords coastal fjords adventure Norway scenic';
		$svc     = new AISuggestionService();
		$res     = $svc->suggest( $content );
		// Allow keyword presence either in question or one of the options to be
		// resilient to future heuristic phrasing changes.
		$keywordFound = stripos( $res[ 'question' ], 'fjord' ) !== false || stripos( $res[ 'question' ], 'fjords' ) !== false;
		if ( ! $keywordFound ) {
			foreach ( $res[ 'options' ] as $opt ) {
				if ( stripos( $opt, 'fjord' ) !== false || stripos( $opt, 'fjords' ) !== false ) {
					$keywordFound = true;
					break;
				}
			}
		}
		$this->assertTrue( $keywordFound, 'Expected fjord-related keyword in question or options.' );
		$this->assertTrue( count( $res[ 'options' ] ) >= 2 );
	}
}
