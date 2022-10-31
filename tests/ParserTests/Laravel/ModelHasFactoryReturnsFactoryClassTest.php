<?php

use DocWatch\Parsers\Laravel\ModelHasFactoryReturnsFactoryClass;

test('parser can generate method docs for has factory', function () {
    $parser = new ModelHasFactoryReturnsFactoryClass();
    $file = getFile('Brand');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @method static \Database\Factories\BrandFactory factory(array \$attributes = [], int \$count = 1) // [via ModelHasFactoryReturnsFactoryClass]
 */
class Brand
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});