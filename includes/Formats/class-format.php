<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class Format {
	/**
	 * Turns an mf2 array into an entry we can save to our database.
	 *
	 * @param  mixed  $item   Item being parsed.
	 * @param  StdObj $feed   Feed Feed the item belongs to.
	 * @param  mixed  $source Item source.
	 * @return array          Entry, ready to be inserted into the database.
	 */
	protected static function parse_item( $item, $feed, $source = null ) {
		$entry = array(
			'uid'       => $item['properties']['uid'][0],
			'published' => $item['properties']['published'][0],
			'url'       => ! empty( $item['properties']['url'][0] ) ? $item['properties']['url'][0] : null,
			'name'      => ! empty( $item['properties']['name'][0] ) ? $item['properties']['name'][0] : null,
			'author'    => ! empty( $item['properties']['author'][0]['name'][0] ) ? $item['properties']['author'][0]['name'][0] : null,
			'content'   => ! empty( $item['properties']['content'][0]['html'] )
				? $item['properties']['content'][0]['html']
				: null,
			'summary'   => ! empty( $item['properties']['summary'][0] ) ? $item['properties']['summary'][0] : null,
			'is_read'   => is_null( $feed->last_polled ) ? 1 : 0, // Mark newly added feeds as read.
			'feed_id'   => $feed->id,
			'user_id'   => $feed->user_id,
			'data'      => wp_json_encode( $item ), // Store `$item` as Mf2 JSON, for (eventual) use with Microsub readers.
		);

		$entry['name']   = apply_filters( 'feed_reader_set_entry_name', $entry['name'], $item, $feed );
		$entry['author'] = apply_filters( 'feed_reader_set_entry_author', $entry['author'], $item, $feed );

		return $entry;
	}

	protected static function absolutize_urls( $html, $base ) {
		// There must (!) be a root-level element at all times. This'll get
		// stripped out during sanitization.
		$html = '<div>' . mb_convert_encoding( $html, 'HTML-ENTITIES', \FeedReader\Helpers\detect_encoding( $html ) ) . '</div>';

		libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$xpath = new \DOMXPath( $doc );

		// Since we're going to always run this function, we might as well use
		// it to filter out "invalid" `img` elements.
		foreach ( $xpath->query( '//img' ) as $node ) {
			if ( ! $node->hasAttribute( 'src' ) || empty( $node->getAttribute( 'src' ) ) ) {
				$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		// And "invalid" hyperlinks.
		foreach ( $xpath->query( '//a' ) as $node ) {
			if ( ! $node->hasChildNodes() && empty( $node->textContent ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
		}

		foreach ( $xpath->query( '//*[@href or @src or @srcset]' ) as $node ) {
			if ( $node->hasAttribute( 'href' ) && 0 !== strpos( $node->getAttribute( 'href' ), 'http' ) ) { // Ran into an issue here where `href="http://"`, so not a valid, nor a relative URL. Need to fix this properly.
				$node->setAttribute( 'href', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'href' ) ) );
			}

			if ( $node->hasAttribute( 'src' ) && 0 !== strpos( $node->getAttribute( 'src' ), 'http' ) ) {
				$node->setAttribute( 'src', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'src' ) ) );
			}

			if ( $node->hasAttribute( 'srcset' ) && 0 !== strpos( $node->getAttribute( 'srcset' ), 'http' ) ) {
				$srcset = array();

				foreach ( explode( ',', $node->getAttribute( 'srcset' ) ) as $item ) {
					if ( preg_match( '/^(.+?)(\s+.+)?$/', $item, $matches ) ) {
						$size = isset( $matches[2] ) ? trim( $matches[2] ) : '';

						$srcset[] = (string) SimplePie_IRI::absolutize( $base, $matches[1] ) . ' ' . $size;
					}
				}

				if ( ! empty( $srcset ) ) {
					$node->setAttribute( 'srcset', $srcset = implode( ', ', $srcset ) );
				}
			}
		}

		$html = $doc->saveHTML();
		$html = str_replace( '</source>', '', $html ); // Work around https://bugs.php.net/bug.php?id=73175.

		return $html;
	}
}
