<?php

/**
 * Simulate base_path() function from Laravel here.
 */
if (!function_exists('base_path')) {
    /**
     * Fake non-laravel base_path function that mimics Laravel's base_path function.
     *
     * @param string $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return rtrim(dirname(dirname(__DIR__)), '/') . '/' . ltrim($path, '/');
    }
}

