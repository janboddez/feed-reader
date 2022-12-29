<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @codingStandardsIgnoreStart
// $feeds = \FeedReader\Models\Feed::all(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

// Filter out just the categories.
// $categories = array_unique(
// 	array_filter(
// 		array_map(
// 			function( $value ) {
// 				return ! empty( $value->category_id ) && ! empty( $value->category_name )
// 					? (object) array(
// 						'id'   => $value->category_id,
// 						'name' => $value->category_name,
// 					)
// 					: null;
// 			},
// 			$feeds
// 		)
// 	),
// 	SORT_REGULAR
// );
// @codingStandardsIgnoreEnd

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
	// $cur_feed = (int) $entries[0]->feed_id;
	$cur_cat = (int) ( \FeedReader\Models\Feed::find( $entries[0]->feed_id ) )->category_id; // Another useless query, but hey.
}

$categories = \FeedReader\Models\Category::all();

if ( ! empty( $categories ) ) :
	foreach ( $categories as $category ) :
		// @codingStandardsIgnoreStart
		// Filter out just this category's feeds.
		// $cat_feeds = array_filter(
		// 	$feeds,
		// 	function( $value ) use ( $category ) {
		// 		return (int) $category->id === (int) $value->category_id;
		// 	}
		// );
		// @codingStandardsIgnoreEnd

		// Could "eager load" these and perform some array magic as per the code
		// above, but not sure overall load times would benefit all that much.
		$feeds  = \FeedReader\Models\Category::feeds( $category->id, 'all' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$unread = 0;

		if ( ! empty( $feeds ) ) :
			// Probably a smarter way to do this in SQL, but this'll do for now.
			$unread = array_reduce(
				$feeds,
				function( $carry, $item ) {
					$carry += $item->unread_count;
					return $carry;
				}
			);
			?>
			<details <?php echo ( $cur_cat === (int) $category->id || in_array( $cur_feed, array_map( 'intval', array_column( $feeds, 'id' ) ), true ) ? 'open="open"' : '' ); ?>>
				<summary>
					<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'view', $category->id, empty( $unread ) ) ); ?>"><?php echo esc_html( $category->name ); ?> <?php echo esc_html( ! empty( $unread ) ? "($unread)" : '' ); ?></a>
				</summary>
				<ul>
					<?php foreach ( $feeds as $feed ) : ?>
						<li <?php echo ( $cur_feed === (int) $feed->id ? 'class="active"' : '' ); ?>>
							<?php if ( ! empty( $feed->icon ) ) : ?>
								<img class="avatar" src="<?php echo esc_url( $feed->icon ); ?>" width="16" height="16" loading="lazy">
							<?php endif; ?>
							<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'view', $feed->id, empty( $feed->unread_count ) ) ); ?>"><?php echo esc_html( ! empty( $feed->name ) ? $feed->name : preg_replace( '~^www.~', '', wp_parse_url( $feed->url, PHP_URL_HOST ) ) ); ?> <?php echo esc_html( ! empty( $feed->unread_count ) ? "({$feed->unread_count})" : '' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
			<?php
		endif;
	endforeach;
endif;
