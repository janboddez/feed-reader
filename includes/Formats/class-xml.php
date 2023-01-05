<?php

namespace FeedReader\Formats;

use SimplePie;
use SimplePie_IRI;

class XML extends Format {
	public static function parse( $body, $feed ) {
		$items = array();

		try {
			$simplepie = new SimplePie();

			$simplepie->set_stupidly_fast( true ); // Bypass sanitization (and a few more things), which we'll tackle in a second.
			$simplepie->set_url_replacements( array() ); // Bypass relative URL resolution, handled below.
			$simplepie->set_raw_data( trim( $body ) );
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
			$content = wpautop( \FeedReader\Helpers\kses( $content ), false );

			if ( ! empty( $entry['properties']['url'] ) ) {
				$content = static::absolutize_urls( $content, ( (array) $entry['properties']['url'] )[0] );
			}

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => wp_strip_all_tags( $content ),
				),
			);

			$summary = $item->get_description( true );

			if ( empty( $summary ) && ! empty( $entry['properties']['content'][0]['text'] ) ) {
				$summary = $entry['properties']['content'][0]['text'];
			}

			if ( ! empty( $summary ) ) {
				$entry['properties']['summary'] = (array) wp_trim_words( wp_strip_all_tags( $summary ), 30, ' [&hellip;]' );
			}
		}

		$title = $item->get_title();

		if ( ! empty( $title ) ) {
			$title = wp_strip_all_tags( $title );
			$title = html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, mb_detect_encoding( $title ) ); // To be escaped on output!
			$check = preg_replace( array( '~\s~', '~...$~', '~…$~' ), '', $title );

			if (
				! empty( $content ) &&
				0 === stripos( preg_replace( '~\s~', '', html_entity_decode( wp_strip_all_tags( $content ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, mb_detect_encoding( $content ) ), $check )
			) {
				// If the content starts with the title, treat the entry as a note.
				$title = '';
			}

			if ( $title !== $entry['properties']['url'][0] ) {
				$entry['properties']['name'] = (array) sanitize_text_field( $title );
			}
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
