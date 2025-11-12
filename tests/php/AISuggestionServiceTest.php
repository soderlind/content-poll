<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentVote\Services\AISuggestionService;

final class AISuggestionServiceTest extends TestCase {
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
		$this->assertTrue( stripos( $res[ 'question' ], 'fjord' ) !== false || stripos( $res[ 'question' ], 'fjords' ) !== false );
		$this->assertTrue( count( $res[ 'options' ] ) >= 2 );
	}
}
