<?php

namespace Feed_Reader;

use Feed_Reader\Models\Category;
use Feed_Reader\Models\Feed;
use WP_List_Table;

class Feed_List_Table extends WP_List_Table {
	public function get_columns() {
		return array(
			'name'        => __( 'Name', 'feed-reader' ),
			'site_url'    => __( 'Site URL', 'feed-reader' ),
			'url'         => __( 'Feed URL', 'feed-reader' ),
			'last_polled' => __( 'Last Polled', 'feed-reader' ),
			'last_error'  => __( 'Last Error', 'feed-reader' ),
			'category'    => __( 'Category', 'feed-reader' ),
		);
	}

	public function get_sortable_columns() {
		return array();
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden  = array();

		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() );

		$feeds = Feed::paginate();

		if ( ! is_array( $feeds ) ) {
			return;
		}

		$this->set_pagination_args(
			array(
				'total_items' => Feed::count(),
				'per_page'    => 15,
			)
		);

		$this->items = $feeds;
	}

	public function column_default( $item, $column_name ) {
		return ! empty( $item->$column_name )
			? esc_html( $item->$column_name )
			: '&mdash;';
	}

	public function column_name( $item ) {
		$name = ! empty( $item->name ) ? $item->name : preg_replace( '~^www\.~', '', wp_parse_url( $item->url, PHP_URL_HOST ) );

		$actions = array(
			'view'   => '<a href="' . esc_url( get_url( 'feeds', 'view', $item->id, true ) ) . '">' . esc_html__( 'View', 'feed-reader' ) . '</a>',
			'edit'   => '<a href="' . esc_url( get_url( 'feeds', 'edit', $item->id ) ) . '">' . esc_html__( 'Edit', 'feed-reader' ) . '</a>',
			'delete' => '<a href="' . esc_url( $this->get_delete_url( $item->id ) ) . '">' . esc_html__( 'Delete', 'feed-reader' ) . '</a>',
		);

		return '<strong><a href="' . esc_url( get_url( 'feeds', 'view', $item->id ) ) . '">' . esc_html( $name ) . '</a></strong>' . PHP_EOL . $this->row_actions( $actions );
	}

	public function column_site_url( $item ) {
		return ! empty( $item->site_url )
			? '<a href="' . esc_url( $item->site_url ) . '">' . esc_url( $item->site_url ) . '</a>'
			: '&mdash;';
	}

	public function column_url( $item ) {
		return '<a href="' . esc_url( $item->url ) . '">' . esc_url( $item->url ) . '</a>';
	}

	public function column_category( $item ) {
		if ( ! empty( $item->category_name ) ) {
			return '<a href="' . esc_url( \Feed_Reader\get_url( 'categories', 'view', $item->category_id ) ) . '">' . esc_html( $item->category_name ) . '</a>';
		};

		return '&mdash;';
	}

	public function column_last_polled( $item ) {
		return isset( $item->last_polled )
			/* translators: %1$s: date, %2$s: time */
			? sprintf( esc_html__( '%1$s at %2$s', 'feed-reader' ), wp_date( get_option( 'date_format' ), strtotime( $item->last_polled ) ), wp_date( get_option( 'time_format' ), strtotime( $item->last_polled ) ) )
			: '&mdash;';
	}

	protected function get_delete_url( $id ) {
		return add_query_arg(
			array(
				'action'   => 'feed_reader_feeds_delete',
				'id'       => $id,
				'_wpnonce' => wp_create_nonce( "feed-reader-feeds:delete:$id" ),
			),
			admin_url( 'admin-post.php' )
		);
	}
}
