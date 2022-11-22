<?php

if (class_exists(\Illuminate\Support\Facades\App::class)) {
    return;
}

/**
 * Half of this package relies on Laravel but that doesn't mean we should load it in for testing.
 * Here we autoload fake Laravel classes that represent their real counterparts.
 * 
 * This reduces the amount of time it takes to run tests considerably, by not having to load an entire framework in lol
 */
require_once 'laravel/carbon.php';
require_once 'laravel/helpers.php';
require_once 'laravel/config.php';
require_once 'laravel/database.php';
require_once 'laravel/basepath.php';
require_once 'laravel/str.php';
require_once 'laravel/artisan.php';
require_once 'laravel/formrequest.php';
require_once 'laravel/serviceprovider.php';

/**
 * For Laravel, we're going to use the following models which cover most eloquent relationship types.
 */
require_once realpath(__DIR__ . '/../app/Builders/ProductBuilder.php');
require_once realpath(__DIR__ . '/../app/Casts/LatLng.php');
require_once realpath(__DIR__ . '/../app/Casts/LatLngOptional.php');
require_once realpath(__DIR__ . '/../app/DTOs/Coordinates.php');
require_once realpath(__DIR__ . '/../app/Models/Brand.php');
require_once realpath(__DIR__ . '/../app/Models/Category.php');
require_once realpath(__DIR__ . '/../app/Models/Comment.php');
require_once realpath(__DIR__ . '/../app/Models/NotModel.php');
require_once realpath(__DIR__ . '/../app/Models/Product.php');
require_once realpath(__DIR__ . '/../app/Models/ProductCategory.php');
require_once realpath(__DIR__ . '/../app/Providers/AppServiceProvider.php');
require_once realpath(__DIR__ . '/../app/Http/Requests/ProductCreateRequest.php');
require_once realpath(__DIR__ . '/../app/Rules/SuitableTags.php');
