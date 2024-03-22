<?php
namespace RRZE\ShortURL;

use WP_REST_Response;
use WP_Error;

class API {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    }

    public function register_rest_endpoints() {
        register_rest_route('short-url/v1', '/shorten', array(
            'methods' => 'POST',
            'callback' => array($this, 'shorten_url_callback'),
        ));

        register_rest_route('short-url/v1', '/active-short-urls', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_active_short_urls_callback'),
        ));

        register_rest_route('short-url/v1', '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_categories_callback'),
        ));

        register_rest_route('short-url/v1', '/add-category', array(
            'methods' => 'POST',
            'callback' => array($this, 'add_category_callback'),
            // 'permission_callback' => function () {
            //     return current_user_can('manage_options');
            // },
        ));  
        
        register_rest_route('short-url/v1', '/tags', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tags_callback'),
        ));

        register_rest_route( 'short-url/v1', '/add-tag', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'add_tag_callback'),
            // 'permission_callback' => 'rest_allow_authenticated', // Set your permission callback function
        ) );
    }

    public function add_tag_callback($request) {

        $parameters = $request->get_json_params();
    
        if (empty($parameters['label'])) {
            return new WP_Error('invalid_name', __('Tag label is required.', 'rrze-shorturl'), array('status' => 400));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_tags';
    
        $inserted = $wpdb->insert($table_name, array('label' => sanitize_text_field($parameters['label'])));
    
        if (!$inserted) {
            return new WP_Error('insert_failed', __('Failed to add tag to the database.', 'rrze-shorturl'), array('status' => 500));
        }
    
        $tag_id = $wpdb->insert_id;
    
        $tags = array('id' => $tag_id, 'label' => $parameters['label']);

        return new WP_REST_Response($tags, 200);

    }
    
    // Callback function to get tags
    function get_tags_callback( $request ) {
        global $wpdb;
    
        // Query tags from the database
        $tags = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}shorturl_tags" );
    
        // Initialize an empty array to store tag data
        $tag_data = array();
    
        // Loop through the tags and extract required data
        foreach ( $tags as $tag ) {
            $tag_data[] = array(
                'id'    => $tag->id,
                'label' => $tag->label,
            );
        }
    
        // Return the tag data as a JSON response
        return new WP_REST_Response($tag_data, 200);
    }
    

    public function add_category_callback($request) {

        $parameters = $request->get_json_params();
    
        if (empty($parameters['label'])) {
            return new WP_Error('invalid_name', __('Category label is required.', 'rrze-shorturl'), array('status' => 400));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_categories';
    
        $inserted = $wpdb->insert($table_name, array('label' => sanitize_text_field($parameters['label'])));
    
        if (!$inserted) {
            return new WP_Error('insert_failed', __('Failed to add category to the database.', 'rrze-shorturl'), array('status' => 500));
        }
    
        $category_id = $wpdb->insert_id;
    
        $categories = array('id' => $category_id, 'label' => $parameters['label']);

        return new WP_REST_Response($categories, 200);
    }

    
    public function get_categories_callback() {
        global $wpdb;
    
        try {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories", ARRAY_A);
    
            if (is_wp_error($categories)) {
                throw new Exception(__('Error retrieving shorturl categories', 'rrze-shorturl'));
            }
    
            return new WP_REST_Response($categories, 200);
        } catch (Exception $e) {
            return new WP_Error('shorturl_categories_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    public function shorten_url_callback($request) {
        try {
            $parameters = $request->get_json_params();

            if (!isset($parameters['url'])) {
                throw new WP_Error('missing_url_parameter', __('URL parameter is missing.', 'rrze-shorturl'));
            }

            $shortened_url = ShortURL::shorten($parameters);

            return new WP_REST_Response($shortened_url, 200);
        } catch (\Exception $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }

    public function get_active_short_urls_callback($request) {
        try {
            $active_short_urls = ShortURL::getActiveShortURLs();
            
            return new WP_REST_Response($active_short_urls, 200);
        } catch (\Exception $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }
}
