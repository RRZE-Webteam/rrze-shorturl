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
            $schedules['monthly'] = array(
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
    try {
        // Set up arguments for WP_Query to fetch all 'idm' posts that have no associated 'link' posts
        $args = [
            'post_type'      => 'idm',    // Custom Post Type for IdMs
            'posts_per_page' => -1,       // Fetch all IdMs
            'meta_query'     => [
                [
                    'key'     => 'idm',   // Exclude system IdM
                    'value'   => 'system',
                    'compare' => '!='
                ]
            ]
        ];

        // Execute the query to get all non-system IdMs
        $query = new \WP_Query($args);

        // Array to store unused IdM post IDs
        $unused_idms = [];

        // Loop through the IdMs to check if they are associated with any links
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();

                $idm_id = get_the_ID();

                // Check if there are any links associated with this IdM
                $link_query = new \WP_Query([
                    'post_type'      => 'link',
                    'posts_per_page' => 1,
                    'meta_query'     => [
                        [
                            'key'     => 'idm_id',
                            'value'   => $idm_id,
                            'compare' => '='
                        ]
                    ]
                ]);

                // If no links found, mark this IdM as unused
                if (!$link_query->have_posts()) {
                    $unused_idms[] = $idm_id;
                }

                // Restore original Post Data
                wp_reset_postdata();
            }
        }

        // Restore original Post Data
        wp_reset_postdata();

        // If there are unused IdMs, delete them
        if (!empty($unused_idms)) {
            foreach ($unused_idms as $idm_id) {
                wp_delete_post($idm_id, true); // Force delete the unused IdMs
            }
        }
    } catch (CustomException $e) {
        error_log('An error occurred: ' . $e->getMessage());
    }
}

    // public function cleanInactiveIdMs()
    // {
    //     global $wpdb;

    //     try {
    //         // cleanup table shorturl_idms
    //         // fetch the IdMs that didn't create any links
    //         $query = "SELECT {$wpdb->prefix}shorturl_idms.id AS 'unused_id' FROM {$wpdb->prefix}shorturl_idms LEFT JOIN {$wpdb->prefix}shorturl_links ON {$wpdb->prefix}shorturl_idms.id = {$wpdb->prefix}shorturl_links.idm_id WHERE {$wpdb->prefix}shorturl_links.idm_id IS NULL AND {$wpdb->prefix}shorturl_idms.idm != 'system'";
    //         $result = $wpdb->get_results($query, ARRAY_A);

    //         if (empty($result)) {
    //             return;
    //         }

    //         // delete entries in shorturl_idms
    //         $idms_ids = array_column($result, 'unused_id');
    //         $idms_ids_str = implode(',', $idms_ids);
    //         $wpdb->query("DELETE FROM {$wpdb->prefix}shorturl_idms WHERE id IN ({$idms_ids_str})");
    //     } catch (CustomException $e) {
    //         error_log('An error occurred: ' . $e->getMessage());
    //     }
    // }


    public static function cleanInvalidLinks()
{
    try {
        // Query to find links with 'valid_until' date in the past and update them to inactive
        $args = [
            'post_type'      => 'link',  // The Custom Post Type for links
            'posts_per_page' => -1,      // Get all links
            'meta_query'     => [
                [
                    'key'     => 'valid_until',
                    'value'   => date('Y-m-d'),
                    'compare' => '<',
                    'type'    => 'DATE'
                ],
                [
                    'key'     => 'valid_until',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        // Fetch all links with expired 'valid_until'
        $expired_links = new \WP_Query($args);

        // Set the expired links as inactive
        if ($expired_links->have_posts()) {
            while ($expired_links->have_posts()) {
                $expired_links->the_post();
                update_post_meta(get_the_ID(), 'active', 0);
            }
        }

        // Reset post data after the query
        wp_reset_postdata();

        // Now query all active links
        $args = [
            'post_type'      => 'link',
            'posts_per_page' => -1,  // Get all active links
            'meta_query'     => [
                [
                    'key'     => 'active',
                    'value'   => '1',
                    'compare' => '='
                ]
            ],
            'orderby'        => 'created_at',
            'order'          => 'DESC'
        ];

        $active_links = new \WP_Query($args);

        // Loop through the active links and check their validity
        if ($active_links->have_posts()) {
            while ($active_links->have_posts()) {
                $active_links->the_post();
                $long_url = get_post_meta(get_the_ID(), 'long_url', true);

                // Perform a remote GET request to the long URL
                $response = wp_remote_get($long_url);

                if (is_wp_error($response)) {
                    // Log the error and continue with the next URL
                    error_log("Error fetching URL: " . $long_url);
                    continue;
                }

                $http_code = wp_remote_retrieve_response_code($response);
                if ($http_code >= 400 && $http_code < 500) {
                    // Set active = 0 for invalid URLs
                    update_post_meta(get_the_ID(), 'active', 0);
                }
            }
        }

        // Reset post data after the query
        wp_reset_postdata();

    } catch (CustomException $e) {
        error_log("Error cleaning invalid links: " . $e->getMessage());
        return json_encode(array('error' => 'An error occurred while cleaning short URLs.'));
    }
}

    // public static function cleanInvalidLinks()
    // {
    //     global $wpdb;

    //     try {
    //         // Set active = 0 for URLs with valid_until in the past
    //         $wpdb->query(
    //             "UPDATE {$wpdb->prefix}shorturl_links 
    //                 SET active = 0 
    //                 WHERE valid_until IS NOT NULL 
    //                 AND valid_until < CURDATE()"
    //             );

    //         // Fetch active short URLs
    //         $query = "SELECT id, long_url 
    //                   FROM {$wpdb->prefix}shorturl_links 
    //                   WHERE active = 1 
    //                   ORDER BY created_at DESC";
    //         $results = $wpdb->get_results($query, ARRAY_A);

    //         foreach ($results as $result) {
    //             $response = wp_remote_get($result['long_url']);

    //             if (is_wp_error($response)) {
    //                 // Log the error and continue with the next URL
    //                 error_log("Error fetching URL: " . $result['long_url']);
    //                 continue;
    //             }

    //             $http_code = wp_remote_retrieve_response_code($response);
    //             if ($http_code >= 400 && $http_code < 500) {
    //                 // Set active = 0 for invalid URLs
    //                 $update_query = $wpdb->prepare(
    //                     "UPDATE {$wpdb->prefix}shorturl_links SET active = 0 WHERE id = %d",
    //                     $result['id']
    //                 );
    //                 $wpdb->query($update_query);
    //             }
    //         }
    //     } catch (CustomException $e) {
    //         error_log("Error fetching active short URLs: " . $e->getMessage());
    //         return json_encode(array('error' => 'An error occurred while fetching short URLs.'));
    //     }
    // }

}
