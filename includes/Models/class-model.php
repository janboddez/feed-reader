<?php

namespace FeedReader\Models;

abstract class Model {
	/** @var string $table */
	protected static $table = '';

	public static function count() {
		global $wpdb;

		$sql = sprintf( 'SELECT COUNT(*) FROM %s WHERE user_id = %%d', static::table() );

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, get_current_user_id() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function find( $id ) {
		global $wpdb;

		$sql = sprintf( 'SELECT * FROM %s WHERE id = %%d AND user_id = %%d', static::table() );

		return $wpdb->get_row( $wpdb->prepare( $sql, $id, get_current_user_id() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function insert( $data ) {
		global $wpdb;

		$data['created_at'] = current_time( 'mysql', 1 );

		if ( $wpdb->insert( self::table(), $data ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $wpdb->insert_id;
		}

		return null;
	}

	public static function update( $data, $where ) {
		global $wpdb;

		$data['modified_at'] = current_time( 'mysql', 1 );

		return $wpdb->update( self::table(), $data, $where ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function delete( $value ) {
		global $wpdb;

		$sql = sprintf( 'DELETE FROM %s WHERE id = %%s', static::table() );

		return $wpdb->query( $wpdb->prepare( $sql, $value ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function table() {
		if ( empty( static::$table ) ) {
			throw new \Exception( 'Empty table name.' );
		}

		global $wpdb;

		return $wpdb->prefix . static::$table;
	}
}
