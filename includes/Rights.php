<?php

namespace RRZE\ShortURL;

class Rights
{
    public static function getRights(string $idm): array {
        global $wpdb;
        $aRet = [
            'uri_allowed' => false,
            'get_allowed' => false
        ];
        
        try {
            $result = $wpdb->get_row($wpdb->prepare("SELECT allow_uri, allow_get FROM {$wpdb->prefix}shorturl_idms WHERE idm = %s", $idm), ARRAY_A );
    
            if ($result) {
                return [
                    'uri_allowed' => (bool)$result['allow_uri'],
                    'get_allowed' => (bool)$result['allow_get']
                ];
            } else {
                return $aRet;
            }
        } catch (\Exception $e) {
            error_log('Error fetching rights: ' . $e->getMessage());
            return $aRet;
        }
    }
    
}