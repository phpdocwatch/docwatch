<?php

namespace Illuminate\Support\Facades;

class Artisan
{
    public static string $output = '';

    public static array $faked = [];

    public static function fake(string $name, array $arguments = [], string $output)
    {
        static::$faked[$name . json_encode($arguments)] = $output;
    }

    public static function call(string $name, array $arguments = []): void
    {
        static::$output = static::$faked[$name . json_encode($arguments)] ?? 'no output mocked';
    }

    public static function output(): string
    {
        return static::$output;
    }
}

namespace Illuminate\Console;

class Command
{
}