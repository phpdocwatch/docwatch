<?php

use App\Models\Brand;
use DocWatch\Parsers\Laravel\AbstractLaravelParser;

test('laravel parser can identify columns and relations for a model', function () {
    fakeArtisanModelShow();

    $data = AbstractLaravelParser::getModelData(Brand::class);

    $name = null;
    foreach ($data['attributes'] as $attribute) {
        if ($attribute['name'] === 'name') {
            $name = $attribute;
        }
    }

    expect($name)->toBe([
        'name' => 'name',
        'type' => 'string(255)',
        'increments' => false,
        'nullable' => false,
        'default' => null,
        'unique' => false,
        'fillable' => true,
        'hidden' => false,
        'appended' => null,
        'cast' => null,
    ]);

    // This test was more useful when I was trying to figure out how to parse the artisan model:show command
    // until I realised --json option existed :facepalm:
});