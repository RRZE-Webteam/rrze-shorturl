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
            'callback' => array($this, 'get_shorturl_categories'),
        ));

        register_rest_route('short-url/v1', '/add-category', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_add_category_request'),
            // 'permission_callback' => function () {
            //     return current_user_can('manage_options');
            // },
        ));        
    }


    public function handle_add_category_request($request) {

        $parameters = $request->get_json_params();
    
        if (empty($parameters['label'])) {
            return new WP_Error('invalid_name', 'Category label is required.', array('status' => 400));
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_categories';
    
        $inserted = $wpdb->insert($table_name, array('label' => sanitize_text_field($parameters['label'])));
    
        if (!$inserted) {
            error_log('KONNTE NICHT GEINSERTED WERDEN');
            return new WP_Error('insert_failed', 'Failed to add category to the database.', array('status' => 500));
        }
    
        $category_id = $wpdb->insert_id;
        error_log('PASST');
    
        $categories = array('id' => $category_id, 'label' => $parameters['label']);
        
        return new WP_REST_Response($categories, 200);

    }

    
    public function get_shorturl_categories() {
        global $wpdb;
    
        try {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories", ARRAY_A);
    
            if (is_wp_error($categories)) {
                throw new Exception('Error retrieving shorturl categories');
            }
    
            return new WP_REST_Response($categories, 200);
        } catch (Exception $e) {
            return new WP_Error('shorturl_categories_error', $e->getMessage(), array('status' => 500));
        }
    }
    
    public function shorten_url_callback($request) {
        try {
            $parameters = $request->get_json_params();

            // Check if the 'url' parameter is provided
            if (!isset($parameters['url'])) {
                throw new WP_Error('missing_url_parameter', 'URL parameter is missing.');
            }

            // Shorten the URL
            $shortened_url = ShortURL::shorten($parameters);

            // Return the shortened URL in the response
            return rest_ensure_response($shortened_url);
        } catch (\Exception $e) {
            // Handle any exceptions that occur
            // You can log the error, return a WP_Error, or handle it in any other way appropriate for your application
            error_log('Error in shorten_url_callback: ' . $e->getMessage());
            return new WP_Error('callback_error', 'Error processing request.');
        }
    }

    public function get_active_short_urls_callback($request) {
        try {
            // Call the getActiveShortURLs method from the ShortURL class
            $active_short_urls = ShortURL::getActiveShortURLs();
            
            // Return the active short URLs as JSON
            return new WP_REST_Response($active_short_urls, 200);
        } catch (\Exception $e) {
            // Handle any exceptions that occur
            // You can log the error, return a WP_Error, or handle it in any other way appropriate for your application
            error_log('Error in get_active_short_urls_callback: ' . $e->getMessage());
            return new WP_Error('callback_error', 'Error processing request.');
        }
    }
}
