<?php

use DocWatch\Parsers\Laravel\ScopesAsMethods;

test('parser can generate property docs for all scopes for Brand model', function () {
    $parser = new ScopesAsMethods();
    fakeArtisanModelShow();

    $file = getFile('Brand');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @method static \Illuminate\Database\Eloquent\Builder establishedRecently() // [via ScopesAsMethods]
 * @method static \Illuminate\Database\Eloquent\Builder establishedAround(\Carbon\Carbon \$year) // [via ScopesAsMethods]
 * @method static array asList(string \$label = "name", string \$id = "id") // [via ScopesAsMethods]
 */
class Brand
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});