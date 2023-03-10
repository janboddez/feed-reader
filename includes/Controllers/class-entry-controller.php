<?php

namespace FeedReader\Controllers;

use FeedReader\Models\Entry;

class Entry_Controller extends Controller {
	public static function index() {
		$all = isset( $_GET['all'] ) && '1' === $_GET['all']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		list( $entries, $before, $after ) = Entry::cursor_paginate( 15, $all );

		static::render( 'entries/list', compact( 'entries', 'before', 'after' ) );
	}

	public static function view() {
		$entry = \FeedReader\Helpers\current_model();

		if ( ! $entry ) {
			wp_die( esc_html_e( 'Unknown entry.', 'feed-reader' ) );
		}

		if ( 0 === intval( $entry->is_read ) ) {
			// Immediately mark as read, for now.
			$result = Entry::update(
				array( 'is_read' => 1 ),
				array( 'id' => $entry->id )
			);

			if ( $result ) {
				// No need to refetch.
				$entry->is_read = 1;
			}
		}

		static::render( 'entries/view', compact( 'entry' ) );
	}

	public static function delete() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-entries:delete:' . intval( $_POST['id'] ) ) ) {
			// Missing or invalid nonce or category ID.
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		$entry = Entry::find( (int) $_POST['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $entry ) {
			Entry::delete( $entry->id );
		}

		wp_die();
	}

	public static function mark_read() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-entries:mark-read:' . intval( $_POST['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		$entry = Entry::find( (int) $_POST['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $entry ) {
			Entry::update(
				array( 'is_read' => 1 ),
				array( 'id' => $entry->id )
			);
		}

		wp_die();
	}

	public static function mark_unread() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-entries:mark-unread:' . intval( $_POST['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		$entry = Entry::find( (int) $_POST['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $entry ) {
			Entry::update(
				array( 'is_read' => 0 ),
				array( 'id' => $entry->id )
			);
		}

		wp_die();
	}
}
