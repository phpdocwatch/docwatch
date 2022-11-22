<?php

use DocWatch\Parsers\Laravel\CastsAsProperties;

test('parser can generate property docs for all casts for Brand model', function () {
    $parser = new CastsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Brand');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property array \$meta // [via CastsAsProperties]
 * @property \App\DTOs\Coordinates \$coordinates // [via CastsAsProperties]
 */
class Brand
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});

test('parser can generate property docs for all casts for Comment model', function () {
    $parser = new CastsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Comment');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property \Carbon\Carbon \$approved_at // [via CastsAsProperties]
 */
class Comment
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});