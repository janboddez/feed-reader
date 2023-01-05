<?php

namespace FeedReader\Controllers;

abstract class Controller {
	protected static function render( $name, $data = null ) {
		if ( ! empty( $data ) ) {
			extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		// Validate view path. Now, as all view paths are currently hardcoded,
		// this may not be needed, but whatevs.
		$allowed = realpath( __DIR__ . '/../views' ); // `$dir` **must** be the `views` directory (or a (sub)subdirectory).
		$path    = realpath( __DIR__ . "/../views/$name.php" );

		if (
			dirname( $path ) !== $allowed &&
			dirname( dirname( $path ) ) !== $allowed &&
			dirname( dirname( dirname( $path ) ) ) !== $allowed
		) {
			wp_die( esc_html_e( 'Invalid view.', 'feed-reader' ) );
		}

		// We don't check whether the file exists. If we tested correctly, it will.
		require __DIR__ . "/../views/$name.php";
	}
}
