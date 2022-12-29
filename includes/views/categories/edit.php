<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php isset( $category ) ? esc_html_e( 'Edit Category', 'feed-reader' ) : esc_html_e( 'Add Category', 'feed-reader' ); ?></h1>

	<form action="admin-post.php" method="post">
		<?php if ( isset( $category ) ) : ?>
			<?php wp_nonce_field( "feed-reader-categories:edit:{$category->id}" ); ?>
			<input type="hidden" name="action" value="feed_reader_categories_update" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $category->id ); ?>" />
		<?php else : ?>
			<?php wp_nonce_field( 'feed-reader-categories:add' ); ?>
			<input type="hidden" name="action" value="feed_reader_categories_store" />
		<?php endif; ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="category-name"><?php esc_html_e( 'Category Name', 'feed-reader' ); ?></th>
				<td><input type="text" class="widefat" id="category-name" name="category_name" value="<?php echo esc_attr( isset( $category->name ) ? $category->name : '' ); ?>" /></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php isset( $category ) ? esc_html_e( 'Save Changes', 'feed-reader' ) : esc_html_e( 'Add Category', 'feed-reader' ); ?></button>
		</p>

		<?php if ( isset( $category ) ) : ?>
			<fieldset>
				<legend><?php esc_html_e( 'Danger Zone', 'feed-reader' ); ?></legend>
				<div class="form-group">
					<span class="description"><?php esc_html_e( 'Permanently delete this category. Its feeds will not be deleted, but become &ldquo;uncategorized.&rdquo;', 'feed-reader' ); ?></span>
					<a class="button delete" href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'delete', $category->id ) ); ?>"><?php esc_html_e( 'Delete', 'feed-reader' ); ?></a>
				</div>
			</fieldset>
		<?php endif; ?>
	</form>
</div>
