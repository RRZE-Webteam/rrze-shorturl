<?php

namespace RRZE\ShortURL;

class ShortURL
{
    public static array $CONFIG = [];

    public function __construct()
    {
        self::$CONFIG = [
            "ShortURLBase" => "http://go.fau.de/",
            "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-",
            "AllowedDomains" => self::getAllowedDomains()
        ];
    }

    public static function getIdResourceByServiceURL($url)
    {
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
                $type = $aService['type'];
                break;
            } elseif ($sslserviceurl && preg_match('/' . preg_quote($sslserviceurl, '/') . '([0-9]+)/', $url, $matches)) {
                $id = $matches[1];
                $type = $aService['type'];
                break;
            } elseif ($aliasurl && preg_match('/' . preg_quote($aliasurl, '/') . '([0-9]+)/', $url, $matches)) {
                $id = $matches[1];
                $type = $aService['type'];
                break;
            }
        }

        return [$id, $type];
    }

    public static function createTargetURL($type, $id)
    {
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
    }

    public static function getIdResourceByShortURL($url)
    {
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
    }

    public static function isValidUrl($url)
    {
        // Use PHP's built-in filter_var function with FILTER_VALIDATE_URL flag
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return "invalid url";
        }
        return true;
    }

    public static function calcResource($code)
    {
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
    }

    public static function posShortURLModChar($char)
    {
        $modchars = self::$CONFIG['ShortURLModChars'];
        $res = strpos($modchars, $char);
        if ($res !== false) {
            $res += 1;
            return $res;
        }
        return -1;
    }

    public static function getShortURL($id, $resourcetype)
    {
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
    }

    public static function calcShortURLId($zahl)
    {
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
    }

    public static function getParamByType($type, $param)
    {
        if (!$type)
            return;

        foreach (self::$CONFIG['AllowedDomains'] as $key => $service) {
            if (strtolower($key) == strtolower($type)) {
                return $service[$param] ?? null;
            }
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
        foreach (self::$CONFIG['AllowedDomains'] as $key => $service) {
            if ($prefix == $service['prefix']) {
                return $service['targeturl'];
            }
        }
    }

    public static function getLinkfromDB($long_url){
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
        
        // Query the links table to get the ID and short_url where long_url matches $long_url
        $result = $wpdb->get_results($wpdb->prepare("SELECT id, short_url FROM $table_name WHERE long_url = %s", $long_url), ARRAY_A);
        
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
            return $result[0];
        }
    }
    
    public static function setShortURLinDB($link_id, $shortURL){
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
        // Store in the database
        $wpdb->update(
            $table_name,
            array('short_url' => $shortURL),
            array('id' => $link_id)
        );
    }

    public static function checkDomain($long_url){
        $aRet = ["prefix" => 0, "hostname" => ''];
        $domain = wp_parse_url($long_url, PHP_URL_HOST);

        // Check if the extracted domain belongs to one of our allowed domains
        $isOurDomain = false;
        foreach (self::$CONFIG['AllowedDomains'] as $prefix => $allowed_domain) {
            if ($domain === $allowed_domain) {
                $aRet = ["prefix" => 1, "hostname" => $domain];
                break;
            }
        }

        return $aRet;
    }
    
    // Function to retrieve our domains from the database
    public static function getAllowedDomains()
    {
        global $wpdb;

        // Table name
        $table_name = $wpdb->prefix . 'shorturl_domains';

        // Query to select servername from the shorturl_domains table
        $query = "SELECT hostname, prefix FROM $table_name";

        // Execute the query
        $results = $wpdb->get_results($query);

        // Extract servernames from the results
        $aDomains = [];
        foreach ($results as $result) {
            $aDomains[$result->prefix] = $result->hostname;
        }

        return $aDomains;
    }

    public static function shorten($long_url)
    {
        global $wpdb;
        $aDomain = ['prefix' => 0, 'domainkey' => ''];

        // Validate the URL
        $isValid = self::isValidUrl($long_url);
        if ($isValid !== true) {
            return ['error' => true, 'txt' => 'URL is not valid'];
        }
        $aDomain = self::checkDomain($long_url);

        if ($aDomain['prefix'] == 0){
            return ['error' => true, 'txt' => 'Domain is not allowed to use our shortening service.'];
        }

        $aLink = self::getLinkfromDB($long_url); // 2DO: liefert tab.id und tab.shortURL => shortURL isNUll => berechnen, sonst ausgeben

        if ($aLink['shortURL'] !== ''){
            // url found in DB => return it
            $targetURL = $aLink['shortURL'];
            $shortURL = self::$CONFIG['ShortURLBase'] . $targetURL;
            return ['error' => false, 'txt' => $shortURL];
        }

        // Create shortURL
        if ($aDomain['prefix'] == 1){
            // customer domain
            $targetURL = self::createTargetURL($aDomain['type'], $aLink['id']) . 'TEST-CustomDomain' . $aLink['id'];
        }else{
            // service domain
            // Get ID and type from the service URL
            [$id, $aDomain['type']] = self::getIdResourceByServiceURL($long_url);
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
        $shortURL = self::$CONFIG['ShortURLBase'] . $targetURL;

        self::setShortURLinDB($aLink['id'], $shortURL);

        return ['error' => false, 'txt' => $shortURL];
    }



}

