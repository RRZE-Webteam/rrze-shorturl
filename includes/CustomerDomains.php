<?php
namespace RRZE\ShortURL;

class CustomerDomains
{
    public function __construct()
    {
        // Schedule a cron job to fetch and store data periodically
        if (!wp_next_scheduled('fetch_and_store_customerdomains')) {
            wp_schedule_event(time(), 'hourly', 'fetch_and_store_customerdomains');
        }
        add_action('fetch_and_store_customerdomains', array($this, 'fetch_and_store_customerdomains_from_api'));
    }

    // Define a function to fetch data from REST API and store it in the database
    public function fetch_and_store_customerdomains_from_api()
    {
        global $wpdb;

        try {
            // REST API URL
            $api_url = 'https://www.wmp.rrze.fau.de/api/server/type/1,2,16,18/active/1';

            // Make a GET request to the API
            $response = wp_remote_get($api_url);

            // if (is_wp_error($response)) {
            //     $error_message = $response->get_error_message();
            //     error_log("Error retrieving data: $error_message");
            // } else {
            //     $response_code = wp_remote_retrieve_response_code($response);
            //     $response_body = wp_remote_retrieve_body($response);
            //     error_log("Response code: $response_code, Response body: $response_body");
            // }

            // Check if the request was successful
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (!empty($data)) {
                    // Loop through the data and store servername if active = 1
                    foreach ($data as $entry) {
                        if ($entry['aktiv'] == 1) {
                            $wpdb->query(
                                $wpdb->prepare(
                                    "INSERT IGNORE INTO {$wpdb->prefix}shorturl_domains (hostname, prefix) VALUES (%s, 1)",
                                    $entry['hostname']
                                )
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Handle the exception
            error_log('An error occurred: ' . $e->getMessage());
        }
    }
}
