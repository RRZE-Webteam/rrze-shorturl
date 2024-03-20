<?php

namespace RRZE\ShortURL;

use \RRZE\AccessControl\Permissions;

class Rights
{
    public static function getRights(): array
    {
        global $wpdb;
        $aRet = [
            'id' => 0,
            'uri_allowed' => false,
            'get_allowed' => false
        ];


        // TEST !!!
        return [
            'id' => 1,
            'uri_allowed' => true,
            'get_allowed' => true
        ];


        try {
            if (class_exists('\RRZE\AccessControl\Permissions')) {
                $permissions = new Permissions();

                error_log('in Rights : ' . json_encode($permissions));
                echo '<pre>';
                var_dump($permissions);
                exit;

                $idm = $permissions->personAttributes['idm']; // key überprüfen, wie der genau heißt

                if ($idm) {
                    $result = $wpdb->get_row($wpdb->prepare("SELECT id, allow_uri, allow_get FROM {$wpdb->prefix}shorturl_idms WHERE idm = %s", $idm), ARRAY_A );

                    if ($result) {
                        return [
                            'id' => $result['id'],
                            'uri_allowed' => (bool)$result['allow_uri'],
                            'get_allowed' => (bool)$result['allow_get']
                        ];
                    } else {
                        return $aRet;
                    }
                } else {
                    error_log('\RRZE\AccessControl\Permissions did not return IdM ');
                    return $aRet;
                }
            } else {
                error_log('\RRZE\AccessControl\Permissions is not available');
                return $aRet;
            }
        } catch (\Exception $e) {
            error_log('Error fetching rights: ' . $e->getMessage());
            return $aRet;
        }
    }
}
