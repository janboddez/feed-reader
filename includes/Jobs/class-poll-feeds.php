<?php

namespace FeedReader\Jobs;

use FeedReader\Models\Entry;
use FeedReader\Models\Feed;
use FeedReader\zz\Html\HTMLMinify;
use SimplePie_IRI;

class Poll_Feeds {
	/** @var array $poll_frequencies */
	public static $poll_frequencies = array( 2, 6, 18, 48 );

	public static function poll_feeds() {
		global $wpdb;

		// Select feeds that are newly added or past due.
		$sql = sprintf( 'SELECT * FROM %s WHERE last_polled IS NULL OR next_check <= %%s ORDER BY RAND() LIMIT 10', Feed::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$feeds = $wpdb->get_results( $wpdb->prepare( $sql, current_time( 'mysql', 1 ) ) );

		if ( ! empty( $feeds ) ) {
			foreach ( $feeds as $feed ) {
				static::poll_feed( $feed );
			}
		} else {
			error_log( '[Reader] No feeds due.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	public static function poll_feed( $feed ) {
		if ( empty( $feed->url ) || ! wp_http_validate_url( $feed->url ) ) {
			error_log( '[Reader] Oops. Could it be the feed at ' . esc_url_raw( $feed->url ) . ' is invalid?' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( filter_var( $feed->url, FILTER_VALIDATE_URL ) ) {
				// For diagnostics.
				error_log( "[Reader] `filter_var()` seems to think it's OK." ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return;
		}

		$now = current_time( 'mysql', 1 );

		$simplepie = fetch_feed( $feed->url ); // Caches feeds for 12 hours.

		if ( is_wp_error( $simplepie ) ) {
			error_log( '[Reader] An error occurred polling the feed at ' . esc_url_raw( $feed->url ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			$poll_frequency = end( static::$poll_frequencies );

			// Next check includes some randomness (up to +/- 1 hour).
			$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

			Feed::update(
				array(
					'last_error'     => $simplepie->get_error_message(),
					'last_polled'    => $now,
					'poll_frequency' => $poll_frequency,
					'next_check'     => date( 'Y-m-d H:i:s', $next_check ),
				),
				array( 'id' => $feed->id )
			);

			return;
		}

		// Good chance these don't actually do anything anymore, at this point.
		$simplepie->set_stupidly_fast( true ); // Disable sanitization, which screws up HTML comments and style tags, and we'll tackle separately.
		$simplepie->enable_cache( true ); // Re-enable cache?

		$new_items = false;

		$items = $simplepie->get_items();

		if ( empty( $items ) ) {
			error_log( '[Reader] The feed at ' . esc_url_raw( $feed->url ) . ' came up empty.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		foreach ( $items as $item ) {
			$entry  = static::parse_item( $item, $simplepie, $feed );
			$exists = Entry::exists( $entry['uid'], $feed );

			if ( ! $exists ) {
				$new_items = true;

				Entry::insert(
					array_filter(
						array(
							'uid'       => $entry['uid'],
							'published' => $entry['published'],
							'url'       => ! empty( $entry['url'] ) ? $entry['url'] : null,
							'name'      => ! empty( $entry['name'] ) ? $entry['name'] : null,
							'author'    => ! empty( $entry['author']['name'] ) ? $entry['author']['name'] : null,
							'content'   => ! empty( $entry['content']['html'] ) ? $entry['content']['html'] : null,
							'summary'   => ! empty( $entry['summary'] ) ? $entry['summary'] : null,
							'is_read'   => is_null( $feed->last_polled ) ? 1 : 0, // Mark newly added feeds as read.
							'feed_id'   => $feed->id,
							'user_id'   => $feed->user_id,
							'data'      => wp_json_encode( $entry ),
						)
					)
				);

				// @todo: Store `$entry` as JSON, for (eventual) use with Microsub readers.
			}

			$poll_frequency = end( static::$poll_frequencies );

			if ( $new_items ) {
				$empty_poll_count = 0;
				$poll_frequency   = reset( static::$poll_frequencies );
			} else {
				$empty_poll_count = $feed->empty_poll_count + 1;

				if ( $empty_poll_count > 3 ) {
					$empty_poll_count = 3; // Limit to 3.

					$key = array_search( $feed->poll_frequency, static::$poll_frequencies, true );

					if ( array_key_exists( $key + 1, static::$poll_frequencies ) ) {
						$poll_frequency = static::$poll_frequencies[ $key + 1 ];
					}
				}
			}

			// Next check includes some randomness (up to +/- 1 hour).
			$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

			Feed::update(
				array(
					'last_error'       => null,
					'last_polled'      => $now,
					'poll_frequency'   => $poll_frequency,
					'empty_poll_count' => $empty_poll_count,
					'next_check'       => date( 'Y-m-d H:i:s', $next_check ),
				),
				array( 'id' => $feed->id )
			);
		}
	}

	public static function parse_item( $item, $simplepie, $feed ) {
		$entry = array();

		$published = $item->get_gmdate( 'c' );
		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}
		$entry['published'] = $published;

		$base = $simplepie->get_link() ?: $feed->url;
		if ( 0 !== strpos( $base, 'http' ) || 0 !== strpos( $base, '//' ) ) {
			$base = (string) SimplePie_IRI::absolutize( $feed->url, $base );
		}
		$base = (string) SimplePie_IRI::absolutize( $base, './' ); // Converts, e.g., `http://example.org/blog/feed.xml` to `http://example.org/blog/`. I think.

		// We'll want to use original URLs as the base to absolutize asset URLs
		// in FeedBurner posts. Or something.
		if ( ! empty( $item->data['child']['http://rssnamespace.org/feedburner/ext/1.0']['origLink'][0]['data'] ) ) {
			$orig = $item->data['child']['http://rssnamespace.org/feedburner/ext/1.0']['origLink'][0]['data'];
		}
		$url          = ! empty( $orig ) ? $orig : $item->get_link();
		$url          = ! empty( $url ) ? (string) SimplePie_IRI::absolutize( $base, $url ) : '';
		$entry['url'] = esc_url_raw( $url );

		$uid = $item->get_id();
		if ( empty( $uid ) ) {
			$uid = ! empty( $entry['url'] )
				? '@' . $entry['url']
				: '#' . md5( wp_json_encode( $item ) );
		}
		$entry['uid'] = $uid;

		$content = $item->get_content();

		if ( ! empty( $content ) ) {
			// @todo: Move to HTMLPurifier?
			$content = preg_replace( '~<!--.*?-->~s', '', $content );
			$content = preg_replace( '~<style.*?>.*?</style>~s', '', $content );

			if ( ! empty( $entry['url'] ) ) {
				$content = static::absolutize_urls( $content, $entry['url'] );
			}

			// Strip unnecessary whitespace. Slowish, but improves `wpautop()`
			// results.
			$content = HTMLMinify::minify( $content );
			$content = wpautop( \FeedReader\kses( $content ) );

			$entry['content'] = array(
				'html' => $content,
				'text' => wp_strip_all_tags( $content ),
			);

			/* @todo: Look for an actual summary first. */
			$entry['summary'] = wp_trim_words( $entry['content']['html'], 25, ' [&hellip;]' ); // 55 seemed too long.
		}

		$title = $item->get_title();
		if ( $title !== $entry['url'] ) {
			$entry['name'] = sanitize_text_field( $title );
		}

		$author = $item->get_author();
		if ( ! empty( $author ) ) {
			$entry['author']['name'] = sanitize_text_field( $author->get_name() );
			$entry['author']['url']  = esc_url_raw( $author->get_link() ?: $simplepie->get_link() );
		} else {
			$entry['author']['name'] = sanitize_text_field( $simplepie->get_title() );
			$entry['author']['url']  = esc_url_raw( $simplepie->get_link() );
		}
		$entry['author'] = array_filter( $entry['author'] );

		$entry = array_filter( $entry ); // Remove empty values.

		return $entry;
	}

	public static function absolutize_urls( $html, $base ) {
		// There must (!) be a root-level element at all times. This'll get
		// stripped out during sanitization.
		$html = '<div>' . mb_convert_encoding( $html, 'HTML-ENTITIES', mb_detect_encoding( $html ) ) . '</div>';

		libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$xpath = new \DOMXPath( $doc );

		// @todo: Currently leaves `srcset` untouched; we should fix that.
		foreach ( $xpath->query( '//*[@src or @href]' ) as $node ) {
			if ( $node->hasAttribute( 'href' ) && 0 !== strpos( $node->getAttribute( 'href' ), 'http' ) ) { // Ran into an issue here where `href="http://"`, so not a valid, nor a relative URL. Need to fix this properly.
				$node->setAttribute( 'href', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'href' ) ) );
			}

			if ( $node->hasAttribute( 'src' ) && 0 !== strpos( $node->getAttribute( 'src' ), 'http' ) ) {
				$node->setAttribute( 'src', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'src' ) ) );
			}
		}

		$html = $doc->saveHTML();
		$html = str_replace( '</source>', '', $html ); // Work around https://bugs.php.net/bug.php?id=73175.

		return $html;
	}
}
