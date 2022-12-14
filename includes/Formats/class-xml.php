<?php

namespace FeedReader\Formats;

use FeedReader\Jobs\Poll_Feeds;
use SimplePie;
use SimplePie_IRI;

class XML extends Format {
	public static function parse( $body, $feed ) {
		$items = array();

		try {
			$simplepie = new SimplePie();

			$simplepie->set_stupidly_fast( true ); // Bypass sanitization (and a few more things), which we'll tackle in a second.
			$simplepie->set_url_replacements( array() ); // Bypass relative URL resolution, handled in below.
			$simplepie->set_raw_data( $body );
			$simplepie->init();
			$simplepie->handle_content_type();

			foreach ( $simplepie->get_items() as $item ) {
				$items[] = static::parse_item( $item, $simplepie, $feed );
			}
		} catch ( \Exception $e ) {
			error_log( '[Reader] An error occurred parsing the feed at ' . esc_url_raw( $feed->url ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Bump to lowest tier.
			$poll_frequency = end( Poll_Feeds::$poll_frequencies );

			// Next check includes some randomness (up to +/- 1 hour).
			$now        = current_time( 'mysql', 1 );
			$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

			Feed::update(
				array(
					'last_error'     => $e->getMessage(),
					'last_polled'    => $now,
					'poll_frequency' => $poll_frequency,
					'next_check'     => date( 'Y-m-d H:i:s', $next_check ),
				),
				array( 'id' => $feed->id )
			);
		}

		return $items;
	}

	protected static function parse_item( $item, $simplepie, $feed ) {
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

			// @todo: Remove comments, script tags, and images without `src` attribute.
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
}
