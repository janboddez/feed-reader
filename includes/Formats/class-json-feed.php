<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class JSON_Feed extends Format {
	public static function parse( $body, $feed ) {
		$items = array();
		$data  = json_decode( $body );

		if ( empty( $data->items ) ) {
			/** @todo: Update `$feed` here rather than in the poll job? */
			return $items;
		}

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

		$published = ! empty( $item->date_published ) ? $item->date_published : '';

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['properties']['published'] = (array) $published;

		if ( ! empty( $item->url ) && filter_var( $item->url, FILTER_VALIDATE_URL ) ) {
			$entry['properties']['url'] = (array) esc_url_raw( $item->url );
		}

		if ( ! empty( $item->id ) ) {
			$uid = $item->id;
		} else {
			$uid = ! empty( $entry['properties']['url'] )
				? '@' . ( (array) $entry['properties']['url'] )[0]
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['properties']['uid'] = $uid;

		if ( ! empty( $item->title ) ) {
			$entry['properties']['name'] = (array) sanitize_text_field( $item->title );
		}

		if ( ! empty( $item->content_html ) ) {
			$content = $item->content_html;

			// @todo: Remove comments, script tags, and images without `src` attribute.
			$content = wpautop( \FeedReader\kses( $content ) );

			$entry['properties']['content'] = array(
				array(
					'html' => $content,
					'text' => ! empty( $item->content_text )
						? wp_strip_all_tags( $item->content_text )
						: wp_strip_all_tags( $content ),
				),
			);
		} elseif ( ! empty( $item->content_text ) ) {
			$entry['properties']['content'] = array(
				array( 'text' => wp_strip_all_tags( $item->content_text ) ),
			);
		}

		if ( ! empty( $item->summary ) ) {
			$entry['properties']['summary'] = (array) \FeedReader\kses( $item->summary );
		}

		/** @todo: Autogenerate (shorter) summaries? */

		$base = ! empty( $entry['properties']['url'] )
			? esc_url_raw( ( (array) $entry['properties']['url'] )[0] )
			: $feed->url;

		if ( ! empty( $item->image ) ) {
			$entry['properties']['photo'] = esc_url_raw( (string) SimplePie_IRI::absolutize( $item->image, $base ) );
		}

		if ( ! empty( $item->tags ) ) {
			$entry['properties']['category'] = array_map( 'wp_strip_all_tags', (array) $item->tags );
		}

		$author = array();

		if ( ! empty( $item->author->url ) && filter_var( $item->author->url, FILTER_VALIDATE_URL ) ) {
			$author['url'] = (array) esc_url_raw( $item->author->url );
		} elseif ( ! empty( $data->home_page_url ) && filter_var( $data->home_page_url, FILTER_VALIDATE_URL ) ) {
			$author['url'] = (array) esc_url_raw( $data->home_page_url );
		}

		if ( ! empty( $item->author->name ) ) {
			$author['name'] = (array) sanitize_text_field( $item->author->name );
		} elseif ( ! empty( $data->title ) ) {
			$author['name'] = (array) sanitize_text_field( $data->title );
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
