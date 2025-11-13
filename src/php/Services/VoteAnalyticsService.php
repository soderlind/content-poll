<?php

declare(strict_types=1);

namespace ContentPoll\Services;

/**
 * Analytics service for vote data aggregation and reporting.
 *
 * Provides methods to query vote statistics across posts, blocks, and options.
 * Includes helper to parse block attributes from post content for display purposes.
 */
class VoteAnalyticsService {
	private string $table;
	private $db; // wpdb-like object

	/**
	 * @param object|null $db Inject a wpdb-like object for easier testing.
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
	 * Get total votes across all polls.
	 *
	 * @return int Total vote count.
	 */
	public function get_total_votes(): int {
		$db     = $this->db;
		$result = $db->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		return $result ? (int) $result : 0;
	}

	/**
	 * Get total number of distinct polls (blocks).
	 *
	 * @return int Number of unique block IDs.
	 */
	public function get_total_polls(): int {
		$db     = $this->db;
		$result = $db->get_var( "SELECT COUNT(DISTINCT block_id) FROM {$this->table}" );
		return $result ? (int) $result : 0;
	}

	/**
	 * Get average votes per poll.
	 *
	 * @return float Average votes per distinct block.
	 */
	public function get_average_votes_per_poll(): float {
		$total_votes = $this->get_total_votes();
		$total_polls = $this->get_total_polls();
		return $total_polls > 0 ? round( $total_votes / $total_polls, 2 ) : 0.0;
	}

	/**
	 * Get top polls by total votes.
	 *
	 * @param int $limit Maximum number of results.
	 * @return array Array of objects with block_id, total_votes, post_id, last_vote.
	 */
	public function get_top_polls( int $limit = 10 ): array {
		$db   = $this->db;
		$rows = $db->get_results( $db->prepare(
			"SELECT block_id, post_id, COUNT(*) as total_votes, MAX(created_at) as last_vote 
			FROM {$this->table} 
			GROUP BY block_id, post_id 
			ORDER BY total_votes DESC 
			LIMIT %d",
			$limit
		) );
		return $rows ?: [];
	}

	/**
	 * Get recent activity (polls with most recent votes).
	 *
	 * @param int $limit Maximum number of results.
	 * @return array Array of objects with block_id, post_id, total_votes, last_vote.
	 */
	public function get_recent_activity( int $limit = 10 ): array {
		$db   = $this->db;
		$rows = $db->get_results( $db->prepare(
			"SELECT block_id, post_id, COUNT(*) as total_votes, MAX(created_at) as last_vote 
			FROM {$this->table} 
			GROUP BY block_id, post_id 
			ORDER BY last_vote DESC 
			LIMIT %d",
			$limit
		) );
		return $rows ?: [];
	}

	/**
	 * Get total votes for a specific post (all blocks combined).
	 *
	 * @param int $post_id Post ID.
	 * @return int Total votes for all polls on this post.
	 */
	public function get_post_total_votes( int $post_id ): int {
		$db = $this->db;
		// Fallback: include legacy votes stored with post_id = 0 if their block_id appears in this post.
		$block_map = $this->get_post_block_attributes( $post_id );
		$block_ids = array_keys( $block_map );
		if ( empty( $block_ids ) ) {
			return 0;
		}
		// Build IN clause safely.
		$in = implode( ',', array_map( function ( $id ) use ( $db ) {
			return '\'' . esc_sql( $id ) . '\'';
		}, $block_ids ) );
		// Count votes where post_id matches OR legacy (post_id=0) for blocks belonging to this post.
		$sql    = $db->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE post_id = %d OR (post_id = 0 AND block_id IN ($in))", $post_id );
		$result = $db->get_var( $sql );
		return $result ? (int) $result : 0;
	}

	/**
	 * Get per-block vote counts for a specific post.
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of objects with block_id, total_votes, last_vote.
	 */
	public function get_post_block_totals( int $post_id ): array {
		$db        = $this->db;
		$block_map = $this->get_post_block_attributes( $post_id );
		$block_ids = array_keys( $block_map );
		if ( empty( $block_ids ) ) {
			return [];
		}
		$in   = implode( ',', array_map( function ( $id ) use ( $db ) {
			return '\'' . esc_sql( $id ) . '\'';
		}, $block_ids ) );
		$sql  = $db->prepare( "SELECT block_id, COUNT(*) as total_votes, MAX(created_at) as last_vote FROM {$this->table} WHERE (post_id = %d OR post_id = 0) AND block_id IN ($in) GROUP BY block_id ORDER BY total_votes DESC", $post_id );
		$rows = $db->get_results( $sql );
		return $rows ?: [];
	}

