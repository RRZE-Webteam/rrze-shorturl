<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class CustomerDomains
{
    public function __construct()
    {
        add_action('rrze_shorturl_fetch_and_store_customerdomains', array($this, 'fetch_and_store_customerdomains'));

        if (!wp_next_scheduled('rrze_shorturl_fetch_and_store_customerdomains')) {
            // job has never run: do it immediately (like on plugin activation)
            $this->fetch_and_store_customerdomains();

            // let the job run daily as 4 a.m.
            wp_schedule_event(strtotime('today 4:00'), 'daily', 'rrze_shorturl_fetch_and_store_customerdomains');
        }
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
                                $parsed_url = parse_url($url);
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
                                $existing_domain = get_page_by_title($host, OBJECT, 'domain');

                                if ($existing_domain) {
                                    // Update existing domain
                                    update_post_meta($existing_domain->ID, 'notice', $notice);
                                    update_post_meta($existing_domain->ID, 'webmaster_name', $webmaster_name);
                                    update_post_meta($existing_domain->ID, 'webmaster_email', $webmaster_email);
                                    update_post_meta($existing_domain->ID, 'active', $active);
                                } else {
                                    // Create a new domain entry as a Custom Post Type
                                    $post_data = [
                                        'post_title' => $host,
                                        'post_type' => 'domain', // Assuming 'domain' is a registered Custom Post Type
                                        'post_status' => 'publish'
                                    ];

                                    $post_id = wp_insert_post($post_data);

                                    if (!is_wp_error($post_id)) {
                                        // Add meta data for the new domain
                                        update_post_meta($post_id, 'notice', $notice);
                                        update_post_meta($post_id, 'webmaster_name', $webmaster_name);
                                        update_post_meta($post_id, 'webmaster_email', $webmaster_email);
                                        update_post_meta($post_id, 'active', $active);
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


    // public function fetch_and_store_customerdomains()
    // {
    //     global $wpdb;

    //     $aAPI_url = [
    //         'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-18.json',
    //         'https://statistiken.rrze.fau.de/webauftritte/domains/analyse/domain-analyse-1.json',
    //         'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-2-3.json',
    //     ];

    //     foreach ($aAPI_url as $api_url) {
    //         try {
    //             $response = wp_remote_get($api_url);

    //             if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    //                 $body = wp_remote_retrieve_body($response);
    //                 $jsonArray = json_decode($body, true);

    //                 $jsonArray = !empty($jsonArray['data']) ? $jsonArray['data'] : [];

    //                 $filteredResponse = array_filter($jsonArray, function ($item) {
    //                     return $item['httpstatus'] == '200';
    //                 });

    //                 if (!empty($filteredResponse)) {
    //                     foreach ($filteredResponse as $entry) {

    //                         $notice = '';
    //                         $webmaster_name = '';
    //                         $webmaster_email = '';
    //                         $active = 1;
    //                         if (empty($entry['content']['tos']['Impressum']['href'])) {
    //                             $notice = __('the imprint', 'rrze-shorturl');
    //                             $active = 0;
    //                         } elseif (empty($entry['content']['tos']['Datenschutz']['href'])) {
    //                             $notice = __('the privacy policy', 'rrze-shorturl');
    //                             $active = 0;
    //                         } elseif (empty($entry['content']['tos']['Barrierefreiheit']['href'])) {
    //                             $notice = __('the accessibility statement', 'rrze-shorturl');
    //                             $active = 0;
    //                         }

    //                         $url = !empty($entry['wmp']['url']) ? $entry['wmp']['url'] : '';

    //                         if (!empty($url)) {
    //                             // get the host
    //                             $parsed_url = parse_url($url);
    //                             $host = $parsed_url['host'];

    //                             if (!$active) {
    //                                 // get webmaster
    //                                 try {
    //                                     $api_url = 'https://www.wmp.rrze.fau.de/suche/impressum/' . $host . '/format/json';

    //                                     $response = wp_remote_get($api_url);

    //                                     if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    //                                         $body = wp_remote_retrieve_body($response);
    //                                         $jsonArray = json_decode($body, true);
    //                                         $webmaster_name = !empty($jsonArray['webmaster']['name']) ? $jsonArray['webmaster']['name'] : 'Name not found';
    //                                         $webmaster_email = !empty($jsonArray['webmaster']['email']) ? $jsonArray['webmaster']['email'] : 'eMail not found';
    //                                     }
    //                                 } catch (CustomException $e) {
    //                                     error_log('An error occurred: ' . $e->getMessage());
    //                                 }
    //                             }

    //                             $wpdb->query(
    //                                 $wpdb->prepare(
    //                                     "INSERT INTO {$wpdb->prefix}shorturl_domains (hostname, notice, webmaster_name, webmaster_email, active, prefix)
    //                                     VALUES (%s, %s,  %s,  %s, %d, 1)
    //                                 ON DUPLICATE KEY UPDATE
    //                                     notice = VALUES(notice),
    //                                     webmaster_name = VALUES(webmaster_name),
    //                                     webmaster_email = VALUES(webmaster_email),
    //                                     active = VALUES(active)",
    //                                     $host,
    //                                     $notice,
    //                                     $webmaster_name,
    //                                     $webmaster_email,
    //                                     $active
    //                                 )
    //                             );
    //                         }
    //                     }
    //                 } else {
    //                     error_log('fetch_and_store_customerdomains() $data is empty');
    //                 }
    //             } else {
    //                 error_log('fetch_and_store_customerdomains() API returns ' . wp_remote_retrieve_response_code($response));
    //             }
    //         } catch (CustomException $e) {
    //             error_log('An error occurred: ' . $e->getMessage());
    //         }
    //     }
    // }
}
