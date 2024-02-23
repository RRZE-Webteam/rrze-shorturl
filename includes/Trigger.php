<?php

namespace RRZE\ShortURL;

class Trigger
{
    public function __construct()
    {
        // Hook the create_shorturl_links_post method to the wpdb::insert action
        add_action('shortlink_inserted', array($this, 'create_shorturl_post'), 10, 3);
    }

    public function create_shorturl_post(array $data)
    {
        global $wpdb;

        try {
            // Get the category IDs and tag IDs from the inserted data
            $shorturl_id = (!empty($data['shorturl_id']) ? $data['shorturl_id'] : null);
            $category_id = (!empty($data['category_id']) ? $data['category_id'] : null);
            $tag_ids = (!empty($data['tag_ids']) ? $data['tag_ids'] : []);

            $post_data = array(
                'post_title' => 'Short URL ' . $shorturl_id, // Example post title
                'post_status' => 'publish',
                'post_type' => 'shorturl_links', // Your custom post type
            );

            // Insert the post into the database
            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // Store shorturl_id as post meta
            if (!empty($shorturl_id)) {
                update_post_meta($post_id, 'shorturl_id', $shorturl_id);
            }

            // Assign categories and tags to the post
            if (!empty($category_id)) {
                wp_set_object_terms($post_id, $category_id, 'shorturl_category');
            }
            if (!empty($tag_ids)) {
                wp_set_object_terms($post_id, $tag_ids, 'shorturl_tag');
            }
        } catch (Exception $e) {
            // Handle exception
            error_log('Error creating short URL post: ' . $e->getMessage());
            // Optionally, you can display an error message to the user
        }
    }

}

