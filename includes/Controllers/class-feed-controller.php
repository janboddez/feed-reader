<?php

namespace FeedReader\Controllers;

use FeedReader\Controllers\Controller;
use FeedReader\Helpers\Feed_List_Table;
use FeedReader\Jobs\Poll_Feeds;
use FeedReader\Models\Category;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;

class Feed_Controller extends Controller {
	public static function index() {
		$feed_table = new Feed_List_Table();
		$feed_table->prepare_items();

		static::render( 'feeds/list', compact( 'feed_table' ) );
	}

	public static function view() {
		$feed = \FeedReader\Helpers\current_model();

		if ( ! $feed ) {
			wp_die( esc_html_e( 'Unknown feed.', 'feed-reader' ) );
		}

		$all = isset( $_GET['all'] ) && '1' === $_GET['all']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		list( $entries, $before, $after ) = Entry::cursor_paginate( 15, $all, $feed->id );

		static::render( 'entries/list', compact( 'feed', 'entries', 'before', 'after' ) );
	}

	public static function create() {
		static::render( 'feeds/create' );
	}

	public static function store() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
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
		if ( isset( $_POST['category'] ) && ctype_digit( $_POST['category'] ) ) {
			$category = Category::find( (int) $_POST['category'] );
		}

		$feed_id = Feed::exists( esc_url_raw( $url ) );

		if ( ! $feed_id ) {
			$feed_id = Feed::insert(
				array(
					'url'         => esc_url_raw( $url ),
					'name'        => isset( $name ) ? $name : preg_replace( '~^www~', '', wp_parse_url( $url, PHP_URL_HOST ) ),
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'site_url'    => isset( $_POST['site_url'] ) && wp_http_validate_url( $_POST['site_url'] ) ? esc_url_raw( $_POST['site_url'] ) : null,
					'category_id' => isset( $category->id ) ? $category->id : null,
					'user_id'     => get_current_user_id(),
				)
			);

			if ( $feed_id ) {
				$feed = Feed::find( $feed_id );

				if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) ) {
					Feed::fetch_favicon( $feed );
				}

