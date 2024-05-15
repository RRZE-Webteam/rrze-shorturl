<?php

namespace RRZE\ShortURL;

class MyCrypt
{
    protected static $baseChars;
    protected static $base;

    public function __construct()
    {
        self::$baseChars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        self::$base = 37;

    }

    public function decrypt(string $code): int
    {
        if (!preg_match('/^[-a-z0-9]+$/i', $code)) {
            throw new InvalidArgumentException("Invalid code: $code");
        }

        $base37Chars = '-' . self::$baseChars;
        $result = 0;
        $len = strlen($code) - 1;

        for ($t = 0; $t <= $len; $t++) {
            $result = $result + strpos($base37Chars, substr($code, $t, 1)) * pow(self::$base, $len - $t);
        }

        return $result;
    }


    public function encrypt(int $id): string
    {
        if ($id < 0) {
            throw new InvalidArgumentException("Ungültige ID: $id");
        }

        $base37Chars = self::$baseChars . '-';
        $result = '';

        while ($id > 0) {
            $remainder = ($id % self::$base) - 1;
            $result = $base37Chars[$remainder] . $result;
            $id = intdiv($id, self::$base);
        }

        return $result === '' ? '0' : $result;
    }

}
