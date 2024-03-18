<?php
namespace RRZE\ShortURL;

class CustomerDomains
{
    public function __construct()
    {
        if (!wp_next_scheduled('fetch_and_store_customerdomains')) {
            wp_schedule_event(time(), 'hourly', 'fetch_and_store_customerdomains');
        }
        add_action('fetch_and_store_customerdomains', array($this, 'fetch_and_store_customerdomains_from_api'));
    }

    public function fetch_and_store_customerdomains_from_api()
    {
        global $wpdb;

        try {
            $api_url = 'https://www.wmp.rrze.fau.de/api/server/type/1,2,16,18/active/1';

            $response = wp_remote_get($api_url);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (!empty($data)) {
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
            error_log('An error occurred: ' . $e->getMessage());
        }
    }
}
