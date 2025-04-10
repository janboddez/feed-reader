<?php

namespace FeedReader\Helpers;

/**
 * Temporarily "stores," and subsequently returns, the "current" model.
 *
 * @param  mixed $value The "current" model, i.e., category, feed or entry.
 * @return mixed        The "current" model, or `null`.
 */
function current_model( $value = null ) {
	static $model = null;

	if ( $value ) {
		$model = $value;
	}

	return $model;
}

/**
 * Generates the URL, including a complete query string, for a specific "feed
 * reader" route.
 *
 * @param  string|null $controller Controller.
 * @param  string|null $method     Controller method.
 * @param  string|null $id         Model ID.
 * @param  string|null $all        If all entries should be queried (or just the unread ones).
 * @return string                  (Admin) URL.
 */
function get_url( $controller = null, $method = null, $id = null, $all = false ) {
	if ( in_array( $method, array( 'delete', 'mark-read', 'export' ), true ) ) {
		$args = array(
			'action' => "feed_reader_{$controller}_" . str_replace( '-', '_', $method ),
			'id'     => $id,
		);

		if ( ! empty( $id ) ) {
			$args['_wpnonce'] = wp_create_nonce( "feed-reader-{$controller}:{$method}:$id" );
		}

		return add_query_arg(
			$args,
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

/**
 * Determines whether an entry should be shown in full.
 *
 * @param  \FeedReader\Models\Entry $entry Entry.
 * @return bool                            If the entry should be shown in full.
 */
function show_in_full( $entry ) {
	if ( empty( $entry->content ) ) {
		return false;
	}

	return mb_strlen( wp_strip_all_tags( $entry->content ) ) <= 500;
}

/**
 * Cleans up potentially unsafe HTML.
 *
 * @param  string $text Raw HTML.
 * @return string       Sanitized HTML.
 */
function kses( $text ) {
	$text = preg_replace( '~<!--.*?-->~s', '', $text );
	$text = preg_replace( '~<script.*?>.*?</script>~s', '', $text );
	$text = preg_replace( '~<style.*?>.*?</style>~s', '', $text );

	$text = \FeedReader\zz\Html\HTMLMinify::minify( $text );

	/** @todo: Allow certain `iframe` elements, like the ones that point to YouTube, only? */
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
		'aside'      => array(),
		'b'          => array(),
		'blockquote' => array(
			'cite' => array(),
		),
		'br'         => array(),
		'cite'       => array(),
		'code'       => array(
			'div' => array(),
		),
		'del'        => array(
			'datetime' => true,
		),
		'em'         => array(),
		'figure'     => array(),
		'figcaption' => array(),
		'footer'     => array(),
		'header'     => array(),
		'i'          => array(),
		'img'        => array(
			'alt'    => true,
			'src'    => true,
			'srcset' => true,
			'width'  => true,
			'height' => true,
			'style'  => array(
				'values' => array( 'u-photo' ),
			),
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
		'pre'        => array(
			'div' => array(),
		),
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
		'sub'        => array(),
		'sup'        => array(),
	);

	$text = wp_kses( $text, $allowed_html );
	$text = str_replace( array( '<p></p>', '<div></div>' ), '', $text );

	return $text;
}

/**
 * Prints the entry pagination menu.
 *
 * @param  string|null $before "Before" cursor, if any.
 * @param  string|null $after  "After" cursor, if any.
 */
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

/**
 * Returns the current admin URL.
 *
 * For use with cursor pagination.
 */
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

/**
 * Returns Feed Reader's "user agent" string.
 *
 * @param  string $url URL of whatever we're about be fetch.
 * @return string      User agent string.
 */
function get_user_agent( $url = '' ) {
	$user_agent = 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) . '; FeedReader';

	// Allow developers to override this user agent.
	return apply_filters( 'feed_reader_user_agent', $user_agent, $url );
}

/**
 * Wrapper around PHP's built-in `mb_detect_encoding()`.
 *
 * @param  string $text The string being inspected.
 * @return string       Detected encoding.
 */
function detect_encoding( $text ) {
	$encoding = mb_detect_encoding( $text );

	if ( 'ASCII' === $encoding ) {
		$encoding = 'UTF-8';
	}

	return $encoding;
}
