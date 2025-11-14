<?php

declare(strict_types=1);

namespace ContentPoll\Services;

// WordPress global functions/classes used; no direct namespacing.

/**
 * Persistence service for vote submissions.
 *
 * Provides methods to record a vote atomically (preventing duplicates
 * via UNIQUE constraint) and to retrieve aggregate statistics including
 * per-option counts and percentages, as well as a specific user's vote.
 */
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
	/**
	 * Record a single vote if not already submitted by this user token.
	 *
	 * @param string $block_id     Block instance identifier.
	 * @param int    $post_id      Associated post ID (0 if unknown).
	 * @param int    $option_index Option index (0-5).
	 * @param string $hashed_token SHA256(token + AUTH_KEY).
	 * @return array Aggregate results or error payload.
	 */
	public function record_vote( string $block_id, int $post_id, int $option_index, string $hashed_token ) {
		$db = $this->db;
		// Basic validation.
		if ( $option_index < 0 || $option_index > 5 ) {
			return [ 'error' => true, 'code' => 'invalid_option', 'message' => 'Invalid option index.', 'status' => 400 ];
		}
		// Insert row using wpdb::insert; rely on UNIQUE(block_id, hashed_token) to prevent duplicates.
		$inserted = $db->insert(
			$this->table,
			[
				'block_id'     => $block_id,
				'post_id'      => $post_id,
				'option_index' => $option_index,
				'hashed_token' => $hashed_token,
				'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			],
			[ '%s', '%d', '%d', '%s', '%s' ]
		);
		if ( $inserted === false ) {
			// Check if failure was due to duplicate key on (block_id, hashed_token).
			// wpdb does not expose SQLSTATE directly, so treat any failure here as duplicate vote.
			return [ 'error' => true, 'code' => 'duplicate_vote', 'message' => 'You have already voted.', 'status' => 400 ];
		}
		return $this->get_aggregate( $block_id );
	}

	/**
	 * Get aggregate counts and percentages for a block.
	 * 
	 * @param string $block_id Block identifier
	 * @return array<string,mixed> Aggregate data with counts and percentages
	 */
	/**
	 * Get aggregated vote results for block.
	 *
	 * @param string $block_id Block identifier.
	 * @return array{blockId:string,totalVotes:int,counts:array<int,int>,percentages:array<int,float>} Summary data.
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
	/**
	 * Retrieve user's previously selected option index.
	 *
	 * @param string $block_id     Block identifier.
	 * @param string $hashed_token Hashed token.
	 * @return int|null Option index or null.
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
