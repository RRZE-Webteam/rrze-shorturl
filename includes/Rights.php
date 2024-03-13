<?php

namespace RRZE\ShortURL;

class Rights
{
    public static function isAllowedToSetURI(string $idm): bool{
        return true;
    }

    public static function isAllowedToUseGET(string $idm): bool{
        return true;
    }

}