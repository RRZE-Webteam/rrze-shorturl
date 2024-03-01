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

    function add_tag_callback( $request ) {
        global $wpdb;
        
        $tag_label = $request->get_param( 'label' );
    
        if ( empty( $tag_label ) ) {
            return new WP_Error( 'missing_tag_label', __( 'Tag label is required.' ), array( 'status' => 400 ) );
        }
    
        // Check if tag already exists
        $existing_tag = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shorturl_tags WHERE label = %s", $tag_label ) );
    
        if ( ! $existing_tag ) {
            // Tag does not exist, so add it
            $wpdb->insert( $wpdb->prefix . 'shorturl_tags', array( 'label' => $tag_label ) );
    
            // Return the newly added tag
            $tag_id = $wpdb->insert_id;
            $response = array(
                'id'    => $tag_id,
                'label' => $tag_label,
            );
    
            return rest_ensure_response( $response );
        } else {
            // Tag already exists
            return new WP_Error( 'tag_already_exists', __( 'Tag already exists.' ), array( 'status' => 400 ) );
        }
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
        return rest_ensure_response( $tag_data );
    }
    

    public function add_category_callback($request) {

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

    
    public function get_categories_callback() {
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
