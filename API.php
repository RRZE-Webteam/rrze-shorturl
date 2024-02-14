<?php

namespace RRZE\ShortURL;

// Include the UniportalShortURL class
require_once plugin_dir_path(__FILE__) . 'UniportalShortURL.php';

// Define the REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('uniportal-short-url/v1', '/shorten-url', array(
        'methods' => 'POST',
        'callback' => 'uniportal_shorten_url_callback',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
    ));
});

// Callback function to handle the REST API request
function uniportal_shorten_url_callback($request) {
    $parameters = $request->get_json_params();

    // Get the URL to shorten from the request parameters
    $url_to_shorten = $parameters['url'];

    // Shorten the URL using the UniportalShortURL class
    $shortened_url = UniportalShortURL::shorten($url_to_shorten);

    // Store the original and shortened URLs in the database
    global $wpdb;
    $table_name = $wpdb->prefix . 'short_urls';
    $wpdb->insert($table_name, array(
        'original_url' => $url_to_shorten,
        'shortened_url' => $shortened_url,
        'created_at' => current_time('mysql'),
    ));

    // Return the shortened URL in the response
    return rest_ensure_response($shortened_url);
}
