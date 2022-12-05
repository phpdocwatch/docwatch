<?php

use App\Models\Brand;
use DocWatch\Exceptions\DoctrineDbalRequiredException;
use DocWatch\Parsers\Laravel\AbstractLaravelParser;

test('a model show fils when doctrine dbal does not exist in the composer.json file', function () {
    /** Reset so that it is parsed from the composer.json file */
    AbstractLaravelParser::$hasDoctrineDbal = null;
    $e = null;
    
    try {
        fakeArtisanModelShow();
        AbstractLaravelParser::getModelData(Brand::class);
    } catch (\Exception $e) {
    }

    expect($e)->toBeInstanceOf(DoctrineDbalRequiredException::class);

    /** Reset so that other tests will read regardless on dbal (this package doesn't and shouldn't require it) */
    AbstractLaravelParser::$hasDoctrineDbal = true;
});

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