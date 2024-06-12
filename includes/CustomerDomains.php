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
        global $wpdb;

        $aAPI_url = [
            'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-18.json',
            'https://statistiken.rrze.fau.de/webauftritte/domains/analyse/domain-analyse-1.json',
        ];

        foreach ($aAPI_url as $api_url) {
            try {
                $response = wp_remote_get($api_url);

                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $jsonArray = json_decode($body, true);

                    $jsonArray = !empty($jsonArray['data']) ? $jsonArray['data'] : [];

                    $filteredResponse = array_filter($jsonArray, function ($item) {
                        return $item['httpstatus'] == '200';
                    });

                    if (!empty($filteredResponse)) {
                        foreach ($filteredResponse as $entry) {

                            $notice = '';
                            $webmaster_name = '';
                            $webmaster_email = '';
                            $active = 1;
                            if (empty($entry['content']['tos']['Impressum']['href'])) {
                                $notice = __('das Impressum', 'rrze-shorturl');
                                $active = 0;
                            } elseif (empty($entry['content']['tos']['Datenschutz']['href'])) {
                                $notice = __('die Datenschutzerklärung', 'rrze-shorturl');
                                $active = 0;
                            } elseif (empty($entry['content']['tos']['Barrierefreiheit']['href'])) {
                                $notice = __('die Barrierefreiheitserklärung', 'rrze-shorturl');
                                $active = 0;
                            }

                            $url = !empty($entry['wmp']['url']) ? $entry['wmp']['url'] : '';

                            if (!empty($url)) {
                                // get the host
                                $parsed_url = parse_url($url);
                                $host = $parsed_url['host'];

                                if (!$active) {
                                    // get webmaster
                                    try {
                                        $api_url = 'https://www.wmp.rrze.fau.de/suche/impressum/' . $host . '/format/json';

                                        $response = wp_remote_get($api_url);

                                        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                                            $body = wp_remote_retrieve_body($response);
                                            $jsonArray = json_decode($body, true);
                                            $webmaster_name = !empty($jsonArray['webmaster']['name']) ? $jsonArray['webmaster']['name'] : 'Name not found';
                                            $webmaster_email = !empty($jsonArray['webmaster']['email']) ? $jsonArray['webmaster']['email'] : 'eMail not found';
                                        }
                                    } catch (CustomException $e) {
                                        error_log('An error occurred: ' . $e->getMessage());
                                    }
                                }

                                $wpdb->query(
                                    $wpdb->prepare(
                                        "INSERT INTO {$wpdb->prefix}shorturl_domains (hostname, notice, webmaster_name, webmaster_email, active, prefix)
                                        VALUES (%s, %s,  %s,  %s, %d, 1)
                                    ON DUPLICATE KEY UPDATE
                                        notice = VALUES(notice),
                                        webmaster_name = VALUES(webmaster_name),
                                        webmaster_email = VALUES(webmaster_email),
                                        active = VALUES(active)",
                                        $host,
                                        $notice,
                                        $webmaster_name,
                                        $webmaster_email,
                                        $active
                                    )
                                );
                            }
                        }
                    } else {
                        error_log('fetch_and_store_customerdomains() $data is empty');
                    }
                } else {
                    error_log('fetch_and_store_customerdomains() API returns ' . wp_remote_retrieve_response_code($response));
                }
            } catch (CustomException $e) {
                error_log('An error occurred: ' . $e->getMessage());
            }
        }
    }
}
