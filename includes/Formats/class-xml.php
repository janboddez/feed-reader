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
			$simplepie->set_url_replacements( array() ); // Bypass relative URL resolution, handled below.
			$simplepie->set_raw_data( $body );
			$simplepie->init();
			$simplepie->handle_content_type();

			foreach ( $simplepie->get_items() as $item ) {
				$entry = static::parse_item( $item, $feed, $simplepie );

				if ( ! empty( $entry ) ) {
					$items[] = $entry;
				}
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

	/**
	 * Turns a SimplePie item into an Mf2 JSON array.
	 *
	 * @param  mixed  $item      Item being parsed.
	 * @param  StdObj $feed      Feed Feed the item belongs to.
	 * @param  mixed  $simplepie SimplePie object.
	 * @return array             Mf2 JSON representation of the item.
	 */
	protected static function parse_item( $item, $feed, $simplepie = null ) {
		$entry = array();

		$published = $item->get_gmdate( 'c' );

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['properties']['published'] = (array) $published;

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
		$url = ! empty( $orig ) ? $orig : $item->get_link();
		$url = ! empty( $url ) ? (string) SimplePie_IRI::absolutize( $base, $url ) : '';

		$entry['properties']['url'] = (array) esc_url_raw( $url );

		$uid = $item->get_id();

		if ( empty( $uid ) ) {
			$uid = ! empty( $entry['url'] )
				? '@' . $entry['url']
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['properties']['uid'] = (array) $uid;

		$content = $item->get_content();

		if ( ! empty( $content ) ) {
			$content = preg_replace( '~<!--.*?-->~s', '', $content );
			$content = preg_replace( '~<style.*?>.*?</style>~s', '', $content );

			if ( ! empty( $entry['url'] ) ) {
				$content = static::absolutize_urls( $content, $entry['url'] );
			}

			// @todo: Remove comments, script tags, and images without `src` attribute.
			$content = wpautop( \FeedReader\kses( $content ) );

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => wp_strip_all_tags( $content ),
				),
			);

			/* @todo: Look for an actual summary first. */
			$entry['properties']['summary'] = (array) wp_trim_words( $entry['properties']['content'][0]['html'], 20, ' [&hellip;]' );
		}

		$title = $item->get_title();
		if ( $title !== $entry['properties']['url'][0] ) {
			$entry['properties']['name'] = (array) sanitize_text_field( $title );
		}

		$author = $item->get_author();

		if ( ! empty( $author ) ) {
			$author_name = $author->get_name() ?: $feed->name;
			$author_url  = $author->get_link() ?: ( $simplepie->get_link() ?: $feed->url );
		} else {
			$author_name = $simplepie->get_title() ?: $feed->name;
			$author_url  = $simplepie->get_link() ?: $feed->url;
		}

		// Pfff.
		$author = array();

		if ( ! empty( $author_name ) ) {
			$author['name'] = (array) sanitize_text_field( $author_name );
		}

		if ( ! empty( $author_url ) ) {
			$author['url'] = (array) esc_url_raw( $author_url );
		}

		if ( ! empty( $author ) ) {
			$entry['properties']['author'] = array( $author );
		}

		// Convert to array that can be inserted directly into the database.
		return parent::parse_item( $entry, $feed );
	}
}
