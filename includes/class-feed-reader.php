<?php

namespace Feed_Reader;

use Feed_Reader\Doctrine\Inflector\InflectorFactory;
use Feed_Reader\Jobs\Poll_Feeds;
use Feed_Reader\Router;

class Feed_Reader {
	const DB_VERSION     = '1.0';
	const PLUGIN_VERSION = '0.1.0';

	/** @var Router $router */
	private $router;

	/** @var Feed_Reader $instance */
	private static $instance;

	/** @var InflectorFactory $inflector */
	private static $inflector;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function inflector() {
		if ( null === self::$inflector ) {
			self::$inflector = InflectorFactory::create()->build();
		}

		return self::$inflector;
	}

	public function register() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'feed_reader_poll_feeds', array( Poll_Feeds::class, 'poll_feeds' ) );

		$this->admin = new Router();
		$this->admin->register();
	}

	public function init() {
		if ( false === wp_next_scheduled( 'feed_reader_poll_feeds' ) ) {
			wp_schedule_event( time(), 'hourly', 'feed_reader_poll_feeds' );
		}

		if ( get_option( 'feed_reader_db_version' ) !== self::DB_VERSION ) {
			$this->migrate();
		}
	}

	public function migrate() {
		if ( ! function_exists( '\\dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		ob_start();
		include __DIR__ . '/database/schema.php';
		$sql = ob_get_clean();

		dbDelta( $sql );

		update_option( 'feed_reader_db_version', self::DB_VERSION );
	}
}
