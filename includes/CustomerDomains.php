<?php
namespace RRZE\ShortURL;

class CustomerDomains
{
    public function __construct()
    {
        if (!wp_next_scheduled('rrze_shorturl_fetch_and_store_customerdomains')) {
            wp_schedule_event(time(), 'hourly', 'rrze_shorturl_fetch_and_store_customerdomains');
        }
        add_action('rrze_shorturl_fetch_and_store_customerdomains', array($this, 'fetch_and_store_customerdomains'));
    }

    public function fetch_and_store_customerdomains()
    {
        global $wpdb;

        try {
            $api_url = 'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-18.json';

            $response = wp_remote_get($api_url);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $jsonArray = json_decode($body, true);

                $jsonArray = !empty ($jsonArray['data']) ? $jsonArray['data'] : [];

                $filteredResponse = array_filter($jsonArray, function ($item) {
                    return $item['httpstatus'] == '200';
                });

                if (!empty ($filteredResponse)) {
                    foreach ($filteredResponse as $entry) {

                        $notice = '';
                        $active = 1;
                        if (empty ($entry['content']['tos']['Impressum']['href'])) {
                            $notice = __('Impressum fehlt.', 'rrze-shorturl');
                            $active = 0;
                        }elseif (empty ($entry['content']['tos']['Datenschutz']['href'])) {
                            $notice = __('Datenschutzerklärung fehlt.', 'rrze-shorturl');
                            $active = 0;
                        }elseif (empty ($entry['content']['tos']['Barrierefreiheit']['href'])) {
                            $notice = __('Barrierefreiheitserklärung fehlt.', 'rrze-shorturl');
                            $active = 0;
                        }
    
                        $url = !empty ($entry['wmp']['url']) ? $entry['wmp']['url'] : '';

                        if (!empty ($url)) {
                            // get the host
                            $parsed_url = parse_url($url);
                            $host = $parsed_url['host'];
                            $wpdb->query(
                                $wpdb->prepare(
                                    "INSERT INTO {$wpdb->prefix}shorturl_domains (hostname, notice, active, prefix)
                                        VALUES (%s, %s, %d, 1)
                                    ON DUPLICATE KEY UPDATE
                                        notice = VALUES(notice),
                                        active = VALUES(active)",
                                    $host,
                                    $notice,
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
        } catch (\Exception $e) {
            error_log('An error occurred: ' . $e->getMessage());
        }
    }
}
