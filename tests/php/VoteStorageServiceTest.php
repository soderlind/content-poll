<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\Services\VoteStorageService;

final class VoteStorageServiceTest extends TestCase {
	private function makeStubDb(): object {
		// Simple in-memory stub implementing minimal wpdb-like API.
		return new class {
			public string $prefix = 'wp_';
			public int $rows_affected = 0;
			private array $rows = [];
			public function prepare( string $query, ...$args ): string {
				// Very naive replacement for %s / %d markers sufficient for tests.
				foreach ( $args as $a ) {
					$quoted = is_string( $a ) ? "'" . $a . "'" : (string) $a;
					$query  = preg_replace( '/%[sd]/', $quoted, $query, 1 );
				}
				return $query;
			}
			public function get_var( string $query ) {
				// Detect selection pattern: WHERE poll_id='x' AND hashed_token='y'
				if ( preg_match( "/WHERE poll_id='([^']+)' AND hashed_token='([^']+)'/", $query, $m ) ) {
					$poll_id = $m[ 1 ];
					$token   = $m[ 2 ];
					foreach ( $this->rows as $r ) {
						if ( $r[ 'poll_id' ] === $poll_id && $r[ 'hashed_token' ] === $token ) {
							return $r[ 'id' ];
						}
					}
				}
				return null;
			}
			public function insert( string $table, array $data, array $fmt ) {
				// Enforce uniqueness by (poll_id, hashed_token)
				foreach ( $this->rows as $r ) {
					if ( $r[ 'poll_id' ] === $data[ 'poll_id' ] && $r[ 'hashed_token' ] === $data[ 'hashed_token' ] ) {
						return false; // duplicate
					}
				}
				$data[ 'id' ] = count( $this->rows ) + 1;
				$this->rows[] = $data;
				return true;
			}
			public function get_results( string $query ) {
				if ( preg_match( "/WHERE poll_id='([^']+)'/", $query, $m ) ) {
					$poll_id = $m[ 1 ];
					$counts  = [];
					foreach ( $this->rows as $r ) {
						if ( $r[ 'poll_id' ] === $poll_id ) {
							$idx            = (int) $r[ 'option_index' ];
							$counts[ $idx ] = ( $counts[ $idx ] ?? 0 ) + 1;
						}
					}
					return array_map( fn( $idx, $cnt ) => (object) [ 'option_index' => $idx, 'cnt' => $cnt ], array_keys( $counts ), array_values( $counts ) );
				}
				return [];
			}
			public function query( $sql ) {
				// Not used in current VoteStorageService tests.
				return false;
			}
		};
	}

	public function testRecordVoteInsertsAndAggregates(): void {
		$db      = $this->makeStubDb();
		$service = new VoteStorageService( $db );
		$agg1    = $service->record_vote( 'block1', 10, 0, 'tokenA' );
		$this->assertArrayNotHasKey( 'error', $agg1, 'First vote should succeed without error.' );
		$this->assertTrue( $agg1[ 'totalVotes' ] === 1, 'First vote should increment total.' );
		$this->assertSame( 1, $agg1[ 'counts' ][ 0 ] );
		$agg2 = $service->record_vote( 'block1', 10, 2, 'tokenB' );
		$this->assertSame( 2, $agg2[ 'totalVotes' ] );
		$this->assertSame( 1, $agg2[ 'counts' ][ 0 ] );
		$this->assertSame( 1, $agg2[ 'counts' ][ 2 ] );
	}

	public function testDuplicateVotePrevention(): void {
		$db      = $this->makeStubDb();
		$service = new VoteStorageService( $db );
		$service->record_vote( 'blockX', 55, 1, 'dupToken' );
		$result = $service->record_vote( 'blockX', 55, 3, 'dupToken' );
		$this->assertTrue( isset( $result[ 'error' ] ) && $result[ 'error' ] === true, 'Duplicate vote should return error array.' );
		$this->assertSame( 'duplicate_vote', $result[ 'code' ] );
	}

	public function testInvalidOptionIndex(): void {
		$db      = $this->makeStubDb();
		$service = new VoteStorageService( $db );
		$result  = $service->record_vote( 'blockZ', 1, 7, 'tok' );
		$this->assertTrue( $result[ 'error' ] );
		$this->assertSame( 'invalid_option', $result[ 'code' ] );
	}
}
