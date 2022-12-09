<?php

namespace Feed_Reader;

use Feed_Reader\Controllers\Category_Controller;
use Feed_Reader\Controllers\Entry_Controller;
use Feed_Reader\Controllers\Feed_Controller;
use Feed_Reader\Controllers\OPML_Controller;

class Router {
	public function register() {
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'parent_file', array( $this, 'highlight_menu_page' ) );

		add_action( 'admin_post_feed_reader_categories_store', array( Category_Controller::class, 'store' ) );
		add_action( 'admin_post_feed_reader_categories_update', array( Category_Controller::class, 'update' ) );
		add_action( 'admin_post_feed_reader_categories_delete', array( Category_Controller::class, 'delete' ) );

		add_action( 'admin_post_feed_reader_feeds_store', array( Feed_Controller::class, 'store' ) );
		add_action( 'admin_post_feed_reader_feeds_update', array( Feed_Controller::class, 'update' ) );
		add_action( 'admin_post_feed_reader_feeds_delete', array( Feed_Controller::class, 'delete' ) );

		add_action( 'admin_post_feed_reader_opml_import', array( OPML_Controller::class, 'import' ) );

		add_action( 'wp_ajax_feed_reader_entries_delete', array( Entry_Controller::class, 'delete' ) );
		add_action( 'wp_ajax_feed_reader_entries_mark_read', array( Entry_Controller::class, 'mark_read' ) );
		add_action( 'wp_ajax_feed_reader_entries_mark_unread', array( Entry_Controller::class, 'mark_unread' ) );
	}

	public function create_menu() {
		add_menu_page(
			esc_html( $this->get_title() ),
			__( 'Reader', 'feed-reader' ),
			'activate_plugins',
			'feed-reader',
			array( Entry_Controller::class, 'index' ),
			'dashicons-rss'
		);

		add_submenu_page(
			'feed-reader',
			esc_html( $this->get_title() ),
			__( 'View Entry', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-entries-view',
			array( Entry_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Feeds', 'feed-reader' ),
			__( 'Feeds', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-feeds',
			array( Feed_Controller::class, 'index' )
		);

		add_submenu_page(
			'feed-reader',
			esc_html( $this->get_title() ),
			__( 'View Feed', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-feeds-view',
			array( Feed_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Add Feed', 'feed-reader' ),
			__( 'Add Feed', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-feeds-create',
			array( Feed_Controller::class, 'create' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Edit Feed', 'feed-reader' ),
			__( 'Edit Feed', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-feeds-edit',
			array( Feed_Controller::class, 'edit' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Categories', 'feed-reader' ),
			__( 'Categories', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-categories',
			array( Category_Controller::class, 'index' )
		);

		add_submenu_page(
			'feed-reader',
			esc_html( $this->get_title() ),
			__( 'View Category', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-categories-view',
			array( Category_Controller::class, 'view' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Add Category', 'feed-reader' ),
			__( 'Add Category', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-categories-create',
			array( Category_Controller::class, 'create' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Edit Category', 'feed-reader' ),
			__( 'Edit Category', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-categories-edit',
			array( Category_Controller::class, 'edit' )
		);

		add_submenu_page(
			'feed-reader',
			__( 'Import OPML', 'feed-reader' ),
			__( 'Import OPML', 'feed-reader' ),
			'activate_plugins',
			'feed-reader-opml-import',
			array( OPML_Controller::class, 'upload' )
		);

		add_action( 'admin_head', array( $this, 'remove_submenu_pages' ) );
	}

	public function remove_submenu_pages() {
		remove_submenu_page( 'feed-reader', 'feed-reader-entries-view' );
		remove_submenu_page( 'feed-reader', 'feed-reader-feeds-view' );
		remove_submenu_page( 'feed-reader', 'feed-reader-feeds-create' );
		remove_submenu_page( 'feed-reader', 'feed-reader-feeds-edit' );
		remove_submenu_page( 'feed-reader', 'feed-reader-categories-view' );
		remove_submenu_page( 'feed-reader', 'feed-reader-categories-create' );
		remove_submenu_page( 'feed-reader', 'feed-reader-categories-edit' );
	}

	public function highlight_menu_page( $parent_file ) {
		global $submenu_file;

		if ( 'feed-reader' !== $parent_file ) {
			return $parent_file;
		}

		$page = isset( $_GET['page'] ) ? $_GET['page'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( preg_match( '~^feed-reader-(entries|feeds|categories)~', $page, $matches ) ) {
			if ( 'entries' === $matches[1] ) {
				$submenu_file = 'feed-reader'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				$submenu_file = "feed-reader-{$matches[1]}"; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		return $parent_file;
	}

	public function enqueue_scripts( $hook_suffix ) {
		if ( false !== strpos( $hook_suffix, 'feed-reader' ) ) {
			// Enqueue CSS and JS.
			wp_enqueue_style( 'feed-reader-fonts', plugins_url( '/assets/fonts.css', __DIR__ ), array(), \Feed_Reader\Feed_Reader::PLUGIN_VERSION );
			wp_enqueue_style( 'feed-reader', plugins_url( '/assets/style.css', __DIR__ ), array( 'feed-reader-fonts' ), \Feed_Reader\Feed_Reader::PLUGIN_VERSION );

			wp_enqueue_script( 'feed-reader', plugins_url( '/assets/feed-reader.js', __DIR__ ), array( 'jquery' ), \Feed_Reader\Feed_Reader::PLUGIN_VERSION, true );
			wp_localize_script(
				'feed-reader',
				'feed_reader_obj',
				array(
					'confirm'     => esc_attr__( 'Are you sure?', 'feed-reader' ),
					'mark_read'   => esc_attr__( 'Mark as read', 'feed-reader' ),
					'mark_unread' => esc_attr__( 'Mark as unread', 'feed-reader' ),
					'all_done'    => esc_attr__( 'It looks like you&rsquo;ve all caught up!', 'feed-reader' ),
				)
			);
		}
	}

	private function get_title() {
		$controller = $this->get_controller();
		$method     = $this->get_method();

		$title = ucwords( $controller );

		if ( in_array( $method, array( 'view', 'edit' ), true ) ) {
			$model = $this->get_current_model( $controller );

			if ( ! empty( $model->name ) ) {
				return $model->name;
			} elseif ( ! empty( $model->summary ) ) {
				// @todo: Shorten further?
				return $model->summary;
			}

			// Singular for view and edit screens.
			$title = ucwords( \Feed_Reader\singularize( $controller ) );
		}

		return $title;
	}

	private function get_current_model( $controller ) {
		if ( empty( $_GET['id'] ) || ! ctype_digit( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return null;
		}

		$model  = '\\Feed_Reader\\Models\\' . ucwords( \Feed_Reader\singularize( $controller ) );
		$result = $model::find( $_GET['id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $result ) {
			wp_cache_set( 'feed-reader:model', null );
			return null;
		}

		wp_cache_set( 'feed-reader:model', $result );
		return $result;
	}

	private function get_controller() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['page'] ) && preg_match( '~^feed-reader-categories~', $_GET['page'] ) ) {
			return 'categories';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['page'] ) && preg_match( '~^feed-reader-feeds~', $_GET['page'] ) ) {
			return 'feeds';
		}

		return 'entries';
	}

	private function get_method() {
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = preg_replace( '~^feed-reader-(?:entries|feeds|categories)-~', '', $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( in_array( $action, array( 'view', 'edit' ), true ) ) {
				return $action;
			}
		}

		return 'index';
	}
}
