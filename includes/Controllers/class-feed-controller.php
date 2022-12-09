<?php

namespace Feed_Reader\Controllers;

use Feed_Reader\Controllers\Controller;
use Feed_Reader\Feed_List_Table;
use Feed_Reader\Jobs\Poll_Feeds;
use Feed_Reader\Models\Category;
use Feed_Reader\Models\Entry;
use Feed_Reader\Models\Feed;

class Feed_Controller extends Controller {
	public static function index() {
		$feed_table = new Feed_List_Table();
		$feed_table->prepare_items();

		static::render( 'feeds/list', compact( 'feed_table' ) );
	}

	public static function view() {
		$feed = wp_cache_get( 'feed-reader:model' );

		wp_cache_delete( 'feed-reader:model' );

		if ( ! $feed ) {
			wp_die( esc_html_e( 'Unknown feed.', 'feed-reader' ) );
		}

		$all   = isset( $_GET['all'] ) && '1' === $_GET['all']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$count       = Feed::entries_count( $feed->id, $all );
		$entries     = Feed::entries( $feed->id, 15, $all );
		$total_pages = ceil( $count / 15 );

		static::render( 'entries/list', compact( 'feed', 'entries', 'paged', 'total_pages' ) );
	}

	public static function create() {
		static::render( 'feeds/edit' );
	}

	public static function store() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-feeds:add' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['feed_url'] ) || ! wp_http_validate_url( $_POST['feed_url'] ) ) {
			wp_die( esc_html__( 'Missing URL.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$url = wp_unslash( $_POST['feed_url'] );

		if ( empty( $url ) ) {
			wp_die( esc_html__( 'URL cannot be empty.', 'feed-reader' ) );
		}

		if ( isset( $_POST['feed_name'] ) && is_string( $_POST['feed_name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['feed_name'] ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['site_url'] ) && wp_http_validate_url( $_POST['site_url'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$site_url = $_POST['site_url'];
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['category'] ) && ctype_digit( $_POST['category'] ) ) {
			$category = Category::find( (int) $_POST['category'] );
		}

		if ( ! Feed::exists( esc_url_raw( $url ) ) ) {
			$feed_id = Feed::insert(
				array(
					'url'         => esc_url_raw( $url ),
					'name'        => isset( $name ) ? $name : preg_replace( '~^www~', '', wp_parse_url( $url, PHP_URL_HOST ) ),
					'site_url'    => isset( $site_url ) ? esc_url_raw( $site_url ) : null,
					'category_id' => isset( $category->id ) ? $category->id : null,
					'user_id'     => get_current_user_id(),
				)
			);

			if ( $feed_id ) {
				$feed = Feed::find( $feed_id );
				Poll_Feeds::poll_feed( $feed );
			}
		}

		wp_safe_redirect( esc_url_raw( \Feed_Reader\get_url( 'feeds' ) ) );
		exit;
	}

	public static function edit() {
		$feed = Feed::find( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		static::render( 'feeds/edit', compact( 'feed' ) );
	}

	public static function update() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-feeds:edit:' . intval( $_POST['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_POST['feed_url'] ) || ! wp_http_validate_url( $_POST['feed_url'] ) ) {
			wp_die( esc_html__( 'Invalid URL.', 'feed-reader' ) );
		}

		$feed = Feed::find( (int) $_POST['id'] );

		if ( ! $feed ) {
			wp_die( esc_html__( 'Unknown feed.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$url = $_POST['feed_url'];

		if ( isset( $_POST['feed_name'] ) && is_string( $_POST['feed_name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['feed_name'] ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['site_url'] ) && wp_http_validate_url( $_POST['site_url'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$site_url = $_POST['site_url'];
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['category'] ) && ctype_digit( $_POST['category'] ) ) {
			$category = Category::find( (int) $_POST['category'] );
		}

		$exists = Feed::exists( esc_url_raw( $url ) );

		if ( ! $exists || intval( $feed->id ) === $exists ) {
			$result = Feed::update(
				array(
					'url'         => esc_url_raw( $url ),
					'name'        => isset( $name ) ? $name : preg_replace( '~^www~', '', wp_parse_url( $url, PHP_URL_HOST ) ),
					'site_url'    => isset( $site_url ) ? esc_url_raw( $site_url ) : null,
					'category_id' => isset( $category->id ) ? $category->id : null,
				),
				array( 'id' => $feed->id )
			);

			if ( $result ) {
				Poll_Feeds::poll_feed( $feed );
			}
		}

		wp_safe_redirect( esc_url_raw( \Feed_Reader\get_url( 'feeds' ) ) );
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'feed-reader-feeds:delete:' . intval( $_GET['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_GET['id'] ) || ! ctype_digit( $_GET['id'] ) ) {
			wp_die( esc_html__( 'Missing ID.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$feed = Feed::find( (int) $_GET['id'] ); // Ensure feed exists and belongs to the current user.

		if ( $feed ) {
			global $wpdb;

			// (Permanently) delete entries.
			$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				Entry::table(),
				array( 'feed_id' => $feed->id )
			);

			// Delete feed.
			Feed::delete( $feed->id );
		}

		wp_safe_redirect( esc_url_raw( \Feed_Reader\get_url( 'feeds' ) ) );
		exit;
	}
}
