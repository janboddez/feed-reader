<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'feed_reader_settings' );
?>
<div class="wrap feed-reader <?php echo esc_attr( empty( $options['hide_sidebar'] ) ? 'with-sidebar' : '' ); ?> <?php echo esc_attr( empty( $options['system_fonts'] ) ? 'custom-fonts' : '' ); ?>">
	<?php if ( empty( $options['hide_sidebar'] ) ) : ?>
		<aside class="feed-reader-sidebar">
			<?php static::render( 'sidebar', array( 'entries' => isset( $entries ) ? $entries : array() ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope ?>
		</aside>
	<?php endif; ?>

	<div class="feed-reader-main">
		<?php if ( ! empty( $feed ) ) : ?>
			<h1 class="page-title"><?php echo esc_html( ! empty( $feed->name ) ? $feed->name : preg_replace( '~^www~', '', wp_parse_url( $feed->url, PHP_URL_HOST ) ) ); ?></h1>
			<div class="feed-links">
				<?php if ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
				<?php endif; ?>

				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'edit', $feed->id ) ); ?>"><?php esc_html_e( 'Edit Feed', 'feed-reader' ); ?></a>

				<?php if ( ! empty( $feed->site_url ) ) : ?>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( $feed->site_url ); ?>"><?php esc_html_e( 'Visit Site', 'feed-reader' ); ?></a>
				<?php endif; ?>

				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'mark-read', $feed->id ) ); ?>"><?php esc_html_e( 'Mark Read', 'feed-reader' ); ?></a>
			</div>
		<?php elseif ( ! empty( $category->name ) ) : ?>
			<h1 class="page-title"><?php echo esc_html( $category->name ); ?></h1>
			<div class="feed-links">
				<?php if ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'view', $category->id, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'view', $category->id, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
				<?php endif; ?>

				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'edit', $category->id ) ); ?>"><?php esc_html_e( 'Edit Category', 'feed-reader' ); ?></a>

				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'mark-read', $category->id ) ); ?>"><?php esc_html_e( 'Mark Read', 'feed-reader' ); ?></a>
			</div>
		<?php elseif ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<h1 class="page-title"><?php esc_html_e( 'All Entries', 'feed-reader' ); ?></h1>
			<div class="feed-links">
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( '', '', null, false ) ); ?>"><?php esc_html_e( 'Unread Only', 'feed-reader' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'create' ) ); ?>"><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></a>
			</div>
		<?php else : ?>
			<h1 class="page-title"><?php esc_html_e( 'Unread', 'feed-reader' ); ?></h1>
			<div class="feed-links">
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( '', '', null, true ) ); ?>"><?php esc_html_e( 'Show All', 'feed-reader' ); ?></a>
				<span aria-hidden="true">/</span>
				<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'create' ) ); ?>"><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></a>
			</div>
		<?php endif; ?>

		<div class="hfeed">
			<?php if ( ! empty( $entries ) ) : ?>
				<?php foreach ( $entries as $entry ) : ?>
					<?php if ( ! empty( $entry->url ) ) : ?>
						<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-url="<?php echo esc_url( $entry->url ); ?>" data-id="<?php echo esc_attr( $entry->id ); ?>" data-feed-id="<?php echo esc_attr( $entry->feed_id ); ?>">
					<?php else : ?>
						<article class="hentry <?php echo esc_attr( ! empty( $entry->name ) ? 'article' : 'note' ); ?>" data-id="<?php echo esc_attr( $entry->id ); ?>" data-feed-id="<?php echo esc_attr( $entry->feed_id ); ?>">
					<?php endif; ?>

						<?php if ( ! empty( $entry->name ) ) : ?>
							<h2 class="entry-title"><a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->name ); ?></a></h2>
						<?php elseif ( ! empty( $entry->summary ) ) : ?>
							<h2 class="screen-reader-text"><a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( $entry->summary ); ?></a></h2>
							<?php
						endif;

						static::render( 'entries/partials/entry-meta', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
						?>

						<?php if ( \FeedReader\Helpers\show_in_full( $entry ) ) : ?>
							<div class="entry-content">
								<?php /** @todo: Check content exists. */ ?>
								<?php echo \FeedReader\Helpers\proxy_images( $entry->content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						<?php elseif ( ! empty( $entry->summary ) ) : ?>
							<div class="entry-summary">
								<p>
									<?php /** @todo: Check a summary exists. And maybe add the paragraph tags during parsing already. */ ?>
									<?php echo $entry->summary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php /* translators: %s: Entry title */ ?>
									<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php printf( esc_html__( 'Continue reading %s &rarr;', 'feed-reader' ), '<span class="screen-reader-text">' . esc_html( $entry->name ) . '</span>' ); ?></a>
								</p>
							</div>
							<?php
							$data = \FeedReader\Models\Entry::data( $entry );

							/** @todo: Support multiple images, video, and whatnot. */
							if ( ! empty( $data['properties']['photo'][0] ) ) :
								?>
								<div class="entry-photo">
									<img src="<?php echo esc_url( \FeedReader\Helpers\proxy_image( $data['properties']['photo'][0] ) ); ?>" alt="" />
								</div>
								<?php
							endif;
						endif;

						// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar,Squiz.PHP.CommentedOutCode.Found
						// static::render( 'entries/partials/entry-actions', compact( 'entry' ) ); // phpcs:ignore PHPCompatibility.Classes.NewLateStaticBinding.OutsideClassScope
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

		<?php \FeedReader\Helpers\cursor_pagination( $before, $after ); ?>
	</div>
</div>
