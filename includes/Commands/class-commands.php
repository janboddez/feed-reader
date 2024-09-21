<?php

namespace FeedReader\Commands;

use FeedReader\Jobs\Poll_Feeds;
use FeedReader\Models\Category;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;
use WP_CLI;

class Commands {
	/**
	 * Subscribes to a feed.
	 *
	 * <url>
	 * : The URL of the feed.
	 *
	 * [--cat=<cat>]
	 * : The ID of the category to use. Must belong to the "current" user.
	 *
	 * @param array $args       Arguments.
	 * @param array $assoc_args "Associated" arguments.
	 */
	public function subscribe( $args, $assoc_args ) {
		if ( 0 === get_current_user_id() ) {
			WP_CLI::error( 'Missing user ID.' );
			return;
		}

		$url = trim( $args[0] );
		if ( ! preg_match( '~^https?://~', $url ) ) {
			$url = 'http://' . ltrim( $url, '/' );
		}

		if ( ! wp_http_validate_url( $url ) ) {
			WP_CLI::error( 'Invalid URL.' );
			return;
		}

		if ( ! empty( $assoc_args['cat'] ) && ctype_digit( (string) $assoc_args['cat'] ) ) {
			// Look up category.
			$category = Category::find( (int) $assoc_args['cat'] );

			if ( ! $category ) {
				// Category doesn't exist or doesn't belong to the current user.
				WP_CLI::warning( 'Invalid category ID.' );
			}
		}

		$feed_id = Feed::exists( esc_url_raw( $url ) );

		if ( ! $feed_id ) {
			$feed_id = Feed::insert(
				array(
					'url'         => esc_url_raw( $url ),
					'name'        => preg_replace( '~^www~', '', wp_parse_url( $url, PHP_URL_HOST ) ),
					'category_id' => isset( $category->id ) ? $category->id : null,
					'user_id'     => get_current_user_id(),
				)
			);
		} else {
			WP_CLI::warning( 'The user with ID ' . get_current_user_id() . ' is already subscribed to the feed at ' . esc_url_raw( $url ) . '.' );
		}

		if ( $feed_id ) {
			$feed = Feed::find( $feed_id );

			if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) ) {
				Feed::fetch_favicon( $feed );
			}

			WP_CLI::line( 'Polling the feed at ' . esc_url_raw( $url ) . '.' );
			Poll_Feeds::poll_feed( $feed );

			WP_CLI::success( 'All done!' );
		} else {
			WP_CLI::warning( 'Feed could not be added.' );
		}
	}

	/**
	 * Permanently deletes old, "stale" entries.
	 *
	 * [--days=<days>]
	 * : Delete only entries older than this number of days. Default: 30.
	 *
	 * @param array $args       (Optional) arguments.
	 * @param array $assoc_args (Optional) "associated" arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$max = isset( $assoc_args['days'] ) && ctype_digit( (string) $assoc_args['days'] )
			? (int) $assoc_args['days']
			: 30;
		$max = date( 'Y-m-d H:i:s', time() - $max * DAY_IN_SECONDS );

		WP_CLI::line( "Looking at entries older than {$max} UTC." );

		global $wpdb;

		// We're looking for items that are read or "deleted" and not currently
		// in a feed, and that are over `$days` days old.
		$sql   = sprintf( 'SELECT COUNT(*) FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $max, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		WP_CLI::line( "Found {$count} entries to be removed." );

		if ( ! empty( $count ) ) {
			$sql = sprintf( 'DELETE FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
			$wpdb->query( $wpdb->prepare( $sql, $max, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		}

		WP_CLI::success( 'All done!' );
	}

	/**
	 * Mark **all** entries (for all users) as read.
	 *
	 * @subcommand mark-read
	 *
	 * @param array $args       (Optional) arguments.
	 * @param array $assoc_args (Optional) "associated" arguments.
	 */
	public function mark_read( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wpdb;

		$data = array(
			'is_read'     => 1,
			'modified_at' => current_time( 'mysql', 1 ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = (int) $wpdb->update( Entry::table(), $data, array( 'is_read' => 0 ) );

		WP_CLI::success( "All done! Modified {$rows} row(s)." );
	}

	/**
	 * Updates WordPress' built-in bookmarks' icons, if they're also in our feed
	 * list.
	 *
	 * @subcommand update-bookmark-icons
	 */
	public function update_bookmark_icons() {
		$links = get_bookmarks();

		if ( empty( $links ) || ! is_array( $links ) ) {
			WP_CLI::warning( 'No links found.' );
			return;
		}

		global $wpdb;

		foreach ( $links as $link ) {
			if ( empty( $link->link_rss ) ) {
				continue;
			}

			$sql  = sprintf( 'SELECT icon FROM %s WHERE url = %%s LIMIT 1', Feed::table() );
			$icon = $wpdb->get_var( $wpdb->prepare( $sql, esc_url_raw( $link->link_rss ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $icon ) ) {
				WP_CLI::log( 'Could not find feed icon for ' . esc_url_raw( $link->link_rss ) . '.' );
				continue;
			}

			wp_update_link(
				array(
					'link_id'    => $link->link_id,
					'link_image' => $icon,
				)
			);
		}

		WP_CLI::success( 'All done.' );
	}
}
