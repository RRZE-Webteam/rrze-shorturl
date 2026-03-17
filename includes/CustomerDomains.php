<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class CustomerDomains
{
    public function __construct()
    {
        // Add special domains on init
        add_action('init', [$this, 'add_special_domains']);

        // Schedule daily fetch if not scheduled
        add_action('init', function () {
            if (!wp_next_scheduled('rrze_shorturl_fetch_and_store_customerdomains')) {
                wp_schedule_event(strtotime('tomorrow 4:00'), 'daily', 'rrze_shorturl_fetch_and_store_customerdomains');
            }
        });

        // Hook fetch function to cron
        add_action('rrze_shorturl_fetch_and_store_customerdomains', [$this, 'fetch_and_store_customerdomains']);
    }

    public function add_special_domains()
    {
        // if (get_option('rrze_shorturl_special_domains_added')) return;

        $domains = [
            [
                'url' => 'https://faubox.rrze.uni-erlangen.de',
                'notice' => '',
                'webmaster_name' => '',
                'webmaster_email' => '',
                'active' => 1,
                'prefix' => 1,
                'external' => 0,
            ],
        ];

        foreach ($domains as $domain) {
            $this->insert_or_update_domain($domain['url'], $domain);
        }

        // update_option('rrze_shorturl_special_domains_added', 1);
    }

    public function fetch_and_store_customerdomains()
    {
        $api_urls = [
            'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-18.json',
            'https://statistiken.rrze.fau.de/webauftritte/domains/analyse/domain-analyse-1.json',
            'https://statistiken.rrze.fau.de/webauftritte/domains//analyse/domain-analyse-2-3.json',
        ];

        foreach ($api_urls as $url) {
            try {
                $response = wp_remote_get($url, ['timeout' => 10]);
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
                    continue;

                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (json_last_error() !== JSON_ERROR_NONE)
                    continue;

                $entries = array_filter($data['data'] ?? [], fn($item) => isset($item['httpstatus']) && $item['httpstatus'] == '200');

                foreach ($entries as $entry) {
                    $meta = [
                        'notice' => '',
                        'webmaster_name' => '',
                        'webmaster_email' => '',
                        'active' => 1,
                        'prefix' => 1,
                        'external' => 0,
                    ];

                    $impressum = $entry['content']['tos']['Impressum']['href'] ?? null;
                    $datenschutz = $entry['content']['tos']['Datenschutz']['href'] ?? null;
                    $barrierefreiheit = $entry['content']['tos']['Barrierefreiheit']['href'] ?? null;

                    if (!$impressum) {
                        $meta['notice'] = __('the imprint', 'rrze-shorturl');
                        $meta['active'] = 0;
                    } elseif (!$datenschutz) {
                        $meta['notice'] = __('the privacy policy', 'rrze-shorturl');
                        $meta['active'] = 0;
                    } elseif (!$barrierefreiheit) {
                        $meta['notice'] = __('the accessibility statement', 'rrze-shorturl');
                        $meta['active'] = 0;
                    }

                    $long_url = $entry['wmp']['long_url'] ?? '';
                    if ($long_url)
                        $this->insert_or_update_domain($long_url, $meta);
                }
            } catch (CustomException $e) {
                error_log('Fetch error: ' . $e->getMessage());
            }
        }
    }

    private function insert_or_update_domain(string $url, array $meta)
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (!$host)
            return;

        // Debug: Host prüfen
        echo "<script>alert('Host: " . esc_js($host) . "');</script>";

        if (empty($meta['active'])) {
            try {
                $response = wp_remote_get("https://www.wmp.rrze.fau.de/suche/impressum/{$host}/format/json", ['timeout' => 10]);
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $json = json_decode(wp_remote_retrieve_body($response), true);
                    $meta['webmaster_name'] = $json['webmaster']['name'] ?? __('Name not found', 'rrze-shorturl');
                    $meta['webmaster_email'] = $json['webmaster']['email'] ?? __('Email not found', 'rrze-shorturl');

                    // Debug: Webmaster Info
                    echo "<script>alert('Webmaster Name: " . esc_js($meta['webmaster_name']) . "\\nEmail: " . esc_js($meta['webmaster_email']) . "');</script>";
                }
            } catch (CustomException $e) {
                error_log('Webmaster fetch error: ' . $e->getMessage());
                echo "<script>alert('Webmaster fetch error: " . esc_js($e->getMessage()) . "');</script>";
            }
        }

        $name = sanitize_title($host);
        $existing = get_posts([
            'post_type' => 'shorturl_domain',
            'post_name' => $name,
            'post_status' => 'all',
            'numberposts' => 1,
            'fields' => 'ids',
        ]);

        if ($existing) {
            $id = $existing[0];
            foreach ($meta as $key => $value)
                update_post_meta($id, $key, $value);

            // Debug: Domain aktualisiert
            echo "<script>alert('Domain aktualisiert: " . esc_js($host) . " (ID: " . esc_js($id) . ")');</script>";
        } else {
            $id = wp_insert_post([
                'post_name' => $name,
                'post_title' => $host,
                'post_type' => 'shorturl_domain',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($id)) {
                foreach ($meta as $key => $value)
                    update_post_meta($id, $key, $value);

                // Debug: Domain neu eingefügt
                echo "<script>alert('Domain eingefügt: " . esc_js($host) . " (ID: " . esc_js($id) . ")');</script>";
            } else {
                error_log("Insert error: $host");
                echo "<script>alert('Insert error: " . esc_js($host) . "');</script>";
            }
        }
    }
}