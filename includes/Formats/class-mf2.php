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
			$entry = static::parse_item( $item, $feed, $data );

			if ( ! empty( $entry ) ) {
				$items[] = $entry;
			}
		}

		return $items;
	}

	protected static function parse_item( $item, $feed, $data = null ) {
		if ( ! empty( $item['type'] ) && in_array( 'h-entry', (array) $item['type'], true ) ) {
			// We currently only offer support for `h-entry` (and do not store
			// `in-reply-to` and similar properties).
			$entry = static::parse_as_hentry( $item, $feed, $data );
		} else {
			return null;
		}

		return parent::parse_item( $entry, $feed );
	}

	protected static function parse_as_hentry( $item, $feed = null, $data = null ) {
		// Sanitize publication date.
		$published = ! empty( $item['properties']['published'][0] ) ? $item['properties']['published'][0] : '';

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['properties']['published'][0] = $published;

		if ( ! empty( $item['properties']['url'] ) && filter_var( ( (array) $item['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
			$entry['properties']['url'] = (array) esc_url_raw( ( (array) $item['properties']['url'] )[0] );
		}

		// Ensure an ID is set.
		if ( ! empty( $item['properties']['uid'] ) ) {
			$uid = sanitize_text_field( $item['properties']['uid'] );
		} else {
			$uid = ! empty( $entry['properties']['url'] )
				? '@' . ( (array) $entry['properties']['url'] )[0]
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['properties']['uid'] = (array) $uid;

		if ( ! empty( $item['properties']['name'] ) ) {
			$entry['properties']['name'] = (array) sanitize_text_field( ( (array) $item['properties']['name'] )[0] );
		}

		if ( ! empty( $item['properties']['content'][0]['html'] ) ) {
			$content = $item['properties']['content'][0]['html'];

			$content = preg_replace( '~<!--.*?-->~s', '', $content );
			$content = preg_replace( '~<style.*?>.*?</style>~s', '', $content );

			if ( ! empty( $entry['properties']['url'] ) ) {
				$content = static::absolutize_urls( $content, ( (array) $entry['properties']['url'] )[0] );
			}

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => ! empty( $item['properties']['content'][0]['value'] )
						? wp_strip_all_tags( $item['properties']['content'][0]['value'] )
						: wp_strip_all_tags( $content ),
				),
			);
		} elseif ( ! empty( $item['properties']['content'][0]['value'] ) ) {
			$entry['properties']['content'] = array(
				array( 'text' => wp_strip_all_tags( $item['properties']['content'][0]['value'] ) ),
			);
		}

		if ( ! empty( $item['properties']['summary'] ) ) {
			$summary = ( (array) $item['properties']['summary'] )[0];
		} elseif ( ! empty( $entry['properties']['content'][0]['text'] ) ) {
			$summary = $entry['properties']['content'][0]['text'];
		}

		if ( ! empty( $summary ) ) {
			$entry['properties']['summary'] = (array) wp_trim_words( wp_strip_all_tags( $summary ), 30, ' [&hellip;]' );
		}

		$base = ! empty( $entry['properties']['url'] )
			? esc_url_raw( ( (array) $entry['properties']['url'] )[0] )
			: $feed->url;

		if ( ! empty( $item['properties']['photo'][0]['value'] ) ) {
			$entry['photo'] = (array) esc_url_raw( (string) SimplePie_IRI::absolutize( $item['properties']['photo'][0]['value'], $base ) );
		}

		if ( ! empty( $item['properties']['category'] ) ) {
			$entry['category'] = array_map( 'wp_strip_all_tags', (array) $item['properties']['category'] );
		}

		$entry['properties']['author'] = array( static::get_author( $item, $data ) );

		return $entry;
	}

	protected static function get_author( $item, $data ) {
		$author = array();

		if ( ! empty( $item['author'][0] ) ) {
			if ( is_string( $item['author'][0] ) ) {
				$author['name'] = (array) sanitize_text_field( $item['author'][0] );

				return $author;
			}

			if ( ! empty( $item['author'][0]['properties']['url'] ) && filter_var( ( (array) $item['author'][0]['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
				$author['url'] = (array) esc_url_raw( ( (array) $item['author'][0]['properties']['url'] )[0] );
			}

			if ( ! empty( $item['author'][0]['properties']['name'] ) ) {
				$author['name'] = (array) sanitize_text_field( ( (array) $item['author'][0]['properties']['name'] )[0] );
			}

			if ( ! empty( $item['author'][0]['properties']['photo'][0]['value'] ) && filter_var( $item['author'][0]['properties']['photo'][0]['value'], FILTER_VALIDATE_URL ) ) {
				$author['photo'] = (array) esc_url_raw( $item['author'][0]['properties']['photo'][0]['value'] );
			}
		}

		if ( ! empty( $data['items'][0]['properties']['author'][0] ) ) {
			// Feed h-card.
			$card = $data['items'][0]['properties']['author'][0];

			if ( ! empty( $card['properties']['url'] ) && filter_var( ( (array) $card['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
				$author['url'] = (array) esc_url_raw( ( (array) $card['properties']['url'] )[0] );
			}

			if ( ! empty( $card['properties']['name'] ) ) {
				$author['name'] = (array) sanitize_text_field( ( (array) $card['properties']['name'] )[0] );
			}

			if ( ! empty( $card['properties']['photo'][0]['value'] ) && filter_var( $card['properties']['photo'][0]['value'], FILTER_VALIDATE_URL ) ) {
				$author['photo'] = (array) esc_url_raw( $card['properties']['photo'][0]['value'] );
			}

			return $author;
		}

		return null;
	}
}
