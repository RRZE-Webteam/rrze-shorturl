<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

class OurDomains
{

    public function __construct()
    {

        register_activation_hook(__FILE__, 'create_custom_tables');

        // Schedule a cron job to fetch and store data periodically
        if (!wp_next_scheduled('fetch_and_store_data_event')) {
            wp_schedule_event(time(), 'hourly', 'fetch_and_store_data_event');
        }
        add_action('fetch_and_store_data_event', 'fetch_and_store_data_from_api');
    }


    // Define a function to create tables in WordPress database
    public static function create_custom_tables()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Create the our_domains table
    $our_domains_table_name = $wpdb->prefix . 'our_domains';
    $our_domains_sql = "CREATE TABLE IF NOT EXISTS $our_domains_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        hostname varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($our_domains_sql);

    // Create the links table
    $links_table_name = $wpdb->prefix . 'links';
    $links_sql = "CREATE TABLE IF NOT EXISTS $links_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        long_url varchar(255) UNIQUE NOT NULL,
        short_url varchar(255) UNIQUE NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($links_sql);
}

    // Define a function to fetch data from REST API and store it in the database
    public static function fetch_and_store_data_from_api()
    {
        global $wpdb;
    
        try {
            // REST API URL
            $api_url = 'https://www.wmp.rrze.fau.de/api/server/type/1,2,16,18/active/1';
    
            // Make a GET request to the API
            $response = wp_remote_get($api_url);
    
            // Check if the request was successful
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
    
                // Loop through the data and store servername if active = 1
                foreach ($data as $entry) {
                    if ($entry['aktiv'] == 1) {
                        $wpdb->query(
                            $wpdb->prepare(
                                "INSERT IGNORE INTO {$wpdb->prefix}our_domains (hostname) VALUES (%s)",
                                $entry['hostname']
                            )
                        );
                    }
                }
            }
        } catch (Exception $e) {
            // Handle the exception
            error_log('An error occurred: ' . $e->getMessage());
        }
    }
    
    


}
