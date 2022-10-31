<?php

use DocWatch\Documentor;

test('documentor can run full documentation process', function () {
    $documentor = new Documentor();
    fakeArtisanModelShow();

    $config = $documentor->readConfig();

    // Default example configuration
    expect($config)->toHaveKey('directories');
    expect($config['directories'])->toHaveKey('app/Models');
    expect($config['directories']['app/Models'])->toHaveKey('parsers');

    // We only want the Models test right now.
    $config = [
        'directories' => [
            'app/Models' => $config['directories']['app/Models'],
        ],
    ];
    Documentor::withConfig($config);

    $actual = $documentor->run()->trim()->compile();
    $expect = file_get_contents(__DIR__ . '/fixtures/documentor_generated.txt');

    expect($actual)->toBe($expect);
});

// test('documentor can fetch output file', function () {
//     $documentor = new Documentor();
//     $documentor->readConfig();

//     $actual = $documentor->getOutputFile();
//     $expect = base_path('bootstrap/docwatch.php');

//     expect($actual)->toBe($expect);
// });
