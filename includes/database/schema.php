<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
?>
CREATE TABLE <?php echo \FeedReader\Models\Category::table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> (
	id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
	uid varchar(191) DEFAULT '' NOT NULL,
	name varchar(191),
	created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	modified_at datetime,
	user_id bigint(20) UNSIGNED,
	PRIMARY KEY (id)
) <?php echo $wpdb->get_charset_collate(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;

CREATE TABLE <?php echo \FeedReader\Models\Feed::table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> (
	id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
	url varchar(191),
	name varchar(191),
	site_url varchar(191),
	icon varchar(191),
	last_error varchar(191),
	last_polled datetime,
	empty_poll_count tinyint(4) DEFAULT 0 NOT NULL,
	poll_frequency tinyint(4) DEFAULT 1 NOT NULL,
	next_check datetime,
	is_hidden boolean DEFAULT 0 NOT NULL,
	created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	modified_at datetime,
	category_id mediumint(9) UNSIGNED,
	user_id bigint(20) UNSIGNED,
	PRIMARY KEY (id)
) <?php echo $wpdb->get_charset_collate(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;

CREATE TABLE <?php echo \FeedReader\Models\Entry::table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> (
	id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
	uid varchar(191) DEFAULT '' NOT NULL,
	url varchar(191),
	name varchar(191),
	author varchar(191),
	content text,
	summary text,
	is_read boolean DEFAULT 0 NOT NULL,
	is_starred boolean DEFAULT 0 NOT NULL,
	in_feed boolean DEFAULT 1 NOT NULL,
	published datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	modified_at datetime,
	deleted_at datetime,
	feed_id mediumint(9) UNSIGNED,
	user_id bigint(20) UNSIGNED,
	data text,
	PRIMARY KEY (id)
) <?php echo $wpdb->get_charset_collate(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
