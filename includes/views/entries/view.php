<?php

use FeedReader\Controllers\Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap feed-reader">
	<?php if ( ! empty( $entry->url ) ) : ?>
		<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-url="<?php echo esc_url( $entry->url ); ?>">
	<?php else : ?>
		<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>">
	<?php endif; ?>
		<?php if ( ! empty( $entry->name ) ) : ?>
			<h1 class="entry-title"><a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->name ); ?></a></h1>
		<?php elseif ( ! empty( $entry->summary ) ) : ?>
			<h1 class="screen-reader-text"><a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->summary ); ?></a></h1>
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
