<?php

use Illuminate\Support\Facades\Artisan;
use DocWatch\Documentor;
use DocWatch\File;
use DocWatch\Parsers\Laravel\AbstractLaravelParser;
use DocWatch\Resolver;

Documentor::fake();
AbstractLaravelParser::$hasDoctrineDbal = true;

require_once 'laravel/vendor/autoload-fake.php';

require_once __DIR__ . '/fixtures/ReflectionParameterItem.php';
require_once __DIR__ . '/fixtures/ClassWithProxyMethod.php';
require_once __DIR__ . '/fixtures/SomeEnum.php';

if (!function_exists('dump')) {
    function dump()
    {
        array_map(function ($item) {
            echo print_r($item, true) . "\n";
        }, func_get_args());
    }
}

if (!function_exists('dd')) {
    function dd()
    {
        dump(...func_get_args());
        
        die();
    }
}

function getFile(string $name): File
{
    $map = [
        'ClassWithProxyMethod' => __DIR__ . '/fixtures/ClassWithProxyMethod.php',
        'AppServiceProvider' => __DIR__ . '/laravel/app/Providers/AppServiceProvider.php',
        'Brand' => __DIR__ . '/laravel/app/Models/Brand.php',
        'Category' => __DIR__ . '/laravel/app/Models/Category.php',
        'Comment' => __DIR__ . '/laravel/app/Models/Comment.php',
        'Product' => __DIR__ . '/laravel/app/Models/Product.php',
        'ProductCategory' => __DIR__ . '/laravel/app/Models/ProductCategory.php',
        'ProductCreateRequest' => __DIR__ . '/laravel/app/Http/Requests/ProductCreateRequest.php',
    ];

    $path = $map[$name];
    $namespace = Resolver::getNamespace($path);

    return new File($path, $namespace);
}

function fakeArtisanModelShow()
{
    $map = [
        'App\\Models\\Brand' => __DIR__ . '/laravel/artisan/modelshow_App_Models_Brand.json',
        'App\\Models\\Category' => __DIR__ . '/laravel/artisan/modelshow_App_Models_Category.json',
        'App\\Models\\Comment' => __DIR__ . '/laravel/artisan/modelshow_App_Models_Comment.json',
        'App\\Models\\Product' => __DIR__ . '/laravel/artisan/modelshow_App_Models_Product.json',
    ];

    foreach ($map as $model => $path) {
        Artisan::fake('model:show', [
            'model' => $model,
            '--json' => true,
        ], file_get_contents($path));
    }
}