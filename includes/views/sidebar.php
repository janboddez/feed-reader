<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cur_feed = null;
$cur_cat  = null;

if ( 'categories' === \FeedReader\Router::get_controller() ) {
	if ( ! empty( $_GET['id'] ) && ctype_digit( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cur_cat = (int) $_GET['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
} elseif ( 'feeds' === \FeedReader\Router::get_controller() ) {
	if ( ! empty( $_GET['id'] ) && ctype_digit( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cur_feed = (int) $_GET['id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
} elseif ( 'entries' === \FeedReader\Router::get_controller() && ! empty( $entries[0]->feed_id ) ) {
	$cur_feed = (int) $entries[0]->feed_id;
}

$categories = \FeedReader\Models\Category::all();

if ( ! empty( $categories ) ) :
	foreach ( $categories as $category ) :
		$feeds = \FeedReader\Models\Category::feeds( $category->id, 'all' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! empty( $feeds ) ) :
			?>
			<details <?php echo ( $cur_cat === (int) $category->id || in_array( $cur_feed, array_map( 'intval', array_column( $feeds, 'id' ) ), true ) ? 'open="open"' : '' ); ?>>
				<summary>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'view', $category->id, true ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
				</summary>
				<ul>
					<?php foreach ( $feeds as $feed ) : ?>
						<li <?php echo ( $cur_feed === (int) $feed->id ? 'class="active"' : '' ); ?>>
							<?php if ( ! empty( $feed->icon ) ) : ?>
								<img class="avatar" src="<?php echo esc_url( $feed->icon ); ?>" width="16" height="16" loading="lazy">
							<?php endif; ?>
							<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id, true ) ); ?>"><?php echo esc_html( ! empty( $feed->name ) ? $feed->name : preg_replace( '~^www.~', '', wp_parse_url( $feed->url ) ) ); ?> <?php echo esc_html( ! empty( $feed->unread_count ) ? "({$feed->unread_count})" : '' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
			<?php
		endif;
	endforeach;
endif;
