<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Import OPML', 'feed-reader' ); ?></h1>

	<form action="admin-post.php" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'feed-reader-opml:import' ); ?>
		<input type="hidden" name="action" value="feed_reader_opml_import">

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="opml-file"><?php esc_html_e( 'OPML File', 'feed-reader' ); ?></label></th>
				<td>
					<input type="file" name="opml_file" id="opml-file" accept="text/xml">
					<p class="description"><?php esc_html_e( 'OPML file to be imported.', 'feed-reader' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit"><?php submit_button( __( 'Import OPML', 'feed-reader' ), 'primary', 'submit', false ); ?></p>
	</form>
</div>
