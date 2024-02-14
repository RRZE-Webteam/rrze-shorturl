<?php

namespace RRZE\ShortURL;

// Activation hook to create the database tables
register_activation_hook(__FILE__, 'uniportal_short_url_create_tables');

function uniportal_short_url_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Create short_urls table
    $short_urls_table_name = $wpdb->prefix . 'short_urls';
    $short_urls_sql = "CREATE TABLE $short_urls_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        original_url text NOT NULL,
        shortened_url text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($short_urls_sql);

    // Create statistics table
    $statistics_table_name = $wpdb->prefix . 'short_url_statistics';
    $statistics_sql = "CREATE TABLE $statistics_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        short_url_id mediumint(9) NOT NULL,
        iCount int NOT NULL DEFAULT 0,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        FOREIGN KEY (short_url_id) REFERENCES $short_urls_table_name(id),
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($statistics_sql);
}
