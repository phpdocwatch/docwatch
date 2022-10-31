<?php

use Illuminate\Support\Facades\Config;
use DocWatch\Documentor;

it('reads configuration file', function () {
    $config = Documentor::readConfig();

    expect($config)->toHaveKey('directories');
});

it('reads configuration file when using laravel', function () {
    Documentor::unfake();
    expect(Config::$getCalled)->toBeFalse();
    
    $config = Documentor::readConfig();
    expect($config)->toHaveKey('directories');
    expect(Config::$getCalled)->toBeTrue();
});

it('can convert a relative path to an absolute path', function () {
    Documentor::fake();
    
    $path = Documentor::getPath('app/Models');
    expect($path)->toBe(__DIR__ . '/laravel/app/Models');
});

it('can convert a relative path to an absolute path when using laravel', function () {
    Documentor::unfake();
    
    $path = Documentor::getPath('app/Models');
    expect($path)->toBe(__DIR__ . '/laravel/app/Models');

    Documentor::fake();
});