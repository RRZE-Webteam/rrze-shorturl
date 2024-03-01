<?php

namespace RRZE\ShortURL;

class Trigger
{
    public function __construct()
    {
        // Hook the create_shorturl_links_post method to the wpdb::insert action
        add_action('shortlink_inserted', array($this, 'create_shorturl_post'), 10, 3);

        // Hook the delete_shorturl_links_entry method to the before_delete_post action
        add_action('before_delete_post', array($this, 'delete_shorturl_links_entry'));
    }

    public function create_shorturl_post(array $data)
    {
        global $wpdb;

        try {
            $data['shorturl_id'] = (!empty($data['shorturl_id']) ? $data['shorturl_id'] : null);
            $data['valid_until'] = (!empty($data['valid_until']) ? $data['valid_until'] : null);
            $data['category_id'] = (!empty($data['category_id']) ? $data['category_id'] : null);
            $data['tag_ids'] = (!empty($data['tag_ids']) ? $data['tag_ids'] : []);
            $data['short_url'] = (!empty($data['short_url']) ? $data['short_url'] : []);
            $data['long_url'] = (!empty($data['long_url']) ? $data['long_url'] : []);

            $post_data = array(
                'post_title' => 'Short URL ' . $data['shorturl_id'], // Example post title
                'post_status' => 'publish',
                'post_type' => 'shorturl_links', // Your custom post type
            );

            // Insert the post into the database
            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }

            // store all post_meta
            if (!empty($post_id)) {
                foreach ($data as $key => $value) {
                    $meta_id = update_post_meta($post_id, $key, $data[$key]);
                    if (!$meta_id) {
                        error_log("could not update post_meta $key");
                    }
                }
            }

            // Assign categories and tags to the post
            if (!empty($category_id)) {
                wp_set_object_terms($post_id, $category_id, '   ');
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


    /**
     * Delete the corresponding row in the shorturl_links table when a shorturl_links post is deleted.
     *
     * @param int $post_id The ID of the post being deleted.
     */
    public function delete_shorturl_links_entry($post_id)
    {
        try {
            // Check if the post being deleted is of the 'shorturl_links' post type
            if (get_post_type($post_id) === 'shorturl_links') {
                // Get the shorturl_id meta value associated with the post
                $shorturl_id = get_post_meta($post_id, 'shorturl_id', true);

                // If a shorturl_id exists, delete the corresponding row from the shorturl_links table
                if (!empty($shorturl_id)) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'shorturl_links';

                    // Delete the row with the corresponding shorturl_id
                    $wpdb->delete($table_name, array('id' => $shorturl_id), array('%d'));
                }
            }
        } catch (Exception $e) {
            // Handle any exceptions that may occur during the deletion process
            error_log('Error deleting shorturl_links entry: ' . $e->getMessage());
            // You can add additional error handling logic here, such as displaying an error message to the user.
        }
    }

}

