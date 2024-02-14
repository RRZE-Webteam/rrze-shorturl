<?php

namespace RRZE\ShortURL;

// Activation hook to create the database table
register_activation_hook(__FILE__, 'uniportal_short_url_create_table');

function uniportal_short_url_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        original_url text NOT NULL,
        shortened_url text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
