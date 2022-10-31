<?php

use DocWatch\Parsers\Laravel\RelationsAsProperties;

test('parser can generate property docs for all relations for Brand model', function () {
    $parser = new RelationsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Brand');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property \App\Models\Product|null \$firstProduct // [via RelationsAsProperties]
 * @property \Illuminate\Support\Collection<int,\App\Models\Product> \$products // [via RelationsAsProperties]
 * @property \Illuminate\Support\Collection<int,\App\Models\Comment> \$comments // [via RelationsAsProperties]
 */
class Brand
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});

test('parser can generate property docs for all relations for Comment model', function () {
    $parser = new RelationsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Comment');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property \Illuminate\Database\Eloquent\Model \$commentable // [via RelationsAsProperties]
 */
class Comment
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});