<?php

namespace FeedReader\Controllers;

use FeedReader\Controllers\Controller;

class Settings_Controller extends Controller {
	public static function edit() {
		static::render( 'settings' );
	}
}
