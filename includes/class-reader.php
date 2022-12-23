<?php

namespace FeedReader;

use FeedReader\Helpers\Image_Proxy;
use FeedReader\Jobs\Poll_Feeds;
use FeedReader\Models\Category;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;
use FeedReader\Router;

class Reader {
	const PLUGIN_VERSION = '0.1.0';
	const DB_VERSION     = '2';

	/** @var Router $router */
	private $router;

	/** @var Feed_Reader $instance */
	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'feed_reader_poll_feeds', array( Poll_Feeds::class, 'poll_feeds' ) );

		$this->router = new Router();
		$this->router->register();

		add_action( 'rest_api_init', array( Image_Proxy::class, 'register' ) );
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

		if ( (int) get_option( 'feed_reader_db_version' ) < 2 ) {
			Category::insert(
				array(
					'name'    => 'General',
					'user_id' => get_current_user_id(),
				)
			);

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				sprintf(
					'ALTER table %s ADD FOREIGN KEY (category_id) REFERENCES %s(id)', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
					Feed::table(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					Category::table() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);

			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				sprintf(
					'ALTER table %s ADD FOREIGN KEY (feed_id) REFERENCES %s(id) ON DELETE CASCADE', // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
					Entry::table(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					Feed::table() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				)
			);
		}

		update_option( 'feed_reader_db_version', self::DB_VERSION );
	}
}
