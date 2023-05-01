<?php

namespace FeedReader\Controllers;

class Post_Controller {
	public static function process() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader:post' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		if ( function_exists( '\\IndieBlocks\\get_options' ) ) {
			$indieblocks = \IndieBlocks\get_options();
		}

		$name      = '';
		$content   = '';
		$status    = 'draft';
		$post_type = 'post';

		if ( ! empty( $_POST['name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		}

		if ( ! empty( $_POST['content'] ) ) {
			$content = sanitize_textarea_field( wp_unslash( $_POST['content'] ) );
		}

		if ( ! empty( $_POST['status'] ) && in_array( wp_unslash( $_POST['status'] ), array( 'draft', 'private', 'publish' ), true ) ) {
			$status = $_POST['status']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( ! empty( $_POST['in-reply-to'] ) && filter_var( wp_unslash( $_POST['in-reply-to'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['in-reply-to'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $indieblocks['enable_blocks'] ) ) {
				$input   = array( 'properties' => array( 'content' => (array) $content ) );
				$content = \IndieBlocks\Micropub_Compat::render( 'reply', $url, $input );
			} else {
				/* translators: %s: URL of the page being replied to */
				$context = sprintf( __( 'In reply to %s.', 'feed-reader' ), '<a class="in-reply-to" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' );
				$content = trim( '<i>' . $context . '</i>' . PHP_EOL . PHP_EOL . '<div class="e-content">' . $content . '</div>' );
			}

			if ( ! empty( $indieblocks['enable_notes'] ) ) {
				$post_type = 'indieblocks_note';
			}
		}

		if ( ! empty( $_POST['bookmark-of'] ) && filter_var( wp_unslash( $_POST['bookmark-of'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['bookmark-of'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $indieblocks['enable_blocks'] ) ) {
				$input   = array( 'properties' => array( 'content' => (array) $content ) );
				$content = \IndieBlocks\Micropub_Compat::render( 'bookmark', $url, $input );
			} else {
				/* translators: %s: Bookmark URL */
				$context = sprintf( __( 'Bookmarked %s.', 'feed-reader' ), '<a class="u-bookmark-of" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' );
				$content = trim( '<i>' . $context . '</i>' . PHP_EOL . PHP_EOL . '<div class="e-content">' . $content . '</div>' );
			}

			if ( ! empty( $indieblocks['enable_notes'] ) ) {
				$post_type = 'indieblocks_note';
			}
		}

		if ( ! empty( $_POST['like-of'] ) && filter_var( wp_unslash( $_POST['like-of'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['like-of'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $indieblocks['enable_blocks'] ) ) {
				$content = \IndieBlocks\Micropub_Compat::render( 'like', $url );
			} else {
				/* translators: %s: URL of the page being "liked" */
				$content = '<i>' . sprintf( __( 'Likes %s.', 'feed-reader' ), '<a class="u-like-of" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' ) . '</i>';
			}

			if ( ! empty( $indieblocks['enable_likes'] ) ) {
				$post_type = 'indieblocks_like';
			} elseif ( ! empty( $indieblocks['enable_notes'] ) ) {
				$post_type = 'indieblocks_note';
			}
		}

		if ( empty( $content ) ) {
			// Nothing to do.
			wp_die();
		}

		$args = array(
			'post_content' => $content,
			'post_status'  => apply_filters( 'feed_reader_post_status', $status, $content ),
			'post_author'  => get_current_user_id(),
			'post_type'    => apply_filters( 'feed_reader_post_type', $post_type, $content ),
		);

		if ( ! empty( $name ) ) {
			$args['post_title'] = $name;
		}

		$post_id = wp_insert_post( apply_filters( 'feed_reader_post_args', $args ) );
		if ( is_wp_error( $post_id ) ) {
			wp_die();
		}

		$synd_requested = array();

		if ( ! empty( $_POST['mp-syndicate-to'] ) && is_array( $_POST['mp-syndicate-to'] ) ) {
			$synd_requested = wp_unslash( $_POST['mp-syndicate-to'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		do_action( 'micropub_syndication', $post_id, $synd_requested );
		wp_die();
	}
}
