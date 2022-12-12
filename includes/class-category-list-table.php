<?php

namespace FeedReader;

use FeedReader\Models\Category;

class Category_List_Table extends \WP_List_Table {
	public function get_columns() {
		return array(
			'name' => __( 'Name', 'feed-reader' ),
		);
	}

	public function get_sortable_columns() {
		return array();
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden  = array();

		$this->_column_headers = array( $columns, $hidden, $this->get_sortable_columns() );

		$search = null;

		if ( ! empty( $_GET['s'] ) && is_string( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		list( $categories, $total ) = Category::paginate( 15, $search );

		if ( ! is_array( $categories ) ) {
			return;
		}

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => 15,
			)
		);

		$this->items = $categories;
	}

	public function column_default( $item, $column_name ) {
		return esc_html( $item->$column_name );
	}

	public function column_name( $item ) {
		$actions = array(
			'view'   => '<a href="' . esc_url( get_url( 'categories', 'view', $item->id, true ) ) . '">' . esc_html__( 'View', 'feed-reader' ) . '</a>',
			'edit'   => '<a href="' . esc_url( get_url( 'categories', 'edit', $item->id ) ) . '">' . esc_html__( 'Edit', 'feed-reader' ) . '</a>',
			'delete' => '<a href="' . esc_url( $this->get_delete_url( $item->id ) ) . '">' . esc_html__( 'Delete', 'feed-reader' ) . '</a>',
		);

		return '<strong><a href="' . esc_url( get_url( 'categories', 'view', $item->id ) ) . '">' . esc_html( $item->name ) . '</a></strong>' . PHP_EOL . $this->row_actions( $actions );
	}

	protected function get_delete_url( $id ) {
		return add_query_arg(
			array(
				'action'   => 'feed_reader_categories_delete',
				'id'       => $id,
				'_wpnonce' => wp_create_nonce( "feed-reader-categories:delete:$id" ),
			),
			admin_url( 'admin-post.php' )
		);
	}
}
