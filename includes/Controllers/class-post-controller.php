<?php

namespace FeedReader\Controllers;

class Post_Controller {
	public static function process() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader:post' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		$content   = '';
		$post_type = 'post';

		if ( function_exists( '\\IndieBlocks\\get_options' ) ) {
			$indieblocks = \IndieBlocks\get_options();
		}

		if ( isset( $_POST['content'] ) && is_string( $_POST['content'] ) ) {
			$content = sanitize_textarea_field( wp_unslash( $_POST['content'] ) );
		}

		if ( ! empty( $_POST['in-reply-to'] ) && filter_var( wp_unslash( $_POST['in-reply-to'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['in-reply-to'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! class_exists( '\\IndieBlocks\\Micropub_Compat' ) ) {
				/* translators: %s: URL of the page being replied to */
				$context = sprintf( __( 'In reply to %s.', 'feed-reader' ), '<a class="in-reply-to" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' );
				$content = trim( '<i>' . $context . '</i>' . PHP_EOL . PHP_EOL . $content );
			} else {
				$input   = array( 'properties' => array( 'content' => (array) $content ) );
				$content = \IndieBlocks\Micropub_Compat::render( 'reply', $url, $input );
			}

			if ( ! empty( $indieblocks['enable_notes'] ) ) {
				$post_type = 'indieblocks_note';
			}
		}

		if ( ! empty( $_POST['bookmark-of'] ) && filter_var( wp_unslash( $_POST['bookmark-of'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['bookmark-of'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! class_exists( '\\IndieBlocks\\Micropub_Compat' ) ) {
				/* translators: %s: Bookmark URL */
				$context = sprintf( __( 'Bookmarked %s.', 'feed-reader' ), '<a class="u-bookmark-of" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' );
				$content = '<i>' . $context . '</i>' . PHP_EOL . PHP_EOL . $content;
			} else {
				$input   = array( 'properties' => array( 'content' => (array) $content ) );
				$content = \IndieBlocks\Micropub_Compat::render( 'bookmark', $url, $input );
			}

			if ( ! empty( $indieblocks['enable_notes'] ) ) {
				$post_type = 'indieblocks_note';
			}
		}

		if ( ! empty( $_POST['like-of'] ) && filter_var( wp_unslash( $_POST['like-of'] ), FILTER_VALIDATE_URL ) ) {
			$url = wp_unslash( $_POST['like-of'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! class_exists( '\\IndieBlocks\\Micropub_Compat' ) ) {
				/* translators: %s: URL of the page being "liked" */
				$content = '<i>' . sprintf( __( 'Likes %s.', 'feed-reader' ), '<a class="u-like-of" href="' . esc_url( $url ) . '">' . esc_url( $url ) . '</a>' ) . '</i>';
			} else {
				$content = \IndieBlocks\Micropub_Compat::render( 'like', $url );
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
			// 'post_title' => ...
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_type'    => apply_filters( 'feed_reader_post_post_type', $post_type, $content ),
		);

		wp_insert_post( apply_filters( 'feed_reader_post_args', $args ) );
		wp_die();
	}
}
