<?php

declare(strict_types=1);

namespace ContentPoll\Admin;

use ContentPoll\Services\VoteAnalyticsService;

// WP_List_Table is an internal WP class; load if missing.
if ( ! class_exists( '\\WP_List_Table' ) && defined( 'ABSPATH' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table for displaying posts containing poll blocks with analytics data.
 * Provides pagination, sorting and basic actions.
 */
class PollsListTable extends \WP_List_Table {
	private VoteAnalyticsService $analytics;

	public function __construct( VoteAnalyticsService $analytics ) {
		parent::__construct( [
			'singular' => 'poll_post',
			'plural'   => 'poll_posts',
			'ajax'     => false,
		] );
		$this->analytics = $analytics;
	}

	public function get_columns(): array {
		return [
			'post_title'  => __( 'Post Title', 'content-poll' ),
			'poll_count'  => __( 'Polls', 'content-poll' ),
			'total_votes' => __( 'Total Votes', 'content-poll' ),
			'last_vote'   => __( 'Last Activity', 'content-poll' ),
			'actions'     => __( 'Actions', 'content-poll' ),
		];
	}

	protected function get_sortable_columns(): array {
		return [
			'post_title'  => [ 'post_title', false ],
			'poll_count'  => [ 'poll_count', false ],
			'total_votes' => [ 'total_votes', true ],
			'last_vote'   => [ 'last_vote', false ],
		];
	}

	public function no_items(): void {
		esc_html_e( 'No posts with polls found.', 'content-poll' );
	}

	/**
	 * Prepare items: fetch data, sort, paginate.
	 */
	public function prepare_items(): void {
		// Set up column headers (required for WP_List_Table to render properly)
		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$data                  = $this->analytics->get_posts_summary();

		// Sorting
		$orderby = isset( $_GET[ 'orderby' ] ) ? sanitize_key( (string) $_GET[ 'orderby' ] ) : 'total_votes';
		$order   = isset( $_GET[ 'order' ] ) ? strtolower( (string) $_GET[ 'order' ] ) : 'desc';
		$valid   = [ 'post_title', 'poll_count', 'total_votes', 'last_vote' ];
		if ( ! in_array( $orderby, $valid, true ) ) {
			$orderby = 'total_votes';
		}
		if ( $order !== 'asc' && $order !== 'desc' ) {
			$order = 'desc';
		}
		usort( $data, function ( $a, $b ) use ( $orderby, $order ) {
			$valA = $a->$orderby;
			$valB = $b->$orderby;
			// Normalize null last_vote to empty string for comparison.
			if ( $orderby === 'last_vote' ) {
				$valA = $valA ?? '';
				$valB = $valB ?? '';
			}
			if ( is_string( $valA ) ) {
				$cmp = strcmp( $valA, (string) $valB );
			} else {
				$cmp = $valA <=> $valB;
			}
			return ( $order === 'asc' ) ? $cmp : -$cmp;
		} );

		$per_page     = (int) $this->get_items_per_page( 'content_poll_polls_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$data = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

		$this->items = $data;
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => $per_page > 0 ? (int) ceil( $total_items / $per_page ) : 1,
		] );
	}

	/**
	 * Default column output fallback.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'poll_count':
				return esc_html( number_format_i18n( (int) $item->poll_count ) );
			case 'total_votes':
				return esc_html( number_format_i18n( (int) $item->total_votes ) );
			case 'last_vote':
				return $item->last_vote ? esc_html( human_time_diff( strtotime( $item->last_vote ), time() ) . ' ' . __( 'ago', 'content-poll' ) ) : '—';
			case 'actions':
				$url = add_query_arg( [
					'page'    => 'content-poll-settings',
					'tab'     => 'analytics',
					'post_id' => (int) $item->post_id,
				], admin_url( 'options-general.php' ) );
				return '<a href="' . esc_url( $url ) . '" class="button button-small">' . esc_html__( 'View Details', 'content-poll' ) . '</a>';
			default:
				return ''; // Should not reach here for defined columns.
		}
	}

	public function column_post_title( $item ) {
		$edit_link = get_edit_post_link( (int) $item->post_id );
		$title     = $item->post_title ?: __( '(No title)', 'content-poll' );
		$html      = '<strong>';
		if ( $edit_link ) {
			$html .= '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
		} else {
			$html .= esc_html( $title );
		}
		$html .= '</strong>';
		return $html;
	}
}
