<?php

namespace RRZE\ShortURL;

use \RRZE\AccessControl\Permissions;
use RRZE\ShortURL\CustomException;

class Rights
{
    private $idm = null;

    public function __construct()
    {
        // $this->idm = 'system';
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $this->idm = $current_user->user_nicename;
        } elseif (class_exists('\RRZE\AccessControl\Permissions')) {
            $permissionsInstance = new Permissions();
            $checkSSOLoggedIn = $permissionsInstance->checkSSOLoggedIn();
            $personAttributes = $permissionsInstance->personAttributes;


            // $this->idm = (!empty($personAttributes['urn:mace:dir:attribute-def:uid'][0]) ? $personAttributes['urn:mace:dir:attribute-def:uid'][0] : null);
            $this->idm = (!empty($personAttributes['uid']) ? $personAttributes['uid'] : null);

            $tmp = json_encode($personAttributes);
            echo "<script>console.log('BK DEBUG: in construct " . $tmp . "' );</script>";

            
            if ($this->idm !== null && str_ends_with($this->idm, 'fau-de')) {
                $this->idm = substr($this->idm, 0, -6);
            }
        } else {
            error_log('\RRZE\AccessControl\Permissions is not available');
        }
    }

    public function getRights(): array
    {

        echo "<script>console.log('BK DEBUG: " . $this->idm . "' );</script>";
        
        global $wpdb;
        $aRet = [
            'id' => 0,
            'allow_uri' => false,
            'allow_get' => false,
            'allow_utm' => false
        ];

        try {
            $result = $wpdb->get_row($wpdb->prepare("SELECT id, allow_uri, allow_get, allow_utm FROM {$wpdb->prefix}shorturl_idms WHERE idm = %s", $this->idm), ARRAY_A);

            if ($result) {
                $aRet['id'] = $result['id'];
                $aRet['allow_uri'] = (bool) $result['allow_uri'];
                $aRet['allow_get'] = (bool) $result['allow_get'];
                $aRet['allow_utm'] = (bool) $result['allow_utm'];
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
                    } catch (CustomException $e) {
                        error_log('Error adding idm: ' . $e->getMessage());
                        return $aRet;
                    }
                }
            }
            return $aRet;
        } catch (CustomException $e) {
            error_log('Error fetching rights: ' . $e->getMessage());
            return $aRet;
        }
    }
}
