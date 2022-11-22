<?php

use DocWatch\Parsers\Laravel\MacrosAsMethods;

test('parser can generate property docs for all macros', function () {
    $parser = new MacrosAsMethods();

    $file = getFile('AppServiceProvider');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace Carbon;
/**
 * @method static \Carbon\Carbon|null tryParse(\$value, \$tz) // [via MacrosAsMethods]
 */
class Carbon
{
}




namespace Illuminate\Support;
/**
 * @method static string cleanTitle(string \$value) // [via MacrosAsMethods]
 * @method static string cleanSnake(string \$value) // [via MacrosAsMethods]
 */
class Str
{
}




namespace Illuminate\Database\Eloquent;
/**
 * @method static \Illuminate\Database\Eloquent\Builder whereLike(array \$attributes, string \$searchTerm) // [via MacrosAsMethods]
 */
class Builder
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});