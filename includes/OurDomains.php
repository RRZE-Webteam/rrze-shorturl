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

        // Table name
        $table_name = $wpdb->prefix . 'our_domains';

        // SQL query to create table
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        servername VARCHAR(255) NOT NULL
    )";

        // Execute SQL query
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Define a function to fetch data from REST API and store it in the database
    public static function fetch_and_store_data_from_api()
    {
        global $wpdb;

        // REST API URL
        $api_url = 'https://www.wmp.rrze.fau.de/api/ca/domains/cnf/1/type/1/';

        // Make a GET request to the API
        $response = wp_remote_get($api_url);

        // Check if the request was successful
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            // Empty the table first
            $wpdb->query( "TRUNCATE TABLE $table_name" );

            // Loop through the data and store servername if active = 1
            foreach ($data as $entry) {
                if ($entry['aktiv'] == 1) {
                    $wpdb->insert(
                        $wpdb->prefix . 'our_domains',
                        array(
                            'servername' => $entry['servername'],
                        )
                    );
                }
            }
        }
    }


}
