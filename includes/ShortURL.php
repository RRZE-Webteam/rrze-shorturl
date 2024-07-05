<?php

namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class ShortURL
{

    protected static $rights;

    public static array $CONFIG = [
        "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-"
    ];

    public function __construct()
    {
        $rightsObj = new Rights();
        self::$rights = $rightsObj->getRights();

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


    public static function getLinkfromDB($domain_id, $long_url, $idm_id, $uri)
    {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'shorturl_links';

            if (empty($uri)) {
                $result = $wpdb->get_results($wpdb->prepare("SELECT id, short_url, valid_until FROM $table_name WHERE long_url = %s LIMIT 1", $long_url), ARRAY_A);
            } else {
                $result = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, short_url, valid_until 
                         FROM $table_name 
                         WHERE long_url = %s AND uri = %s AND idm_id = %s 
                         LIMIT 1",
                        $long_url,
                        $uri,
                        $idm_id
                    ),
                    ARRAY_A
                );
            }

            if (empty($result)) {
                $long_url = self::$rights['get_allowed'] ? self::add_url_components($long_url, array('scheme', 'host', 'path', 'query', 'fragment')) : self::add_url_components($long_url, array('scheme', 'host', 'path', 'fragment'));

                // Insert into the links table
                $wpdb->insert(
                    $table_name,
                    array(
                        'idm_id' => $idm_id,
                        'domain_id' => (int) $domain_id,
                        'long_url' => $long_url
                    )
                );
                $link_id = $wpdb->insert_id;

                return array('id' => $link_id, 'short_url' => '');
            } else {
                return array('id' => $result[0]['id'], 'short_url' => $result[0]['short_url'], 'valid_until' => $result[0]['valid_until']);
            }
        } catch (CustomException $e) {
            error_log("Error in getLinkfromDB: " . $e->getMessage());
            return null;
        }
    }

    public static function updateLink(
        $idm_id,
        $link_id,
        $domain_id,
        $shortURL,
        $uri,
        $valid_until,
        $categories
    ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
        $link_categories_table = $wpdb->prefix . 'shorturl_links_categories';
        // $link_tags_table = $wpdb->prefix . 'shorturl_links_tags';

        try {
            // Store in the database    
            $update_result = $wpdb->update(
                $table_name,
                [
                    'idm_id' => $idm_id,
                    'domain_id' => $domain_id,
                    'short_url' => $shortURL,
                    'uri' => $uri,
                    'valid_until' => $valid_until
                ],
                ['id' => $link_id]
            );

            if ($update_result !== false) {
                // Delete existing categories and tags for the link
                $wpdb->delete($link_categories_table, ['link_id' => $link_id]);
                // $wpdb->delete($link_tags_table, ['link_id' => $link_id]);

                // Insert new categories
                if (!empty($categories)) {
                    foreach ($categories as $category_id) {
                        $wpdb->insert(
                            $link_categories_table,
                            ['link_id' => $link_id, 'category_id' => $category_id]
                        );
                    }
                }

                // Insert new tags
                // if (!empty ($tags)) {
                //     foreach ($tags as $tag_id) {
                //         $wpdb->insert(
                //             $link_tags_table,
                //             ['link_id' => $link_id, 'tag_id' => $tag_id]
                //         );
                //     }
                // }
            }

            return $update_result;
        } catch (\Throwable $e) {
            error_log("Error in updateLink: " . $e->getMessage());
            return null;
        }
    }


    public static function getServices()
    {
        global $wpdb;

        try {
            $table_name = $wpdb->prefix . 'shorturl_services';
            $query = "SELECT * FROM $table_name";

            $results = $wpdb->get_results($query, ARRAY_A);

            $aDomains = [];
            foreach ($results as $result) {
                $aDomains[] = $result;
            }

            return $aDomains;
        } catch (CustomException $e) {
            error_log("Error in getServices: " . $e->getMessage());
            return null;
        }
    }

    // Function to retrieve our domains from the database
    public static function getAllowedDomains()
    {
        global $wpdb;

        try {
            $table_name = $wpdb->prefix . 'shorturl_domains';
            $query = "SELECT * FROM $table_name ORDER BY hostname";

            $results = $wpdb->get_results($query, ARRAY_A);

            $aDomains = [];
            foreach ($results as $result) {
                $aDomains[] = $result;
            }

            return $aDomains;
        } catch (CustomException $e) {
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
                'hostname' => '',
                'notice' => __('Domain is not allowed to use our shortening service.', 'rrze-shorturl') . ' ' . '<a href="../domain-liste">' . __('Liste an erlaubten Domains', 'rrze-shorturl') . '</a>',
                'message_type' => 'error'
            ];

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
                        $notice = __('Auf die Webseite kann derzeit kein ShortURL erstellt werden, da', 'rrze-shorturl') . ' ' . $aEntry['notice'] . ' ' . __('fehlt', 'rrze-shorturl') . '. ';
                        $notice .= __('Um diesen Fehler beheben zu lassen, können Sie sich an die/den technischen AnsprechpartnerIn der betreffenden Website wenden', 'rrze-shorturl') . ': <a href="mailto:' . $aEntry['webmaster_email'] . '">' . $aEntry['webmaster_name'] . ' &lt;' . $aEntry['webmaster_email'] . '&gt;</a>';
                    }

                    $aRet = [
                        'is_valid' => $aEntry['active'],
                        'id' => $aEntry['id'],
                        'prefix' => ($aEntry['active'] ? $aEntry['prefix'] : 0),
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

    public static function isUniqueURI($uri)
    {
        if (empty($uri)) {
            return true;
        }

        global $wpdb;

        // Check if the slug is used by posts or pages 
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_name = %s", $uri);

        if ($wpdb->get_var($query) != 0) {
            return false;
        }

        // Check if the slug exists in shorturl_links
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shorturl_links WHERE active = 1 AND uri = %s", $uri);

        if ($wpdb->get_var($query) != 0) {
            return false;
        }

        // $uri is not used anywhere, it is unique
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
        $current_date = new \DateTime(); // Using DateTime object directly

        // Check if $valid_until is in the past
        if ($valid_until_date < $current_date) {
            return ['error' => true, 'txt' => __('Validity date cannot be in the past.', 'rrze-shorturl')];
        }

        // Calculate maxDate (default => 1 year from now; long_life_links_allowed => 5 years from now)
        // $maxDate = clone $current_date;

        // if (self::$rights['allow_longlifelinks']) {
        //     $maxDate->add(new \DateInterval('P5Y'));
        //     $msg = __('five years', 'rrze-shorturl');
        // } else {
        //     $maxDate->add(new \DateInterval('P1Y'));
        //     $msg = __('one year', 'rrze-shorturl');
        // }

        // // Check if $valid_until is more than $maxDate
        // if ($valid_until_date > $maxDate) {
        //     return ['error' => true, 'txt' => sprintf(__('Validity cannot be more than %s in the future.', 'rrze-shorturl'), $msg)];
        // }

        // If the date is valid and within the allowed range
        return ['error' => false, 'txt' => __('Date is valid.', 'rrze-shorturl')];
    }



    private static function add_url_components($url, $components)
    {
        $parsed_url = parse_url($url);
        $new_url = '';

        if (!isset($parsed_url['scheme'])) {
            $parsed_url['scheme'] = 'https';
        }

        foreach ($components as $component) {
            if (isset($parsed_url[$component])) {
                if ($component == 'scheme') {
                    $parsed_url[$component] .= '://';
                }
                if ($component == 'query') {
                    $parsed_url[$component] = '?' . $parsed_url[$component];
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
        global $wpdb;

        try {
            $query = $wpdb->prepare("
                SELECT COUNT(*) AS count_shortenings
                FROM {$wpdb->prefix}shorturl_links
                WHERE idm_id = %d
                AND created_at >= %s
            ", self::$rights['id'], date('Y-m-d H:i:s', strtotime('-60 minutes')));
            $result = $wpdb->get_row($query);

            if ($result === null) {
                throw new Exception('Database query returned null.');
            }

            $count = isset($result->count_shortenings) ? $result->count_shortenings : 0;
            return $count;
        } catch (Exception $e) {
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

    public static function shorten($shortenParams)
    {
        try {
            // check if maximum shortenings is reached
            if (self::maxShorteningReached()) {
                return [
                    'error' => true,
                    'message_type' => 'notice',
                    'txt' => sprintf(
                        __('You cannot shorten more than %s links per hour', 'rrze-shorturl'),
                        self::$CONFIG['maxShortening']
                    )
                ];
            }

            $long_url = $shortenParams['url'] ?? null;
            $uri = self::$rights['uri_allowed'] ? sanitize_text_field($_POST['uri'] ?? '') : '';

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
                        'txt' => __('You cannot shorten a link to our shortening service. This is the long URL:') . " $true_long_url",
                        'long_url' => $long_url
                    ];
                } else {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'txt' => __('You cannot shorten a link to our shortening service. The link you\'ve provided is unknown.'),
                        'long_url' => $long_url
                    ];
                }
            }

            // Check if 'get_allowed' is false and remove GET parameters if necessary
            $long_url = self::$rights['get_allowed'] ? self::add_url_components($long_url, array('scheme', 'host', 'path', 'query', 'fragment')) : self::add_url_components($long_url, array('scheme', 'host', 'path', 'fragment'));

            // Is it an allowed domain?
            $aDomain = self::checkDomain($long_url);

            if (!$aDomain['is_valid']) {
                return [
                    'error' => true,
                    'message_type' => $aDomain['message_type'],
                    'txt' => $aDomain['notice'],
                    'long_url' => $long_url
                ];
            }

            // Validate the URI
            $isValidURI = false;
            if (self::$rights['uri_allowed']) {
                $isValidURI = self::isValidURI($uri);

                if ($isValidURI !== true) {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'txt' => $long_url . __('is not a valid URI', 'rrze-shorturl'),
                        'long_url' => $long_url
                    ];
                }

                if (!self::isUniqueURI($uri)) {
                    return [
                        'error' => true,
                        'message_type' => 'error',
                        'txt' => $uri . ' ' . __('is already in use. Try another one.', 'rrze-shorturl'),
                        'long_url' => $long_url
                    ];
                }
            }

            if ($aDomain['prefix'] == 0 && !$isValidURI) {
                // since 1.4.10 we offer Custom URI for Services, too. Before: only for Custom Links
                return [
                    'error' => true,
                    'message_type' => $aDomain['message_type'],
                    'txt' => $aDomain['notice'],
                    'long_url' => $long_url
                ];
            }


            $valid_until = (!empty($shortenParams['valid_until']) ? $shortenParams['valid_until'] : NULL);

            $categories = $shortenParams['categories'] ?? [];
            // $tags = $shortenParams['tags'] ?? [];

            // Validate the Date
            $isValid = self::isValidDate($valid_until);
            if ($isValid['error'] !== false) {
                return [
                    'error' => true,
                    'message_type' => 'error',
                    'txt' => $isValid['txt'],
                    'long_url' => $long_url
                ];
            }

            // Validate the URL
            $isValid = self::isValidUrl($long_url);
            if ($isValid !== true) {
                return [
                    'error' => true,
                    'message_type' => 'error',
                    'txt' => $long_url . __('is not a valid URL', 'rrze-shorturl'),
                    'long_url' => $long_url
                ];
            }

            // Fetch or insert on new
            $aLink = self::getLinkfromDB($aDomain['id'], $long_url, self::$rights['id'], $uri);

            // Create shortURL
            if (!empty($uri)) {
                $targetURL = $uri;
            } else {
                // Create shortURL
                $targetURL = $aDomain['prefix'] . self::cryptNumber($aLink['id']);
            }

            if (empty($aLink['short_url'])) {
                // Create shortURL
                $shortURL = self::$CONFIG['ShortURLBase'] . '/' . $targetURL;
            } else {
                $shortURL = $aLink['short_url'];
            }

            $bUpdated = self::updateLink(self::$rights['id'], $aLink['id'], $aDomain['id'], $shortURL, $uri, $valid_until, $categories);

            if ($bUpdated === false) {
                return [
                    'error' => true,
                    'message_type' => 'error',
                    'txt' => __('Unable to update database table', 'rrze-shorturl'),
                    'long_url' => $long_url
                ];
            }

            $valid_until_formatted = (!empty($valid_until) ? date_format(date_create($valid_until), 'd.m.Y') : __('indefinite', 'rrze-shorturl'));

            return [
                'error' => false,
                'message_type' => 'standard',
                'txt' => $shortURL,
                'link_id' => $aLink['id'],
                'long_url' => $long_url,
                'valid_until_formatted' => $valid_until_formatted
            ];
        } catch (CustomException $e) {
            error_log("Error in shorten: " . $e->getMessage());
            return null;
        }
    }

    public static function getActiveShortURLs()
    {
        global $wpdb;

        try {
            // Perform the database query to fetch active short URLs
            $query = "SELECT long_url, short_url, valid_until FROM {$wpdb->prefix}shorturl_links WHERE active = 1 ORDER BY created_at DESC";
            $results = $wpdb->get_results($query, ARRAY_A);

            return $results;
        } catch (Exception $e) {
            error_log("Error fetching active short URLs: " . $e->getMessage());
            return json_encode(array('error' => 'An error occurred while fetching short URLs.'));
        }
    }

    public static function getLongURL($short_url)
    {
        global $wpdb;

        $short_url = self::$CONFIG['ShortURLBase'] . '/' . $short_url;

        try {
            $result = $wpdb->get_var($wpdb->prepare("SELECT long_url FROM {$wpdb->prefix}shorturl_links WHERE short_url = %s LIMIT 1", $short_url));

            return $result;
        } catch (Exception $e) {
            error_log("Error fetching long_url by short_url: " . $e->getMessage());
            return json_encode(array('error' => 'An error occurred while fetching short URLs.'));
        }
    }

}

