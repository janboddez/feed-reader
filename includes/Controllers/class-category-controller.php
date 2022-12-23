<?php

namespace FeedReader\Controllers;

use FeedReader\Controllers\Controller;
use FeedReader\Helpers\Category_List_Table;
use FeedReader\Models\Category;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;

class Category_Controller extends Controller {
	public static function index() {
		$feed_table = new Category_List_Table();
		$feed_table->prepare_items();

		static::render( 'categories/list', compact( 'feed_table' ) );
	}

	public static function view() {
		$category = wp_cache_get( 'feed-reader:model' );
		wp_cache_delete( 'feed-reader:model' );

		if ( ! $category ) {
			wp_die( esc_html_e( 'Unknown category.', 'feed-reader' ) );
		}

		$all = isset( $_GET['all'] ) && '1' === $_GET['all']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		list( $entries, $before, $after ) = Entry::cursor_paginate( 15, $all, null, $category->id );

		static::render( 'entries/list', compact( 'category', 'entries', 'before', 'after' ) );
	}

	public static function create() {
		static::render( 'categories/edit' );
	}

	public static function store() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-categories:add' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['category_name'] ) || ! is_string( $_POST['category_name'] ) ) {
			wp_die( esc_html__( 'Missing name.', 'feed-reader' ) );
		}

		$name = sanitize_text_field( wp_unslash( $_POST['category_name'] ) );

		if ( empty( $name ) ) {
			wp_die( esc_html__( 'Name cannot be empty.', 'feed-reader' ) );
		}

		if ( ! Category::exists( $name ) ) {
			global $wpdb;

			// Generate a unique UID.
			do {
				$uid    = bin2hex( openssl_random_pseudo_bytes( 16 ) );
				$sql    = sprintf( 'SELECT id FROM %s WHERE uid = %%s LIMIT 1', Category::table() );
				$exists = $wpdb->get_var( $wpdb->prepare( $sql, $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			} while ( $exists );

			$result = Category::insert(
				array(
					'name'    => $name,
					'uid'     => $uid,
					'user_id' => get_current_user_id(),
				)
			);
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'categories' ) ) );
		exit;
	}

	public static function edit() {
		$category = Category::find( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		static::render( 'categories/edit', compact( 'category' ) );
	}

	public static function update() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-categories:edit:' . intval( $_POST['id'] ) ) ) {
			// Missing or invalid nonce or category ID.
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		if ( ! empty( $_POST['category_name'] ) && is_string( $_POST['category_name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['category_name'] ) );
		}

		if ( empty( $name ) ) {
			// Sanitized name is empty.
			wp_die( esc_html__( 'Missing category name.', 'feed-reader' ) );
		}

		$category = Category::find( (int) $_POST['id'] ); // Ensure the category exists and belongs to the current user.

		if ( ! $category ) {
			wp_die( esc_html__( 'Unknown category.', 'feed-reader' ) );
		}

		if ( ! Category::exists( 'name' ) ) {
			Category::update(
				array(
					'name' => $name,
				),
				array( 'id' => $category->id )
			);
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'categories' ) ) );
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'feed-reader-categories:delete:' . intval( $_GET['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_GET['id'] ) || ! ctype_digit( $_GET['id'] ) ) {
			wp_die( esc_html__( 'Missing ID.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$category = Category::find( (int) $_GET['id'] ); // Ensure the category exists and belongs to the current user.

		if ( $category ) {
			// Detach feeds.
			Feed::update(
				array( 'category_id' => null ),
				array( 'category_id' => $category->id )
			);

			// Delete category.
			Category::delete( $category->id );
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'categories' ) ) );
		exit;
	}
}
