<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="entry-meta">
	<?php if ( ! empty( $entry->author ) ) : ?>
		<a href="<?php echo esc_url( \Feed_Reader\get_url( 'feeds', 'view', $entry->feed_id, true ) ); ?>"><?php echo esc_html( $entry->author ); ?></a>
		<span aria-hidden="true">&bull;</span>
	<?php elseif ( ! empty( $entry->feed_name ) ) : ?>
		<a href="<?php echo esc_url( \Feed_Reader\get_url( 'feeds', 'view', $entry->feed_id, true ) ); ?>"><?php echo esc_html( $entry->feed_name ); ?></a>
		<span aria-hidden="true">&bull;</span>
	<?php endif; ?>

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

	<span aria-hidden="true">&bull;</span>

	<?php if ( ! $entry->is_read ) : ?>
		<button class="button-link mark-read" data-entry-id="<?php echo esc_attr( $entry->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:mark-read:{$entry->id}" ) ); ?>">
			<?php esc_html_e( 'Mark as read', 'feed-reader' ); ?>
		</button>
	<?php else : ?>
		<button class="button-link mark-unread" data-entry-id="<?php echo esc_attr( $entry->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:mark-unread:{$entry->id}" ) ); ?>">
			<?php esc_html_e( 'Mark as unread', 'feed-reader' ); ?>
		</button>
	<?php endif; ?>
</div>
