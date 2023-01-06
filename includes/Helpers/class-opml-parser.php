<?php

namespace FeedReader\Helpers;

/**
 * Dead-simple OPML parser.
 *
 * Borrows heavily from WordPress's own OPML importer.
 */
class OPML_Parser {
	/**
	 * After the OPML document's been parsed, will hold the feed list.
	 *
	 * @var array $feeds
	 */
	private $feeds = array();

	/**
	 * If categories should be imported.
	 *
	 * @var bool $categories_enabled
	 */
	private $categories_enabled = false;

	/**
	 * Category "iterator."
	 *
	 * @var string $current_category
	 */
	private $current_category = '';

	/**
	 * Parses an OPML file.
	 *
	 * @param string $opml               OPML string.
	 * @param bool   $categories_enabled If categories should be imported.
	 *
	 * @return array List of feeds.
	 */
	public function parse( $opml, $categories_enabled = false ) {
		if ( ! function_exists( 'xml_parser_create' ) ) {
			error_log( __( "PHP's XML extension is not available. Please contact your hosting provider to enable PHP's XML extension." ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.Security.EscapeOutput.OutputNotEscaped
			return array();
		}

		$this->categories_enabled = $categories_enabled;

		// Create an XML parser.
		$xml_parser = xml_parser_create();

		// Set the functions to handle opening and closing tags.
		xml_set_element_handler( $xml_parser, array( $this, 'startElement' ), array( $this, 'endElement' ) );

		if ( ! xml_parse( $xml_parser, $opml, true ) ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					/* translators: 1: Error message, 2: Line number. */
					__( 'XML Error: %1$s at line %2$s' ),
					xml_error_string( xml_get_error_code( $xml_parser ) ),
					xml_get_current_line_number( $xml_parser )
				)
			);
		}

		// Free up memory used by the XML parser.
		xml_parser_free( $xml_parser );

		return $this->feeds;
	}

	/**
	 * XML callback function for the start of a new XML tag.
	 *
	 * @access private
	 *
	 * @param mixed  $parser  XML Parser resource.
	 * @param string $tagName XML element name.
	 * @param array  $attrs   XML element attributes.
	 */
	private function startElement( $parser, $tagName, $attrs ) { // phpcs:ignore WordPress.NamingConventions
		if ( 'OUTLINE' === $tagName ) { // phpcs:ignore WordPress.NamingConventions
			$name = '';

			if ( isset( $attrs['TEXT'] ) ) {
				$name = $attrs['TEXT'];
			}

			if ( isset( $attrs['TITLE'] ) ) {
				$name = $attrs['TITLE'];
			}

			$url = '';

			if ( isset( $attrs['URL'] ) ) {
				$url = $attrs['URL'];
			}

			if ( isset( $attrs['HTMLURL'] ) ) {
				$url = $attrs['HTMLURL'];
			}

			if ( empty( $url ) && empty( $attrs['XMLURL'] ) ) {
				// Not a link or feed. Category? Note that this _will not_
				// "clear" the current category for "uncategorized" feeds.
				if ( '' !== $name && $this->categories_enabled ) {
					$this->current_category = $name;
				}

				// Continue.
				return;
			}

			$category = '';

			if ( isset( $attrs['CATEGORY'] ) ) {
				$category = explode( ',', $attrs['CATEGORY'] );
				$category = trim( reset( $category ) ); // We support only one category per feed.
			} elseif ( '' !== $this->current_category ) {
				// OPML v1 uses outlines for categories rather than a `category`
				// attribute.
				$category = $this->current_category;
			}

			$this->feeds[] = array(
				'name'        => $name,
				'site_url'    => $url,
				'target'      => isset( $attrs['TARGET'] ) ? $attrs['TARGET'] : '',
				'url'         => isset( $attrs['XMLURL'] ) ? $attrs['XMLURL'] : '',
				'description' => isset( $attrs['DESCRIPTION'] ) ? $attrs['DESCRIPTION'] : '',
				'category'    => $category,
				'type'        => isset( $attrs['TYPE'] ) ? $attrs['TYPE'] : '',
			);
		}
	}

	/**
	 * XML callback function that is called at the end of a XML tag.
	 *
	 * @access private
	 *
	 * @param mixed  $parser  XML Parser resource.
	 * @param string $tagName XML tag name.
	 */
	private function endElement( $parser, $tagName ) { // phpcs:ignore WordPress.NamingConventions
		// Nothing to do.
	}
}
