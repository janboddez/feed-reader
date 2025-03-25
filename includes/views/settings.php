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
				<th scope="row"><?php esc_html_e( 'Show Actions', 'feed-reader' ); ?></th>
				<td>
					<label><input type="checkbox" name="feed_reader_settings[show_actions]" <?php checked( ! empty( $options['show_actions'] ) ); ?> /> <?php esc_html_e( 'Display &ldquo;action buttons&rdquo; below single entries', 'feed-reader' ); ?></label>
					<p class="description"><?php esc_html_e( 'These allow you to reply to, favorite, or bookmark feed entries, directly from the reader.', 'feed-reader' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'feed-reader' ); ?></button>
		</p>
	</form>
</div>
