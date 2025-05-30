<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options       = get_option( 'feed_reader_settings' );
$user_settings = get_user_meta( get_current_user_id(), 'feed_reader_settings', true );
?>
<div class="wrap feed-reader <?php echo esc_attr( empty( $user_settings['hide_sidebar'] ) ? 'with-sidebar' : '' ); ?> <?php echo esc_attr( empty( $user_settings['system_fonts'] ) ? 'custom-fonts' : '' ); ?>">
	<?php if ( empty( $user_settings['hide_sidebar'] ) ) : ?>
		<aside class="feed-reader-sidebar">
			<?php static::render( 'sidebar', array( 'entries' => array( $entry ) ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope ?>
		</aside>
	<?php endif; ?>

	<div class="feed-reader-main">
		<?php if ( ! empty( $entry->url ) ) : ?>
			<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-url="<?php echo esc_url( $entry->url ); ?>" data-id="<?php echo esc_attr( $entry->id ); ?>" data-feed-id="<?php echo esc_attr( $entry->feed_id ); ?>">
		<?php else : ?>
			<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-id="<?php echo esc_attr( $entry->id ); ?>" data-feed-id="<?php echo esc_attr( $entry->feed_id ); ?>">
		<?php endif; ?>
			<?php if ( ! empty( $entry->name ) ) : ?>
				<h1 class="entry-title"><?php echo esc_html( $entry->name ); ?></h1>
			<?php elseif ( ! empty( $entry->summary ) ) : ?>
				<h1 class="screen-reader-text"><?php echo esc_html( $entry->summary ); ?></h1>
			<?php endif; ?>

			<?php static::render( 'entries/partials/entry-meta', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope ?>

			<?php if ( ! empty( $entry->content ) ) : ?>
				<div class="entry-content">
					<?php echo $entry->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php
			endif;

			static::render( 'entries/partials/entry-actions', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
			?>
		</article>
	</div>
</div>