	/**
	 * Get per-option vote counts for a specific block.
	 *
	 * @param string $block_id Block identifier.
	 * @return array Array with counts and percentages (0-5 indexes).
	 */
	public function get_block_option_breakdown( string $block_id ): array {
		$db     = $this->db;
		$rows   = $db->get_results( $db->prepare(
			"SELECT option_index, COUNT(*) as cnt FROM {$this->table} WHERE block_id=%s GROUP BY option_index",
			$block_id
		) );
		$counts = [];
		$total  = 0;
		foreach ( $rows as $r ) {
			$idx             = (int) $r->option_index;
			$cnt             = (int) $r->cnt;
			$counts[ $idx ]  = $cnt;
			$total          += $cnt;
		}
		// Normalize to 0..5 indexes
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
			'counts'      => $counts,
			'percentages' => $percentages,
			'total'       => $total,
		];
	}

	/**
	 * Get summary of all posts with poll blocks.
	 *
	 * @return array Array of objects with post_id, post_title, poll_count, total_votes, last_vote.
	 */
	public function get_posts_summary(): array {
		// Attempt cached summary first (built data for all posts)
		$cached = get_transient( 'content_poll_posts_summary' );
		if ( $cached !== false && is_array( $cached ) ) {
			return $cached;
		}
		$db    = $this->db;
		$posts = $db->get_results( "SELECT ID, post_title, post_content FROM {$db->posts} WHERE post_status IN ('publish','draft','future') AND post_type IN ('post','page') AND post_content LIKE '%content-poll/vote-block%'" );
		if ( empty( $posts ) ) {
			return [];
		}
		$non_legacy = $db->get_results( "SELECT post_id, block_id, COUNT(*) cnt, MAX(created_at) last_vote FROM {$this->table} WHERE post_id > 0 GROUP BY post_id, block_id" );
		$non_map    = [];
		foreach ( $non_legacy as $row ) {
			$key             = (int) $row->post_id . '|' . $row->block_id;
			$non_map[ $key ] = [ 'cnt' => (int) $row->cnt, 'last' => $row->last_vote ];
		}
		$legacy     = $db->get_results( "SELECT block_id, COUNT(*) cnt, MAX(created_at) last_vote FROM {$this->table} WHERE post_id = 0 GROUP BY block_id" );
		$legacy_map = [];
		foreach ( $legacy as $row ) {
			$legacy_map[ $row->block_id ] = [ 'cnt' => (int) $row->cnt, 'last' => $row->last_vote ];
		}
		$summary = [];
		foreach ( $posts as $p ) {
			$blocks    = parse_blocks( $p->post_content );
			$block_ids = [];
			foreach ( $blocks as $b ) {
				if ( isset( $b[ 'blockName' ] ) && $b[ 'blockName' ] === 'content-poll/vote-block' && isset( $b[ 'attrs' ][ 'blockId' ] ) ) {
					$block_ids[] = $b[ 'attrs' ][ 'blockId' ];
				}
			}
			$block_ids = array_unique( $block_ids );
			if ( empty( $block_ids ) ) {
				continue;
			}
			$total_votes = 0;
			$last_vote   = null;
			foreach ( $block_ids as $bid ) {
				$key = (int) $p->ID . '|' . $bid;
				if ( isset( $non_map[ $key ] ) ) {
					$total_votes += $non_map[ $key ][ 'cnt' ];
					if ( ! $last_vote || $non_map[ $key ][ 'last' ] > $last_vote ) {
						$last_vote = $non_map[ $key ][ 'last' ];
					}
				}
				if ( isset( $legacy_map[ $bid ] ) ) {
					$total_votes += $legacy_map[ $bid ][ 'cnt' ];
					if ( ! $last_vote || $legacy_map[ $bid ][ 'last' ] > $last_vote ) {
						$last_vote = $legacy_map[ $bid ][ 'last' ];
					}
				}
			}
			$summary[] = (object) [
				'post_id'     => (int) $p->ID,
				'post_title'  => $p->post_title,
				'poll_count'  => count( $block_ids ),
				'total_votes' => $total_votes,
				'last_vote'   => $last_vote,
			];
		}
		usort( $summary, function ( $a, $b ) {
			return $b->total_votes <=> $a->total_votes;
		} );
		$ttl = defined( 'MINUTE_IN_SECONDS' ) ? 5 * MINUTE_IN_SECONDS : 300;
		set_transient( 'content_poll_posts_summary', $summary, $ttl );
		return $summary;
	}

	/**
	 * Parse blocks from post content to extract poll attributes.
	 *
	 * @param int $post_id Post ID.
	 * @return array Map of block_id => ['question' => string, 'options' => array].
	 */
	public function get_post_block_attributes( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		$blocks = parse_blocks( $post->post_content );
		$map    = [];

		foreach ( $blocks as $block ) {
			if ( $block[ 'blockName' ] === 'content-poll/vote-block' && isset( $block[ 'attrs' ] ) ) {
				$attrs    = $block[ 'attrs' ];
				$block_id = $attrs[ 'blockId' ] ?? '';
				if ( $block_id ) {
					$map[ $block_id ] = [
						'question' => $attrs[ 'question' ] ?? __( 'Untitled Poll', 'content-poll' ),
						'options'  => $attrs[ 'options' ] ?? [],
					];
				}
			}
		}

		return $map;
	}

	/**
	 * Get block attributes for a specific block ID from post content.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $block_id Block identifier.
	 * @return array|null Array with 'question' and 'options' or null if not found.
	 */
	public function get_block_attributes( int $post_id, string $block_id ): ?array {
		$map = $this->get_post_block_attributes( $post_id );
		return $map[ $block_id ] ?? null;
	}

	/**
	 * Delete all vote rows for a given block id (orphan cleanup).
	 *
	 * @param string $block_id Block identifier.
	 * @return int Rows affected.
	 */
	public function delete_block_votes( string $block_id ): int {
		$db       = $this->db;
		$affected = $db->query( $db->prepare( "DELETE FROM {$this->table} WHERE block_id = %s", $block_id ) );
		// Invalidate cached summary after deletion.
		delete_transient( 'content_poll_posts_summary' );
		return (int) $affected;
	}

	/**
	 * Explicit cache invalidation helper.
	 */
	public static function invalidate_cache(): void {
		delete_transient( 'content_poll_posts_summary' );
	}
}
