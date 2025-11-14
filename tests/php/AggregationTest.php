<?php
declare(strict_types=1);

use ContentPoll\Services\VoteStorageService;
use PHPUnit\Framework\TestCase;

final class AggregationTest extends TestCase {
	private function stubDb(): object {
		return new class {
			public string $prefix = 'wp_';
			public int $rows_affected = 0;
			public array $rows = [];
			public function prepare( string $q, ...$a ) {
				foreach ( $a as $v ) {
					$quoted = is_string( $v ) ? "'" . $v . "'" : (string) $v;
					$q      = preg_replace( '/%[sd]/', $quoted, $q, 1 );
				}
				return $q;
			}
			public function get_var( string $q ) {
				return null;
			}
			public function insert( string $table, array $data, array $fmt ) {
				foreach ( $this->rows as $r ) {
					if ( $r[ 'poll_id' ] === $data[ 'poll_id' ] && $r[ 'hashed_token' ] === $data[ 'hashed_token' ] )
						return false;
				}
				$data[ 'id' ] = count( $this->rows ) + 1;
				$this->rows[] = $data;
				return true;
			}
			public function get_results( string $q ) {
				if ( preg_match( "/WHERE poll_id='([^']+)'/", $q, $m ) ) {
					$block  = $m[ 1 ];
					$counts = [];
					foreach ( $this->rows as $r ) {
						if ( $r[ 'poll_id' ] === $block ) {
							$idx            = (int) $r[ 'option_index' ];
							$counts[ $idx ] = ( $counts[ $idx ] ?? 0 ) + 1;
						}
					}
					return array_map( fn( $idx, $cnt ) => (object) [ 'option_index' => $idx, 'cnt' => $cnt ], array_keys( $counts ), array_values( $counts ) );
				}
				return [];
			}
			public function query( $sql ) {
				return false;
			}
		};
	}

	public function testPercentagesComputedCorrectly(): void {
		$db  = $this->stubDb();
		$svc = new VoteStorageService( $db );
		$svc->record_vote( 'blk', 1, 0, 't1' );
		$svc->record_vote( 'blk', 1, 0, 't2' );
		$svc->record_vote( 'blk', 1, 2, 't3' );
		$agg = $svc->get_aggregate( 'blk' );
		$this->assertSame( 3, $agg[ 'totalVotes' ] );
		// Option 0: 2/3 ≈ 66.67, Option 2: 1/3 ≈ 33.33
		$this->assertTrue( $agg[ 'percentages' ][ 0 ] >= 66 && $agg[ 'percentages' ][ 0 ] <= 67 );
		$this->assertTrue( $agg[ 'percentages' ][ 2 ] >= 33 && $agg[ 'percentages' ][ 2 ] <= 34 );
	}
}
