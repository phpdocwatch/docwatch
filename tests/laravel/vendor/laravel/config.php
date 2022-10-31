<?php

namespace Illuminate\Support\Facades;

class Config
{
    public static bool $getCalled = false;

    public static function get(string $key, $default = null)
    {
        $path = dirname(dirname(__DIR__)) . '/config/docwatch.php';
        static::$getCalled = true;

        $data = [
            'docwatch' => require $path,
        ];

        return static::dot($data, $key, $default);
    }

    private static function dot(&$array, string $key, $default = null) {
        $pieces = explode('.', $key);
        
        foreach ($pieces as $piece) {
            if (!is_array($array) || !array_key_exists($piece, $array)) {
                return $default;
            }

            $array = &$array[$piece];
        }

        return $array ?? $default;
    }
}