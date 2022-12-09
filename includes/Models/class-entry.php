<?php

namespace Feed_Reader\Models;

class Entry extends Model {
	/** @var string $table */
	protected static $table = 'feed_reader_entries';

	public static function paginate( $limit = 15, $all = false ) {
		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		global $wpdb;

		if ( $all ) {
			$sql = sprintf(
				'SELECT e.*, f.name AS feed_name
				 FROM (SELECT * FROM %s WHERE deleted_at IS NULL AND user_id = %%d ORDER BY published DESC, id DESC LIMIT %d OFFSET %d) AS e
				 JOIN %s AS f ON f.id = e.feed_id
				 ORDER BY e.published DESC, e.id DESC',
				static::table(),
				$limit,
				$offset,
				Feed::table()
			);
		} else {
			$sql = sprintf(
				'SELECT e.*, f.name AS feed_name
				 FROM (SELECT * FROM %s WHERE is_read = 0 AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC, id DESC LIMIT %d OFFSET %d) AS e
				 JOIN %s AS f ON f.id = e.feed_id
				 ORDER BY e.published DESC, e.id DESC',
				static::table(),
				$limit,
				$offset,
				Feed::table()
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, get_current_user_id() ) );
	}

	public static function count( $all = false ) {
		global $wpdb;

		if ( $all ) {
			$sql = sprintf( 'SELECT COUNT(*) FROM %s WHERE AND deleted_at IS NULL user_id = %%d', static::table() );
		} else {
			$sql = sprintf( 'SELECT COUNT(*) FROM %s WHERE is_read = 0 AND deleted_at IS NULL AND user_id = %%d', static::table() );
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, get_current_user_id() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function find( $id ) {
		global $wpdb;

		$sql = sprintf(
			'SELECT e.*, f.name AS feed_name
			 FROM (SELECT * FROM %s WHERE id = %%d AND deleted_at IS NULL AND user_id = %%d) AS e
			 LEFT JOIN %s AS f ON f.id = e.feed_id',
			static::table(),
			Feed::table()
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $wpdb->prepare( $sql, $id, get_current_user_id() ) );
	}

	public static function delete( $id, $force = false ) {
		if ( $force ) {
			return parent::delete( $id );
		} else {
			return static::update(
				array( 'deleted_at' => current_time( 'mysql', 1 ) ),
				array( 'id' => $id )
			);
		}
	}

	public static function exists( $uid, $feed ) {
		global $wpdb;

		$sql = sprintf( 'SELECT id FROM %s WHERE uid = %%s AND feed_id = %%d AND user_id = %%d', static::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $wpdb->prepare( $sql, $uid, $feed->id, $feed->user_id ) );
	}
}
