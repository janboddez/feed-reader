<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Feeds', 'feed-reader' ); ?></h1>
	<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'create' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'feed-reader' ); ?></a>

	<hr class="wp-header-end" />

	<form id="feeds-filter" action="admin.php" method="get">
		<input type="hidden" name="page" value="feed-reader/feeds" />
		<?php $feed_table->search_box( esc_html__( 'Search Feeds' ), 'feed-reader-feed' ); ?>
		<div style="clear: both;"></div>
	</form>

	<?php $feed_table->display(); ?>
</div>
