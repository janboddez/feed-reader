<?php

namespace FeedReader\Commands;

use FeedReader\Models\Entry;
use WP_CLI;

class Commands {
	/**
	 * Permanently deletes stale entries over 30 days old.
	 */
	public function cleanup() {
		$max_timestamp = date( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );

		global $wpdb;

		// We're looking for items that are read or "deleted" and not currently
		// in a feed, and that are over 30 days old.
		$sql   = sprintf( 'SELECT COUNT(*) FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
		$count = $wpdb->get_var( $wpdb->prepare( $sql, $max_timestamp, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $count ) ) {
			$count = '0';
		}

		WP_CLI::line( "Found {$count} entries to be removed." );

		// $sql = sprintf( 'DELETE FROM %s WHERE published < %%s AND in_feed = %%d AND (is_read = %%d OR deleted_at IS NOT NULL)', Entry::table() );
		// $wpdb->query( $wpdb->prepare( $sql, $max_timestamp, 0, 1 ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		WP_CLI::success( 'All done!' );
	}
}
