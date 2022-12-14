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
				<th scope="row"><label for="feed-name"><?php esc_html_e( 'Feed Name', 'feed-reader' ); ?></label></th>
				<td><input type="text" class="widefat" id="feed-name" name="feed_name" value="<?php echo esc_attr( isset( $feed->name ) ? $feed->name : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="feed-url"><?php esc_html_e( 'Feed URL', 'feed-reader' ); ?></label></th>
				<td><input type="url" class="widefat" id="feed-url" name="feed_url" value="<?php echo esc_attr( isset( $feed->url ) ? $feed->url : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="site-url"><?php esc_html_e( 'Site URL', 'feed-reader' ); ?></label></th>
				<td><input type="url" class="widefat" id="site-url" name="site_url" value="<?php echo esc_attr( isset( $feed->site_url ) ? $feed->site_url : '' ); ?>" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="category"><?php esc_html_e( 'Category', 'feed-reader' ); ?></label></th>
				<td><select id="category" name="category" style="min-width: 33%;">
					<option><?php esc_html_e( '&mdash;', 'feed-reader' ); ?></option>
					<?php foreach ( \FeedReader\Models\Category::all() as $category ) : ?>
						<option value="<?php echo esc_attr( $category->id ); ?>" <?php selected( isset( $feed->category_id ) ? $feed->category_id : null, $category->id ); ?>><?php echo esc_html( $category->name ); ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php isset( $feed ) ? esc_html_e( 'Save Changes', 'feed-reader' ) : esc_html_e( 'Add Feed', 'feed-reader' ); ?></button>
		</p>

		<fieldset style="max-width: 50em;">
			<legend><?php esc_html_e( 'Danger Zone', 'feed-reader' ); ?></legend>
			<div class="form-group">
				<span class="description"><?php esc_html_e( 'Permanently delete this feed and all of its entries.', 'feed-reader' ); ?></span>
				<a class="button delete" href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'delete', $feed->id ) ); ?>"><?php esc_html_e( 'Delete', 'feed-reader' ); ?></a>
			</div>
		</fieldset>
	</form>
</div>
