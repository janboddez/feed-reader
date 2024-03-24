<?php

namespace FeedReader\Models;

use FeedReader\Helpers\IcoParser\IcoParser;
use FeedReader\Models\Category;

class Feed extends Model {
	/** @var string $table */
	protected static $table = 'feed_reader_feeds';

	public static function all() {
		global $wpdb;

		$sql = sprintf(
			'SELECT
				f.*,
				c.name AS category_name,
				(SELECT COUNT(*) FROM %s WHERE feed_id = f.id AND is_read = 0 AND user_id = %%d) AS unread_count
			 FROM (SELECT * FROM %s WHERE user_id = %%d) AS f
			 LEFT JOIN %s AS c ON c.id = f.category_id AND c.user_id = %%d
			 ORDER BY category_name ASC, f.url ASC, f.id ASC',
			Entry::table(),
			static::table(),
			Category::table()
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, get_current_user_id(), get_current_user_id(), get_current_user_id() ) );
	}

	public static function paginate( $limit = 15, $search = null ) {
		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s', static::table() );

		if ( $search ) {
			$search = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), $search );

			$sql .= $wpdb->prepare(
				' WHERE user_id = %d AND (url LIKE %s OR name LIKE %s) ORDER BY ISNULL(NULLIF(last_error, "")), name, url ASC LIMIT %d OFFSET %d',
				get_current_user_id(),
				"%$search%",
				"%$search%",
				$limit,
				$offset
			);
		} else {
			$sql .= $wpdb->prepare( ' WHERE user_id = %d ORDER BY ISNULL(NULLIF(last_error, "")), name, url ASC LIMIT %d OFFSET %d', get_current_user_id(), $limit, $offset );
		}

		$total = preg_replace( '~^SELECT \*~', 'SELECT COUNT(*)', $sql );
		$total = preg_replace( '~LIMIT \d+ OFFSET \d+$~', '', $total );

		$sql = sprintf(
			'SELECT f.*, c.name AS category_name
			 FROM (%s) AS f
			 LEFT JOIN %s AS c ON c.id = f.category_id',
			$sql,
			Category::table()
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $sql );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $total );

		return array( $items, $total );
	}

	public static function entries( $id, $limit = 15, $all = false ) {
		global $wpdb;

		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		if ( $all ) {
			$sql = sprintf(
				'SELECT e.*, f.name as feed_name
				 FROM (SELECT * FROM %s WHERE feed_id = %%d AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC LIMIT %d OFFSET %d) AS e
				 LEFT JOIN %s AS f ON f.id = e.feed_id
				 ORDER BY e.published DESC, e.id DESC',
				Entry::table(),
				$limit,
				$offset,
				static::table()
			);
		} else {
			$sql = sprintf(
				'SELECT e.*, f.name as feed_name
				 FROM (SELECT * FROM %s WHERE feed_id = %%d AND is_read = 0 AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC LIMIT %d OFFSET %d) AS e
				 LEFT JOIN %s AS f ON f.id = e.feed_id
				 ORDER BY e.published DESC, e.id DESC',
				Entry::table(),
				$limit,
				$offset,
				static::table()
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $id, get_current_user_id() ) );
	}

	public static function entries_count( $id, $all = false ) {
		global $wpdb;

		if ( $all ) {
			$sql = sprintf( 'SELECT COUNT(*) FROM %s WHERE feed_id = %%d AND deleted_at IS NULL AND user_id = %%d', Entry::table() );
		} else {
			$sql = sprintf( 'SELECT COUNT(*) FROM %s WHERE feed_id = %%d AND is_read = 0 AND deleted_at IS NULL AND user_id = %%d', Entry::table() );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $id, get_current_user_id() ) );
	}

	public static function exists( $url ) {
		global $wpdb;

		$sql = sprintf( 'SELECT id FROM %s WHERE url = %%s AND user_id = %%d', static::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $url, get_current_user_id() ) );
	}

	public static function fetch_favicon( $feed ) {
		if ( empty( $feed->url ) ) {
			return null;
		}

		$upload_dir = wp_get_upload_dir();
		$dir        = trailingslashit( trailingslashit( $upload_dir['basedir'] ) . 'reader/avatars' );

		wp_mkdir_p( $dir );

		if ( ! empty( $feed->site_url ) ) {
			$domain = preg_replace( '~^www.~', '', wp_parse_url( $feed->site_url, PHP_URL_HOST ) );
		} else {
			$domain = preg_replace( '~^www.~', '', wp_parse_url( $feed->url, PHP_URL_HOST ) );
		}

		$file = hash( 'sha256', $domain ) . '.png';
		$file = $dir . $file;
		$url  = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file );

		if ( is_file( $file ) && ( time() - filectime( $file ) ) < MONTH_IN_SECONDS ) {
			// If the file exists, store its URL.
			if ( empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => esc_url_raw( $url ) ),
					array( 'id' => $feed->id )
				);

				set_transient( "feed-reader:feeds:{$feed->id}:avatar", $url, WEEK_IN_SECONDS );
				return;
			}
		}

		$icon     = "https://icons.duckduckgo.com/ip3/{$domain}.ico"; // Note: Despite the extension, may in fact return ICO, PNG, SVG, JPEG or GIF files.
		$response = wp_safe_remote_get(
			esc_url_raw( $icon ),
			array(
				'timeout'    => 11,
				'user-agent' => \FeedReader\Helpers\get_user_agent( $icon ),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( '[Reader] Somehow could not download the image at ' . esc_url_raw( $icon ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( ! empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => null ),
					array( 'id' => $feed->id )
				);

				set_transient( "feed-reader:feeds:{$feed->id}:avatar", null, WEEK_IN_SECONDS );
			}

			return;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			error_log( '[Reader] The page at ' . esc_url_raw( $icon ) . ' returned an error.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( ! empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => null ),
					array( 'id' => $feed->id )
				);
			}

			set_transient( "feed-reader:feeds:{$feed->id}:avatar", null, WEEK_IN_SECONDS );
			return;
		}

		$blob = wp_remote_retrieve_body( $response );

		if ( 0 === strpos( trim( $blob ), '<svg ' ) ) {
			// SVG icon. We don't support these, yet.
			if ( ! empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => null ),
					array( 'id' => $feed->id )
				);
			}

			set_transient( "feed-reader:feeds:{$feed->id}:avatar", null, WEEK_IN_SECONDS );
			return;
		}

		try {
			$im = ( new IcoParser() )->parse( $blob );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// `$im` will remain empty.
		}

		if ( empty( $im ) ) {
			error_log( '[Reader] Invalid image format.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( ! empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => null ),
					array( 'id' => $feed->id )
				);
			}

			set_transient( "feed-reader:feeds:{$feed->id}:avatar", null, WEEK_IN_SECONDS );
			return;
		}

		// Write image data.
		imagepng( $im, $file );

		if ( ! function_exists( 'wp_crop_image' ) ) {
			// Load image functions.
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		if ( ! file_is_valid_image( $file ) || ! file_is_displayable_image( $file ) ) {
			// Somehow not a valid image. Delete it.
			wp_delete_file( $file );

			error_log( '[Reader] Invalid image file: ' . esc_url_raw( $icon ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( ! empty( $feed->icon ) ) {
				static::update(
					array( 'icon' => null ),
					array( 'id' => $feed->id )
				);
			}

			set_transient( "feed-reader:feeds:{$feed->id}:avatar", null, WEEK_IN_SECONDS );
			return;
		}

		// Try to scale down and crop it.
		$image = wp_get_image_editor( $file );

		if ( ! is_wp_error( $image ) ) {
			$image->resize( 32, 32, true );
			$image->save( $file );
		} else {
			error_log( '[Reader] Could not resizing the image at ' . $file . ': ' . $image->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		static::update(
			array( 'icon' => esc_url_raw( $url ) ),
			array( 'id' => $feed->id )
		);

		set_transient( "feed-reader:feeds:{$feed->id}:avatar", $url, WEEK_IN_SECONDS );
	}
}
