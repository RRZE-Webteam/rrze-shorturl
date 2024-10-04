<?php

namespace RRZE\ShortURL;

// use \RRZE\AccessControl\Permissions;
use RRZE\ShortURL\CustomException;

class Rights
{
    private $idm = null;

    public function __construct()
    {
        $this->idm = 'system';
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $this->idm = $current_user->user_nicename;
        } elseif (class_exists('\RRZE\AccessControl\Permissions')) {
            $permissionsInstance = new Permissions();
            $checkSSOLoggedIn = $permissionsInstance->checkSSOLoggedIn();
            $personAttributes = $permissionsInstance->personAttributes;
            $this->idm = (!empty($personAttributes['uid'][0]) ? $personAttributes['uid'][0] : null);
        } else {
            error_log('\RRZE\AccessControl\Permissions is not available');
        }
        add_action('init', [$this, 'getRights']);
    }

    public function getRights(): array
    {
        // Default return array with no rights
        $aRet = [
            'id' => 0,
            'allow_uri' => false,
            'allow_get' => false,
            'allow_utm' => false
        ];

        try {
            // Set up WP_Query to search for the IDM Custom Post Type with the provided 'idm'
            $args = [
                'post_type' => 'shorturl_idm',  // The Custom Post Type for IDMs
                'posts_per_page' => 1,      // We only need one result
                'fields' => 'ids',
                'name' => sanitize_title($this->idm)
            ];

            // Execute the query
            $query = new \WP_Query($args);

            // Check if a matching IDM post was found
            if (!empty($query->posts)) {
                $post_id = $query->posts[0];

                // Fetch the rights from the post meta
                $aRet['id'] = $post_id;
                $aRet['allow_uri'] = (bool) get_post_meta($post_id, 'allow_uri', true);
                $aRet['allow_get'] = (bool) get_post_meta($post_id, 'allow_get', true);
                $aRet['allow_utm'] = (bool) get_post_meta($post_id, 'allow_utm', true);

                // Restore original Post Data
                wp_reset_postdata();
            } else {
                // If no matching IDM post exists, create a new one if 'idm' is provided
                if (!empty($this->idm)) {
                    $post_data = [
                        'post_title' => $this->idm,  // Using the IDM as the title for the post
                        'post_type' => 'shorturl_idm',
                        'post_status' => 'publish'
                    ];

                    // Insert the new IDM post
                    $inserted_post_id = wp_insert_post($post_data);

                    if ($inserted_post_id) {
                        // If the post was successfully inserted, add 'idm' meta data
                        update_post_meta($inserted_post_id, 'idm', $this->idm);
                        $aRet['id'] = $inserted_post_id;
                    }
                }
            }

            return $aRet;
        } catch (CustomException $e) {
            // Log the error and return the default rights array
            error_log('Error fetching rights: ' . $e->getMessage());
            return $aRet;
        }
    }

}
