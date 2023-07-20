<?php

namespace FeedReader\Commands;

use FeedReader\Models\Entry;
use WP_CLI;

class Commands {
	/**
	 * Permanently deletes old "stale" entries.
	 *
	 * [--days=<days>]
	 * : Delete only entries older than this number of days.
	 *
	 * @param array $args       (Optional) arguments.
	 * @param array $assoc_args (Optional) "associated" arguments.
	 */
	public function cleanup( $args, $assoc_args ) {
		$days = 30;

		if ( isset( $assoc_args['days'] ) && ctype_digit( (string) $assoc_args['days'] ) ) {
			$days = (int) $assoc_args['days'];
		}

		$max_timestamp = date( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );

		WP_CLI::line( "Looking at entries older than {$max_timestamp} UTC." );

		global $wpdb;

		// We're looking for items that are read or "deleted" and not currently
		// in a feed, and that are over 30 days old.
		$sql   = sprintf( 'SELECT COUNT(*) FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $max_timestamp, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $count ) ) {
			$count = '0';
		}

		WP_CLI::line( "Found {$count} entries to be removed." );

		$sql = sprintf( 'DELETE FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
		$wpdb->query( $wpdb->prepare( $sql, $max_timestamp, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		WP_CLI::success( 'All done!' );
	}
}
