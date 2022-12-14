<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></h1>

	<div id="feed-discover">
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="site_or_feed_url"><?php esc_html_e( 'Site or Feed URL', 'feed-reader' ); ?></th>
				<td><input type="url" class="widefat" id="site_or_feed_url" name="site_url" /></td>
			</tr>
		</table>

		<p class="submit">
			<button type="button" class="button button-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'feed-reader-feeds:discover' ) ); ?>">
				<?php esc_html_e( 'Find Feeds', 'feed-reader' ); ?>
			</button>
		</p>
	</div>

	<ul id="feed-list"></ul>

	<form id="feed-create" action="admin-post.php" method="post">
		<?php wp_nonce_field( 'feed-reader-feeds:add' ); ?>
		<input type="hidden" name="action" value="feed_reader_feeds_store" />

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="feed-name"><?php esc_html_e( 'Feed Name', 'feed-reader' ); ?></th>
				<td><input type="text" class="widefat" id="feed-name" name="feed_name" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="feed-url"><?php esc_html_e( 'Feed URL', 'feed-reader' ); ?></th>
				<td><input type="url" class="widefat" id="feed-url" name="feed_url" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="site-url"><?php esc_html_e( 'Site URL', 'feed-reader' ); ?></th>
				<td><input type="url" class="widefat" id="site-url" name="site_url" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="category"><?php esc_html_e( 'Category', 'feed-reader' ); ?></th>
				<td><select id="category" name="category" style="min-width: 33%;">
					<option><?php esc_html_e( '&mdash;', 'feed-reader' ); ?></option>
					<?php foreach ( \FeedReader\Models\Category::all() as $category ) : ?>
						<option value="<?php echo esc_attr( $category->id ); ?>"><?php echo esc_html( $category->name ); ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Feed', 'feed-reader' ); ?></button>
		</p>
	</form>
</div>
