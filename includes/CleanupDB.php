<?php
namespace RRZE\ShortURL;

class CleanupDB
{
    public function __construct()
    {
        add_action('rrze_shorturl_cleanup_database', array($this, 'cleanup_database'));

        // Define the custom "monthly" interval
        add_filter('cron_schedules', function ($schedules) {
            $schedules['monthly'] = array (
                'interval' => 30 * DAY_IN_SECONDS,
                'display' => __('Monthly')
            );
            return $schedules;
        });

        if (!wp_next_scheduled('rrze_shorturl_cleanup_database')) {
            // let the job run monthly
            wp_schedule_event(time(), 'monthly', 'rrze_shorturl_cleanup_database');
        }
    }

    public function cleanup_database()
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
        } catch (\Exception $e) {
            error_log('An error occurred: ' . $e->getMessage());
        }
    }

}
