<?php

namespace FeedReader;

use FeedReader\Commands\Commands;
use FeedReader\Helpers\Image_Proxy;
use FeedReader\Jobs\Poll_Feeds;
use FeedReader\Models\Category;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;
use FeedReader\Router;

class Reader {
	const PLUGIN_VERSION = '0.3.0';
	const DB_VERSION     = '3';

	/** @var Feed_Reader $instance */
	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function register() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// Register our new CLI command.
			\WP_CLI::add_command( 'reader', Commands::class );
		}

		// Cron events, etc.
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		add_action( 'feed_reader_poll_feeds', array( Poll_Feeds::class, 'poll_feeds' ) );
		add_action( 'init', array( $this, 'init' ) );

		// Settings.
		add_option( 'feed_reader_settings', array() );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		add_action( 'rest_api_init', array( Image_Proxy::class, 'register' ) );

		// Additional admin styles and functions.
		add_action( 'admin_bar_menu', array( $this, 'top_bar_menu' ), 40 ); // After "home" button.
		add_action( 'wp_before_admin_bar_render', array( $this, 'new_content' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'wp_head' ) );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
		add_action( 'admin_footer', array( $this, 'include_icon_sprites' ) );

		Router::register();
	}

	public function add_cron_schedule( $schedules ) {
		$schedules['every_15_minutes'] = array(
			'interval' => 900,
			'display'  => __( 'Once every 15 minutes', 'feed-reader' ),
		);

		return $schedules;
	}

	public function init() {
		if ( false === wp_next_scheduled( 'feed_reader_poll_feeds' ) ) {
			wp_schedule_event( time(), 'every_15_minutes', 'feed_reader_poll_feeds' );
		}

		if ( get_option( 'feed_reader_db_version' ) !== self::DB_VERSION ) {
			$this->migrate();
		}
	}

	public function register_settings() {
		// General plugin settings.
		register_setting(
			'feed-reader-settings-group',
			'feed_reader_settings',
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);

		// User-specific settings.
		add_action( 'show_user_profile', array( $this, 'profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_profile_fields' ) );
	}

	public function sanitize_settings( $settings ) {
		return array(
			'show_actions'       => isset( $settings['show_actions'] ) ? true : false,
			'image_proxy'        => isset( $settings['image_proxy'] ) ? true : false,
			'image_proxy_secret' => isset( $settings['image_proxy_secret'] ) ? $settings['image_proxy_secret'] : '',
		);
	}

	public function profile_fields( $user ) {
		if ( ! user_can( $user, 'edit_others_posts' ) ) {
			return;
		}

		$user_settings = get_user_meta( $user->ID, 'feed_reader_settings', true );
		?>
			<div id="feed-reader-section">
				<h2><?php esc_html_e( 'Feed Reader', 'feed-reader' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Collapse Menu', 'feed-reader' ); ?></th>
						<td><label><input type="checkbox" name="feed_reader_settings[collapse_menu]" <?php checked( ! empty( $user_settings['collapse_menu'] ) ); ?> />
						<?php esc_html_e( 'Auto-collapse WordPress&rsquo; admin menu', 'feed-reader' ); ?></label>
						<p class="description"><?php esc_html_e( 'Avoid distraction by auto-collapsing WordPress&rsquo; side menu while reading.', 'feed-reader' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'System Fonts', 'feed-reader' ); ?></th>
						<td><label><input type="checkbox" name="feed_reader_settings[system_fonts]" <?php checked( ! empty( $user_settings['system_fonts'] ) ); ?> />
						<?php esc_html_e( 'Use system fonts rather than Feed Reader&rsquo;s', 'feed-reader' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hide Sidebar', 'feed-reader' ); ?></th>
						<td><label><input type="checkbox" name="feed_reader_settings[hide_sidebar]" <?php checked( ! empty( $user_settings['hide_sidebar'] ) ); ?> />
						<?php esc_html_e( 'Hide the category and feed sidebar', 'feed-reader' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Redirect After Login', 'feed-reader' ); ?></th>
						<td><label><input type="checkbox" name="feed_reader_settings[login_redirect]" <?php checked( ! empty( $user_settings['login_redirect'] ) ); ?> />
						<?php esc_html_e( 'After logging in, get sent to your feed reader rather than WordPress&rsquo; dashboard', 'feed-reader' ); ?></label></td>
					</tr>
				</table>
		</div>
		<?php
	}

	public function save_profile_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			// Unauthorized.
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), "update-user_{$user_id}" ) ) {
			// Invalid nonce.
			return;
		}

		if ( ! user_can( $user_id, 'edit_others_posts' ) ) {
			// Unsupported role.
			return;
		}

		$settings = ! empty( $_POST['feed_reader_settings'] )
			? wp_unslash( $_POST['feed_reader_settings'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$user_settings = array(
			'collapse_menu'  => isset( $settings['collapse_menu'] ) ? true : false,
			'system_fonts'   => isset( $settings['system_fonts'] ) ? true : false,
			'hide_sidebar'   => isset( $settings['hide_sidebar'] ) ? true : false,
			'login_redirect' => isset( $settings['login_redirect'] ) ? true : false,
		);

		update_user_meta( $user_id, 'feed_reader_settings', $user_settings );
	}

	public function top_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'feed-reader',
				'title' => sprintf( '<span class="ab-icon" aria-hidden="true"></span> <span class="ab-label">%s (%d)</span>', esc_html__( 'Reader', 'feed-reader' ), Entry::count() ),
				'href'  => esc_url( \FeedReader\Helpers\get_url() ),
			)
		);
	}

	public function new_content() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		global $wp_admin_bar;

		$args = array(
			'id'     => 'feed-reader-new-feed',
			'title'  => esc_html__( 'Feed', 'feed-reader' ),
			'parent' => 'new-content',
			'href'   => esc_url_raw( \FeedReader\Helpers\get_url( 'feeds', 'create' ) ),
		);

		$wp_admin_bar->add_node( $args );
	}

	public function wp_head() {
		/** @todo: Move these to our CSS sheet. */
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

		body.profile-php #application-passwords-section + #feed-reader-section h2,
		body.user-edit-php #application-passwords-section + #feed-reader-section h2 {
			margin-block-start: 2em;
		}
		</style>
		<?php
	}

	public function enqueue_scripts( $hook_suffix ) {
		if ( false !== strpos( $hook_suffix, 'feed-reader' ) ) {
			$user_settings = get_user_meta( get_current_user_id(), 'feed_reader_settings', true );

			if ( empty( $user_settings['system_fonts'] ) ) {
				wp_enqueue_style( 'feed-reader-fonts', plugins_url( '/assets/fonts.css', __DIR__ ), array(), self::PLUGIN_VERSION );
			}

			// wp_enqueue_style( 'highlight-js-css', plugins_url( '/assets/highlight.min.css', __DIR__ ), array(), self::PLUGIN_VERSION ); // Our "own" highlight.js styles.
			wp_enqueue_style( 'feed-reader', plugins_url( '/assets/style.css', __DIR__ ), array(), self::PLUGIN_VERSION );

			// wp_enqueue_script( 'highlight-js', plugins_url( '/assets/highlight.min.js', __DIR__ ), array(), '11.7.0', true );
			wp_enqueue_script( 'feed-reader', plugins_url( '/assets/feed-reader.js', __DIR__ ), array( 'jquery' /*, 'highlight-js'*/ ), self::PLUGIN_VERSION, true );

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

		$user_settings = get_user_meta( get_current_user_id(), 'feed_reader_settings', true );

		if (
			! empty( $user_settings['collapse_menu'] ) &&
			in_array( $hook_suffix, array( 'toplevel_page_feed-reader', 'reader_page_feed-reader/entries/view', 'reader_page_feed-reader/feeds/view', 'reader_page_feed-reader/categories/view' ), true )
		) {
			add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		}
	}

	public function body_class( $classes ) {
		$classes   = explode( ' ', $classes );
		$classes[] = 'folded';

		return implode( ' ', array_unique( $classes ) );
	}

	public function include_icon_sprites() {
		/** @todo: Load these only where relevant. */
		$svg_icons = __DIR__ . '/../assets/icons.svg';

		if ( is_file( $svg_icons ) ) {
			require $svg_icons;
		}
	}

	public static function login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( admin_url() !== $requested_redirect_to ) {
			// When we got here through a specific request, don't redirect.
			return $redirect_to;
		}

		if ( is_wp_error( $user ) || ! user_can( $user, 'edit_others_posts' ) ) {
			// Don't redirect users that wouldn't have access.
			return $redirect_to;
		}

		$user_settings = get_user_meta( $user->ID, 'feed_reader_settings', true );

		if ( empty( $user_settings['login_redirect'] ) ) {
			// Do nothing.
			return $redirect_to;
		}

		return esc_url_raw( \FeedReader\Helpers\get_url() );
	}

	protected function migrate() {
		if ( ! function_exists( '\\dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		ob_start();
		include __DIR__ . '/database/schema.php';
		$sql = ob_get_clean();

		dbDelta( $sql );

		if ( (int) get_option( 'feed_reader_db_version' ) < 2 ) {
			global $wpdb;

			// Generate a unique UID.
			do {
				$uid    = bin2hex( openssl_random_pseudo_bytes( 16 ) );
				$sql    = sprintf( 'SELECT id FROM %s WHERE uid = %%s LIMIT 1', Category::table() );
				$exists = $wpdb->get_var( $wpdb->prepare( $sql, $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			} while ( $exists );

			Category::insert(
				array(
					'name'    => 'General',
					'uid'     => $uid,
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
