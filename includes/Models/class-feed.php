<?php

namespace FeedReader\Models;

use FeedReader\Models\Category;

class Feed extends Model {
	/** @var string $table */
	protected static $table = 'feed_reader_feeds';

	public static function paginate( $limit = 15, $search = null ) {
		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s', static::table() );

		if ( $search ) {
			$search = str_replace( '%', '', $search );

			$sql .= $wpdb->prepare(
				' WHERE user_id = %d AND (url LIKE %s OR name LIKE %s) ORDER BY url ASC LIMIT %d OFFSET %d',
				get_current_user_id(),
				"%$search%",
				"%$search%",
				$limit,
				$offset
			);
		} else {
			$sql .= $wpdb->prepare( ' WHERE user_id = %d ORDER BY url ASC LIMIT %d OFFSET %d', get_current_user_id(), $limit, $offset );
		}

		$total = preg_replace( '~^SELECT \*~', 'SELECT COUNT(*)', $sql );
		$total = preg_replace( '~LIMIT \d+ OFFSET \d+$~', '', $total );

		$sql = sprintf(
			'SELECT f.*, c.name AS category_name
			 FROM (%s) AS f
			 LEFT JOIN %s AS c ON c.id = f.category_id
			 ORDER BY f.url',
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
}
