<?php

use DocWatch\Parsers\Laravel\AccessorsAsProperties;

test('parser can generate property docs for all accessors for Comment model', function () {
    $parser = new AccessorsAsProperties();

    $file = getFile('Comment');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property-read int \$net_likes // [via AccessorsAsProperties]::getNetLikesAttribute()
 * @property-write int \$rating // [via AccessorsAsProperties]::setRatingAttribute()
 * @property-read string|null \$preview // [via AccessorsAsProperties]::preview()
 */
class Comment
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});