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

        $shortened_url = ShortURL::shorten($url_to_shorten);
        
        // Return the shortened URL in the response
        return rest_ensure_response($shortened_url);
    }
}

