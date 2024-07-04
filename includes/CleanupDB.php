<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class CleanupDB
{
    public function __construct()
    {
        add_action('rrze_shorturl_cleanup_inactive_idms', array($this, 'cleanInactiveIdMs'));

        // Define the custom "monthly" interval
        add_filter('cron_schedules', function ($schedules) {
            $schedules['monthly'] = array (
                'interval' => 30 * DAY_IN_SECONDS,
                'display' => __('Monthly')
            );
            return $schedules;
        });

        if (!wp_next_scheduled('rrze_shorturl_cleanup_inactive_idms')) {
            // let the job run monthly
            wp_schedule_event(time(), 'monthly', 'rrze_shorturl_cleanup_inactive_idms');
        }

        add_action('rrze_shorturl_cleanup_invalid_links', array($this, 'cleanInvalidLinks'));

        if (!wp_next_scheduled('rrze_shorturl_cleanup_invalid_links')) {
            // let the job run daily at 2:00 a.m.
            wp_schedule_event(strtotime('today 2:00'), 'daily', 'rrze_shorturl_cleanup_invalid_links');
        }
    }

    public function cleanInactiveIdMs()
    {
        global $wpdb;

        try {
            // cleanup table shorturl_idms
            // fetch the IdMs that didn't create any links
            $query = "SELECT {$wpdb->prefix}shorturl_idms.id AS 'unused_id' FROM {$wpdb->prefix}shorturl_idms LEFT JOIN {$wpdb->prefix}shorturl_links ON {$wpdb->prefix}shorturl_idms.id = {$wpdb->prefix}shorturl_links.idm_id WHERE {$wpdb->prefix}shorturl_links.idm_id IS NULL AND {$wpdb->prefix}shorturl_idms.idm != 'system'";
            $result = $wpdb->get_results($query, ARRAY_A);

            if (empty($result)) {
                return;
            }

            // delete entries in shorturl_idms
            $idms_ids = array_column($result, 'unused_id');
            $idms_ids_str = implode(',', $idms_ids);
            $wpdb->query("DELETE FROM {$wpdb->prefix}shorturl_idms WHERE id IN ({$idms_ids_str})");
        } catch (CustomException $e) {
            error_log('An error occurred: ' . $e->getMessage());
        }
    }


    public static function cleanInvalidLinks()
    {
        global $wpdb;
    
        try {
            // Fetch active short URLs
            $query = "SELECT id, long_url 
                      FROM {$wpdb->prefix}shorturl_links 
                      WHERE active = 1 
                      ORDER BY created_at DESC";
            $results = $wpdb->get_results($query, ARRAY_A);
    
            foreach ($results as $result) {
                $response = wp_remote_get($result['long_url']);
    
                if (is_wp_error($response)) {
                    // Log the error and continue with the next URL
                    error_log("Error fetching URL: " . $result['long_url']);
                    continue;
                }
    
                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code >= 400 && $http_code < 500) {
                    // Set active = 0 for invalid URLs
                    $update_query = $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}shorturl_links SET active = 0 WHERE id = %d",
                        $result['id']
                    );
                    $wpdb->query($update_query);
                }
            }    
        } catch (CustomException $e) {
            error_log("Error fetching active short URLs: " . $e->getMessage());
            return json_encode(array('error' => 'An error occurred while fetching short URLs.'));
        }
    }
    
}
