<?php

use DocWatch\Parsers\Laravel\RequestParametersAsProperties;

test('parser can identify rules from a request class', function () {
    $file = getFile('ProductCreateRequest');

    $parser = new RequestParametersAsProperties();
    $docs = $parser->parse($file);

    $expect = <<<EOL
namespace App\Http\Requests;
/**
 * @property string|mixed \$sku // [via RequestParametersAsProperties]
 * @property string|mixed \$name // [via RequestParametersAsProperties]
 * @property int|mixed \$brand_id // [via RequestParametersAsProperties]
 * @property array|mixed \$categories // [via RequestParametersAsProperties]
 */
class ProductCreateRequest
{
}
EOL;

    expect((string) $docs)->toBe($expect);
});