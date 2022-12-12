<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php isset( $feed ) ? esc_html_e( 'Edit Feed', 'feed-reader' ) : esc_html_e( 'Add Feed', 'feed-reader' ); ?></h1>

	<form action="admin-post.php" method="post">
		<?php if ( isset( $feed ) ) : ?>
			<?php wp_nonce_field( "feed-reader-feeds:edit:{$feed->id}" ); ?>
			<input type="hidden" name="action" value="feed_reader_feeds_update" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $feed->id ); ?>" />
		<?php else : ?>
			<?php wp_nonce_field( 'feed-reader-feeds:add' ); ?>
			<input type="hidden" name="action" value="feed_reader_feeds_store" />
		<?php endif; ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="feed-name"><?php esc_html_e( 'Feed Name', 'feed-reader' ); ?></th>
				<td><input type="text" id="feed-name" name="feed_name" style="min-width: 33%;" value="<?php echo esc_attr( isset( $feed->name ) ? $feed->name : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="feed-url"><?php esc_html_e( 'Feed URL', 'feed-reader' ); ?></th>
				<td><input type="url" id="feed-url" name="feed_url" style="min-width: 33%;" value="<?php echo esc_attr( isset( $feed->url ) ? $feed->url : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="site-url"><?php esc_html_e( 'Site URL', 'feed-reader' ); ?></th>
				<td><input type="url" id="site-url" name="site_url" style="min-width: 33%;" value="<?php echo esc_attr( isset( $feed->site_url ) ? $feed->site_url : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="category"><?php esc_html_e( 'Category', 'feed-reader' ); ?></th>
				<td><select id="category" name="category">
					<?php foreach ( \FeedReader\Models\Category::all() as $category ) : ?>
						<option value="<?php echo esc_attr( $category->id ); ?>" <?php selected( isset( $feed->category_id ) ? $feed->category_id : null, $category->id ); ?>><?php echo esc_html( $category->name ); ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary"><?php isset( $feed ) ? esc_html_e( 'Save Changes', 'feed-reader' ) : esc_html_e( 'Add Feed', 'feed-reader' ); ?></button>
		</p>
	</form>
</div>
