<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="entry-meta">
	<?php if ( ! empty( $entry->feed_icon ) ) : ?>
		<img class="avatar" src="<?php echo esc_url( $entry->feed_icon ); ?>" width="18" height="18" loading="lazy">
	<?php endif; ?>

	<?php if ( ! empty( $entry->author ) ) : ?>
		<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'view', $entry->feed_id, true ) ); ?>"><?php echo esc_html( $entry->author ); ?></a>
	<?php elseif ( ! empty( $entry->feed_name ) ) : ?>
		<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'view', $entry->feed_id, true ) ); ?>"><?php echo esc_html( $entry->feed_name ); ?></a>
	<?php else : ?>
		<a href="<?php echo esc_url( \FeedReader\get_url( 'feeds', 'view', $entry->feed_id, true ) ); ?>"><?php echo esc_html( preg_replace( '~^www.~', '', wp_parse_url( esc_html( $entry->url ), PHP_URL_HOST ) ) ); ?></a>
	<?php endif; ?>

	<span aria-hidden="true">&bull;</span>

	<?php if ( isset( $_GET['page'] ) && 'feed-reader-entries-view' === $_GET['page'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<?php if ( ! empty( $entry->url ) ) : ?>
			<time datetime="<?php echo esc_attr( date( 'Y-m-d\TH:i:s\Z', strtotime( $entry->published ) ) ); ?>">
				<?php /* translators: %s: Human-readable time ago */ ?>
				<a href="<?php echo esc_url( $entry->url ); ?>"><?php echo esc_html( sprintf( __( '%s ago', 'feed-reader' ), human_time_diff( strtotime( $entry->published ), time() ) ) ); ?></a>
			</time>
		<?php else : ?>
			<time datetime="<?php echo esc_attr( date( 'Y-m-d\TH:i:s\Z', strtotime( $entry->published ) ) ); ?>">
				<?php /* translators: %s: Human-readable time ago */ ?>
				<?php echo esc_html( sprintf( __( '%s ago', 'feed-reader' ), human_time_diff( strtotime( $entry->published ), time() ) ) ); ?>
			</time>
		<?php endif; ?>
	<?php else : ?>
		<time datetime="<?php echo esc_attr( date( 'Y-m-d\TH:i:s\Z', strtotime( $entry->published ) ) ); ?>">
			<?php /* translators: %s: Human-readable time ago */ ?>
			<a href="<?php echo esc_url( \FeedReader\get_url( 'entries', 'view', $entry->id ) ); ?>"><?php echo esc_html( sprintf( __( '%s ago', 'feed-reader' ), human_time_diff( strtotime( $entry->published ), time() ) ) ); ?></a>
		</time>
	<?php endif; ?>

	<span aria-hidden="true">&bull;</span>

	<?php if ( ! $entry->is_read ) : ?>
		<button class="button-link mark-read" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:mark-read:{$entry->id}" ) ); ?>">
			<?php esc_html_e( 'Mark as read', 'feed-reader' ); ?>
		</button>
	<?php else : ?>
		<button class="button-link mark-unread" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:mark-unread:{$entry->id}" ) ); ?>">
			<?php esc_html_e( 'Mark as unread', 'feed-reader' ); ?>
		</button>
	<?php endif; ?>

	<?php if ( ! defined( 'FEED_READER_ACTIONS' ) || ! FEED_READER_ACTIONS ) : ?>
		<span aria-hidden="true">&bull;</span>

		<button class="button-link delete" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:delete:{$entry->id}" ) ); ?>">
			<?php esc_html_e( 'Delete', 'feed-reader' ); ?>
		</button>
	<?php endif; ?>
</div>