				Poll_Feeds::poll_feed( $feed );
			}
		}

		if ( $feed_id ) {
			wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed_id ) ) );
			exit;
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds' ) ) );
		exit;
	}

	public static function edit() {
		$feed = Feed::find( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		static::render( 'feeds/edit', compact( 'feed' ) );
	}

	public static function update() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['id'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-feeds:edit:' . intval( $_POST['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_POST['feed_url'] ) || ! wp_http_validate_url( $_POST['feed_url'] ) ) {
			wp_die( esc_html__( 'Invalid URL.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$url = esc_url_raw( $_POST['feed_url'] );

		$feed = Feed::find( (int) $_POST['id'] );

		if ( ! $feed ) {
			wp_die( esc_html__( 'Unknown feed.', 'feed-reader' ) );
		}

		if ( isset( $_POST['feed_name'] ) && is_string( $_POST['feed_name'] ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['feed_name'] ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['category'] ) && ctype_digit( $_POST['category'] ) ) {
			$category = Category::find( (int) $_POST['category'] );
		}

		$exists = Feed::exists( $url );

		// Should we do this? Update only if another feed doesn't already have this URL?
		if ( ! $exists || intval( $feed->id ) === $exists ) {
			$result = Feed::update(
				array(
					'url'         => $url,
					'name'        => isset( $name ) ? $name : preg_replace( '~^www~', '', wp_parse_url( $url, PHP_URL_HOST ) ),
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'site_url'    => isset( $_POST['site_url'] ) && wp_http_validate_url( $_POST['site_url'] ) ? esc_url_raw( $_POST['site_url'] ) : null,
					'category_id' => isset( $category->id ) ? $category->id : null,
				),
				array( 'id' => $feed->id )
			);

			if ( $result ) {
				if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) ) {
					Feed::fetch_favicon( $feed );
				}

				Poll_Feeds::poll_feed( $feed );
			}
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id ) ) );
		exit;
	}

	public static function delete() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
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
			// Delete feed. Linked entries are handled by the cascade.
			Feed::delete( $feed->id );
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds' ) ) );
		exit;
	}

	public static function mark_read() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( empty( $_GET['_wpnonce'] ) || empty( $_GET['id'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'feed-reader-feeds:mark-read:' . intval( $_GET['id'] ) ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$feed = Feed::find( (int) $_GET['id'] ); // Ensure feed exists and belongs to the current user.

		if ( $feed ) {
			Entry::update(
				array( 'is_read' => 1 ),
				array( 'feed_id' => $feed->id )
			);

			wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id ) ) );
			exit;
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds' ) ) );
		exit;
	}

	public static function discover() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-feeds:discover' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		if ( empty( $_POST['url'] ) || ! wp_http_validate_url( $_POST['url'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Invalid URL.', 'feed-reader' ) );
		}

		$url = esc_url_raw( $_POST['url'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$response = wp_safe_remote_get(
			esc_url_raw( $url ),
			array(
				'timeout'    => 11,
				'user-agent' => \FeedReader\Helpers\get_user_agent( $url ),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_die();
		}

		// Some defaults.
		$title = '';
		$feeds = array();

		$body         = wp_remote_retrieve_body( $response );
		$content_type = (array) wp_remote_retrieve_header( $response, 'content-type' );

		$content_type = array_pop( $content_type );
		$content_type = strtok( $content_type, ';' );
		strtok( '', '' );

		if ( in_array( $content_type, array( 'application/feed+json', 'application/json' ), true ) ) {
			$data = json_decode( trim( $body ) );

			if ( ! empty( $data->version ) && false !== strpos( $data->version, 'https://jsonfeed.org/version/' ) ) {
				$feeds[] = array(
					'format' => 'json_feed',
					'url'    => esc_url_raw( $url ),
				);
			}
		} elseif ( in_array( $content_type, array( 'application/rss+xml' ), true ) ) {
			$feeds[] = array(
				'format' => 'rss',
				'url'    => esc_url_raw( $url ),
			);
		} elseif ( in_array( $content_type, array( 'application/atom+xml' ), true ) ) {
			$feeds[] = array(
				'format' => 'atom',
				'url'    => esc_url_raw( $url ),
			);
		} elseif ( in_array( $content_type, array( 'text/xml', 'application/xml' ), true ) ) {
			$feeds[] = array(
				'format' => 'xml',
				'url'    => esc_url_raw( $url ),
			);
		} else {
			$body = mb_convert_encoding( $body, 'HTML-ENTITIES', \FeedReader\Helpers\detect_encoding( $body ) );
			$dom  = new \DOMDocument();

			libxml_use_internal_errors( true );

			$dom->loadHTML( $body );

			$el = $dom->getElementsByTagName( 'title' );

			if ( isset( $el->length ) && $el->length > 0 ) {
				$title = trim( $el->item( 0 )->textContent );
			}

			// Now parse `rel="alternate"` URLs.
			$mf2 = \FeedReader\Mf2\Parse( $body, $url );

			if ( empty( $mf2['rel-urls'] ) ) {
				return array(
					'title' => $title,
					'feeds' => $feeds,
				);
			}

			foreach ( $mf2['rel-urls'] as $rel => $info ) {
				if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) && isset( $info['type'] ) ) {
					if ( false !== strpos( $info['type'], 'application/feed+json' ) || false !== strpos( $info['type'], 'application/json' ) ) {
						$feeds[] = array(
							'format' => 'json_feed',
							'url'    => esc_url_raw( $rel ),
						);
					}

					if ( false !== strpos( $info['type'], 'application/atom+xml' ) ) {
						$feeds[] = array(
							'format' => 'atom',
							'url'    => esc_url_raw( $rel ),
						);
					}

					if ( false !== strpos( $info['type'], 'application/rss+xml' ) ) {
						$feeds[] = array(
							'format' => 'rss',
							'url'    => esc_url_raw( $rel ),
						);
					}
				}
			}

			// Look for mf2.
			$hash = hash( 'sha256', esc_url_raw( $url ) );
			$data = wp_cache_get( "feed-reader:mf2:$hash" );

			if ( false === $data ) {
				$data = \FeedReader\Mf2\parse( $body, $url );
				wp_cache_set( "feed-reader:mf2:$hash", $data, '', 3600 ); /** @todo: Use transients instead? */
			}

			if ( ! empty( $data['items'][0]['type'] ) && in_array( 'h-feed', $data['items'][0]['type'], true ) ) {
				$feeds[] = array(
					'format' => 'mf2',
					'url'    => esc_url_raw( $url ),
				);
			}
		}

		header( 'Content-Type: application/json' );

		echo wp_json_encode(
			array(
				'title' => $title,
				'feeds' => $feeds,
			)
		);
		wp_die();
	}
}
