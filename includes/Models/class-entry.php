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
			$sql = 'SELECT * FROM %s WHERE deleted_at IS NULL AND user_id = %%d ORDER BY published DESC, id DESC LIMIT %d OFFSET %d';
		} else {
			$sql = 'SELECT * FROM %s WHERE is_read = 0 AND deleted_at IS NULL AND user_id = %%d ORDER BY published DESC, id DESC LIMIT %d OFFSET %d';
		}

		$sql = sprintf(
			'SELECT e.*, f.name AS feed_name
			 FROM (%s) AS e
			 JOIN %s AS f ON f.id = e.feed_id
			 ORDER BY e.published DESC, e.id DESC',
			sprintf( $sql, static::table(), $limit, $offset ),
			Feed::table()
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, get_current_user_id() ) );
	}

	public static function cursor_paginate( $limit = 15, $all = false, $feed_id = null, $category_id = null ) {
		global $wpdb;

		// Let's start building our (sub)query. We use "soft deletes" and an
		// explicit `user_id` scope.
		$sql = 'SELECT * FROM %s WHERE deleted_at IS NULL AND user_id = %%d';

		if ( ! $all ) {
			// Unread entries only.
			$sql .= ' AND is_read = 0';
		}

		if ( $feed_id ) {
			// This one's easy.
			$feeds = array( array( 'id' => $feed_id ) );
		} elseif ( $category_id ) {
			// Fetch all of this category's feeds.
			$feeds = Category::feeds( $category_id );
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
			$before = \Feed_Reader\parse_cursor( $_GET['before'] ); // Returns a cursor, or `null`.
		} elseif ( isset( $_GET['after'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$after = \Feed_Reader\parse_cursor( $_GET['after'] ); // Returns a cursor, or `null`.
		}

		if ( isset( $before ) ) {
			// Newer entries.
			$sql .= ' AND (published > %%s OR (published = %%s AND id >= %%d)) ORDER BY published ASC, id ASC LIMIT %%d';
		} elseif ( isset( $after ) ) {
			// Older entries.
			$sql .= ' AND (published < %%s OR (published = %%s AND id <= %%d)) ORDER BY published DESC, id DESC LIMIT %%d';
		} else {
			// First page.
			$sql .= ' ORDER BY published DESC, id DESC LIMIT %%d';
		}

		// Parse in the table name.
		$sql = sprintf( $sql, static::table() );

		if ( isset( $before ) ) {
			// We'll also be counting the total number of items to the left of
			// (and including) our cursor. Rather than rebuild the entire
			// (sub)query, let's keep things simple and use some regex trickery.
			$total = preg_replace( '~^SELECT \*~', 'SELECT COUNT(*)', $sql );
			$total = preg_replace( '~LIMIT %d~', '', $total ); // `%d` and not `%%d`, because of the `sprintf()` call above.
		}

		if ( isset( $before ) ) {
			// Parse in (and escape) the user ID, and cursor date and ID.
			$sql   = $wpdb->prepare( $sql, get_current_user_id(), $before[0], $before[0], $before[1], $limit + 1 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = $wpdb->prepare( $total, get_current_user_id(), $before[0], $before[0], $before[1] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} elseif ( isset( $after ) ) {
			// Parse in (and escape) the user ID, and cursor date and ID.
			$sql = $wpdb->prepare( $sql, get_current_user_id(), $after[0], $after[0], $after[1], $limit + 1 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} else {
			$sql = $wpdb->prepare( $sql, get_current_user_id(), $limit + 1 );// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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

		// Build a "before" cursor.
		$before = isset( $items[0] ) ? \Feed_Reader\build_cursor( $items[0] ) : null;

		// Build an "after" cursor only if there is a next page.
		$after = isset( $items[ $limit ] ) ? \Feed_Reader\build_cursor( $items[ $limit ] ) : null;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['before'] ) && empty( $_GET['after'] ) ) {
			// If we weren't given a cursor, we're on the first page.
			$before = null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $total ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $total );

			if ( $total <= count( $items ) ) {
				// The total number of items left of our cursor is smaller than or
				// equal to the number of items on the current page. I.e., we're on
				// the first page.
				$before = null;
			}
		}

		// This item, if any, is really part of the next page.
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
