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

    public static function getIdResourceByServiceURL($url)
    {
        try {
            if (!$url)
                return;

            $url = preg_replace('/[^a-z0-9\-\?\._\&:;\/\%\$!,\+=]/i', '', $url);

            $id = $type = '';

            foreach (self::$CONFIG['AllowedDomains'] as $aService) {
                $serviceurl = str_replace('$id', '', $aService['targeturl']);
                $sslserviceurl = str_replace('http:', 'https:', $serviceurl);
                $aliasurl = str_replace('$id', '', $aService['servicestarturl']);

                if ($serviceurl && preg_match('/' . preg_quote($serviceurl, '/') . '([0-9]+)/', $url, $matches)) {
                    $id = $matches[1];
                    $type = $aService['type_code'];
                    break;
                } elseif ($sslserviceurl && preg_match('/' . preg_quote($sslserviceurl, '/') . '([0-9]+)/', $url, $matches)) {
                    $id = $matches[1];
                    $type = $aService['type_code'];
                    break;
                } elseif ($aliasurl && preg_match('/' . preg_quote($aliasurl, '/') . '([0-9]+)/', $url, $matches)) {
                    $id = $matches[1];
                    $type = $aService['type_code'];
                    break;
                }
            }

            return [$id, $type];
        } catch (Exception $e) {
            error_log("Error in getIdResourceByServiceURL: " . $e->getMessage());
            return null;
        }
    }

    public static function createTargetURL($type, $id)
    {
        try {
            if (!$type || !$id)
                return;

            $target = self::getTargetURLByPrefix($type);

            if (!$target)
                return;

            $target = str_replace('$id', $id, $target);

            if (strpos($id, '.') !== false) {
                $teil = explode('.', $id);

                foreach ($teil as $key => $val) {
                    $target = str_replace('$p' . ($key + 1), $val, $target);
                }
            }

            return $target;
        } catch (Exception $e) {
            error_log("Error in createTargetURL: " . $e->getMessage());
            return null;
        }
    }

    public static function getIdResourceByShortURL($url)
    {
        try {
            $type = $result = '';

            if (preg_match('/^\/?([0-9])([a-z0-9\-]+)$/i', $url, $matches)) {
                $type = $matches[1];
                $code = $matches[2];
                $result = self::calcResource($code);
                return [$type, $result];
            } elseif (preg_match('/^\/?([0-9])([a-z0-9\-\.]+)$/i', $url, $matches)) {
                $type = $matches[1];
                $partcode = $matches[2];
                $teil = explode('.', $partcode);

                foreach ($teil as $val) {
                    $result .= self::calcResource($val);
                    $result .= ".";
                }
                return [$type, rtrim($result, '.')];
            } else {
                return [0, 0];
            }
        } catch (Exception $e) {
            error_log("Error in getIdResourceByShortURL: " . $e->getMessage());
            return null;
        }
    }

    public static function isValidUrl($url)
    {
        try {
            // Use PHP's built-in filter_var function with FILTER_VALIDATE_URL flag
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                return "invalid url";
            }
            return true;
        } catch (Exception $e) {
            error_log("Error in isValidUrl: " . $e->getMessage());
            return null;
        }
    }

    public static function calcResource($code)
    {
        try {
            if (!$code)
                return;

            $modchars = self::$CONFIG['ShortURLModChars'];
            $modbase = strlen($modchars);
            $charlist = str_split($modchars);
            $stelle = str_split($code);
            $len = strlen($code);
            $result = 0;

            for ($i = count($stelle) - 1; $i >= 0; $i--) {
                $thisval = self::posShortURLModChar($stelle[$i]);
                $offset = 0;
                $posval = $len - $i - 1;

                if ($thisval == $modbase) {
                    $thisval = 0;
                } else {
                    $offset = pow($modbase, $posval);
                    $thisval *= $offset;
                }

                $result += $thisval;
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in calcResource: " . $e->getMessage());
            return null;
        }
    }

    public static function posShortURLModChar($char)
    {
        try {
            $modchars = self::$CONFIG['ShortURLModChars'];
            $res = strpos($modchars, $char);
            if ($res !== false) {
                $res += 1;
                return $res;
            }
            return -1;
        } catch (Exception $e) {
            error_log("Error in posShortURLModChar: " . $e->getMessage());
            return null;
        }
    }

    public static function getShortURL($id, $resourcetype)
    {
        try {
            $modchars = self::$CONFIG['ShortURLModChars'];
            $modbase = strlen($modchars);

            if (!$resourcetype || !$id)
                return;

            $prefix = self::getPrefixByType($resourcetype);

            if (!$prefix)
                return;

            $result = '';

            if (strpos($id, '.') !== false) {
                $teil = explode('.', $id);

                foreach ($teil as $val) {
                    $result .= self::calcShortURLId($val);
                    $result .= ".";
                }
            } else {
                $result = self::calcShortURLId($id);
            }

            $resurl = self::$CONFIG['ShortURLBase'] . $prefix . $result;

            return $resurl;
        } catch (Exception $e) {
            error_log("Error in getShortURL: " . $e->getMessage());
            return null;
        }
    }

    public static function calcShortURLId($zahl)
    {
        try {
            if (!$zahl)
                return;

            $modchars = self::$CONFIG['ShortURLModChars'];
            $modbase = strlen($modchars);
            $charlist = str_split($modchars);
            $result = '';
            $base = (int) ($zahl / $modbase);
            $rest = $zahl % $modbase;

            while ($base > 0) {
                if ($base >= $modbase) {
                    $leftbase = $base % $modbase;
                    if ($leftbase == 0) {
                        $result = $charlist[count($charlist) - 1] . $result;
                    } else {
                        $result = $charlist[$leftbase - 1] . $result;
                    }
                } else {
                    $result = $charlist[$base - 1] . $result;
                }
                $base = (int) ($base / $modbase);
            }

            if (($rest == 0) && (!$result)) {
                $result = $charlist[0];
            }
            $result .= $charlist[$rest - 1];

            return $result;
        } catch (Exception $e) {
            error_log("Error in calcShortURLId: " . $e->getMessage());
            return null;
        }
    }

    public static function getParamByType($type, $param)
    {
        try {
            if (!$type)
                return;

            foreach (self::$CONFIG['AllowedDomains'] as $key => $service) {
                if (strtolower($key) == strtolower($type)) {
                    return $service[$param] ?? null;
                }
            }
        } catch (Exception $e) {
            error_log("Error in getParamByType: " . $e->getMessage());
            return null;
        }
    }

    public static function getPrefixByType($type)
    {
        return self::getParamByType($type, "prefix");
    }

    public static function getTargetURLByType($type)
    {
        return self::getParamByType($type, "targeturl");
    }

    public static function getTargetURLByPrefix($prefix)
    {
        try {
            foreach (self::$CONFIG['AllowedDomains'] as $key => $service) {
                if ($prefix == $service['prefix']) {
                    return $service['targeturl'];
                }
            }
        } catch (Exception $e) {
            error_log("Error in getTargetURLByPrefix: " . $e->getMessage());
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            error_log("Error in checkDomain: " . $e->getMessage());
            return null;
        }
    }

    public static function updateLink($id, $short_url)
    {
        global $wpdb;

        try {
            // Table name
            $table_name = $wpdb->prefix . 'shorturl_links';

            // Data to update
            $data = array(
                'short_url' => $short_url,
            );

            // Where clause to specify the row to update
            $where = array(
                'id' => $id,
            );

            // Update the link in the database
            $updated = $wpdb->update($table_name, $data, $where);

            // Check if the update was successful
            if ($updated === false) {
                // Throw an exception if the update fails
                throw new Exception("Error updating link");
            }

            // Return true if the update was successful
            return true;
        } catch (Exception $e) {
            // Handle the exception
            // You can log the error, return false, or throw a new exception
            // Here, we'll log the error message and return false
            error_log("Error in updateLink: " . $e->getMessage());
            return false;
        }
    }


    public static function shorten($long_url, $get_parameter)
    {
        global $wpdb;

        try {
            // Validate the URL
            $isValid = self::isValidUrl($long_url);
            if ($isValid !== true) {
                return ['error' => true, 'txt' => 'URL is not valid'];
            }


            // is it an allowed domain? If so, then get prefix, ... 
            $aDomain = self::checkDomain($long_url);

            if ($aDomain['prefix'] == 0) {
                return ['error' => true, 'txt' => 'Domain is not allowed to use our shortening service.'];
            }

            $aLink = self::getLinkfromDB($long_url . $get_parameter); 

            if (!empty($aLink['short_url'])) {
                // url found in DB => return it
                return ['error' => false, 'txt' => $aLink['short_url']];
            }

            // Create shortURL
            if ($aDomain['type_code'] == 'customerdomain' || $aDomain['type_code'] == 'zoom') {
                // Customer domain
                $targetURL = $aDomain['prefix'] . self::cryptNumber($aLink['id']) . $get_parameter;
                $bUpdated = self::updateLink($aLink['id'], $targetURL);
                if (!$bUpdated) {
                    return ['error' => true, 'txt' => 'Unable to update database table'];
                }
            } else {
                // return ['error' => true, 'txt' => 'prefix ist nicht 1'];
                // Service domain
                // Get ID and type from the service URL
                [$id, $aDomain['type']] = self::getIdResourceByServiceURL($long_url);
                // return ['error' => true, 'txt' => 'type = ' . $aDomain['type']];

                if (!$id || !$aDomain['type']) {
                    return ['error' => true, 'txt' => 'Unable to extract ID and type from the service URL'];
                }

                // Create target URL
                $targetURL = self::createTargetURL($aDomain['type'], $id);
                if (!$targetURL) {
                    return ['error' => true, 'txt' => 'Unable to create target URL'];
                }
            }

            // Combine the hashed value with ShortURLBase
            $shortURL = self::$CONFIG['ShortURLBase'] . $targetURL . $get_parameter;

            self::setShortURLinDB($aLink['id'], $shortURL);

            return ['error' => false, 'txt' => $shortURL];
        } catch (Exception $e) {
            error_log("Error in shorten: " . $e->getMessage());
            return null;
        }
    }

    /* Test-Data

    https://blogs.fau.de/rewi/2024/02/15/erfolgreiches-pilotprojekt-e-klausuren-in-der-uebung-fuer-fortgeschrittene-im-zivilrecht/
     

    https://www.germanistik.phil.fau.de/wp-content/plugins/contact-form-7/includes/js/index.js?ver=5.8.7:1

    */

}

