<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class JSON_Feed extends Format {
	public static function parse( $body, $feed ) {
		$items = array();
		$data  = json_decode( $body );

		foreach ( $data->items as $item ) {
			$items[] = static::parse_item( $item, $data, $feed );
		}

		return $items;
	}

	protected static function parse_item( $item, $data, $feed ) {
		$entry = array();

		$published = ! empty( $item->date_published ) ? $item->date_published : '';

		if ( in_array( strtotime( $published ), array( false, 0 ), true ) || strtotime( $published ) > time() ) {
			$published = current_time( 'mysql', 1 );
		}

		$entry['published'] = $published;

		$entry['url'] = ! empty( $item->url ) ? $item->url : null;

		if ( ! empty( $item->id ) ) {
			$uid = $item->id;
		} else {
			$uid = ! empty( $entry['url'] )
				? '@' . $entry['url']
				: '#' . md5( wp_json_encode( $item ) );
		}

		$entry['uid']       = $uid;
		$entry['published'] = ! empty( $item->date_published ) ? $item->date_published : null;
		$entry['updated']   = ! empty( $item->date_modified ) ? $item->date_modified : null;
		$entry['name']      = ! empty( $item->title ) ? sanitize_text_field( $item->title ) : null;

		if ( ! empty( $item->content_html ) ) {
			$content = $item->content_html;

			// @todo: Remove comments, script tags, and images without `src` attribute.
			$content = wpautop( \FeedReader\kses( $content ) );

			$entry['content']['html'] = $content;
			$entry['content']['text'] = ! empty( $item->content_text )
				? wp_strip_all_tags( $item->content_text )
				: wp_strip_all_tags( $content );
		} elseif ( ! empty( $item->content_text ) ) {
			$entry['content']['text'] = wp_strip_all_tags( $item->content_text );
		}

		$entry['summary'] = ! empty( $item->summary ) ? \FeedReader\kses( $item->summary ) : null;

		$base = ! empty( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : $feed->url;

		$entry['photo']    = ! empty( $item->image ) ? esc_url_raw( (string) SimplePie_IRI::absolutize( $item->image, $base ) ) : null;
		$entry['category'] = ! empty( $item->tags ) ? array_map( 'wp_strip_all_tags', (array) $item->tags ) : null;

		$author = array();

		$author['url'] = ! empty( $item->author->url )
			? esc_url_raw( $item->author->url )
			: ( ! empty( $data->home_page_url ) ? esc_url_raw( $data->home_page_url ) : null );

		$author['name'] = ! empty( $item->author->name )
			? sanitize_text_field( $item->author->name )
			: ( ! empty( $data->title ) ? sanitize_text_field( $data->title ) : null );

		$author['photo'] = ! empty( $item->author->avatar )
			? esc_url_raw( $item->author->avatar )
			: ( ! empty( $data->icon ) ? esc_url_raw( $data->icon ) : null );

		$entry['author'] = array_filter( $author );
		$entry           = array_filter( $entry );

		return $entry;
	}
}
