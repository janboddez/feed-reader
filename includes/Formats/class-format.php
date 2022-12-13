<?php

namespace FeedReader\Formats;

use SimplePie_IRI;

class Format {
	protected static function absolutize_urls( $html, $base ) {
		// There must (!) be a root-level element at all times. This'll get
		// stripped out during sanitization.
		$html = '<div>' . mb_convert_encoding( $html, 'HTML-ENTITIES', mb_detect_encoding( $html ) ) . '</div>';

		libxml_use_internal_errors( true );

		$doc = new \DOMDocument();
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		$xpath = new \DOMXPath( $doc );

		// @todo: Currently leaves `srcset` untouched; we should fix that.
		foreach ( $xpath->query( '//*[@src or @href]' ) as $node ) {
			if ( $node->hasAttribute( 'href' ) && 0 !== strpos( $node->getAttribute( 'href' ), 'http' ) ) { // Ran into an issue here where `href="http://"`, so not a valid, nor a relative URL. Need to fix this properly.
				$node->setAttribute( 'href', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'href' ) ) );
			}

			if ( $node->hasAttribute( 'src' ) && 0 !== strpos( $node->getAttribute( 'src' ), 'http' ) ) {
				$node->setAttribute( 'src', (string) SimplePie_IRI::absolutize( $base, $node->getAttribute( 'src' ) ) );
			}
		}

		$html = $doc->saveHTML();
		$html = str_replace( '</source>', '', $html ); // Work around https://bugs.php.net/bug.php?id=73175.

		return $html;
	}
}
