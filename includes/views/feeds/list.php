<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Feeds', 'feed-reader' ); ?></h1>
	<a href="<?php echo esc_url( \FeedReader\Helpers\get_url( 'feeds', 'create' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'feed-reader' ); ?></a>

	<hr class="wp-header-end" />

	<div id="feeds-filter">
		<?php $feed_table->search_box( esc_html__( 'Search Feeds' ), 'feed-reader-feed' ); ?>
		<div style="clear: both;"></div>
	</div>

	<?php $feed_table->display(); ?>
</div>
