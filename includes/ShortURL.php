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


    public static function getLinkfromDB($long_url)
    {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'shorturl_links';

            // Query the links table to get the ID and short_url where long_url matches $long_url
            $result = $wpdb->get_results($wpdb->prepare("SELECT id, short_url FROM $table_name WHERE long_url = %s LIMIT 1", $long_url), ARRAY_A);

            if (empty($result)) {

                // Insert into the links table
                $wpdb->insert(
                    $table_name,
                    array(
                        'long_url' => $long_url,
                        'short_url' => ''
                    )
                );
                // Get the ID of the inserted row
                $link_id = $wpdb->insert_id;

                // Return the array with id and short_url as empty
                return array('id' => $link_id, 'short_url' => '');
            } else {
                // Return the array with id and short_url
                return array('id' => $result[0]['id'], 'short_url' => $result[0]['short_url']);
            }
        } catch (\Exception $e) {
            error_log("Error in getLinkfromDB: " . $e->getMessage());
            return null;
        }
    }

    public static function setShortURLinDB($link_id, $shortURL)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
        try {
            // Store in the database
            $wpdb->update(
                $table_name,
                array('short_url' => $shortURL),
                array('id' => $link_id)
            );
        } catch (\Exception $e) {
            error_log("Error in setShortURLinDB: " . $e->getMessage());
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
            $aRet = ["prefix" => 0, "hostname" => '', 'type_code' => ''];

            $domain = wp_parse_url($long_url, PHP_URL_HOST);

            // Check if the extracted domain belongs to one of our allowed domains
            foreach (self::$CONFIG['AllowedDomains'] as $aEntry) {
                if ($domain === $aEntry['hostname']) {
                    $aRet = ["prefix" => $aEntry['prefix'], "hostname" => $aEntry['hostname'], "type_code" => $aEntry['type_code']];
                    break;
                }
            }

            return $aRet;
        } catch (\Exception $e) {
            error_log("Error in checkDomain: " . $e->getMessage());
            return null;
        }
    }

    public static function updateLink($id, $short_url)
    {
        global $wpdb;

        try {
            $table_name = $wpdb->prefix . 'shorturl_links';

            $data = array(
                'short_url' => $short_url,
            );

            $where = array(
                'id' => $id,
            );

            $updated = $wpdb->update($table_name, $data, $where);

            if ($updated === false) {
                throw new Exception("Error updating link");
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error in updateLink: " . $e->getMessage());
            return false;
        }
    }


    public static function shorten($long_url)
    {
        try {
            // Validate the URL
            $isValid = self::isValidUrl($long_url);
            if ($isValid !== true) {
                return ['error' => true, 'txt' => 'URL is not valid'];
            }

            // is it an allowed domain?
            $aDomain = self::checkDomain($long_url);

            if ($aDomain['prefix'] == 0) {
                return ['error' => true, 'txt' => 'Domain is not allowed to use our shortening service.'];
            }

            $aLink = self::getLinkfromDB($long_url); 

            if (!empty($aLink['short_url'])) {
                // url found in DB => return it
                return ['error' => false, 'txt' => $aLink['short_url']];
            }

            // Create shortURL
            $targetURL = $aDomain['prefix'] . self::cryptNumber($aLink['id']);
            $bUpdated = self::updateLink($aLink['id'], $targetURL);

            if (!$bUpdated) {
                return ['error' => true, 'txt' => 'Unable to update database table'];
            }

            $shortURL = self::$CONFIG['ShortURLBase'] . $targetURL;

            self::setShortURLinDB($aLink['id'], $shortURL);

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

