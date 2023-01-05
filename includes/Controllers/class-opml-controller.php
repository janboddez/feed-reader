<?php

namespace FeedReader\Controllers;

use FeedReader\Controllers\Controller;
use FeedReader\Helpers\OPML_Parser;
use FeedReader\Models\Category;
use FeedReader\Models\Feed;

class OPML_Controller extends Controller {
	public static function opml() {
		static::render( 'opml/form' );
	}

	public static function export() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		$categories = Category::all();

		if ( ! empty( $categories ) ) {
			foreach ( $categories as $i => $category ) {
				$categories[ $i ]->feeds = Category::feeds( $category->id, 'all' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
		/** @todo: Add uncategorized feeds. */

		static::render( 'opml/opml', compact( 'categories' ) );
	}

	public static function import() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You have insufficient permissions to access this page.', 'feed-reader' ) );
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'feed-reader-opml:import' ) ) {
			wp_die( esc_html__( 'This page should not be accessed directly.', 'feed-reader' ) );
		}

		if ( empty( $_FILES['opml_file'] ) ) {
			wp_die( esc_html__( 'Something went wrong uploading the file.', 'feed-reader' ) );
		}

		add_filter( 'upload_mimes', array( __CLASS__, 'upload_mimes' ) );

		// Let WordPress handle the uploaded file.
		$uploaded_file = wp_handle_upload(
			$_FILES['opml_file'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			array(
				'test_form' => false,
			)
		);

		if ( ! empty( $uploaded_file['error'] ) ) {
			// `wp_handle_upload()` returned an error.
			wp_die( esc_html( $uploaded_file['error'] ) );
		} elseif ( empty( $uploaded_file['file'] ) || ! is_string( $uploaded_file['file'] ) ) {
			wp_die( esc_html__( 'Something went wrong uploading the file.', 'feed-reader' ) );
		}

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->exists( $uploaded_file['file'] ) ) {
			wp_die( esc_html__( 'Something went wrong uploading the file.', 'feed-reader' ) );
		}

		$opml = $wp_filesystem->get_contents( $uploaded_file['file'] );

		// Run the actual importer.
		$parser = new OPML_Parser();
		$feeds  = $parser->parse( $opml, true );

		// `$feeds` should now represent a multidimensional array.
		if ( empty( $feeds ) || ! is_array( $feeds ) ) {
			wp_die( esc_html__( 'No feeds found.', 'feed-reader' ) );
		}

		foreach ( $feeds as $feed ) {
			if ( false === filter_var( $feed['url'], FILTER_VALIDATE_URL ) ) {
				// Invalid feed URL.
				continue;
			}

			if ( ! Feed::exists( esc_url_raw( $feed['url'] ) ) ) {
				if ( ! empty( $feed['category'] ) ) {
					// Fetch category.
					$category_id = Category::exists( sanitize_text_field( $feed['category'] ) );
				}

				if ( ! $category_id ) {
					// Add category.
					global $wpdb;

					// Generate a unique UID.
					do {
						$uid    = bin2hex( openssl_random_pseudo_bytes( 16 ) );
						$sql    = sprintf( 'SELECT id FROM %s WHERE uid = %%s LIMIT 1', Category::table() );
						$exists = $wpdb->get_var( $wpdb->prepare( $sql, $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
					} while ( $exists );

					$category_id = Category::insert(
						array(
							'name'    => sanitize_text_field( $feed['category'] ),
							'uid'     => $uid,
							'user_id' => get_current_user_id(),
						)
					);
				}

				$feed_id = Feed::insert(
					array_filter(
						array(
							'url'         => esc_url_raw( $feed['url'] ),
							'name'        => isset( $feed['name'] ) ? sanitize_text_field( $feed['name'] ) : preg_replace( '~^www~', '', wp_parse_url( $feed['feed'], PHP_URL_HOST ) ),
							'site_url'    => isset( $feed['site_url'] ) && filter_var( $feed['url'], FILTER_VALIDATE_URL ) ? esc_url_raw( $feed['site_url'] ) : null,
							'category_id' => isset( $category_id ) ? $category_id : null,
							'user_id'     => get_current_user_id(),
						)
					)
				);
			}
		}

		wp_safe_redirect( esc_url_raw( \FeedReader\Helpers\get_url( 'feeds' ) ) );
		exit;
	}

	public static function upload_mimes( $mimes ) {
		return array_merge( $mimes, array( 'xml' => 'text/xml' ) );
	}
}
