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
    }

    public static function makeURL($url, $getParameter = '') {
        if ($getParameter === '') {
            return $url;
        }

        $getParameter = trim($getParameter, '?');

        if (strpos($url, '?') !== false) {
            return $url . '&' . $getParameter;
        } else {
            return $url . '?' . $getParameter;
        }
    }

    public function shorten_url_callback($request) {
        try {
            $parameters = $request->get_json_params();

            // Check if the 'url' parameter is provided
            if (!isset($parameters['url'])) {
                throw new WP_Error('missing_url_parameter', 'URL parameter is missing.');
            }

            // Get the URL to shorten from the request parameters
            $url_to_shorten = self::makeURL($parameters['url'], $parameters['getparameter']);

            // Shorten the URL
            $shortened_url = ShortURL::shorten($url_to_shorten, $parameters['uri']);

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
