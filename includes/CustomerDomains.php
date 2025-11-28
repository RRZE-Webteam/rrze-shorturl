<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class CustomerDomains
{
    public function __construct()
    {
        add_action('init', function () {
            if (!wp_next_scheduled('rrze_shorturl_fetch_and_store_customerdomains')) {
                wp_schedule_event(strtotime('today 4:00'), 'daily', 'rrze_shorturl_fetch_and_store_customerdomains');
            }
        });

        add_action('rrze_shorturl_fetch_and_store_customerdomains', array($this, 'fetch_and_store_customerdomains'));
    }

    public function fetch_and_store_customerdomains()
    {
        // List of API URLs to fetch data from
        $aAPI_url = [
            'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-18.json',
            'https://statistiken.rrze.fau.de/webauftritte/domains/analyse/domain-analyse-1.json',
            'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-2-3.json',
        ];

        foreach ($aAPI_url as $api_url) {
            try {
                // Fetch data from the API URL
                $response = wp_remote_get($api_url);

                // Check if the response is valid and contains data
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $jsonArray = json_decode($body, true);

                    // Extract the 'data' array from the response
                    $jsonArray = !empty($jsonArray['data']) ? $jsonArray['data'] : [];

                    // Filter for entries with 'httpstatus' == '200'
                    $filteredResponse = array_filter($jsonArray, function ($item) {
                        return $item['httpstatus'] == '200';
                    });

                    // If there are valid entries, process each one
                    if (!empty($filteredResponse)) {
                        foreach ($filteredResponse as $entry) {
                            $notice = '';
                            $webmaster_name = '';
                            $webmaster_email = '';
                            $active = 1;
                            $prefix = 1;
                            $external = 0;

                            // Validate the presence of necessary links
                            if (empty($entry['content']['tos']['Impressum']['href'])) {
                                $notice = __('the imprint', 'rrze-shorturl');
                                $active = 0;
                            } elseif (empty($entry['content']['tos']['Datenschutz']['href'])) {
                                $notice = __('the privacy policy', 'rrze-shorturl');
                                $active = 0;
                            } elseif (empty($entry['content']['tos']['Barrierefreiheit']['href'])) {
                                $notice = __('the accessibility statement', 'rrze-shorturl');
                                $active = 0;
                            }

                            $url = !empty($entry['wmp']['url']) ? $entry['wmp']['url'] : '';

                            if (!empty($url)) {
                                // Parse the URL and get the hostname
                                $parsed_url = wp_parse_url($url);
                                $host = $parsed_url['host'];

                                // If the site is inactive, fetch webmaster details
                                if (!$active) {
                                    try {
                                        $api_url = 'https://www.wmp.rrze.fau.de/suche/impressum/' . $host . '/format/json';

                                        $response = wp_remote_get($api_url);

                                        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                                            $body = wp_remote_retrieve_body($response);
                                            $jsonArray = json_decode($body, true);
                                            $webmaster_name = !empty($jsonArray['webmaster']['name']) ? $jsonArray['webmaster']['name'] : __('Name not found', 'rrze-shorturl');
                                            $webmaster_email = !empty($jsonArray['webmaster']['email']) ? $jsonArray['webmaster']['email'] : __('Email not found', 'rrze-shorturl');
                                        }
                                    } catch (CustomException $e) {
                                        error_log('An error occurred while fetching webmaster info: ' . $e->getMessage());
                                    }
                                }

                                // Insert or update the domain entry in the database
                                $existing_domain_id = get_posts(
                                    array(
                                        'post_type' => 'shorturl_domain',
                                        'title' => $host,
                                        'post_status' => 'all',
                                        'numberposts' => 1,
                                        'fields' => 'ids'
                                    )
                                );

                                if (!empty($existing_domain_ids)) {
                                    $post_id = $existing_domain_ids[0];

                                    // Update existing domain
                                    update_post_meta($post_id, 'notice', $notice);
                                    update_post_meta($post_id, 'webmaster_name', $webmaster_name);
                                    update_post_meta($post_id, 'webmaster_email', $webmaster_email);
                                    update_post_meta($post_id, 'active', $active);
                                } else {
                                    // Create a new domain entry as a Custom Post Type
                                    $post_data = [
                                        'post_title' => $host,
                                        'post_type' => 'shorturl_domain',
                                        'post_status' => 'publish'
                                    ];

                                    $post_id = wp_insert_post($post_data);

                                    if (!is_wp_error($post_id)) {
                                        // Add meta data for the new domain
                                        update_post_meta($post_id, 'notice', $notice);
                                        update_post_meta($post_id, 'webmaster_name', $webmaster_name);
                                        update_post_meta($post_id, 'webmaster_email', $webmaster_email);
                                        update_post_meta($post_id, 'active', $active);
                                        update_post_meta($post_id, 'prefix', $prefix);
                                        update_post_meta($post_id, 'external', $external);
                                    } else {
                                        error_log('Error inserting domain: ' . $host);
                                    }
                                }
                            }
                        }
                    } else {
                        error_log('fetch_and_store_customerdomains() $data is empty');
                    }
                } else {
                    error_log('fetch_and_store_customerdomains() API returned ' . wp_remote_retrieve_response_code($response));
                }
            } catch (CustomException $e) {
                error_log('An error occurred: ' . $e->getMessage());
            }
        }
    }
}
