<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'FEED_READER_ACTIONS' ) || ! FEED_READER_ACTIONS ) {
	return;
}

// if ( isset( $_GET['page'] ) && 'feed-reader-entries-view' !== $_GET['page'] && ! \FeedReader\show_in_full( $entry ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
// 	return;
// }
?>
<div class="actions" data-nonce="<?php echo esc_attr( wp_create_nonce( 'feed-reader:post' ) ); ?>">
	<ul>
		<li class="action-reply">
			<button class="button-link button-reply">
				<svg class="icon icon-reply" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-reply"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Reply', 'feed-reader' ); ?></span>
			</button>
		</li>
		<!--
		<li class="action-repost">
			<button class="button-link button-repost">
				<svg class="icon icon-refresh" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-refresh"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Repost', 'feed-reader' ); ?></span>
			</button>
		</li>
		//-->
		<li class="action-like">
			<button class="button-link button-like">
				<svg class="icon icon-star" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-star"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Like', 'feed-reader' ); ?></span>
			</button>
		</li>
		<li class="action-bookmark">
			<button class="button-link button-bookmark">
				<svg class="icon icon-bookmark" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-bookmark"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Bookmark', 'feed-reader' ); ?></span>
			</button>
		</li>
		<li class="action-delete">
			<button class="button-link button-delete">
				<svg class="icon icon-trash" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-trash"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'feed-reader' ); ?></span>
			</button>
		</li>
	</ul>

	<div class="reply-form" style="display: none;">
		<input type="text" class="widefat" placeholder="<?php esc_attr_e( '(Optional) Title', 'feed-reader' ); ?>">
		<textarea rows="4" class="widefat"></textarea>
		<button class="button buttton-primary button-publish-reply"><?php esc_html_e( 'Reply', 'feed-reader' ); ?></button>
	</div>

	<div class="bookmark-form" style="display: none;">
		<input type="text" class="widefat" placeholder="<?php esc_attr_e( '(Optional) Title', 'feed-reader' ); ?>">
		<textarea rows="4" class="widefat"></textarea>
		<button class="button buttton-primary button-publish-bookmark"><?php esc_html_e( 'Bookmark', 'feed-reader' ); ?></button>
	</div>
</div>
