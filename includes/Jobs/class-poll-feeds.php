<?php

namespace FeedReader\Jobs;

use FeedReader\Formats\JSON_Feed;
use FeedReader\Formats\Mf2;
use FeedReader\Formats\XML;
use FeedReader\Models\Entry;
use FeedReader\Models\Feed;

class Poll_Feeds {
	/** @var array $poll_frequencies */
	public static $poll_frequencies = array( 2, 6, 12, 24, 48 );

	public static function poll_feeds() {
		global $wpdb;

		// Select feeds that are newly added or past due.
		$sql = sprintf( 'SELECT * FROM %s WHERE last_polled IS NULL OR next_check <= %%s ORDER BY RAND() LIMIT 10', Feed::table() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$feeds = $wpdb->get_results( $wpdb->prepare( $sql, current_time( 'mysql', 1 ) ) );

		if ( ! empty( $feeds ) ) {
			foreach ( $feeds as $feed ) {
				static::poll_feed( $feed );
			}
		} else {
			error_log( '[Reader] No feeds due.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	public static function poll_feed( $feed ) {
		if ( empty( $feed->url ) || ! wp_http_validate_url( $feed->url ) ) {
			error_log( '[Reader] Oops. Could it be the feed at ' . esc_url_raw( $feed->url ) . ' is invalid?' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( filter_var( $feed->url, FILTER_VALIDATE_URL ) ) {
				// For diagnostics.
				error_log( "[Reader] `filter_var()` seems to think it's OK." ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return;
		}

		$now  = current_time( 'mysql', 1 );
		$hash = hash( 'sha256', esc_url_raw( $feed->url ) );
		$data = get_transient( $hash );

		if ( false === $data ) {
			error_log( '[Reader] Downloading feed at ' . esc_url_raw( $feed->url ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			$response = wp_safe_remote_get(
				esc_url_raw( $feed->url ),
				array(
					'timeout'    => 11,
					'user-agent' => \FeedReader\Helpers\get_user_agent( $feed->url ),
				)
			);

			$data = null;

			if ( is_wp_error( $response ) ) {
				// Something went wrong downloading the feed.
				error_log( '[Reader] Could not download the feed at ' . esc_url_raw( $feed->url ) . '.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

				$poll_frequency = end( static::$poll_frequencies );

				// Next check includes some randomness (up to +/- 1 hour).
				$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

				Feed::update(
					array(
						'last_error'     => $response->get_error_message(),
						'last_polled'    => $now,
						'poll_frequency' => $poll_frequency,
						'next_check'     => date( 'Y-m-d H:i:s', $next_check ),
					),
					array( 'id' => $feed->id )
				);
			} else {
				$data = array(
					'body'   => wp_remote_retrieve_body( $response ),
					'code'   => wp_remote_retrieve_response_code( $response ),
					'format' => (array) wp_remote_retrieve_header( $response, 'content-type' ),
				);
			}

			set_transient( $hash, $data, HOUR_IN_SECONDS );
		} else {
			error_log( '[Reader] Found ' . esc_url_raw( $feed->url ) . ' in cache.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		if ( ! $data ) {
			return;
		}

		if ( false === get_transient( "feed-reader:feeds:{$feed->id}:avatar" ) && empty( $feed->icon ) ) {
			// Fetch icons for feeds that were, e.g., mass-imported.
			Feed::fetch_favicon( $feed );
		}

		$entries = array();

		switch ( static::get_format( $data['format'], $data['body'], $feed ) ) {
			case 'json_feed':
				$entries = JSON_Feed::parse( $data['body'], $feed );
				break;

			case 'xml':
				$entries = XML::parse( $data['body'], $feed );
				break;

			case 'mf2':
			default:
				$entries = MF2::parse( $data['body'], $feed );
				break;
		}

		if ( empty( $entries ) ) {
			error_log( '[Reader] The feed at ' . esc_url_raw( $feed->url ) . ' came up empty.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			// Bump to lowest tier.
			$poll_frequency = end( static::$poll_frequencies );

			// Next check includes some randomness (up to +/- 1 hour).
			$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

			Feed::update(
				array(
					'last_error'     => esc_html__( 'The feed came up empty.', 'feed-reader' ),
					'last_polled'    => $now,
					'poll_frequency' => $poll_frequency,
					'next_check'     => date( 'Y-m-d H:i:s', $next_check ),
				),
				array( 'id' => $feed->id )
			);

			// Stop here.
			return;
		}

		$new_items = false;

		foreach ( $entries as $entry ) {
			$exists = Entry::exists( $entry['uid'], $feed );

			if ( ! $exists ) {
				$new_items = true;

				Entry::insert( $entry );
			}
		}

		$poll_frequency = end( static::$poll_frequencies );

		if ( $new_items ) {
			// Reset empty poll count, poll frequency.
			$empty_poll_count = 0;
			$poll_frequency   = reset( static::$poll_frequencies );
		} else {
			$empty_poll_count = $feed->empty_poll_count + 1;

			if ( $empty_poll_count > 3 ) {
				$empty_poll_count = 3; // Limit to 3.

				$key = array_search( $feed->poll_frequency, static::$poll_frequencies, true );

				if ( array_key_exists( $key + 1, static::$poll_frequencies ) ) {
					$poll_frequency = static::$poll_frequencies[ $key + 1 ];
				} else {
					// Start over if we've somehow updated polling tiers.
					$poll_frequency = reset( static::$poll_frequencies );
				}
			}
		}

		// Next check includes some randomness (up to +/- 1 hour).
		$next_check = strtotime( $now ) + ( $poll_frequency + 1 ) * HOUR_IN_SECONDS - wp_rand( 0, 2 * HOUR_IN_SECONDS );

		Feed::update(
			array(
				'last_error'       => null,
				'last_polled'      => $now,
				'poll_frequency'   => $poll_frequency,
				'empty_poll_count' => $empty_poll_count,
				'next_check'       => date( 'Y-m-d H:i:s', $next_check ),
			),
			array( 'id' => $feed->id )
		);
	}

	protected static function get_format( $content_type, $body, $feed ) {
		$content_type = array_pop( $content_type );
		$content_type = strtok( $content_type, ';' );
		strtok( '', '' );

		if ( in_array( $content_type, array( 'application/feed+json', 'application/json' ), true ) ) {
			$data = json_decode( trim( $body ) );

			if ( ! empty( $data->version ) && false !== strpos( $data->version, 'https://jsonfeed.org/version/' ) ) {
				return 'json_feed';
			}
		}

		if ( in_array( $content_type, array( 'application/rss+xml', 'application/atom+xml', 'text/xml', 'application/xml' ), true ) ) {
			return 'xml';
		}

		return null;
	}
}
