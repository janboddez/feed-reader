<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap feed-reader">
	<?php if ( ! empty( $feed ) ) : ?>
		<h1 class="page-title"><?php echo esc_html( ! empty( $feed->name ) ? $feed->name : preg_replace( '~^www~', '', wp_parse_url( $feed->url, PHP_URL_HOST ) ) ); ?></h1>
	<?php elseif ( ! empty( $category->name ) ) : ?>
		<h1 class="page-title"><?php echo esc_html( $category->name ); ?></h1>
	<?php elseif ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<h1 class="page-title">
			<?php esc_html_e( 'All Entries', 'feed-reader' ); ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \Feed_Reader\get_url( '', '', null, false ) ); ?>"><?php esc_html_e( 'Unread', 'feed-reader' ); ?></a>
		</h1>
	<?php else : ?>
		<h1 class="page-title">
			<?php esc_html_e( 'Unread', 'feed-reader' ); ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \Feed_Reader\get_url( '', '', null, true ) ); ?>"><?php esc_html_e( 'All Entries', 'feed-reader' ); ?></a>
		</h1>
	<?php endif; ?>

	<div class="hfeed">
		<?php if ( ! empty( $entries ) ) : ?>
			<?php foreach ( $entries as $entry ) : ?>
				<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>">
					<?php if ( ! empty( $entry->name ) ) : ?>
						<h2 class="entry-title"><a href="<?php echo esc_url( \Feed_Reader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->name ); ?></a></h2>
					<?php elseif ( ! empty( $entry->summary ) ) : ?>
						<h2 class="screen-reader-text"><a href="<?php echo esc_url( \Feed_Reader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->summary ); ?></a></h2>
						<?php
					endif;

					if ( ! empty( $entry->name ) ) :
						static::render( 'entries/partials/entry-meta', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
					endif;
					?>

					<div class="entry-summary">
						<?php echo $entry->summary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php /* translators: %s: Entry title */ ?>
						<a href="<?php echo esc_url( \Feed_Reader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php printf( esc_html__( 'Continue reading %s', 'feed-reader' ), '<span class="screen-reader-text">' . esc_html( $entry->name ) . '</span>' ); ?></a>
					</div>

					<?php
					if ( empty( $entry->name ) ) :
						static::render( 'entries/partials/entry-meta', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
					endif;
					?>
				</article>
				<?php
			endforeach;
		else :
			?>
			<section class="hentry">
				<p><?php esc_html_e( 'Seems you&rsquo;re all caught up!', 'feed-reader' ); ?></p>
			</section>
		<?php endif; ?>
	</div>

	<?php \Feed_Reader\cursor_pagination( $before, $after ); ?>
</div>
