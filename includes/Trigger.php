<?php

namespace RRZE\ShortURL;

class Trigger
{
    public function __construct()
    {
        // Hook the create_shorturl_links_post method to the wpdb::insert action
        add_action('shortlink_inserted', array($this, 'create_shorturl_links_post'), 10, 3);
    }

    /**
     * Create a WordPress post for each row inserted into the shorturl_links table.
     *
     * @param string $table The name of the table that was inserted into.
     * @param array $data Data that was inserted into the table.
     * @param array $format Data format.
     */
    public function create_shorturl_links_post(array $data)
    {
        global $wpdb;

        // Log information about the hook trigger
        error_log('create_shorturl_links_post() hook triggered');
        error_log('Data received:');
        error_log(print_r($data, true));

        if (is_array($data)) {
            // Get the ID of the last inserted row
            $id = (!empty($data['id']) ? $data['id'] : null);

            if (empty($id)) {
                return;
            }

            // Get the category IDs and tag IDs from the inserted data
            $id = (!empty($data['id']) ? $data['id'] : null);
            $category_id = (!empty($data['categories']) ? $data['categories'] : null);
            $tag_ids = isset($data['tags']) ? $data['tags'] : [];

            error_log('Inserted $category_id: ' . $category_id);
            error_log('Inserted $category_id: ' . json_encode($tag_ids));

            $post_data = array(
                'post_title' => 'Short URL Post ' . $id, // Example post title
                'post_status' => 'publish',
                'post_type' => 'shorturl_links', // Your custom post type
            );

            // Insert the post into the database
            $post_id = wp_insert_post($post_data);

            // Log the inserted post ID
            error_log('Inserted post ID: ' . $post_id);

            // Assign categories and tags to the post
            if (!empty($category_id)) {
                wp_set_object_terms( $post_id, $category_id, 'shorturl_category' );
            }
        }
    }

}

