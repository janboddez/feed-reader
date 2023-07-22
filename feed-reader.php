<?php
/**
 * Plugin Name: Feed Reader
 * Description: A dead-simple feed reader.
 * Plugin URI:  https://jan.boddez.net/wordpress/feed-reader
 * Author:      Jan Boddez
 * Author URI:  https://jan.boddez.net/
 * License:     GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: feed-reader
 * Version:     0.3.0
 *
 * @author  Jan Boddez <jan@janboddez.be>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package FeedReader
 */

namespace FeedReader;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load dependencies.
if ( ! function_exists( '\\fetch_feeds' ) ) {
	require_once ABSPATH . 'wp-includes/class-simplepie.php';
}

if ( ! class_exists( '\\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

require_once __DIR__ . '/build/vendor/scoper-autoload.php';

$reader = Reader::get_instance();
$reader->register();
