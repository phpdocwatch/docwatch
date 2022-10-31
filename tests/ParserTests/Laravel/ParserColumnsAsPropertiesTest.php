<?php

use DocWatch\Parsers\Laravel\ColumnsAsProperties;

test('parser can generate property docs for all columns for Brand model', function () {
    $parser = new ColumnsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Brand');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property integer \$id // [via ColumnsAsProperties]
 * @property string \$name // [via ColumnsAsProperties]
 * @property \Carbon\Carbon \$established // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$created_at // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$updated_at // [via ColumnsAsProperties]
 */
class Brand
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});

test('parser can generate property docs for all columns for Comment model', function () {
    $parser = new ColumnsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Comment');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property integer \$id // [via ColumnsAsProperties]
 * @property string \$commentable_type // [via ColumnsAsProperties]
 * @property integer \$commentable_id // [via ColumnsAsProperties]
 * @property bool \$approved // [via ColumnsAsProperties]
 * @property string \$comment // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$created_at // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$updated_at // [via ColumnsAsProperties]
 */
class Comment
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});

test('parser can generate property docs for all columns for Product model', function () {
    $parser = new ColumnsAsProperties();
    fakeArtisanModelShow();

    $file = getFile('Product');
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Models;
/**
 * @property integer \$id // [via ColumnsAsProperties]
 * @property string \$sku // [via ColumnsAsProperties]
 * @property string \$name // [via ColumnsAsProperties]
 * @property integer \$brand_id // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$created_at // [via ColumnsAsProperties]
 * @property \Carbon\Carbon|null \$updated_at // [via ColumnsAsProperties]
 */
class Product
{
}
EOL;
    $expect = trim($expect);
    $actual = trim((string) $docs);
    
    expect($actual)->toBe($expect);
});