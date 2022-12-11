<?php

namespace Feed_Reader;

function get_url( $controller, $method = null, $id = null, $all = false ) {
	return add_query_arg(
		array_filter(
			array(
				'page' => 'feed-reader' . ( ! empty( $controller ) ? "-$controller" : '' ) . ( ! empty( $method ) ? "-$method" : '' ),
				'id'   => $id,
				'all'  => $all,
			)
		),
		admin_url( 'admin.php' )
	);
}

function pluralize( $value ) {
	$args = array(
		'category' => 'categories',
		'entry'    => 'entries',
		'feed'     => 'feeds',
	);

	return array_key_exists( $value, $args ) ? $args[ $value ] : $value;
}

function singularize( $value ) {
	$args = array(
		'categories' => 'category',
		'entries'    => 'entry',
		'feeds'      => 'feed',
	);

	return array_key_exists( $value, $args ) ? $args[ $value ] : $value;
}

function kses( $string ) {
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
		'cite'       => array(),
		'code'       => array(),
		'del'        => array(
			'datetime' => true,
		),
		'em'         => array(),
		'figure'     => array(
			'figcaption' => array(),
		),
		'i'          => array(),
		'img'        => array(
			'height' => true,
			'src'    => true,
			'width'  => true,
		),
		'p'          => array(),
		'q'          => array(
			'cite' => array(),
		),
		'strike'     => array(),
		'strong'     => array(),
		'table'      => array(),
		'tr'         => array(),
		'td'         => array(
			'colspan' => true,
			'rowspan' => true,
		),
		'th'         => array(
			'colspan' => true,
			'rowspan' => true,
		),
		'pre'        => array(),
		'dl'         => array(
			'dd' => array(),
			'dt' => array(),
		),
		'ol'         => array(),
		'ul'         => array(),
		'li'         => array(),
		'h2'         => array(),
		'h3'         => array(),
		'h4'         => array(),
		'h5'         => array(),
		'h6'         => array(),
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
