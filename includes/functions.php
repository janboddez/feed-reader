<?php

namespace Feed_Reader;

function get_url( $controller, $method = null, $id = null, $all = null ) {
	$all = in_array( $controller, array( 'feeds', 'categories' ), true ) && in_array( $method, array( null, 'view' ), true )
		? '1'
		: $all;

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
	$inflector = \Feed_Reader\Feed_Reader::inflector();

	return $inflector->pluralize( $value );
}

function singularize( $value ) {
	$inflector = \Feed_Reader\Feed_Reader::inflector();

	return $inflector->singularize( $value );
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

function pagination( $paged, $total_pages ) {
	?>
	<nav class="pagination">
		<ul>
			<?php if ( $paged <= 1 ) : ?>
				<li><span class="disabled"><?php echo esc_html_e( '&larr; Previous', 'feed-reader' ); ?></span></li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'paged' => $paged - 1 ), get_current_admin_url() ) ); ?>"><?php esc_html_e( '&larr; Previous', 'feed-reader' ); ?></a></li>
			<?php endif; ?>

			<?php if ( $paged >= $total_pages ) : ?>
				<li><span class="disabled"><?php echo esc_html_e( 'Next &rarr;', 'feed-reader' ); ?></span></li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( add_query_arg( array( 'paged' => $paged + 1 ), get_current_admin_url() ) ); ?>"><?php esc_html_e( 'Next &rarr;', 'feed-reader' ); ?></a></li>
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
		$url .= '?' . $_SERVER['QUERY_STRING']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	return $url;
}
