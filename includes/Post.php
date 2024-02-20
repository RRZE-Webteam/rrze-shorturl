<?php

namespace RRZE\ShortURL;

class Post {
    public function __construct() {
        // Hook the create_shorturl_links_post method to the wpdb::insert action
        add_action('wp_insert_post', array($this, 'create_shorturl_links_post'), 10, 3);
    }

    /**
     * Create a WordPress post for each row inserted into the shorturl_links table.
     *
     * @param string $table The name of the table that was inserted into.
     * @param array $data Data that was inserted into the table.
     * @param array $format Data format.
     */
    public function create_shorturl_links_post($table, $data, $format) {
        global $wpdb;
    
        // Log information about the hook trigger
        error_log('create_shorturl_links_post() hook triggered with table: ' . $table);
        error_log('Data received:');
        error_log(print_r($data, true));
        error_log('Data format:');
        error_log(print_r($format, true));
    
        // Check if the inserted table is shorturl_links and if the data is provided
        if ($table === $wpdb->prefix . 'shorturl_links' && is_array($data)) {
            // Get the ID of the last inserted row
            $id = $wpdb->insert_id;
    
            // Log the inserted row ID
            error_log('Last inserted ID: ' . $id);
    
            // Get the category IDs and tag IDs from the inserted data
            $category_ids = isset($data['category']) ? $data['category'] : array();
            $tag_ids = isset($data['tags']) ? $data['tags'] : array();
    
            // Log the category and tag IDs
            error_log('Category IDs:');
            error_log(print_r($category_ids, true));
            error_log('Tag IDs:');
            error_log(print_r($tag_ids, true));
    
            // Create WordPress post for each row inserted
            foreach ($category_ids as $category_id) {
                // Set up post data
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
                    wp_set_post_categories($post_id, array($category_id)); // Set categories
                }
                if (!empty($tag_ids)) {
                    wp_set_post_tags($post_id, $tag_ids); // Set tags
                }
            }
        }
    }
    
}

new Post(); // Instantiate the class to trigger the constructor and hook the method
