<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'feed_reader_settings' );

if ( empty( $options['show_actions'] ) ) {
	return;
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
		<li class="action-delete">
			<button class="button-link button-delete" data-nonce="<?php echo esc_attr( wp_create_nonce( "feed-reader-entries:delete:{$entry->id}" ) ); ?>">
				<svg class="icon icon-trash" aria-hidden="true" role="img" width="16" height="16"><use href="#icon-trash"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'feed-reader' ); ?></span>
			</button>
		</li>
	</ul>

	<div class="reply-form" style="display: none;">
		<input type="text" class="widefat" placeholder="<?php esc_attr_e( '(Optional) Title', 'feed-reader' ); ?>">
		<textarea rows="4" class="widefat"></textarea>
		<div style="display: flex; justify-content: space-between; gap: 1.5em; align-items: start;">
			<div>
				<select>
					<option value="draft" selected="selected"><?php esc_html_e( 'Draft', 'feed-reader' ); ?></option>
					<option value="publish"><?php esc_html_e( 'Publish', 'feed-reader' ); ?></option>
				</select>
			</div>
			<div>
				<?php
				$syndication_targets = apply_filters( 'micropub_syndicate-to', array(), get_current_user_id() );

				if ( ! empty( $syndication_targets ) && is_array( $syndication_targets ) ) {
					foreach ( $syndication_targets as $syndication_target ) {
						if ( empty( $syndication_target['uid'] ) || empty( $syndication_target['name'] ) ) {
							continue;
						}
						?>
						<label><input type="checkbox" value="<?php echo esc_attr( $syndication_target['uid'] ); ?>"> <?php echo esc_html( $syndication_target['name'] ); ?></label>
						<?php
					}
				}
				?>
			</div>
			<div>
				<button class="button buttton-primary button-publish-bookmark"><?php esc_html_e( 'Bookmark', 'feed-reader' ); ?></button>
			</div>
		</div>
	</div>

	<div class="bookmark-form" style="display: none;">
		<input type="text" class="widefat" placeholder="<?php esc_attr_e( '(Optional) Title', 'feed-reader' ); ?>">
		<textarea rows="4" class="widefat"></textarea>
		<div style="display: flex; justify-content: space-between; gap: 1.5em; align-items: start;">
			<div>
				<select>
					<option value="draft" selected="selected"><?php esc_html_e( 'Draft', 'feed-reader' ); ?></option>
					<option value="publish"><?php esc_html_e( 'Publish', 'feed-reader' ); ?></option>
				</select>
			</div>
			<div>
				<?php
				$syndication_targets = apply_filters( 'micropub_syndicate-to', array(), get_current_user_id() );

				if ( ! empty( $syndication_targets ) && is_array( $syndication_targets ) ) {
					foreach ( $syndication_targets as $syndication_target ) {
						if ( empty( $syndication_target['uid'] ) || empty( $syndication_target['name'] ) ) {
							continue;
						}
						?>
						<label><input type="checkbox" value="<?php echo esc_attr( $syndication_target['uid'] ); ?>"> <?php echo esc_html( $syndication_target['name'] ); ?></label>
						<?php
					}
				}
				?>
			</div>
			<div>
				<button class="button buttton-primary button-publish-bookmark"><?php esc_html_e( 'Bookmark', 'feed-reader' ); ?></button>
			</div>
		</div>
	</div>
</div>
