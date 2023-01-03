<?php

namespace FeedReader\Helpers;

function get_url( $controller = null, $method = null, $id = null, $all = false ) {
	if ( in_array( $method, array( 'delete', 'mark-read' ), true ) ) {
		return add_query_arg(
			array(
				'action'   => "feed_reader_{$controller}_" . str_replace( '-', '_', $method ),
				'id'       => $id,
				'_wpnonce' => wp_create_nonce( "feed-reader-{$controller}:{$method}:{$id}" ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	return add_query_arg(
		array_filter(
			array(
				'page' => 'feed-reader' . ( ! empty( $controller ) ? "/$controller" : '' ) . ( ! empty( $method ) ? "/$method" : '' ),
				'id'   => $id,
				'all'  => $all,
			)
		),
		admin_url( 'admin.php' )
	);
}

function singularize( $value ) {
	$args = array(
		'categories' => 'category',
		'entries'    => 'entry',
		'feeds'      => 'feed',
	);

	return array_key_exists( $value, $args ) ? $args[ $value ] : $value;
}

function show_in_full( $entry ) {
	if ( empty( $entry->content ) ) {
		return false;
	}

	return mb_strlen( wp_strip_all_tags( $entry->content ) ) <= 500;
}

function kses( $string ) {
	$string = preg_replace( '~<!--.*?-->~s', '', $string );
	$string = preg_replace( '~<script.*?>.*?</script>~s', '', $string );
	$string = preg_replace( '~<style.*?>.*?</style>~s', '', $string );

	$string = \FeedReader\zz\Html\HTMLMinify::minify( $string );

	$allowed_html = array(
		'a'          => array(
			'href' => true,
		),
		'abbr'       => array(
			'title' => true,
		),
		'acronym'    => array(
			'title' => true,
		),
		'b'          => array(),
		'blockquote' => array(
			'cite' => array(),
		),
		'br'         => array(),
		'cite'       => array(),
		'code'       => array(),
		'del'        => array(
			'datetime' => true,
		),
		'em'         => array(),
		'figure'     => array(),
		'figcaption' => array(),
		'i'          => array(),
		'img'        => array(
			'alt'    => true,
			'src'    => true,
			'srcset' => true,
			'width'  => true,
			'height' => true,
		),
		'p'          => array(),
		'q'          => array(
			'cite' => array(),
		),
		'strike'     => array(),
		'strong'     => array(),
		'table'      => array(),
		'td'         => array(
			'colspan' => true,
			'rowspan' => true,
		),
		'th'         => array(
			'colspan' => true,
			'rowspan' => true,
		),
		'tr'         => array(),
		'pre'        => array(),
		'dl'         => array(
			'dd' => array(),
			'dt' => array(),
		),
		'ol'         => array(
			'start' => true,
		),
		'ul'         => array(),
		'li'         => array(),
		'h2'         => array(),
		'h3'         => array(),
		'h4'         => array(),
		'h5'         => array(),
		'h6'         => array(),
		'picture'    => array(
			'srcset' => true,
			'type'   => true,
		),
		'video'      => array(
			'controls' => true,
			'poster'   => true,
			'src'      => true,
		),
		'audio'      => array(
			'duration' => true,
			'src'      => true,
		),
		'track'      => array(
			'label'   => true,
			'src'     => true,
			'srclang' => true,
			'kind'    => true,
		),
		'source'     => array(
			'src'    => true,
			'srcset' => true,
			'type'   => true,

		),
		'hr'         => array(),
	);

	return wp_kses( $string, $allowed_html );
}

function cursor_pagination( $before, $after ) {
	?>
	<nav class="pagination">
		<ul>
			<?php if ( $before ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'before' => $before ), get_current_admin_url() ) ); ?>"><?php esc_html_e( '&larr; Previous', 'feed-reader' ); ?></a></li>
			<?php else : ?>
				<li><span class="disabled"><?php esc_html_e( '&larr; Previous', 'feed-reader' ); ?></span></li>
			<?php endif; ?>

			<?php if ( $after ) : ?>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'after' => $after ), get_current_admin_url() ) ); ?>"><?php esc_html_e( 'Next &rarr;', 'feed-reader' ); ?></a></li>
			<?php else : ?>
				<li><span class="disabled"><?php esc_html_e( 'Next &rarr;', 'feed-reader' ); ?></span></li>
			<?php endif; ?>
		</ul>
	</nav>
	<?php
}

function get_current_admin_url() {
	if ( ! is_admin() ) {
		return null;
	}

	global $pagenow;

	$url = $pagenow;

	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
		parse_str( $_SERVER['QUERY_STRING'], $args ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		unset( $args['before'] );
		unset( $args['after'] );
		$url .= '?' . http_build_query( $args ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	// @todo: Remove cursor parameters.

	return $url;
}

function build_cursor( $entry ) {
	return strtotime( $entry->published ) . ',' . $entry->id;
}

function validate_cursor( $cursor ) {
	if ( preg_match( '~^(\d+),(\d+)$~', $cursor, $matches ) ) {
		return $matches;
	}

	return false;
}

function parse_cursor( $cursor ) {
	$matches = validate_cursor( $cursor );

	if ( $matches ) {
		return array( date( 'Y-m-d H:i:s', $matches[1] ), $matches[2] );
	}

	return null;
}

function proxy_images( $html ) {
	if ( ! defined( 'FEED_READER_PROXY_KEY' ) ) {
		return $html;
	}

	$html = '<div>' . mb_convert_encoding( $html, 'HTML-ENTITIES', mb_detect_encoding( $html ) ) . '</div>';

	libxml_use_internal_errors( true );

	$doc = new \DOMDocument();
	$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	$xpath = new \DOMXPath( $doc );

	// @todo: Currently leaves `srcset` untouched; we should fix that.
	foreach ( $xpath->query( '//*[@src or @srcset]' ) as $node ) {
		if ( $node->hasAttribute( 'src' ) ) {
			$node->setAttribute( 'src', proxy_image( $node->getAttribute( 'src' ) ) );
		}

		if ( $node->hasAttribute( 'srcset' ) ) {
			$srcset = array();

			foreach ( explode( ', ', $node->getAttribute( 'srcset' ) ) as $item ) {
				if ( preg_match( '/^(.+?)(\s+.+)?$/', $item, $matches ) ) {
					$size = isset( $matches[2] ) ? trim( $matches[2] ) : '';

					$srcset[] = proxy_image( trim( $matches[1] ) ) . ' ' . $size;
				}
			}

			if ( ! empty( $srcset ) ) {
				$node->setAttribute( 'srcset', implode( ', ', $srcset ) );
			}
		}
	}

	$html = trim( $doc->saveHTML() );

	$html = substr( $html, 5 );
	$html = substr( $html, 0, -6 );

	return $html;
}

function proxy_image( $url ) {
	if ( ! defined( 'FEED_READER_PROXY_KEY' ) ) {
		return $url;
	}

	$query_string = http_build_query(
		array(
			'hash' => hash_hmac( 'sha1', $url, FEED_READER_PROXY_KEY ),
			'url'  => rawurlencode( $url ),
		)
	);

	return get_rest_url( null, '/feed-reader/v1/imageproxy' ) . "?$query_string";
}
