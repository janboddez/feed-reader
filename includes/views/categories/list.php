<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Categories', 'feed-reader' ); ?></h1>
	<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'categories', 'create' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'feed-reader' ); ?></a>

	<hr class="wp-header-end" />

	<form id="categories-filter" action="admin.php" method="get">
		<input type="hidden" name="page" value="feed-reader/categories" />
		<?php $category_table->search_box( esc_html__( 'Search Categories' ), 'feed-reader-category' ); ?>
		<div style="clear: both;"></div>
	</form>

	<?php $category_table->display(); ?>
</div>
