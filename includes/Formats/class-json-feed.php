<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class JSON_Feed extends Format {
	public static function parse( $body, $feed ) {
		$data = json_decode( $body );

		if ( empty( $data->items ) ) {
			/** @todo: Update `$feed` here rather than in the poll job? */
			return array();
		}

		$items = array();

		foreach ( $data->items as $item ) {
			$entry = static::parse_item( $item, $feed, $data );

			if ( ! empty( $entry ) ) {
				$items[] = $entry;
			}
		}

		return $items;
	}

	protected static function parse_item( $item, $feed, $data = null ) {
		$entry = array();

		// Sanitize publication date.
		$published = ! empty( $item->date_published ) ? $item->date_published : '';

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['properties']['published'] = (array) $published;

		if ( ! empty( $item->url ) && filter_var( $item->url, FILTER_VALIDATE_URL ) ) {
			$entry['properties']['url'] = (array) esc_url_raw( $item->url );
		}

		// Ensure an ID is set.
		if ( ! empty( $item->id ) ) {
			$uid = $item->id;
		} else {
			$uid = ! empty( $entry['properties']['url'] )
				? '@' . ( (array) $entry['properties']['url'] )[0]
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['properties']['uid'] = (array) sanitize_text_field( $uid );

		// Set `content`.
		if ( ! empty( $item->content_html ) ) {
			$content = $item->content_html;
		} elseif ( ! empty( $item->content_text ) ) {
			$content = $item->content_text;
		} elseif ( ! empty( $item->summary ) ) {
			$content = $item->summary;
		}

		if ( ! empty( $content ) ) {
			$content = str_replace( '&mldr;', '&hellip;', $content );
			$content = wpautop( \FeedReader\Helpers\kses( $content ), false );

			if ( ! empty( $entry['properties']['url'] ) ) {
				$content = static::absolutize_urls( $content, ( (array) $entry['properties']['url'] )[0] );
			}

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => preg_replace( '~\R\R+~', PHP_EOL . PHP_EOL, wp_strip_all_tags( $content ) ),
				),
			);
		}

		if ( ! empty( $item->summary ) ) {
			$summary = preg_replace( '~\R\R+~', PHP_EOL . PHP_EOL, wp_strip_all_tags( $item->summary ) ); // If there's a summary, use it.
		} elseif ( ! empty( $content ) ) {
			$summary = wp_trim_words( $content, 30, ' [&hellip;]' ); // Else, generate one based on `$content`.
		}

		if ( ! empty( $summary ) ) {
			$entry['properties']['summary'] = (array) $summary;
		}

		if ( ! empty( $item->title ) ) {
			$title = wp_strip_all_tags( $item->title );
			$title = html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, \FeedReader\Helpers\detect_encoding( $title ) );
			$check = preg_replace( array( '~\s~', '~...$~', '~…$~' ), '', $title );

			if (
				! empty( $content ) &&
				0 === stripos( preg_replace( '~\s~', '', html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, \FeedReader\Helpers\detect_encoding( $content ) ) ), $check )
			) {
				// We'll delete this afterward.
				$entry['properties']['original_name'] = (array) sanitize_text_field( $title );
			} elseif ( $title !== $entry['properties']['url'][0] ) {
				// Only store a title if it both doesn't meet the criteria above
				// and is not the item's URL.
				$entry['properties']['name'] = (array) sanitize_text_field( $title );
			}
		}

		$base = ! empty( $entry['properties']['url'] )
			? esc_url_raw( ( (array) $entry['properties']['url'] )[0] )
			: $feed->url;

		if ( ! empty( $item->image ) ) {
			$entry['properties']['photo'] = (array) esc_url_raw( (string) SimplePie_IRI::absolutize( $base, $item->image ) );
		}

		if ( ! empty( $item->tags ) ) {
			$entry['properties']['category'] = array_map( 'sanitize_text_field', (array) $item->tags );
		}

		$author = array();

		if ( ! empty( $item->author->name ) ) {
			$author['name'] = (array) sanitize_text_field( $item->author->name );
		} elseif ( ! empty( $data->title ) ) {
			$author['name'] = (array) sanitize_text_field( $data->title );
		}

		if ( ! empty( $item->author->url ) && filter_var( $item->author->url, FILTER_VALIDATE_URL ) ) {
			$author['url'] = (array) esc_url_raw( $item->author->url );
		} elseif ( ! empty( $data->home_page_url ) && filter_var( $data->home_page_url, FILTER_VALIDATE_URL ) ) {
			$author['url'] = (array) esc_url_raw( $data->home_page_url );
		}

		if ( ! empty( $item->author->avatar ) && filter_var( $item->author->avatar, FILTER_VALIDATE_URL ) ) {
			$author['photo'] = (array) esc_url_raw( $item->author->avatar );
		} elseif ( ! empty( $data->icon ) && filter_var( $data->icon, FILTER_VALIDATE_URL ) ) {
			$author['photo'] = esc_url_raw( $data->icon );
		}

		$entry['properties']['author'] = array( $author );

		return parent::parse_item( $entry, $feed );
	}
}
