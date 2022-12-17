<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap feed-reader">
	<?php if ( ! empty( $feed ) ) : ?>
		<h1 class="page-title"><?php echo esc_html( ! empty( $feed->name ) ? $feed->name : preg_replace( '~^www~', '', wp_parse_url( $feed->url, PHP_URL_HOST ) ) ); ?></h1>
		<div class="feed-links">
			<?php if ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'view', $feed->id, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'view', $feed->id, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
			<?php endif; ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'edit', $feed->id ) ); ?>"><?php esc_html_e( 'Edit Feed', 'feed-reader' ); ?></a>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'mark-read', $feed->id ) ); ?>"><?php esc_html_e( 'Mark as Read', 'feed-reader' ); ?></a>
		</div>
	<?php elseif ( ! empty( $category->name ) ) : ?>
		<h1 class="page-title"><?php echo esc_html( $category->name ); ?></h1>
		<div class="feed-links">
			<?php if ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<a href="<?php echo esc_url( \FeedReader\get_url( 'categories', 'view', $category->id, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
			<?php else : ?>
				<a href="<?php echo esc_url( \FeedReader\get_url( 'categories', 'view', $category->id, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
			<?php endif; ?>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'categories', 'edit', $category->id ) ); ?>"><?php esc_html_e( 'Edit Category', 'feed-reader' ); ?></a>
		</div>
	<?php elseif ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<h1 class="page-title"><?php esc_html_e( 'All Entries', 'feed-reader' ); ?></h1>
		<div class="feed-links">
			<a href="<?php echo esc_url( \FeedReader\get_url( '', '', null, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'create' ) ); ?>"><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></a>
		</div>
	<?php else : ?>
		<h1 class="page-title"><?php esc_html_e( 'Unread', 'feed-reader' ); ?></h1>
		<div class="feed-links">
			<a href="<?php echo esc_url( \FeedReader\get_url( '', '', null, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
			<span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'create' ) ); ?>"><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></a>
		</div>
	<?php endif; ?>

	<div class="hfeed">
		<?php if ( ! empty( $entries ) ) : ?>
			<?php foreach ( $entries as $entry ) : ?>
				<?php if ( ! empty( $entry->url ) ) : ?>
					<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-url="<?php echo esc_url( $entry->url ); ?>">
				<?php else : ?>
					<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>">
				<?php endif; ?>
					<?php if ( ! empty( $entry->name ) ) : ?>
						<h2 class="entry-title"><a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->name ); ?></a></h2>
					<?php elseif ( ! empty( $entry->summary ) ) : ?>
						<h2 class="screen-reader-text"><a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->summary ); ?></a></h2>
						<?php
					endif;

					static::render( 'entries/partials/entry-meta', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
					?>

					<?php if ( \FeedReader\show_in_full( $entry ) ) : ?>
						<div class="entry-content">
							<?php /** @todo: Check content exists. */ ?>
							<?php echo $entry->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php elseif ( ! empty( $entry->summary ) ) : ?>
						<div class="entry-summary">
							<p>
								<?php /** @todo: Check a summary exists. And maybe add the paragraph tags during parsing already. */ ?>
								<?php echo $entry->summary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php /* translators: %s: Entry title */ ?>
								<a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php printf( esc_html__( 'Continue reading %s &rarr;', 'feed-reader' ), '<span class="screen-reader-text">' . esc_html( $entry->name ) . '</span>' ); ?></a>
							</p>
						</div>
						<?php
					endif;

					static::render( 'entries/partials/entry-actions', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
					?>
				</article>
				<?php
			endforeach;
		else :
			?>
			<section class="hentry note">
				<div class="entry-summary">
					<p><?php esc_html_e( 'Seems you&rsquo;re all caught up!', 'feed-reader' ); ?></p>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php \FeedReader\cursor_pagination( $before, $after ); ?>
</div>
