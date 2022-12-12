<?php

namespace FeedReader\Controllers;

abstract class Controller {
	protected static function render( $name, $data = null ) {
		if ( ! empty( $data ) ) {
			extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		require __DIR__ . "/../views/$name.php";
	}
}
