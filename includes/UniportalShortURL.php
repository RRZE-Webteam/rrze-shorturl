<?php

// 2021-02-14 BK : this is a translation in PHP 8.2 of /proj/websource/docs/redirects/www.rrze.fau.info/cgi-bin/lib/Uniportal/ShortURL.pm

namespace RRZE\ShortURL;

use RRZE\ShortURL\UniportalShortURLServices; 

class UniportalShortURL {
    public static array $CONFIG = [
        "ShortURLBase" => "http://go.fau.de/",
        "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-"
    ];

    public static function getIdResourceByServiceURL($url) {
        if (!$url) return;

        $url = preg_replace('/[^a-z0-9\-\?\._\&:;\/\%\$!,\+=]/i', '', $url);

        $id = $type = '';

        foreach (UniportalShortURLServices::$Services as $key => $service) {
            $serviceurl = str_replace('$id', '', $service['targeturl']);
            $sslserviceurl = str_replace('http:', 'https:', $serviceurl);
            $aliasurl = str_replace('$id', '', $service['servicestarturl']);

            if ($serviceurl && preg_match('/' . preg_quote($serviceurl, '/') . '([0-9]+)/', $url, $matches)) {
                $id = $matches[1];
                $type = $key;
                break;
            } elseif ($sslserviceurl && preg_match('/' . preg_quote($sslserviceurl, '/') . '([0-9]+)/', $url, $matches)) {
                $id = $matches[1];
                $type = $key;
                break;
            } elseif ($aliasurl && preg_match('/' . preg_quote($aliasurl, '/') . '([0-9]+)/', $url, $matches)) {
                $id = $matches[1];
                $type = $key;
                break;
            }
        }

        return [$id, $type];
    }

    public static function createTargetURL($type, $id) {
        if (!$type || !$id) return;

        $target = self::getTargetURLByPrefix($type);

        if (!$target) return;

        $target = str_replace('$id', $id, $target);

        if (strpos($id, '.') !== false) {
            $teil = explode('.', $id);

            foreach ($teil as $key => $val) {
                $target = str_replace('$p' . ($key + 1), $val, $target);
            }
        }

        return $target;
    }

    public static function getIdResourceByShortURL($url) {
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

    public static function isValidUrl($url) {
        // Use PHP's built-in filter_var function with FILTER_VALIDATE_URL flag
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return "invalid url";
        }
        return true;
    }

    public static function calcResource($code) {
        if (!$code) return;

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

    public static function posShortURLModChar($char) {
        $modchars = self::$CONFIG['ShortURLModChars'];
        $res = strpos($modchars, $char);
        if ($res !== false) {
            $res += 1;
            return $res;
        }
        return -1;
    }

    public static function getShortURL($id, $resourcetype) {
        $modchars = self::$CONFIG['ShortURLModChars'];
        $modbase = strlen($modchars);

        if (!$resourcetype || !$id) return;

        $prefix = self::getPrefixByType($resourcetype);

        if (!$prefix) return;

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

    public static function calcShortURLId($zahl) {
        if (!$zahl) return;

        $modchars = self::$CONFIG['ShortURLModChars'];
        $modbase = strlen($modchars);
        $charlist = str_split($modchars);
        $result = '';
        $base = (int)($zahl / $modbase);
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
            $base = (int)($base / $modbase);
        }

        if (($rest == 0) && (!$result)) {
            $result = $charlist[0];
        }
        $result .= $charlist[$rest - 1];

        return $result;
    }

    public static function getParamByType($type, $param) {
        if (!$type) return;

        foreach (UniportalShortURLServices::$Services as $key => $service) {
            if (strtolower($key) == strtolower($type)) {
                return $service[$param] ?? null;
            }
        }
    }

    public static function getPrefixByType($type) {
        return self::getParamByType($type, "prefix");
    }

    public static function getTargetURLByType($type) {
        return self::getParamByType($type, "targeturl");
    }

    public static function getTargetURLByPrefix($prefix) {
        foreach (UniportalShortURLServices::$Services as $key => $service) {
            if ($prefix == $service['prefix']) {
                return $service['targeturl'];
            }
        }
    }
}

// // Usage example:
// UniportalShortURL::$CONFIG = [
//     "ShortURLBase" => "http://go.fau.de/",
//     "ShortURLModChars" => "abcdefghijklmnopqrstuvwxyz0123456789-"
// ];

// $url = "http://go.fau.de/example123";
// [$id, $type] = UniportalShortURL::getIdResourceByServiceURL($url);
// echo "ID: $id, Type: $type\n";

// $type = "example";
// $id = "123";
// $targetURL = UniportalShortURL::createTargetURL($type, $id);
// echo "Target URL: $targetURL\n";

// $url = "/1abc";
// [$type, $result] = UniportalShortURL::getIdResourceByShortURL($url);
// echo "Type: $type, Result: $result\n";

// $id = "123";
// $resourcetype = "example";
// $shortURL = UniportalShortURL::getShortURL($id, $resourcetype);
// echo "Short URL: $shortURL\n";
