<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class Mf2 extends Format {
	public static function parse( $body, $feed ) {
		// Look for cached mf2.
		$hash = hash( 'sha256', esc_url_raw( $feed->url ) );
		$data = wp_cache_get( "feed-reader:mf2:$hash" );

		if ( false === $data ) {
			$data = \FeedReader\Mf2\parse( $body, $feed->url );
			wp_cache_set( "feed-reader:mf2:$hash", $data, '', 3600 ); /** @todo: Use transients instead? */
		}

		if ( empty( $data['items'][0]['type'] ) || ! in_array( 'h-feed', $data['items'][0]['type'], true ) || empty( $data['items'][0]['children'] ) ) {
			/** @todo: Update `$feed` here rather than in the poll job? */
			return array();
		}

		$items = array();

		foreach ( $data['items'][0]['children'] as $item ) {
			$entry = static::parse_item( $item, $feed, $data );

			if ( ! empty( $entry ) ) {
				$items[] = $entry;
			}
		}

		return $items;
	}

	protected static function parse_item( $item, $feed, $data = null ) {
		if ( empty( $item['type'] ) || ! in_array( 'h-entry', (array) $item['type'], true ) ) { /** @todo: Expand to `h-review`, etc. */
			// We currently only offer support for `h-entry`.
			return null;
		}

		$entry = array();

		// Set `published`.
		$published = ! empty( $item['properties']['published'][0] ) ? $item['properties']['published'][0] : '';
		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 ); // Fall back to current date.
		}

		$entry['properties']['published'] = (array) $published;

		if ( ! empty( $item['properties']['url'] ) && filter_var( ( (array) $item['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
			$entry['properties']['url'] = (array) esc_url_raw( ( (array) $item['properties']['url'] )[0] );
		}

		// Set `uid`.
		if ( ! empty( $item['properties']['uid'] ) ) {
			$uid = $item['properties']['uid'];
		} else {
			$uid = ! empty( $entry['properties']['url'] )
				? '@' . ( (array) $entry['properties']['url'] )[0]
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['properties']['uid'] = (array) sanitize_text_field( $uid );

		// Set `content`.
		if ( ! empty( $item['properties']['content'][0]['html'] ) ) {
			$content = $item['properties']['content'][0]['html'];
		} elseif ( ! empty( $item['properties']['content'][0]['value'] ) ) {
			$content = $item['properties']['content'][0]['value'];
		} elseif ( ! empty( $item['properties']['content'][0]['text'] ) ) {
			$content = $item['properties']['content'][0]['text'];
		} elseif ( ! empty( $item['properties']['summary'] ) ) {
			$content = ( (array) $item['properties']['summary'] )[0]; // Fall back to summary.
		}

		if ( ! empty( $content ) ) {
			// Sanitize.
			$content = str_replace( '&mldr;', '&hellip;', $content );
			$content = wpautop( \FeedReader\Helpers\kses( $content ), false );

			if ( ! empty( $entry['properties']['url'] ) ) {
				// Resolve URLs.
				$content = static::absolutize_urls( $content, ( (array) $entry['properties']['url'] )[0] );
			}

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => wp_strip_all_tags( $content ),
				),
			);
		}

		// Set `summary`.
		if ( ! empty( $item['properties']['summary'] ) ) {
			$summary = wp_strip_all_tags( ( (array) $item['properties']['summary'] )[0] ); // If there's a summary, use it.
		} elseif ( ! empty( $content ) ) {
			$summary = wp_trim_words( $content, 30, ' [&hellip;]' ); // Else, generate one based on `$content`.
		}

		if ( ! empty( $summary ) ) {
			$entry['properties']['summary'] = (array) $summary;
		}

		// Set `name`.
		if ( ! empty( $item['properties']['name'] ) ) {
			$title = wp_strip_all_tags( ( (array) $item['properties']['name'] )[0] );
			$title = html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, \FeedReader\Helpers\detect_encoding( $title ) );
			$check = preg_replace( array( '~\s~', '~...$~', '~…$~' ), '', $title );

			if (
				! empty( $content ) &&
				0 === stripos( preg_replace( '~\s~', '', html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, \FeedReader\Helpers\detect_encoding( $content ) ) ), $check )
			) {
				// If the content starts with the title, treat the entry as a note.
				$title = '';
			}

			if ( $title !== $entry['properties']['url'][0] ) {
				$entry['properties']['name'] = (array) sanitize_text_field( $title );
			}
		}

		$base = ! empty( $entry['properties']['url'] )
			? esc_url_raw( ( (array) $entry['properties']['url'] )[0] )
			: $feed->url;

		if ( ! empty( $item['properties']['photo'] ) && is_array( $item['properties']['photo'] ) ) {
			$photos = array();

			foreach ( $item['properties']['photo'] as $photo ) {
				if ( is_string( $photo ) && filter_var( $photo, FILTER_VALIDATE_URL ) ) {
					$photos[] = esc_url_raw( (string) SimplePie_IRI::absolutize( $base, $photo ) );
				} elseif ( ! empty( $photo['value'] ) && filter_var( $photo['value'], FILTER_VALIDATE_URL ) ) {
					$photos[] = esc_url_raw( (string) SimplePie_IRI::absolutize( $base, $photo['value'] ) );
				}
			}

			if ( ! empty( $photos ) ) {
				$entry['properties']['photo'] = $photos;
			}
		}

		if ( ! empty( $item['properties']['video'] ) && is_array( $item['properties']['video'] ) ) {
			$videos = array();

			foreach ( $item['properties']['video'] as $video ) {
				if ( is_string( $video ) && filter_var( $video, FILTER_VALIDATE_URL ) ) {
					$videos[] = esc_url_raw( (string) SimplePie_IRI::absolutize( $base, $video ) );
				}
			}

			if ( ! empty( $videos ) ) {
				$entry['properties']['video'] = $videos;
			}
		}

		if ( ! empty( $item['properties']['category'] ) ) {
			$entry['properties']['category'] = array_map( 'sanitize_text_field', (array) $item['properties']['category'] );
		}

		$entry['properties']['author'] = array( static::get_author( $item, $data ) );

		return parent::parse_item( $entry, $feed );
	}

	protected static function get_author( $item, $data ) {
		$author = array();

		if ( ! empty( $item['properties']['author'][0] ) ) {
			if ( is_string( $item['properties']['author'][0] ) ) {
				$author['name'] = (array) sanitize_text_field( $item['properties']['author'][0] );

				return $author;
			}

			if ( ! empty( $item['properties']['author'][0]['properties']['name'] ) ) {
				$author['name'] = (array) sanitize_text_field( ( (array) $item['properties']['author'][0]['properties']['name'] )[0] );
			}

			if ( ! empty( $item['properties']['author'][0]['properties']['url'] ) && filter_var( ( (array) $item['properties']['author'][0]['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
				$author['url'] = (array) esc_url_raw( ( (array) $item['properties']['author'][0]['properties']['url'] )[0] );
			}

			if ( ! empty( $item['properties']['author'][0]['properties']['photo'][0]['value'] ) && filter_var( $item['properties']['author'][0]['properties']['photo'][0]['value'], FILTER_VALIDATE_URL ) ) {
				$author['photo'] = (array) esc_url_raw( $item['properties']['author'][0]['properties']['photo'][0]['value'] );
			}

			return $author;
		}

		if ( ! empty( $data['items'][0]['properties']['author'][0] ) ) {
			// Feed h-card.
			$card = $data['items'][0]['properties']['author'][0];

			if ( ! empty( $card['properties']['name'] ) ) {
				$author['name'] = (array) sanitize_text_field( ( (array) $card['properties']['name'] )[0] );
			}

			if ( ! empty( $card['properties']['url'] ) && filter_var( ( (array) $card['properties']['url'] )[0], FILTER_VALIDATE_URL ) ) {
				$author['url'] = (array) esc_url_raw( ( (array) $card['properties']['url'] )[0] );
			}

			if ( ! empty( $card['properties']['photo'][0]['value'] ) && filter_var( $card['properties']['photo'][0]['value'], FILTER_VALIDATE_URL ) ) {
				$author['photo'] = (array) esc_url_raw( $card['properties']['photo'][0]['value'] );
			}

			return $author;
		}

		return null;
	}
}
