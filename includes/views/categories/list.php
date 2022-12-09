<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Categories', 'feed-reader' ); ?></h1>
	<a href="<?php echo esc_url( \Feed_Reader\get_url( 'categories', 'create' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'feed-reader' ); ?></a>

	<hr class="wp-header-end" />

	<?php $feed_table->display(); ?>
</div>
