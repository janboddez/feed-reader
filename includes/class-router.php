<?php

namespace FeedReader;

use FeedReader\Controllers\Category_Controller;
use FeedReader\Controllers\Entry_Controller;
use FeedReader\Controllers\Feed_Controller;
use FeedReader\Controllers\OPML_Controller;
use FeedReader\Controllers\Post_Controller;
use FeedReader\Controllers\Settings_Controller;

class Router {
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'create_menu' ) ); // `GET` routes.
		add_filter( 'parent_file', array( __CLASS__, 'highlight_menu_page' ) );

		// `POST` routes.
		add_action( 'admin_post_feed_reader_categories_store', array( Category_Controller::class, 'store' ) );
		add_action( 'admin_post_feed_reader_categories_update', array( Category_Controller::class, 'update' ) );
		add_action( 'admin_post_feed_reader_categories_delete', array( Category_Controller::class, 'delete' ) );

		add_action( 'admin_post_feed_reader_feeds_store', array( Feed_Controller::class, 'store' ) );
		add_action( 'admin_post_feed_reader_feeds_update', array( Feed_Controller::class, 'update' ) );
		add_action( 'admin_post_feed_reader_feeds_delete', array( Feed_Controller::class, 'delete' ) );
		add_action( 'admin_post_feed_reader_feeds_mark_read', array( Feed_Controller::class, 'mark_read' ) );

		add_action( 'admin_post_feed_reader_opml_import', array( OPML_Controller::class, 'import' ) );
		add_action( 'admin_post_feed_reader_opml_export', array( OPML_Controller::class, 'export' ) );

		add_action( 'wp_ajax_feed_reader_entries_delete', array( Entry_Controller::class, 'delete' ) );
		add_action( 'wp_ajax_feed_reader_entries_mark_read', array( Entry_Controller::class, 'mark_read' ) );
		add_action( 'wp_ajax_feed_reader_entries_mark_unread', array( Entry_Controller::class, 'mark_unread' ) );

		add_action( 'wp_ajax_feed_reader_post', array( Post_Controller::class, 'process' ) );

		add_action( 'wp_ajax_feed_reader_feeds_discover', array( Feed_Controller::class, 'discover' ) );
	}

	public static function create_menu() {
		add_menu_page(
			esc_html( static::get_title() ),
			__( 'Reader', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader',
			array( Entry_Controller::class, 'index' ),
			'dashicons-rss',
			2
		);

		add_submenu_page(
			'feed-reader',
			esc_html( static::get_title() ),
			__( 'View Entry', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/entries/view',
			array( Entry_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Feeds', 'feed-reader' ),
			__( 'Feeds', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/feeds',
			array( Feed_Controller::class, 'index' )
		);

		add_submenu_page(
			'feed-reader',
			esc_html( static::get_title() ),
			__( 'View Feed', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/feeds/view',
			array( Feed_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Add Feed', 'feed-reader' ),
			__( 'Add Feed', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/feeds/create',
			array( Feed_Controller::class, 'create' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Edit Feed', 'feed-reader' ),
			__( 'Edit Feed', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/feeds/edit',
			array( Feed_Controller::class, 'edit' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Categories', 'feed-reader' ),
			__( 'Categories', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/categories',
			array( Category_Controller::class, 'index' )
		);

		add_submenu_page(
			'feed-reader',
			esc_html( static::get_title() ),
			__( 'View Category', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/categories/view',
			array( Category_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Add Category', 'feed-reader' ),
			__( 'Add Category', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/categories/create',
			array( Category_Controller::class, 'create' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Edit Category', 'feed-reader' ),
			__( 'Edit Category', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/categories/edit',
			array( Category_Controller::class, 'edit' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Import &amp; Export', 'feed-reader' ),
			__( 'Import &amp; Export', 'feed-reader' ),
			'edit_others_posts',
			'feed-reader/opml',
			array( OPML_Controller::class, 'opml' )
		);

		add_action( 'admin_head', array( __CLASS__, 'remove_submenu_pages' ) );

		add_submenu_page(
			'feed-reader',
			__( 'Settings', 'feed-reader' ),
			__( 'Settings', 'feed-reader' ),
			'activate_plugins',
			'feed-reader/settings',
			array( Settings_Controller::class, 'edit' )
		);
	}

	public static function remove_submenu_pages() {
		remove_submenu_page( 'feed-reader', 'feed-reader/entries/view' );
		remove_submenu_page( 'feed-reader', 'feed-reader/feeds/view' );
		remove_submenu_page( 'feed-reader', 'feed-reader/feeds/create' );
		remove_submenu_page( 'feed-reader', 'feed-reader/feeds/edit' );
		remove_submenu_page( 'feed-reader', 'feed-reader/categories/view' );
		remove_submenu_page( 'feed-reader', 'feed-reader/categories/create' );
		remove_submenu_page( 'feed-reader', 'feed-reader/categories/edit' );
	}

	public static function highlight_menu_page( $parent_file ) {
		global $submenu_file;

		if ( 'feed-reader' !== $parent_file ) {
			return $parent_file;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['page'] ) && false === strpos( $_GET['page'], 'categories' ) && false === strpos( $_GET['page'], 'feeds' ) ) {
			return $parent_file;
		}

		$controller = static::get_controller();

		if ( 'entries' === $controller ) {
			$submenu_file = 'feed-reader'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			$submenu_file = "feed-reader/{$controller}"; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $parent_file;
	}

	protected static function get_title() {
		$controller = static::get_controller();
		$method     = static::get_method();

		if ( 'entries' === $controller && 'index' === $method ) {
			if ( isset( $_GET['all'] ) && '1' === $_GET['all'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return esc_html__( 'All Entries', 'feed-reader' );
			} else {
				return esc_html__( 'Unread', 'feed-reader' );
			}
		}

		$title = ucwords( $controller );

		if ( in_array( $method, array( 'view', 'edit' ), true ) ) {
			$model = static::get_model( $controller );

			if ( ! empty( $model->name ) ) {
				return $model->name;
			} elseif ( ! empty( $model->summary ) ) {
				// @todo: Shorten further?
				return $model->summary;
			}

			// Singular for view and edit screens.
			$title = ucwords( \FeedReader\Helpers\singularize( $controller ) );
		}

		return $title;
	}

	protected static function get_model( $controller ) {
		$result = wp_cache_get( 'feed-reader:model' );

		if ( false !== $result ) {
			return $result;
		}

		if ( empty( $_GET['id'] ) || ! ctype_digit( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return null;
		}

		$model  = '\\FeedReader\\Models\\' . ucwords( \FeedReader\Helpers\singularize( $controller ) );
		$result = $model::find( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $result ) {
			wp_cache_set( 'feed-reader:model', null );
			return null;
		}

		wp_cache_set( 'feed-reader:model', $result );
		return $result;
	}

	public static function get_controller() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['page'] ) && preg_match( '~^feed-reader/(feeds|categories)~', $_GET['page'], $matches ) ) {
			return $matches[1];
		}

		return 'entries';
	}

	public static function get_method() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['page'] ) && preg_match( '~^feed-reader/(?:entries|feeds|categories)/(view|edit)~', $_GET['page'], $matches ) ) {
			return $matches[1];
		}

		return 'index';
	}
}
