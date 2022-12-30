<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Reader Settings', 'feed-reader' ); ?></h1>

	<form action="options.php" method="post">
		<?php
		settings_fields( 'feed-reader-settings-group' );
		$options = get_option( 'feed_reader_settings' );
		?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Collapse Menu', 'feed-reader' ); ?></th>
				<td>
					<label><input type="checkbox" name="feed_reader_settings[collapse_menu]" <?php checked( ! empty( $options['collapse_menu'] ) ); ?> /> <?php esc_html_e( 'Auto-collapse WordPress&rsquo; admin menu.', 'feed-reader' ); ?></label>
					<p class="description"><?php esc_html_e( 'Avoid distraction by auto-collapsing WordPress&rsquo; side menu while reading.', 'feed-reader' ); ?></p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Hide Sidebar', 'feed-reader' ); ?></th>
				<td>
					<label><input type="checkbox" name="feed_reader_settings[hide_sidebar]" <?php checked( ! empty( $options['hide_sidebar'] ) ); ?> /> <?php esc_html_e( 'Hide the category and feed sidebar.', 'feed-reader' ); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Show Actions', 'feed-reader' ); ?></th>
				<td>
					<label><input type="checkbox" name="feed_reader_settings[show_actions]" <?php checked( ! empty( $options['show_actions'] ) ); ?> /> <?php esc_html_e( 'Display &ldquo;action buttons&rdquo; below single entries.', 'feed-reader' ); ?></label>
					<p class="description"><?php esc_html_e( 'These allow you to reply to, favorite, or bookmark feed entries, directly from the reader.', 'feed-reader' ); ?></p>
				</td>
			</tr>
			<!--
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Image Proxy', 'feed-reader' ); ?></th>
				<td>
					<label><input type="checkbox" name="feed_reader_settings[image_proxy]" <?php checked( ! empty( $options['image_proxy'] ) ); ?> /> <?php esc_html_e( '&ldquo;Proxy&rdquo; images and video.', 'feed-reader' ); ?></label>
					<p class="description"><?php esc_html_e( 'Avoid &ldquo;mixed context&rdquo; errors when reading feeds.', 'feed-reader' ); ?></p>
				</td>
			</tr>
			//-->
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'feed-reader' ); ?></button>
		</p>
	</form>
</div>
