<?php
/**
 * Based off WordPress's core OPML template.
 *
 * @package FeedReader
 */

header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

echo '<?xml version="1.0"?' . ">\n";
?>
<opml version="1.0">
	<head>
		<title>
		<?php
			/* translators: %s: site title */
			printf( esc_html__( 'Subscriptions for %s' ), esc_attr( get_bloginfo( 'name', 'display' ) ) );
		?>
		</title>
		<dateCreated><?php echo gmdate( 'D, d M Y H:i:s' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> GMT</dateCreated>
	</head>
	<body>
<?php
foreach ( (array) $categories as $category ) :
	?>
<outline type="category" title="<?php echo esc_attr( $category->name ); ?>">
	<?php
	foreach ( (array) $category->feeds as $feed ) :
		?>
<outline text="<?php echo esc_attr( $feed->name ); ?>" type="link" xmlUrl="<?php echo esc_url( $feed->url ); ?>" <?php echo ( ! empty( $feed->site_url ) ? 'htmlUrl="' . esc_url( $feed->site_url ) . '"' : '' ); ?> />
		<?php
	endforeach;
	?>
</outline>
	<?php
endforeach;
?>
</body>
</opml>
