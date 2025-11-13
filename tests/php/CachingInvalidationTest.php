<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ContentPoll\Services\VoteStorageService;
use ContentPoll\Services\VoteAnalyticsService;

/**
 * Tests caching (transient) invalidation on vote recording and manual invalidation.
 */
class CachingInvalidationTest extends TestCase {

	/**
	 * Minimal wpdb-like mock for vote insertion and aggregate queries.
	 */
	private function makeMockDb(): object {
		return new class {
			public string $prefix = 'wp_';
			public int $rows_affected = 0;
			public array $votes = [];

			public function prepare( $query, ...$args ) {
				// Simplistic vsprintf handling; assumes placeholders all %s or %d
				return vsprintf( $query, $args );
			}
			public function query( $sql ) {
				// Detect INSERT IGNORE pattern
				if ( stripos( $sql, 'INSERT IGNORE' ) !== false ) {
					// Very naive parse to extract values (block_id, post_id, option_index, hashed_token)
					if ( preg_match( '/VALUES \(([^)]+)\)/i', $sql, $m ) ) {
						$parts = array_map( 'trim', explode( ',', $m[1] ) );
						if ( count( $parts ) >= 5 ) {
							$block_id = trim( $parts[0], "'" );
							$hashed_token = trim( $parts[3], "'" );
							$key = $block_id . '|' . $hashed_token;
							if ( isset( $this->votes[ $key ] ) ) {
								$this->rows_affected = 0; // duplicate
								return 0;
							}
							$this->votes[ $key ] = true;
							$this->rows_affected = 1;
							return 1;
						}
					}
					$this->rows_affected = 0;
					return 0;
				}
				return 0;
			}
			public function get_results( $sql ) {
				// When aggregating, return empty set so aggregate counts = 0.
				return [];
			}
			public function get_var( $sql ) {
				return null;
			}
		};
	}

	public function test_transient_set_and_invalidate_on_vote(): void {
		// Seed a transient manually to emulate cached summary.
		set_transient( 'content_poll_posts_summary', [ 'dummy' => true ], 60 );
		$this->assertNotFalse( get_transient( 'content_poll_posts_summary' ), 'Transient should exist before vote.' );

		$storage = new VoteStorageService( $this->makeMockDb() );
		$storage->record_vote( 'block123', 42, 0, 'tokenhash' );

		$this->assertFalse( get_transient( 'content_poll_posts_summary' ), 'Transient should be deleted after vote.' );
	}

	public function test_manual_invalidation_helper(): void {
		set_transient( 'content_poll_posts_summary', [ 'dummy' => true ], 60 );
		$this->assertNotFalse( get_transient( 'content_poll_posts_summary' ), 'Transient should exist before manual invalidation.' );

		VoteAnalyticsService::invalidate_cache();

		$this->assertFalse( get_transient( 'content_poll_posts_summary' ), 'Transient should be deleted after manual invalidation.' );
	}
}
