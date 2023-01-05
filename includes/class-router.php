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
		add_option( 'feed_reader_settings', array() );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );

		add_action( 'admin_bar_menu', array( __CLASS__, 'top_bar_menu' ), 40 );
		add_action( 'admin_menu', array( __CLASS__, 'create_menu' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );

		add_action( 'admin_footer', array( __CLASS__, 'include_icon_sprites' ) );

		add_filter( 'parent_file', array( __CLASS__, 'highlight_menu_page' ) );

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

	public static function login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		$options = get_option( 'feed_reader_settings' ); /** @todo: We should make this a user-specific setting. */

		if ( empty( $options['login_redirect'] ) ) {
			return $redirect_to;
		}

		if ( admin_url() !== $requested_redirect_to ) {
			// When we got here through a specific request, don't redirect.
			return $redirect_to;
		}

		if ( is_wp_error( $user ) || ! user_can( $user, 'edit_others_posts' ) ) {
			// Don't redirect users that wouldn't have access.
			return $redirect_to;
		}

		return \FeedReader\Helpers\get_url();
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

	public static function register_settings() {
		register_setting(
			'feed-reader-settings-group',
			'feed_reader_settings',
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) )
		);
	}

	public static function sanitize_settings( $settings ) {
		return array(
			'collapse_menu'      => isset( $settings['collapse_menu'] ) ? true : false,
			'hide_sidebar'       => isset( $settings['hide_sidebar'] ) ? true : false,
			'show_actions'       => isset( $settings['show_actions'] ) ? true : false,
			'image_proxy'        => isset( $settings['image_proxy'] ) ? true : false,
			'image_proxy_secret' => isset( $settings['image_proxy_secret'] ) ? $settings['image_proxy_secret'] : '',
			'login_redirect'     => isset( $settings['login_redirect'] ) ? true : false,
		);
	}

	public static function top_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'feed-reader',
				'title' => sprintf( '<span class="ab-icon" aria-hidden="true"></span> <span class="ab-label">%s</span>', esc_html__( 'Reader', 'feed-reader' ) ),
				'href'  => esc_url( \FeedReader\Helpers\get_url() ),
			)
		);
	}

	public static function admin_head() {
		/** @todo: Move these to our CSS sheet (and enqueue it _everywhere_, which we currently don't)? */
		?>
		<style type="text/css">
		#wp-admin-bar-feed-reader .ab-icon::before {
			content: "\f303";
			top: 2px;
		}

		@media screen and (max-width: 782px) {
			#wpadminbar li#wp-admin-bar-feed-reader {
				display: block;
			}

			#wp-admin-bar-feed-reader .ab-icon {
				scale: 0.9;
			}
		}
		</style>
		<?php
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

	public static function enqueue_scripts( $hook_suffix ) {
		if ( false !== strpos( $hook_suffix, 'feed-reader' ) ) {
			wp_enqueue_style( 'feed-reader-fonts', plugins_url( '/assets/fonts.css', __DIR__ ), array(), \FeedReader\Reader::PLUGIN_VERSION );
			wp_enqueue_style( 'feed-reader', plugins_url( '/assets/style.css', __DIR__ ), array( 'feed-reader-fonts' ), \FeedReader\Reader::PLUGIN_VERSION );

			wp_enqueue_script( 'feed-reader', plugins_url( '/assets/feed-reader.js', __DIR__ ), array( 'jquery' ), \FeedReader\Reader::PLUGIN_VERSION, true );
			wp_localize_script(
				'feed-reader',
				'feed_reader_obj',
				array(
					'confirm'     => esc_attr__( 'Are you sure?', 'feed-reader' ),
					'mark_read'   => esc_attr__( 'Mark read', 'feed-reader' ),
					'mark_unread' => esc_attr__( 'Mark unread', 'feed-reader' ),
					'all_done'    => sprintf(
						'<section class="hentry note"><div class="entry-summary"><p>%s</p></div></section>',
						__( 'Seems you&rsquo;re all caught up!', 'feed-reader' )
					),
				)
			);
		}

		$options = get_option( 'feed_reader_settings' );

		if ( ! empty( $options['collapse_menu'] ) && in_array( $hook_suffix, array( 'toplevel_page_feed-reader', 'reader_page_feed-reader/entries/view', 'reader_page_feed-reader/feeds/view', 'reader_page_feed-reader/categories/view' ), true ) ) {
			add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		}
	}

	public static function body_class( $classes ) {
		$classes   = explode( ' ', $classes );
		$classes[] = 'folded';

		return implode( ' ', array_unique( $classes ) );
	}

	public static function include_icon_sprites() {
		/** @todo: Load these only where relevant. */
		$svg_icons = __DIR__ . '/../assets/icons.svg';

		if ( is_file( $svg_icons ) ) {
			require $svg_icons;
		}
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
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$method = preg_replace( '~^feed-reader/(?:entries|feeds|categories)/~', '', $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( in_array( $method, array( 'view', 'edit' ), true ) ) {
				return $method;
			}
		}

		return 'index';
	}
}
