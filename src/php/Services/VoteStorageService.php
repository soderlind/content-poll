<?php

declare(strict_types=1);

namespace ContentVote\Services;

// WordPress global functions/classes used; no direct namespacing.

class VoteStorageService {
	private string $table;
	private $db; // wpdb-like object

	/**
	 * @param object|null $db Inject a wpdb-like object for easier testing. Must expose prefix, prepare(), get_var(), insert(), get_results(), query().
	 */
	public function __construct( $db = null ) {
		if ( $db ) {
			$this->db = $db;
		} else {
			global $wpdb;
			$this->db = $wpdb;
		}
		$this->table = $this->db->prefix . 'vote_block_submissions';
	}

	/**
	 * Attempt to record a vote with atomic deduplication.
	 * Returns aggregate array OR error array: ['error'=>true,'code'=>string,'message'=>string,'status'=>int]
	 * 
	 * @param string $block_id Block identifier
	 * @param int $post_id Post ID (0 if not in post context)
	 * @param int $option_index Selected option index (0-5)
	 * @param string $hashed_token Hashed voter token
	 * @return array<string,mixed> Aggregate results or error details
	 */
	public function record_vote( string $block_id, int $post_id, int $option_index, string $hashed_token ) {
		$db = $this->db;
		// Basic validation.
		if ( $option_index < 0 || $option_index > 5 ) {
			return [ 'error' => true, 'code' => 'invalid_option', 'message' => 'Invalid option index.', 'status' => 400 ];
		}
		// Use INSERT IGNORE for atomic duplicate prevention (race condition safe).
		// If UNIQUE constraint (block_id, hashed_token) violated, insert silently fails.
		$inserted = $db->query( $db->prepare(
			"INSERT IGNORE INTO {$this->table} (block_id, post_id, option_index, hashed_token, created_at) VALUES (%s, %d, %d, %s, %s)",
			$block_id,
			$post_id,
			$option_index,
			$hashed_token,
			gmdate( 'Y-m-d H:i:s' )
		) );
		// Check if insert actually occurred (affected_rows will be 0 if duplicate).
		if ( $db->rows_affected === 0 ) {
			return [ 'error' => true, 'code' => 'duplicate_vote', 'message' => 'You have already voted.', 'status' => 400 ];
		}
		if ( ! $inserted ) {
			return [ 'error' => true, 'code' => 'db_insert_failed', 'message' => 'Could not record vote.', 'status' => 500 ];
		}
		return $this->get_aggregate( $block_id );
	}

	/**
	 * Get aggregate counts and percentages for a block.
	 * 
	 * @param string $block_id Block identifier
	 * @return array<string,mixed> Aggregate data with counts and percentages
	 */
	public function get_aggregate( string $block_id ): array {
		$db     = $this->db;
		$rows   = $db->get_results( $db->prepare( "SELECT option_index, COUNT(*) as cnt FROM {$this->table} WHERE block_id=%s GROUP BY option_index", $block_id ) );
		$counts = [];
		$total  = 0;
		foreach ( $rows as $r ) {
			$idx             = (int) $r->option_index;
			$cnt             = (int) $r->cnt;
			$counts[ $idx ]  = $cnt;
			$total          += $cnt;
		}
		// Normalize to 0..5 indexes to simplify front-end (options not used remain zero).
		for ( $i = 0; $i < 6; $i++ ) {
			if ( ! isset( $counts[ $i ] ) ) {
				$counts[ $i ] = 0;
			}
		}
		$percentages = [];
		foreach ( $counts as $i => $c ) {
			$percentages[ $i ] = $total > 0 ? round( ( $c / $total ) * 100, 2 ) : 0.0;
		}
		return [
			'blockId'     => $block_id,
			'totalVotes'  => $total,
			'counts'      => $counts,
			'percentages' => $percentages,
		];
	}

	/**
	 * Get the user's vote for a specific block.
	 * 
	 * @param string $block_id Block identifier
	 * @param string $hashed_token Hashed voter token
	 * @return int|null Option index if user has voted, null otherwise
	 */
	public function get_user_vote( string $block_id, string $hashed_token ): ?int {
		$db     = $this->db;
		$result = $db->get_var( $db->prepare(
			"SELECT option_index FROM {$this->table} WHERE block_id=%s AND hashed_token=%s LIMIT 1",
			$block_id,
			$hashed_token
		) );
		return $result !== null ? (int) $result : null;
	}
}
