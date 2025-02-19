<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class ShortURL
{

    protected static $rights;

    public static array $CONFIG = [
        "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-"
    ];

    public function __construct($rights)
    {
        self::$rights = $rights;
        $options = json_decode(get_option('rrze-shorturl'), true);

        self::$CONFIG['ShortURLBase'] = (!empty($options['ShortURLBase']) ? $options['ShortURLBase'] : 'https://go.fau.de');
        self::$CONFIG['maxShortening'] = (!empty($options['maxShortening']) ? $options['maxShortening'] : 60);
        self::$CONFIG['AllowedDomains'] = self::getAllowedDomains();
        self::$CONFIG['Services'] = self::getServices();
    }


    public static function isValidUrl(string $url): bool
    {
        try {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return false;
            }

            return true;
        } catch (CustomException $e) {
            error_log("Error in isValidUrl: " . $e->getMessage());
            return false;
        }
    }

    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    // public static function check_url_status_and_switch_active(string $url, $link_id = 0, $active = null): array
    // {
    //     $aRet = [
    //         'error' => true,
    //         'message' => '',
    //     ];

    //     $response = wp_remote_get($url, ['timeout' => 10]);

    //     if (is_wp_error($response)) {
    //         $message = __('Error fetching URL', 'rrze-shorturl') . ' "' . $url . '" ' . $response->get_error_message();
    //         $aRet['message'] = $message;
    //         error_log($message);
    //         return $aRet;
    //     }

    //     $http_code = wp_remote_retrieve_response_code($response);
    //     if ($http_code >= 200 && $http_code < 400) {
    //         if (!empty($link_id) && !is_null($active)){
    //             // only admins can switch active
    //             update_post_meta($link_id, 'active', $active);
    //         }

    //         $aRet['error'] = false;
    //     }else{
    //         $message = sprintf(
    //             __('URL unreachable: "%s" (HTTP-Code: %d)', 'rrze-shorturl'),
    //             $url,
    //             $http_code
    //         );            
    //         $aRet['message'] = $message;

    //         if (!empty($link_id)){
    //             // Set active = 0 because this URL is invalid
    //             update_post_meta($link_id, 'active', 0);
    //         }
    //     }

    //     return $aRet;
    // }



    public static function cryptNumber($input)
    {
        try {
            $cryptedString = '';

            // Convert input to string to handle each digit separately
            $inputString = strval($input);

            // Iterate over each digit of the input number
            for ($i = 0; $i < strlen($inputString); $i++) {
                // Get the current digit
                $digit = intval($inputString[$i]);

                // Map the digit to its corresponding character using the original cryptNumber function
                $cryptedString .= self::cryptSingleDigit($digit);
            }

            return $cryptedString;
        } catch (CustomException $e) {
            error_log("Error in cryptNumber: " . $e->getMessage());
            return null;
        }
    }

    // Helper function to crypt a single digit
    private static function cryptSingleDigit($digit)
    {
        try {
            // Adjust input to fit within the character set range (1-37)
            $adjustedInput = ($digit - 1) % strlen(self::$CONFIG['ShortURLModChars']) + 1;

            // Map the adjusted input to the corresponding character in the character set
            return self::$CONFIG['ShortURLModChars'][$adjustedInput - 1];
        } catch (CustomException $e) {
            error_log("Error in cryptSingleDigit: " . $e->getMessage());
            return null;
        }
    }

    public static function getServices()
    {
        // Set up arguments for get_posts to fetch all service posts
        $args = [
            'post_type' => 'shorturl_service',  // The Custom Post Type for services
            'numberposts' => -1,                // Retrieve all service posts
            'post_status' => 'publish'          // Only fetch published services
        ];

        // Fetch the posts
        $posts = get_posts($args);

        // Initialize an empty array to store service data
        $aServices = [];

        // Loop through the results and store the relevant data
        foreach ($posts as $post) {
            // Collect post meta data (like hostname, prefix, and regex)
            $aServices[] = [
                'id' => $post->ID,
                'hostname' => $post->post_title, // Use the post title as hostname
                'prefix' => get_post_meta($post->ID, 'prefix', true),
                'regex' => get_post_meta($post->ID, 'regex', true),
                'active' => get_post_meta($post->ID, 'active', true),
                'notice' => get_post_meta($post->ID, 'notice', true)
            ];
        }

        return $aServices;
    }


    public static function getAllowedDomains()
    {
        try {
            // Set up arguments for WP_Query to fetch all domain posts
            $args = [
                'post_type' => 'shorturl_domain',
                'posts_per_page' => -1, // Fetch all domains
                'meta_query' => [
                    [
                        'key' => 'prefix',
                        'value' => '1',
                        'compare' => '='
                    ]
                ],
                'orderby' => 'title', // Sort by the hostname (post_title)
                'order' => 'ASC'
            ];


            // Execute the query
            $query = new \WP_Query($args);

            // Initialize an empty array to store domain data
            $aDomains = [];

            // Loop through the results and store the relevant data
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();

                    // Collect post meta data (like hostname, prefix, and other relevant fields)
                    $aDomains[] = [
                        'id' => get_the_ID(),
                        'hostname' => esc_html(get_the_title()),
                        'prefix' => get_post_meta(get_the_ID(), 'prefix', true),
                        'external' => get_post_meta(get_the_ID(), 'external', true),
                        'active' => get_post_meta(get_the_ID(), 'active', true),
                        'notice' => get_post_meta(get_the_ID(), 'notice', true),
                        'webmaster_name' => get_post_meta(get_the_ID(), 'webmaster_name', true),
                        'webmaster_email' => get_post_meta(get_the_ID(), 'webmaster_email', true)
                    ];
                }
            }

            // Restore original Post Data
            wp_reset_postdata();

            return $aDomains;
        } catch (CustomException $e) {
            // Log error if a CustomException is caught
            error_log("Error in getAllowedDomains: " . $e->getMessage());
            return null;
        }
    }




    public static function checkDomain($long_url)
    {
        try {
            $aRet = [
                'is_valid' => false,
                'id' => 0,
                'prefix' => 0,
                'external' => 0,
                'hostname' => '',
                'notice' => __('Domain is not allowed to use our shortening service.', 'rrze-shorturl') . ' ' . '<a href="../domain-liste">' . __('List of allowed domains', 'rrze-shorturl') . '</a>',
                'message_type' => 'error'
            ];

            $parsed_url = wp_parse_url($long_url);

            if (!isset($parsed_url['scheme'])) {
                $long_url = 'https://' . $long_url;
            }

            $domain = wp_parse_url($long_url, PHP_URL_HOST);

            if (!$domain) {
                $aRet['error'] = __('The provided URL is not a valid domain.', 'rrze-shorturl');
                $aRet['message_type'] = 'error';
                return $aRet;
            }

            $shortURL = '';

            // Check if domain is a service
            foreach (self::$CONFIG['Services'] as $item) {
                if ($item["hostname"] === $domain) {

                    if (preg_match('/(\d+)(?!.*\d)/', $long_url, $matches)) {
                        $id = $matches[1];

                        $myCrypt = new MyCrypt();
                        $encrypted = $myCrypt->encrypt($id);

                        $shortURL = self::$CONFIG['ShortURLBase'] . '/' . $item["prefix"] . $encrypted;

                    }

                    $aRet['is_valid'] = false;
                    $aRet['notice'] = __('You\'ve tried to shorten a service domain. Services will automatically be shortened and redirected.', 'rrze-shorturl');
                    $aRet['notice'] .= ($shortURL ? '<br>' . __('Short URL', 'rrze-shorturl') . ': ' . $shortURL : '');
                    $aRet['message_type'] = 'notice';

                    return $aRet;
                }
            }

            // Check if the extracted domain belongs to one of our allowed domains
            foreach (self::$CONFIG['AllowedDomains'] as $aEntry) {
                if ($domain === $aEntry['hostname']) {
                    $notice = $aEntry['notice'];

                    if (!$aEntry['active']) {
                        $notice = __('A short URL cannot currently be created on the website because', 'rrze-shorturl') . ' ' . $aEntry['notice'] . ' ' . __('is missing', 'rrze-shorturl') . '. ';
                        $notice .= __('To resolve this issue, please contact the technical support person for the website in question', 'rrze-shorturl') . ': <a href="mailto:' . $aEntry['webmaster_email'] . '">' . $aEntry['webmaster_name'] . ' &lt;' . $aEntry['webmaster_email'] . '&gt;</a>';
                    }

                    $aRet = [
                        'is_valid' => $aEntry['active'],
                        'id' => $aEntry['id'],
                        'prefix' => ($aEntry['active'] ? $aEntry['prefix'] : 0),
                        'external' => $aEntry['external'],
                        'hostname' => $aEntry['hostname'],
                        'notice' => $notice,
                        'message_type' => 'standard'
                    ];
                    break;
                }
            }

            return $aRet;
        } catch (CustomException $e) {
            error_log("Error in checkDomain: " . $e->getMessage());
            return null;
        }
    }


    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    public static function isUniqueURI($uri, $link_id = 0)
    {
        // If the URI is empty, consider it unique
        if (empty($uri)) {
            return true;
        }

        // Only admins are allowed to change URI!
        if ($link_id) {
            // has URI changed?
            $stored_uri = get_post_meta($link_id, 'uri', true);
            if ($stored_uri == $uri) {
                return true;
            }
        }

        // Check if the slug (URI) is used by any post (including pages, posts, or any other post type)
        $existing_post = get_page_by_path($uri, OBJECT, ['post', 'page', 'link', 'idm', 'domain', 'service']);

        if ($existing_post) {
            return false;  // If a post or page exists with this URI, it's not unique
        }

        // Check if the URI exists in the 'link' Custom Post Type with 'active' set to true
        $args = [
            'post_type' => 'shorturl_link',  // The Custom Post Type for links
            'meta_query' => [
                [
                    'key' => 'uri',
                    'value' => $uri,
                    'compare' => '='
                ]
                // ,
                // [
                    // 'key' => 'active',
                //     'value' => '1',
                //     'compare' => '='
                // ]
            ],
            'posts_per_page' => 1 // We only need to check if at least one match exists
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            return false; // If a link with the URI exists, it's not unique
        }

        // URI is not used anywhere, it is unique
        return true;
    }



    public static function isValidURI(string $uri = ''): bool
    {
        $isValid = true;

        if (trim($uri) !== '') {
            $uriWithoutSpaces = preg_replace('/\s/', '', $uri);

            if (rawurlencode($uri) !== rawurlencode($uriWithoutSpaces)) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    public static function isValidDate($valid_until)
    {
        if (empty($valid_until)) {
            return ['error' => false, 'txt' => __('Date is valid.', 'rrze-shorturl')];
        }
        $parsed_date = date_parse($valid_until);

        if ($parsed_date['error_count'] > 0 || !checkdate($parsed_date['month'], $parsed_date['day'], $parsed_date['year'])) {
            return ['error' => true, 'txt' => 'Validity is not a valid date.'];
        }

        $valid_until_date = \DateTime::createFromFormat('Y-m-d', $valid_until);
        $current_date = new \DateTime();

        // Check if $valid_until is in the past
        if ($valid_until_date < $current_date) {
            return ['error' => true, 'txt' => __('Validity date cannot be in the past.', 'rrze-shorturl')];
        }

        // If the date is valid and within the allowed range
        return ['error' => false, 'txt' => __('Date is valid.', 'rrze-shorturl')];
    }

    private static function add_or_replace_utm_parameters($url, $utm_parameters)
    {

        $url_components = wp_parse_url($url);

        $query_parameters = [];
        if (isset($url_components['query'])) {
            parse_str($url_components['query'], $query_parameters);
        }

        // add / exchange utm_parameters
        foreach ($utm_parameters as $key => $value) {
            $query_parameters[$key] = $value;
        }

        $url_components['query'] = http_build_query($query_parameters);

        $components = array_keys($url_components);

        return self::add_url_components($url, $components, $url_components['query']);
    }


    private static function add_url_components($url, $components, $query = '')
    {
        $parsed_url = wp_parse_url($url);
        $new_url = '';

        if (!isset($parsed_url['scheme'])) {
            $parsed_url['scheme'] = 'https';
        }

        foreach ($components as $component) {
            if (isset($parsed_url[$component]) || ($component == 'query' && $query)) {
                if ($component == 'scheme') {
                    $parsed_url[$component] .= '://';
                }
                if ($component == 'query') {
                    $parsed_url[$component] = '?' . ($query ? $query : $parsed_url[$component]);
                }
                if ($component == 'fragment') {
                    $parsed_url[$component] = '#' . $parsed_url[$component];
                }
                $new_url .= $parsed_url[$component];
            }
        }

        return $new_url;
    }

    private static function countShortenings()
    {
        try {
            // Define the time range for the query (last 60 minutes)
            $time_limit = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - HOUR_IN_SECONDS);

            // Set up arguments for WP_Query to count posts in 'shorturl_link' Custom Post Type
            $args = [
                'post_type' => 'shorturl_link',  // The Custom Post Type for shortened links
                'posts_per_page' => -1,      // No limit on posts returned
                'meta_query' => [
                    [
                        'key' => 'idm',
                        'value' => self::$rights['idm'],
                        'compare' => '='
                    ],
                    [
                        'key' => 'created_at',
                        'value' => $time_limit,
                        'compare' => '>='
                    ]
                ]
            ];

            // Execute the query
            $query = new \WP_Query($args);

            // Return the total number of posts found
            return $query->found_posts;
        } catch (CustomException $e) {
            // Log error if an exception is thrown
            error_log('Error in countShortenings(): ' . $e->getMessage());
            return 0;
        }
    }



    private static function maxShorteningReached()
    {
        return self::countShortenings() >= self::$CONFIG['maxShortening'];
    }

    private static function isShortURL($url)
    {
        return (strpos($url, self::$CONFIG['ShortURLBase']) === 0);
    }

    private static function getLongURLToService($code)
    {
        preg_match('/^(\d+)(.*)/', $code, $matches);

        if (empty($matches[1]) || empty($matches[2])) {
            // there is no prefix 
            return '';
        } else {
            $prefix = $matches[1];
            $encrypted = $matches[2];

            foreach (self::$CONFIG['Services'] as $service) {
                if ($service['prefix'] === $prefix) {
                    if (!empty($service['regex'])) {

                        $myCrypt = new MyCrypt();
                        $decrypted = $myCrypt->decrypt($encrypted);

                        return preg_replace('/\$\w+/', $decrypted, $service['regex']);
                    }
                }
            }
        }

        // couldn't find the service or regex to found service is empty
        return '';
    }

    /**
     * Shortens a given URL and applies additional parameters such as categories, UTM parameters, and a custom URI.
     *
     * This function takes various parameters to create one shortened URL or two shortened URLs if custom URI is given and allowed to use.
     * 
     * Checks that are made:
     * - allowed domain?
     * - valid URI?
     * - unique URI?
     * - valid expiration date?
     * - valid given URL?
     *
     * @param array $shortenParams {
     *     Parameters used for URL shortening.
     *
     *     @type string 'idm'          IdM - either own or the customer's if user is logged in 
     *     @type string 'long_url'          The long URL to be shortened. Required.
     *     @type string 'valid_until'       (Optional) Expiry date of the shortened URL in `Y-m-d` format.
     *     @type array  'categories'         (Optional) An array of categories to be assigned to the shortened URL.
     *     @type string 'uri'               (Optional) A custom URI to be used for the shortened URL, if allowed.
     *     @type string 'utm_*'             (Optional) UTM parameters such as `utm_source`, `utm_medium`, `utm_campaign`, etc.
     *     @type string 'link_id'            (Optional) ID of the post of this link
     * }
     *
     * @return array Returns an array with detailed results:
     * 
     *  [
     *    'error' => false/true,
     *    'message_type' => 'standard'/'notice'/'error',
     *    'error_msg' => '' or detailed error message,
     *    'shorturl_generated' => if error '' else shorturl_generated,
     *    'shorturl_custom' => if error or custom URI is not given or is not allowed '' or shorturl with custom URI,
     *    'long_url' => the given long_url but without get-parameters if these are not allowed 
     *    'uri' => '' or given URI,
     *    'valid_until' => valid_until,
     *    'valid_until_formatted' => if error '' else formatted given valid_until  
     *   ]
     */
    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    public static function shorten($shortenParams)
    {
        error_log(' in shorten() - START');

        error_log(' $shortenParams = ' . print_r($shortenParams, true));
        $long_url = $shortenParams['long_url'] ?? null;
        $link_id = $shortenParams['link_id'] ?? null;

        // $active = $shortenParams['active'] ?? null;
        $uri = self::$rights['allow_uri'] ? sanitize_text_field(wp_unslash($_POST['uri'] ?? '')) : '';

        $valid_until = (!empty($shortenParams['valid_until']) ? $shortenParams['valid_until'] : NULL);

        $valid_until_formatted = (!empty($valid_until) ? date_format(date_create($valid_until), 'd.m.Y') : __('indefinite', 'rrze-shorturl'));

        $aCategory = $shortenParams['aCategory'] ?? null;

        try {
            if (!is_user_logged_in()) {
                // check if maximum shortenings is reached
                if (self::maxShorteningReached()) {
                    return [
                        'error' => true,
                        'message_type' => 'notice',
                        'message' => sprintf(
                            /* translators: %s: maximum number of links a user can shorten per hour */
                            __('You cannot shorten more than %s links per hour', 'rrze-shorturl'),
                            self::$CONFIG['maxShortening']
                        ),
                        'shorturl_generated' => '',
                        'shorturl_custom' => '',
                        'long_url' => $long_url,
                        'uri' => $uri,
                        'valid_until' => $valid_until,
                        'valid_until_formatted' => $valid_until_formatted
                    ];
                }
            }

            // Check if this is the shortened URL
            if (self::isShortURL($long_url)) {
                $code = basename($long_url, "+"); // "+" => perhaps someone tries to shorten a preview link
                $true_long_url = ShortURL::getLongURL($code);

                if (!$true_long_url) {
                    // is it a service url?
                    $true_long_url = self::getLongURLToService($code);
                }

                if ($true_long_url) {
                    return [
                        'error' => true,
                        'message_type' => 'notice',
                        'message' => __('You cannot shorten a link to our shortening service. This is the long URL:') . " $true_long_url",
                        'shorturl_generated' => '',
                        'shorturl_custom' => '',
                        'long_url' => $long_url,
                        'uri' => $uri,
                        'valid_until' => $valid_until,
                        'valid_until_formatted' => $valid_until_formatted
                    ];
                } else {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'message' => __('You cannot shorten a link to our shortening service. The link you\'ve provided is unknown.'),
                        'shorturl_generated' => '',
                        'shorturl_custom' => '',
                        'long_url' => $long_url,
                        'uri' => $uri,
                        'valid_until' => $valid_until,
                        'valid_until_formatted' => $valid_until_formatted
                    ];
                }
            }

            // Is it an allowed domain?
            $aDomain = self::checkDomain($long_url);

            if (!$aDomain['is_valid']) {
                return [
                    'error' => true,
                    'message_type' => $aDomain['message_type'],
                    'message' => $aDomain['notice'],
                    'shorturl_generated' => '',
                    'shorturl_custom' => '',
                    'long_url' => $long_url,
                    'uri' => $uri,
                    'valid_until' => $valid_until,
                    'valid_until_formatted' => $valid_until_formatted
                ];
            }

            // if external domain we must allow GET
            if ($aDomain['external']) {
                self::$rights['allow_get'] = true;
            }

            // Check if 'allow_get' is false and remove GET parameters if necessary
            if (self::$rights['allow_get']) {
                $aComponents = ['scheme', 'host', 'path', 'query', 'fragment'];
            } else {
                $aComponents = ['scheme', 'host', 'path', 'fragment'];
            }

            $long_url = self::add_url_components($long_url, $aComponents);

            // add / exchange utm_parameters
            $aUTM = [];
            if (self::$rights['allow_utm']) {
                foreach ($shortenParams as $key => $val) {
                    if ((strpos($key, 'utm_') === 0) && !empty($val)) {
                        $aUTM[$key] = $val;
                    }
                }

                $long_url = self::add_or_replace_utm_parameters($long_url, $aUTM);
            }

            // Validate the URI
            $isValidURI = false;

            if (self::$rights['allow_uri']) {
                $isValidURI = self::isValidURI($uri);

                if ($isValidURI !== true) {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'message' => $long_url . __('is not a valid URI', 'rrze-shorturl'),
                        'shorturl_generated' => '',
                        'shorturl_custom' => '',
                        'long_url' => $long_url,
                        'uri' => $uri,
                        'valid_until' => $valid_until,
                        'valid_until_formatted' => $valid_until_formatted
                    ];
                }

                if (!self::isUniqueURI($uri, $link_id)) {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'message' => $uri . ' ' . __('is already in use. Try another one.', 'rrze-shorturl'),
                        'shorturl_generated' => '',
                        'shorturl_custom' => '',
                        'long_url' => $long_url,
                        'uri' => $uri,
                        'valid_until' => $valid_until,
                        'valid_until_formatted' => $valid_until_formatted
                    ];
                }
            }

            if ($aDomain['prefix'] == 0 && !$isValidURI) {
                // since 1.4.10 we offer Custom URI for Services, too. Before: only for Custom Links
                return [
                    'error' => true,
                    'message_type' => $aDomain['message_type'],
                    'message' => $aDomain['notice'],
                    'shorturl_generated' => '',
                    'shorturl_custom' => '',
                    'long_url' => $long_url,
                    'uri' => $uri,
                    'valid_until' => $valid_until,
                    'valid_until_formatted' => $valid_until_formatted
                ];
            }

            $aCategory = $shortenParams['categories'] ?? [];
            // $tags = $shortenParams['tags'] ?? [];

            // Validate the Date
            $isValid = self::isValidDate($valid_until);
            if ($isValid['error'] !== false) {
                return [
                    'error' => true,
                    'message_type' => 'error',
                    'message' => $isValid['txt'],
                    'shorturl_generated' => '',
                    'shorturl_custom' => '',
                    'long_url' => $long_url,
                    'uri' => $uri,
                    'valid_until' => $valid_until,
                    'valid_until_formatted' => $valid_until_formatted
                ];
            }

            // Validate the URL
            $isValid = self::isValidUrl($long_url);
            if ($isValid !== true) {
                return [
                    'error' => true,
                    'message_type' => 'error',
                    'message' => $long_url . __('is not a valid URL', 'rrze-shorturl'),
                    'shorturl_generated' => '',
                    'shorturl_custom' => '',
                    'long_url' => $long_url,
                    'uri' => $uri,
                    'valid_until' => $valid_until,
                    'valid_until_formatted' => $valid_until_formatted
                ];
            }

            // $aCheckUrlStatus = self::check_url_status_and_switch_active($long_url, $link_id, $active);
            // if ($aCheckUrlStatus['error'] !== false) {
            //     return [
            //         'error' => true,
            //         'message_type' => 'error',
            //         'message' => $aCheckUrlStatus['message'],
            //         'shorturl_generated' => '',
            //         'shorturl_custom' => '',
            //         'long_url' => $long_url,
            //         'uri' => $uri,
            //         'valid_until' => $valid_until,
            //         'valid_until_formatted' => $valid_until_formatted
            //     ];
            // }

            $idm = (!empty($shortenParams['customer_idm']) ? $shortenParams['customer_idm'] : self::$rights['idm']);

    
    
            $aShortURLs = self::fetch_or_create_shorturls($aDomain['id'], $long_url, $aDomain['prefix'], $idm, $uri, $valid_until, $aCategory, $link_id);

            error_log('fetch_or_create_shorturls() returned $aShortURLs = ' . print_r($aShortURLs, true));
            error_log('shorten() -- END');

            // no error occurred
            return [
                'error' => false,
                'message_type' => 'standard',
                'message' => __('Link saved', 'rrze-shorturl'),
                'shorturl_generated' => $aShortURLs['shorturl_generated'],
                'shorturl_custom' => $aShortURLs['shorturl_custom'],
                'long_url' => $long_url,
                'uri' => $uri,
                'valid_until' => $aShortURLs['valid_until'],
                'valid_until_formatted' => $aShortURLs['valid_until_formatted']
            ];
        } catch (CustomException $e) {
            error_log("Error in shorten: " . $e->getMessage());
            return [];
        }
    }


    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    public static function fetch_or_create_shorturls($domain_id, $long_url, $prefix, $idm, $uri = '', $valid_until = '', $aCategory = '', $link_id = null)
    {
        $aRet = [
            'post_id' => 0,
            'shorturl_generated' => '',
            'shorturl_custom' => '',
            'valid_until' => '',
            'valid_until_formatted' => '',
        ];

        if ($link_id) {
            // we are editing via backend
            $post_id = $link_id;

            $shorturl_generated = get_post_meta($post_id, 'shorturl_generated', true);
            $shorturl_custom = (!empty($uri) ? self::$CONFIG['ShortURLBase'] . '/' . $uri : '');

            update_post_meta($post_id, 'shorturl_custom', $shorturl_custom);
            update_post_meta($post_id, 'uri', $uri);
            update_post_meta($post_id, 'valid_until', $valid_until);
            $valid_until_formatted = (!empty($valid_until) ? date_format(date_create($valid_until), 'd.m.Y') : __('indefinite', 'rrze-shorturl'));
            update_post_meta($post_id, 'valid_until_formatted', $valid_until_formatted);

            // return result
            $aRet['post_id'] = $post_id;
            $aRet['shorturl_generated'] = $shorturl_generated;
            $aRet['shorturl_custom'] = $shorturl_custom;
            $aRet['valid_until'] = $valid_until;
            $aRet['valid_until_formatted'] = $valid_until_formatted;

            return $aRet;
        }

        try {
            // try to fetch 
            $args = [
                'post_type' => 'shorturl_link',
                'meta_query' => [
                    [
                        'key' => 'idm',
                        'value' => $idm,
                        'compare' => '='
                    ],
                    [
                        'key' => 'domain_id',
                        'value' => $domain_id,
                        'compare' => '='
                    ],
                    [
                        'key' => 'long_url',
                        'value' => $long_url,
                        'compare' => '='
                    ],
                ],
                'posts_per_page' => 1, // Return only one result
            ];

            $query = new \WP_Query($args);

            if ($query->have_posts()) {    
                error_log(' fetch_or_create_shorturls() we have posts');
                $post_id = $query->posts[0]->ID;

                $aRet['post_id'] = $post_id;
                $aRet['shorturl_generated'] = get_post_meta($post_id, 'shorturl_generated', true);

                $shorturl_custom = (!empty($uri) ? self::$CONFIG['ShortURLBase'] . '/' . $uri : '');
                update_post_meta($post_id, 'shorturl_custom', $shorturl_custom);
                update_post_meta($post_id, 'uri', $uri);

                update_post_meta($post_id, 'valid_until', $valid_until);
                $aRet['shorturl_custom'] = get_post_meta($post_id, 'shorturl_custom', true);
                $aRet['valid_until'] = get_post_meta($post_id, 'valid_until', true);
                $aRet['valid_until_formatted'] = get_post_meta($post_id, 'valid_until_formatted', true);
            } else {
                // Create a new post
                $post_data = [
                    'post_title' => $long_url,
                    'post_type' => 'shorturl_link',
                    'post_status' => 'publish'
                ];

                // Insert the post into the database
                $post_id = wp_insert_post($post_data);

                $shorturl_generated = self::$CONFIG['ShortURLBase'] . '/' . $prefix . self::cryptNumber($post_id);
                $shorturl_custom = (!empty($uri) ? self::$CONFIG['ShortURLBase'] . '/' . $uri : '');

                update_post_meta($post_id, 'idm', $idm);
                update_post_meta($post_id, 'domain_id', $domain_id);
                update_post_meta($post_id, 'shorturl_generated', $shorturl_generated);
                update_post_meta($post_id, 'shorturl_custom', $shorturl_custom);
                update_post_meta($post_id, 'uri', $uri);
                update_post_meta($post_id, 'valid_until', $valid_until);
                $valid_until_formatted = (!empty($valid_until) ? date_format(date_create($valid_until), 'd.m.Y') : __('indefinite', 'rrze-shorturl'));
                update_post_meta($post_id, 'valid_until_formatted', $valid_until_formatted);
                // update_post_meta($post_id, 'active', '1');

                $aRet['post_id'] = $post_id;
                $aRet['shorturl_generated'] = $shorturl_generated;
                $aRet['shorturl_custom'] = $shorturl_custom;
                $aRet['valid_until'] = $valid_until;
                $aRet['valid_until_formatted'] = $valid_until_formatted;
            }

            // Update categories
            if (!empty($aCategory)) {
                // Store the category IDs in post meta
                $current_categories = get_post_meta($post_id, 'category_id', false);

                foreach ($aCategory as $category_id) {
                    if (!in_array($category_id, $current_categories)) {
                        add_post_meta($post_id, 'category_id', $category_id, false);
                    }
                }
            } else {
                // Clear categories if none are passed
                delete_post_meta($post_id, 'category_id');
            }

            return $aRet;
        } catch (CustomException $e) {
            error_log("Error in getLinkfromDB: " . $e->getMessage());
            return null;
        }
    }


    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    public static function getActiveShortURLs()
    {
        try {
            // Set up arguments for WP_Query to fetch active short URLs
            $args = [
                'post_type' => 'shorturl_link',
                'posts_per_page' => -1,
                'post_status' => 'publish', 
                // 'meta_query' => [
                //     [
                //         'key' => 'active',
                //         'value' => '1',
                //         'compare' => '='
                //     ]
                // ],
                'orderby' => 'created_at', // Order by the created_at meta field
                'order' => 'DESC'        // Order by most recent first
            ];

            // Execute the query
            $query = new \WP_Query($args);

            // Initialize an empty array to store results
            $active_short_urls = [];

            // Loop through the results and collect data
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();

                    // Fetch the relevant post meta data
                    $post_id = get_the_ID();

                    $active_short_urls[] = [
                        'long_url' => get_the_title($post_id),
                        'shorturl_generated' => get_post_meta($post_id, 'shorturl_generated', true),
                        'shorturl_custom' => get_post_meta($post_id, 'shorturl_custom', true),
                        'valid_until' => get_post_meta($post_id, 'valid_until', true)
                    ];
                }
            }

            // Restore original Post Data
            wp_reset_postdata();

            return $active_short_urls;
        } catch (CustomException $e) {
            // Log the error and return a JSON-encoded error message
            error_log("Error fetching active short URLs: " . $e->getMessage());
            return wp_json_encode(['error' => 'An error occurred while fetching short URLs.']);
        }
    }



    public static function getLongURL($code)
    {
        // Construct the full short URL using the base URL from the configuration
        $short_url = self::$CONFIG['ShortURLBase'] . '/' . $code;

        try {
            // Set up arguments for WP_Query to fetch the post with the matching short URL
            $args = [
                'post_type' => 'shorturl_link',
                'posts_per_page' => 1,     
                'post_status' => 'publish',
                'meta_query' => [
                    'relation' => 'OR',        // We now have TWO short-links see https://github.com/RRZE-Webteam/rrze-shorturl/issues/146
                    [
                        'key' => 'shorturl_generated',
                        'value' => $short_url,
                        'compare' => '='
                    ],
                    [
                        'key' => 'shorturl_custom',
                        'value' => $short_url,
                        'compare' => '='
                    ]
                ]                
            ];

            // Execute the query
            $query = new \WP_Query($args);

            // Check if a matching post was found
            if ($query->have_posts()) {
                $query->the_post();

                $long_url = get_the_title(get_the_ID());

                // Restore original Post Data
                wp_reset_postdata();

                return $long_url;
            } else {
                // Return null if no matching short URL is found
                return null;
            }
        } catch (CustomException $e) {
            // Log the error and return a JSON-encoded error message
            error_log("Error fetching long_url by short_url: " . $e->getMessage());
            return wp_json_encode(['error' => 'An error occurred while fetching the long URL.']);
        }
    }


}

