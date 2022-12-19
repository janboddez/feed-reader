<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class Mf2 extends Format {
	public static function parse( $body, $feed ) {
		$items = array();

		// Look for cached mf2.
		$hash = hash( 'sha256', esc_url_raw( $feed->url ) );
		$data = wp_cache_get( "feed-reader:mf2:$hash" );

		if ( false === $data ) {
			$data = \FeedReader\Mf2\parse( $body, $feed->url );
		}

		if ( empty( $data['items'][0]['type'] ) || ! in_array( 'h-feed', $data['items'][0]['type'], true ) || empty( $data['items'][0]['children'] ) ) {
			/** @todo: Update `$feed` here rather than in the poll job? */
			return $items;
		}

		foreach ( $data['items'][0]['children'] as $item ) {
			$items[] = static::parse_item( $item, $data, $feed );
		}

		return $items;
	}

	protected static function parse_item( $item, $data, $feed ) {
		// Really only parses `h-entry`. Also, we should really just store the
		// mf2 array, and map the other formats to the mf2 array format.
		$entry = array();

		$published = ! empty( $item['properties']['published'][0] ) ? $item['properties']['published'][0] : '';

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['published'] = $published;

		$entry['url'] = ! empty( $item['properties']['url'] ) ? ( (array) $item['properties']['url'] )[0] : null;

		/** @todo: Look for an explicit UID first. */
		$entry['uid'] = ! empty( $entry['url'] )
			? '@' . $entry['url']
			: '#' . md5( wp_json_encode( $item ) );

		$entry['name'] = ! empty( $item['properties']['name'] )
			? sanitize_text_field( ( (array) $item['properties']['name'] )[0] )
			: null;

		if ( ! empty( $item['properties']['content'][0]['html'] ) ) {
			$content = $item['properties']['content'][0]['html'];

			// @todo: Remove comments, script tags, and images without `src` attribute.
			$content = wpautop( \FeedReader\kses( $content ) );

			$entry['content']['html'] = $content;
			$entry['content']['text'] = ! empty( $item['properties']['content'][0]['value'] )
				? wp_strip_all_tags( $item['properties']['content'][0]['value'] )
				: wp_strip_all_tags( $content );
		} elseif ( ! empty( $item['properties']['content'][0]['value'] ) ) {
			$entry['content']['text'] = wp_strip_all_tags( $item['properties']['content'][0]['value'] );
		}

		$entry['summary'] = ! empty( $item['properties']['summary'] )
			? \FeedReader\kses( ( (array) $item['properties']['summary'] )[0] )
			: null;

		$base = ! empty( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : $feed->url;

		$entry['photo'] = ! empty( $item['properties']['photo'] )
			? esc_url_raw( (string) SimplePie_IRI::absolutize( ( (array) $item['properties']['photo'] )[0], $base ) )
			: null;

		$entry['category'] = ! empty( $item['properties']['category'] )
			? array_map( 'wp_strip_all_tags', (array) $item['properties']['category'] )
			: null;

		$entry['author'] = static::get_author( $item, $data );
		$entry           = array_filter( $entry );

		return $entry;
	}

	public static function get_author( $item, $data ) {
		$author = array();

		if ( ! empty( $item['author'][0] ) ) {
			// if ( filter_var( $item['author'][0], FILTER_VALIDATE_URL ) ) {
			// 	$author['name'] = sanitize_text_field( $item['author'][0] );
			// 	return $author;
			// }

			if ( is_string( $item['author'][0] ) ) {
				$author['name'] = sanitize_text_field( $item['author'][0] );
				return $author;
			}

			$author['url'] = ! empty( $item['author'][0]['properties']['url'] ) && filter_var( ( (array) $item['author'][0]['properties']['url'] )[0], FILTER_VALIDATE_URL )
				? esc_url_raw( ( (array) $item['author'][0]['properties']['url'] )[0] )
				: null;

			$author['name'] = ! empty( $item['author'][0]['properties']['name'] )
				? sanitize_text_field( ( (array) $item['author'][0]['properties']['name'] )[0] )
				: null;

			$author['photo'] = ! empty( $item['author'][0]['properties']['photo'][0]['value'] ) && filter_var( $item['author'][0]['properties']['photo'][0]['value'], FILTER_VALIDATE_URL )
				? esc_url_raw( $item['author'][0]['properties']['photo'][0]['value'] )
				: null;

			return array_filter( $author );
		}

		if ( ! empty( $data['items'][0]['properties']['author'][0] ) ) {
			// Feed h-card.
			$card = $data['items'][0]['properties']['author'][0];

			$author['url'] = ! empty( $card['properties']['url'] ) && filter_var( ( (array) $card['properties']['url'] )[0], FILTER_VALIDATE_URL )
				? esc_url_raw( ( (array) $card['properties']['url'] )[0] )
				: null;

			$author['name'] = ! empty( $card['properties']['name'] )
				? sanitize_text_field( ( (array) $card['properties']['name'] )[0] )
				: null;

			$author['photo'] = ! empty( $card['properties']['photo'][0]['value'] ) && filter_var( $card['properties']['photo'][0]['value'], FILTER_VALIDATE_URL )
				? esc_url_raw( $card['photo'][0]['value'] )
				: null;

			return array_filter( $author );
		}

		return null;
	}
}
