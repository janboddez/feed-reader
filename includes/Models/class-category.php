<?php

namespace Feed_Reader\Models;

use Feed_Reader\Models\Feed;

class Category extends Model {
	/** @var string $table */
	protected static $table = 'feed_reader_categories';

	public static function paginate( $limit = 15 ) {
		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		global $wpdb;

		$sql = sprintf(
			'SELECT * FROM %s WHERE user_id = %%d ORDER BY name ASC LIMIT %d OFFSET %d',
			static::table(),
			$limit,
			$offset
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, get_current_user_id() ) );
	}

	public static function all() {
		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s WHERE user_id = %%d ORDER BY name ASC', static::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, get_current_user_id() ) );
	}

	public static function feeds( $id ) {
		global $wpdb;

		$sql = sprintf( 'SELECT id FROM %s WHERE category_id = %%d AND user_id = %%d', Feed::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $id, get_current_user_id() ) );
	}

	public static function entries( $id, $limit = 15, $all = false ) {
		$feeds = static::feeds( $id );

		if ( empty( $feeds ) ) {
			return array();
		}

		global $wpdb;

		$paged  = isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = max( 0, $paged - 1 ) * $limit;

		if ( $all ) {
			$sql = sprintf(
				'SELECT * FROM %s WHERE feed_id IN (%s) AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC LIMIT %d OFFSET %d',
				Entry::table(),
				implode( ',', array_fill( 0, count( $feeds ), '%d' ) ),
				$limit,
				$offset
			);
		} else {
			$sql = sprintf(
				'SELECT * FROM %s WHERE feed_id IN (%s) AND is_read = 0 AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC LIMIT %d OFFSET %d',
				Entry::table(),
				implode( ',', array_fill( 0, count( $feeds ), '%d' ) ),
				$limit,
				$offset
			);
		}

		$args   = array_column( $feeds, 'id' );
		$args[] = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$args
			)
		);
	}

	public static function entries_count( $id, $all = false ) {
		$feeds = static::feeds( $id );

		if ( empty( $feeds ) ) {
			return 0;
		}

		global $wpdb;

		if ( $all ) {
			$sql = sprintf(
				'SELECT COUNT(*) FROM %s WHERE feed_id IN (%s) AND deleted_at IS NULL AND user_id = %%d',
				Entry::table(),
				implode( ',', array_fill( 0, count( $feeds ), '%d' ) )
			);
		} else {
			$sql = sprintf(
				'SELECT COUNT(*) FROM %s WHERE feed_id IN (%s) AND is_read = 0 AND deleted_at IS NULL AND user_id = %%d',
				Entry::table(),
				implode( ',', array_fill( 0, count( $feeds ), '%d' ) )
			);
		}

		$args   = array_column( $feeds, 'id' );
		$args[] = get_current_user_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				$sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$args
			)
		);
	}

	public static function exists( $name ) {
		global $wpdb;

		$sql = sprintf( 'SELECT id FROM %s WHERE name = %%s AND user_id = %%d', static::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $name, get_current_user_id() ) );
	}
}
