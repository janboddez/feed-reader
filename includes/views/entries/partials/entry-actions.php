<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
	</ul>

	<div class="reply-form" style="display: none;">
		<textarea rows="4"></textarea>
		<button class="button button-publish-reply"><?php esc_html_e( 'Reply', 'feed-reader' ); ?></button>
	</div>

	<div class="bookmark-form" style="display: none;">
		<textarea rows="4"></textarea>
		<button class="button button-publish-bookmark"><?php esc_html_e( 'Bookmark', 'feed-reader' ); ?></button>
	</div>
</div>
