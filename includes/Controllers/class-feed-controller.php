<?php

namespace FeedReader\Controllers;

use FeedReader\Controllers\Controller;
use FeedReader\Feed_List_Table;
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
		$feed = wp_cache_get( 'feed-reader:model' );
		wp_cache_delete( 'feed-reader:model' );

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

				if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) ) {
					Feed::fetch_favicon( $feed );
				}

				Poll_Feeds::poll_feed( $feed );
			}
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\get_url( 'feeds' ) ) );
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
				if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) ) {
					Feed::fetch_favicon( $feed );
				}

				Poll_Feeds::poll_feed( $feed );
			}
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\get_url( 'feeds' ) ) );
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

			// Delete feed. Linked entries are handled by the cascade.
			Feed::delete( $feed->id );
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\get_url( 'feeds' ) ) );
		exit;
	}

	public static function mark_read() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
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

			wp_safe_redirect( esc_url_raw( \FeedReader\get_url( 'feeds', 'view', $feed->id ) ) );
			exit;
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\get_url( 'feeds' ) ) );
		exit;
	}

	public static function discover() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-feeds:discover' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		if ( empty( $_POST['url'] ) || ! wp_http_validate_url( wp_unslash( $_POST['url'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_die( esc_html__( 'Invalid URL.', 'feed-reader' ) );
		}

		$url = wp_unslash( $_POST['url'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$response = wp_remote_get(
			esc_url_raw( $url )
		);

		if ( is_wp_error( $response ) ) {
			wp_die();
		}

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
		} elseif ( in_array( $content_type, array( 'text/xml', 'application/xml', 'text/xml' ), true ) ) {
			$feeds[] = array(
				'format' => 'xml',
				'url'    => esc_url_raw( $url ),
			);
		} else {
			$mf2 = \FeedReader\Mf2\Parse( $body, $url );

			if ( empty( $mf2['rel-urls'] ) ) {
				return $feeds;
			}

			foreach ( $mf2['rel-urls'] as $rel => $info ) {
				if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) && isset( $info['type'] ) ) {
					if ( false !== strpos( $info['type'], 'application/feed+json' ) || false !== strpos( $info['type'], 'application/json' ) ) {
						$data = json_decode( $body );

						if ( ! empty( $data->version ) && false !== strpos( $data->version, 'https://jsonfeed.org/version/' ) ) {
							$feeds[] = array(
								'format' => 'json_feed',
								'url'    => esc_url_raw( $rel ),
							);
						}
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
		}

		header( 'Content-Type: application/json' );

		echo wp_json_encode(
			array(
				'title' => '',
				'feeds' => $feeds,
			)
		);
		wp_die();
	}
}
