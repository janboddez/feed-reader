<?php

namespace FeedReader\Models;

class Entry extends Model {
	/** @var string $table */
	protected static $table = 'feed_reader_entries';

	public static function cursor_paginate( $limit = 15, $all = false, $feed_id = null, $category_id = null ) {
		if ( $feed_id ) {
			$feeds = array( array( 'id' => $feed_id ) );
		} elseif ( $category_id ) {
			// Fetch all of this category's feeds.
			$feeds = Category::feeds( $category_id );
		}

		global $wpdb;

		// Let's start building our (sub)query. We use "soft deletes" and an
		// explicit `user_id` scope.
		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			sprintf( 'SELECT * FROM %s WHERE deleted_at IS NULL AND user_id = %%d', static::table() ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			get_current_user_id()
		);

		if ( ! $all ) {
			// Unread entries only.
			$sql .= $wpdb->prepare( ' AND is_read = %d', 0 );
		}

		if ( ! empty( $feeds ) ) {
			// Let's already escape those feed IDs (even if they come from the
			// database itself).
			$sql .= $wpdb->prepare(
				sprintf( ' AND feed_id IN (%s)', implode( ',', array_fill( 0, count( $feeds ), '%d' ) ) ),
				array_column( $feeds, 'id' )
			);
		}

		// And now the cursor-based pagination bit.
		if ( isset( $_GET['before'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$before = \FeedReader\parse_cursor( $_GET['before'] ); // Returns a cursor, or `null`.
		} elseif ( isset( $_GET['after'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$after = \FeedReader\parse_cursor( $_GET['after'] ); // Returns a cursor, or `null`.
		}

		if ( isset( $before ) ) {
			// Newer entries.
			$sql .= $wpdb->prepare(
				' AND (published > %s OR (published = %s AND id >= %d)) ORDER BY published ASC, id ASC LIMIT %d',
				$before[0],
				$before[0],
				$before[1],
				$limit + 1
			);

			// We'll also be counting the total number of items to the left of
			// (and including) our "cursor." Rather than build an entirely new
			// query, let's keep things simple and use some regex trickery.
			$total = preg_replace( '~^SELECT \*~', 'SELECT COUNT(*)', $sql );
			$total = preg_replace( '~LIMIT \d+$~', '', $total );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $total );
		} elseif ( isset( $after ) ) {
			// Older entries.
			$sql .= $wpdb->prepare(
				' AND (published < %s OR (published = %s AND id <= %d)) ORDER BY published DESC, id DESC LIMIT %d',
				$after[0],
				$after[0],
				$after[1],
				$limit + 1
			);
		} else {
			// First page.
			$sql .= $wpdb->prepare( ' ORDER BY published DESC, id DESC LIMIT %d', $limit + 1 );
		}

		// Add it all together.
		$sql = sprintf(
			'SELECT e.*, f.name AS feed_name
			 FROM (%s) AS e
			 JOIN %s AS f ON f.id = e.feed_id
			 ORDER BY e.published DESC, e.id DESC',
			$sql,
			Feed::table()
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $sql );

		// Build a new "before" cursor.
		$before = isset( $items[0] ) ? \FeedReader\build_cursor( $items[0] ) : null;

		// Build a new "after" cursor only if there is a next page.
		$after = isset( $items[ $limit ] ) ? \FeedReader\build_cursor( $items[ $limit ] ) : null;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['before'] ) && empty( $_GET['after'] ) ) {
			// If we weren't given a cursor, we're on the first page.
			$before = null;
		}

		if ( isset( $total ) && $total <= count( $items ) ) {
			// The total number of items left of our cursor is smaller than or
			// equal to the number of items on the current page. I.e., we're on
			// the first page.
			$before = null;
		}

		// This item, if it exists, is really part of the next page.
		unset( $items[ $limit ] );

		return array( $items, $before, $after );
	}

	public static function count( $all = false ) {
		global $wpdb;

		if ( $all ) {
			$sql = 'SELECT COUNT(*) FROM %s WHERE AND deleted_at IS NULL user_id = %%d';
		} else {
			$sql = 'SELECT COUNT(*) FROM %s WHERE is_read = 0 AND deleted_at IS NULL AND user_id = %%d';
		}

		$sql = sprintf( $sql, static::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, get_current_user_id() ) );
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
