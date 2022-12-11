<?php

namespace Feed_Reader;

/**
 * Not actually used (yet).
 */
class Query_Builder {
	/** @var array $query */
	private $query;

	/** @var array $where */
	private $where;

	public function __construct( $query ) {
		$this->query = $query;
	}

	public function where( $key, $value ) {
		global $wpdb;

		$where = is_int( $value )
			? "$key = %d"
			: "$key = %s";

		$this->where[] = $wpdb->prepare( $where, $value ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $this;
	}

	public function where_null( $key ) {
		$this->where[] = "$key IS NULL";

		return $this;
	}

	public function build_query() {
		global $wpdb;

		$query = $this->query;

		if ( ! empty( $this->where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $this->where );
		}

		return $query;
	}
}
