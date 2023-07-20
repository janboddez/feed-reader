<?php

namespace FeedReader\Commands;

use FeedReader\Models\Entry;
use WP_CLI;

class Commands {
	public function cleanup() {
		// Do something.
		WP_CLI::success( 'All done!' );
	}
}
