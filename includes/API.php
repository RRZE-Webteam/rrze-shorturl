<?php
namespace RRZE\ShortURL;

use WP_REST_Response;
use WP_Error;
use RRZE\ShortURL\CustomException;

class API
{
    protected static $rights;
    protected static $aAllowedIPs;

    public function __construct()
    {
        flush_rewrite_rules();
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));

        $rightsObj = new Rights();
        self::$rights = $rightsObj->getRights();

        $options = json_decode(get_option('rrze-shorturl'), true);

        self::$aAllowedIPs = [];
        if (!empty($options['allowed_ip_addresses'])) {
            self::$aAllowedIPs = array_map('trim', explode("\n", $options['allowed_ip_addresses']));
        }
    }

    public function register_rest_endpoints()
    {
        register_rest_route(
            'wp/v2/shorturl/',
            '/shorten',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'shorten_url_callback'),
                'permission_callback' => function () {
                    return self::$rights['id'] !== 0;
                }
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/get-longurl',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_longurl_callback'),
                // 'permission_callback' => [$this, 'is_ip_allowed']
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/active-shorturls',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_active_shorturls_callback'),
                // 'permission_callback' => [$this, 'is_ip_allowed']
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/categories',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_categories_callback'),
                'permission_callback' => function () {
                    return self::$rights['id'] !== 0;
                }
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/add-category',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'add_category_callback'),
                'permission_callback' => function () {
                    return self::$rights['id'] !== 0;
                }
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/services',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_services_callback'),
                // 'permission_callback' => [$this, 'is_ip_allowed']
            )
        );


        register_rest_route(
            'wp/v2/shorturl/',
            '/decrypt',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_decrypt_callback'),
                // 'permission_callback' => [$this, 'is_ip_allowed']
            )
        );

        register_rest_route(
            'wp/v2/shorturl/',
            '/encrypt',
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_encrypt_callback'),
                // 'permission_callback' => [$this, 'is_ip_allowed']
            )
        );

        // Register REST API query filters
        add_action('rest_api_init', [$this, 'addRestQueryFilters']);
    }

    public function is_ip_allowed()
    {
        $client_ip = $this->get_client_ip();

        foreach (self::$aAllowedIPs as $allowed_ip) {
            if ($this->ip_in_range($client_ip, $allowed_ip)) {
                return true;
            }
        }

        return false;
    }

    private function get_client_ip()
    {
        $ip = 0;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    private function ip_in_range($ip, $range)
    {
        // if it is a single IP-address
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);
        $subnet = ip2long($subnet);
        $ip = ip2long($ip);
        $mask = -1 << (32 - $bits);

        return ($ip & $mask) == ($subnet & $mask);
    }



    /**
     * Add filters to the REST API query
     */
    public function addRestQueryFilters()
    {
        // Add filter parameters to the object query
        add_filter('rest_shorturl_query', [$this, 'addFilterParam'], 10, 2);
        // Add filter parameters to the categories query
        add_filter('rest_shorturl_category_query', [$this, 'addFilterParam'], 10, 2);
        // Add filter parameters to the tags query
        add_filter('rest_shorturl_tag_query', [$this, 'addFilterParam'], 10, 2);
    }

    /**
     * Add filter parameters to the query
     *
     * @param array $args
     * @param array $request
     * @return array
     */
    public function addFilterParam($args, $request)
    {
        if (empty($request['filter']) || !is_array($request['filter'])) {
            return $args;
        }
        global $wp;
        $filter = $request['filter'];

        $vars = apply_filters('query_vars', $wp->public_query_vars);
        foreach ($vars as $var) {
            if (isset($filter[$var])) {
                $args[$var] = $filter[$var];
            }
        }
        return $args;
    }

    public function add_category_callback($request)
    {
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

    public function get_categories_callback()
    {
        try {
            // Get all terms from the 'link_category' taxonomy
            $categories = get_terms([
                'taxonomy' => 'link_category',
                'hide_empty' => false             // don't hide terms with no posts
            ]);

            // Check for errors while retrieving terms
            if (is_wp_error($categories)) {
                throw new Exception(__('Error retrieving shorturl categories', 'rrze-shorturl'));
            }

            // Return the categories in the REST response
            return new WP_REST_Response($categories, 200);

        } catch (Exception $e) {
            // Return a WP_Error object in case of an error
            return new WP_Error('shorturl_categories_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function shorten_url_callback($request)
    {
        try {
            $parameters = $request->get_json_params();

            if (!isset($parameters['url'])) {
                return new WP_REST_Response(array('error' => 'missing_url_parameter', 'message' => __('url parameter is missing.', 'rrze-shorturl')), 400);
            }

            $shortened_url = ShortURL::shorten($parameters);

            return new WP_REST_Response($shortened_url, 200);
        } catch (CustomException $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }

    public function get_active_shorturls_callback($request)
    {
        try {
            $active_short_urls = ShortURL::getActiveShortURLs();

            return new WP_REST_Response($active_short_urls, 200);
        } catch (CustomException $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }

    public function get_longurl_callback($request)
    {
        try {
            $code = $request->get_param('code');

            if (empty($code)) {
                return new WP_REST_Response(array('error' => 'missing_url_parameter', 'message' => __('code parameter is missing.', 'rrze-shorturl')), 400);
            }

            $long_url = ShortURL::getLongURL($code);

            return new WP_REST_Response(['long_url' => $long_url], 200);
        } catch (CustomException $e) {
            return new WP_REST_Response(array('error' => 'callback_error', 'message' => __('Error processing request.', 'rrze-shorturl')), 500);
        }
    }


    public function get_services_callback($request)
    {
        try {
            $active_short_urls = ShortURL::getServices();

            return new WP_REST_Response($active_short_urls, 200);
        } catch (CustomException $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }


    public function get_decrypt_callback($request)
    {
        $parameters = $request->get_params();

        if (empty($parameters['encrypted'])) {
            return new WP_Error('invalid_name', __('encrypted value is required.', 'rrze-shorturl'), array('status' => 400));
        }

        try {
            $myCrypt = new MyCrypt();
            $decrypted_string = $myCrypt->decrypt($parameters['encrypted']);

            return new WP_REST_Response($decrypted_string, 200);
        } catch (CustomException $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }

    public function get_encrypt_callback($request)
    {
        $parameters = $request->get_params();

        if (empty($parameters['decrypted'])) {
            return new WP_Error('invalid_name', __('decrypted value is required.', 'rrze-shorturl'), array('status' => 400));
        }

        try {
            $myCrypt = new MyCrypt();
            $encrypted_string = $myCrypt->encrypt($parameters['decrypted']);

            return new WP_REST_Response($encrypted_string, 200);
        } catch (CustomException $e) {
            return new WP_Error('callback_error', __('Error processing request.', 'rrze-shorturl'));
        }
    }

}
