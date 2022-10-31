<?php

use DocWatch\Documentor;
use DocWatch\Resolver;

test('resolver can identify all PHP Files in the given directories', function () {
    $files = Resolver::getFiles(Documentor::getPath('app/Models'));

    foreach ($files as $key => $file) {
        $files[$key] = $file->toArray();
    }

    expect($files)->toBe([
        [
            'path' => base_path('app/Models/Brand.php'),
            'namespace' => \App\Models\Brand::class,
        ],
        [
            'path' => base_path('app/Models/Category.php'),
            'namespace' => \App\Models\Category::class,
        ],
        [
            'path' => base_path('app/Models/Comment.php'),
            'namespace' => \App\Models\Comment::class,
        ],
        [
            'path' => base_path('app/Models/NotModel.php'),
            'namespace' => \App\Models\NotModel::class,
        ],
        [
            'path' => base_path('app/Models/Product.php'),
            'namespace' => \App\Models\Product::class,
        ],
        [
            'path' => base_path('app/Models/ProductCategory.php'),
            'namespace' => \App\Models\ProductCategory::class,
        ],
    ]);
});

test('resolver can identify all Models in the given directories', function () {
    $models = Resolver::getModels(Documentor::getPath('app/Models'));

    foreach ($models as $key => $file) {
        $models[$key] = $file->toArray();
    }

    expect($models)->toBe([
        [
            'path' => base_path('app/Models/Brand.php'),
            'namespace' => \App\Models\Brand::class,
        ],
        [
            'path' => base_path('app/Models/Category.php'),
            'namespace' => \App\Models\Category::class,
        ],
        [
            'path' => base_path('app/Models/Comment.php'),
            'namespace' => \App\Models\Comment::class,
        ],
        [
            'path' => base_path('app/Models/Product.php'),
            'namespace' => \App\Models\Product::class,
        ],
        [
            'path' => base_path('app/Models/ProductCategory.php'),
            'namespace' => \App\Models\ProductCategory::class,
        ],
    ]);
});

