<?php

namespace RRZE\ShortURL;

use \RRZE\AccessControl\Permissions;

class Rights
{
    private $idm = null;

    public function __construct()
    {
        $this->idm = 'system';
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $this->idm = $current_user->user_nicename;
        } elseif (class_exists('\RRZE\AccessControl\Permissions')) {
            $permissionsInstance = new Permissions();
            $checkSSOLoggedIn = $permissionsInstance->checkSSOLoggedIn();
            $personAttributes = $permissionsInstance->personAttributes;
            $this->idm = (!empty($personAttributes['urn:mace:dir:attribute-def:uid'][0]) ? $personAttributes['urn:mace:dir:attribute-def:uid'][0] : null);
        } else {
            error_log('\RRZE\AccessControl\Permissions is not available');
        }
    }

    public function getRights(): array
    {
        global $wpdb;
        $aRet = [
            'id' => 1,
            'uri_allowed' => false,
            'get_allowed' => false,
            'longlifelinks_allowed' => false
        ];

        try {
            $result = $wpdb->get_row($wpdb->prepare("SELECT id, allow_uri, allow_get, allow_longlifelinks FROM {$wpdb->prefix}shorturl_idms WHERE idm = %s", $this->idm), ARRAY_A);

            if ($result) {
                $aRet['id'] = $result['id'];
                $aRet['uri_allowed'] = (bool) $result['allow_uri'];
                $aRet['get_allowed'] = (bool) $result['allow_get'];
                $aRet['longlifelinks_allowed'] = (bool) $result['allow_longlifelinks'];
            } else {
                if (!empty($this->idm)) {
                    try {
                        // add the IdM
                        $wpdb->insert(
                            $wpdb->prefix . 'shorturl_idms',
                            array(
                                'idm' => $this->idm,
                            )
                        );
                        $aRet['id'] = $wpdb->insert_id;
                    } catch (\Exception $e) {
                        error_log('Error adding idm: ' . $e->getMessage());
                        return $aRet;
                    }
                }
            }
            return $aRet;
        } catch (\Exception $e) {
            error_log('Error fetching rights: ' . $e->getMessage());
            return $aRet;
        }
    }
}
