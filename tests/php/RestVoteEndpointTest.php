<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\REST\VoteController;
use ContentPoll\Security\SecurityHelper;

class RestVoteEndpointTest extends TestCase {
	public function testVoteEndpointReturnsAggregate(): void {
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			self::markTestSkipped( 'WordPress REST API classes not available.' );
			return;
		}
		$controller = new VoteController();
		$controller->register();
		$nonce   = SecurityHelper::create_nonce();
		$blockId = 'integration_block_1';
		$req     = new WP_REST_Request( 'POST', '/content-poll/v1/block/' . $blockId . '/vote' );
		$req->set_header( 'X-WP-Nonce', $nonce );
		$req->set_param( 'optionIndex', 0 );
		$req->set_param( 'postId', 123 );
		$response = rest_do_request( $req );
		$data     = $response->get_data();
		$this->assertTrue( isset( $data[ 'totalVotes' ] ) && $data[ 'totalVotes' ] === 1, 'Aggregate should report first vote.' );
		$this->assertSame( 1, $data[ 'counts' ][ 0 ] );
	}
}
