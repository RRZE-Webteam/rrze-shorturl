<?php

namespace RRZE\ShortURL;

class ShortURL
{
    public static array $CONFIG = [
        "ShortURLBase" => "http://go.fau.de/",
        "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-",
        "AllowedDomains" => [] // Initialize empty, this will be populated later
    ];

    public function __construct()
    {
        self::$CONFIG['AllowedDomains'] = self::getAllowedDomains();
    }


    public static function isValidUrl($url)
    {
        try {
            // Use PHP's built-in filter_var function with FILTER_VALIDATE_URL flag
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return "invalid url";
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error in isValidUrl: " . $e->getMessage());
            return null;
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            error_log("Error in cryptSingleDigit: " . $e->getMessage());
            return null;
        }
    }


    public static function getLinkfromDB($long_url, $uri)
    {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'shorturl_links';

            // Query the links table to get the ID and short_url where long_url matches $long_url
            $result = $wpdb->get_results($wpdb->prepare("SELECT id, short_url, uri FROM $table_name WHERE long_url = %s AND uri = %s LIMIT 1", $long_url, $uri), ARRAY_A);

            error_log("$result : " . json_encode($result));

            if (empty($result)) {

                // Insert into the links table
                $wpdb->insert(
                    $table_name,
                    array(
                        'long_url' => $long_url
                    )
                );
                // Get the ID of the inserted row
                $link_id = $wpdb->insert_id;

                // Return the array with id and short_url as empty
                return array('id' => $link_id, 'short_url' => '', 'uri' => $uri);
            } else {
                // Return the array with id and short_url
                return array('id' => $result[0]['id'], 'short_url' => $result[0]['short_url'], 'uri' => $result[0]['uri']);
            }
        } catch (\Exception $e) {
            error_log("Error in getLinkfromDB: " . $e->getMessage());
            return null;
        }
    }

    public static function updateLink($link_id, $shortURL, $uri)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
        try {
            // Store in the database
            return $wpdb->update(
                $table_name,
                [
                    'short_url' => $shortURL,
                    'uri' => $uri
                ],
                ['id' => $link_id]
            );
        } catch (\Exception $e) {
            error_log("Error in updateLink: " . $e->getMessage());
            return null;
        }
    }


    // Function to retrieve our domains from the database
    public static function getAllowedDomains()
    {
        global $wpdb;

        try {
            // Table name
            $table_name = $wpdb->prefix . 'shorturl_domains';

            // Query to select servername from the shorturl_domains table
            $query = "SELECT * FROM $table_name";

            // Execute the query
            $results = $wpdb->get_results($query, ARRAY_A);

            // Extract servernames from the results
            $aDomains = [];
            foreach ($results as $result) {
                $aDomains[] = $result;
            }

            return $aDomains;
        } catch (\Exception $e) {
            error_log("Error in getAllowedDomains: " . $e->getMessage());
            return null;
        }
    }

    public static function checkDomain($long_url)
    {
        try {
            $aRet = ["prefix" => 0, "hostname" => ''];

            $domain = wp_parse_url($long_url, PHP_URL_HOST);

            // Check if the extracted domain belongs to one of our allowed domains
            foreach (self::$CONFIG['AllowedDomains'] as $aEntry) {
                if ($domain === $aEntry['hostname']) {
                    $aRet = ["prefix" => $aEntry['prefix'], "hostname" => $aEntry['hostname']];
                    break;
                }
            }

            return $aRet;
        } catch (\Exception $e) {
            error_log("Error in checkDomain: " . $e->getMessage());
            return null;
        }
    }

    public static function isUniqueURI($uri)
    {
        global $wpdb;

        // Check if the slug is used by posts or pages 
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_name = %s", $uri);

        if ($wpdb->get_var($query) != 0) {
            return false;
        }

        // Check if the slug exists in shorturl_links
        $query = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shorturl_links WHERE uri = %s", $uri);

        if ($wpdb->get_var($query) != 0) {
            return false;
        }

        // $uri is not used anywhere, it is unique
        return true;
    }

    public static function isValidURI(string $uri = ''): bool {
        $isValid = true;
        
        // Check if URI is not empty
        if (trim($uri) !== '') {
            // Remove spaces from the URI
            $uriWithoutSpaces = preg_replace('/\s/', '', $uri);
            
            // Check if rawurlencode returns the same value for the URI
            if (rawurlencode($uri) !== rawurlencode($uriWithoutSpaces)) {
                $isValid = false;
            }
        }
        
        return $isValid;
    }
    


    public static function shorten($long_url, $uri)
    {
        try {
            // Validate the URL
            $isValid = self::isValidUrl($long_url);
            if ($isValid !== true) {
                return ['error' => true, 'txt' => 'URL is not valid'];
            }

            // Validate the URI
            $isValid = self::isValidURI($uri);
            if ($isValid !== true) {
                return ['error' => true, 'txt' => $uri . ' ' . 'is not valid'];
            }

            // is it an allowed domain?
            $aDomain = self::checkDomain($long_url);

            if ($aDomain['prefix'] == 0) {
                return ['error' => true, 'txt' => 'Domain is not allowed to use our shortening service.'];
            }

            $aLink = self::getLinkfromDB($long_url, $uri);

            error_log('$aLink : ' . json_encode($aLink));
            // return ['error' => true, 'txt' => 'test'];



            // Check if already exists in DB 
            if (!empty($aLink['short_url'])) {
                // url found in DB => return it
                return ['error' => false, 'txt' => $aLink['short_url']];
            }

            // Create shortURL
            if (!empty($uri)) {
                if (!self::isUniqueURI($uri)){
                    return ['error' => true, 'txt' => $uri . ' is already in use. Try another one.'];
                }
                $targetURL = $uri;
            } else {
                error_log('da');
                // Create shortURL
                $targetURL = $aDomain['prefix'] . self::cryptNumber($aLink['id']);
            }
            // error_log(json_encode($aLink));

            // // Create shortURL
            // $bUpdated = self::updateLink($aLink['id'], $targetURL, $uri);

            $shortURL = self::$CONFIG['ShortURLBase'] . $targetURL;

            error_log('X : ' . $aLink['id']);
            error_log('Y : ' . $shortURL);
            error_log('Z : ' . $uri);

            $bUpdated = self::updateLink($aLink['id'], $shortURL, $uri);

            error_log('$bUpdated : ' . json_encode($bUpdated));

            if (!$bUpdated) {
                return ['error' => true, 'txt' => 'Unable to update database table'];
            }
            

            // 2DO: fill these arrays via edit.js
            $aCategories = [];
            $aTags = [];

            $data = [
                'id' => $aLink['id'],
                'categories' => $aCategories,
                'tags' => $aTags                 
            ];

            do_action('shortlink_inserted', $data);

            return ['error' => false, 'txt' => $shortURL];
        } catch (\Exception $e) {
            error_log("Error in shorten: " . $e->getMessage());
            return null;
        }
    }

    /* Test-Data

    https://blogs.fau.de/rewi/2024/02/15/erfolgreiches-pilotprojekt-e-klausuren-in-der-uebung-fuer-fortgeschrittene-im-zivilrecht/
     

    https://www.germanistik.phil.fau.de/wp-content/plugins/contact-form-7/includes/js/index.js?ver=5.8.7:1

    */

}

