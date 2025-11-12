<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentVote\REST\ResultsController;
use ContentVote\REST\VoteController;
use ContentVote\Security\SecurityHelper;

class RestResultsEndpointTest extends TestCase {
	public function testResultsEndpointReturnsAggregateAfterVote(): void {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			self::markTestSkipped( 'WordPress REST API classes not available.' );
			return;
		}
		// Register controllers
		( new VoteController() )->register();
		( new ResultsController() )->register();
		$nonce   = SecurityHelper::create_nonce();
		$blockId = 'results_block';
		// Cast a vote first
		$voteReq = new WP_REST_Request( 'POST', '/content-vote/v1/block/' . $blockId . '/vote' );
		$voteReq->set_header( 'X-WP-Nonce', $nonce );
		$voteReq->set_param( 'optionIndex', 0 );
		$voteReq->set_param( 'postId', 0 );
		rest_do_request( $voteReq );
		// Fetch results
		$resReq   = new WP_REST_Request( 'GET', '/content-vote/v1/block/' . $blockId . '/results' );
		$response = rest_do_request( $resReq );
		$data     = $response->get_data();
		$this->assertSame( 1, $data[ 'totalVotes' ] );
		$this->assertSame( 1, $data[ 'counts' ][ 0 ] );
	}
}
