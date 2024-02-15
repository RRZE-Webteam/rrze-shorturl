<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\ShortURL;

class API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    }

    public function register_rest_endpoints() {
        register_rest_route('short-url/v1', '/shorten', array(
            'methods' => 'POST',
            'callback' => array($this, 'uniportal_shorten_url_callback'),
            // 'permission_callback' => function () {
            //     return current_user_can('edit_posts');
            // },
        ));
    }
    public function uniportal_shorten_url_callback($request) {
        $parameters = $request->get_json_params();

        // Get the URL to shorten from the request parameters
        $url_to_shorten = $parameters['url'];

        // // Check if the original URL already exists in the database
        // global $wpdb;
        // $table_name = $wpdb->prefix . 'short_urls';
        // $existing_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE original_url = %s", $url_to_shorten));

        // if ($existing_row) {
        //     // If the original URL exists, update the corresponding row
        //     $wpdb->update($table_name, array('shortened_url' => $existing_row->shortened_url), array('id' => $existing_row->id));
        //     $shortened_url = $existing_row->shortened_url;
        // } else {
        //     // If the original URL doesn't exist, shorten it and insert a new row
            $shortened_url = ShortURL::shorten($url_to_shorten);
        //     $wpdb->insert($table_name, array(
        //         'original_url' => $url_to_shorten,
        //         'shortened_url' => $shortened_url,
        //         'created_at' => current_time('mysql'),
        //     ));
        // }

        // Return the shortened URL in the response
        return rest_ensure_response($shortened_url);
    }
}

