<?php

use DocWatch\Parsers\Laravel\ModelsExposeQueryBuilderMethods;

test('parser can generate method docs for query builder methods', function () {
    $parser = new ModelsExposeQueryBuilderMethods();
    $docs = $parser->standalone();

    $expect = <<<EOL
namespace Illuminate\Database\Eloquent;
/**
 * @method static \Illuminate\Database\Eloquent\Builder withoutGlobalScopes(array|null \$scopes = null) // [via ModelsExposeQueryBuilderMethods]
 * @method static \Illuminate\Database\Eloquent\Builder whereKey(\$id) // [via ModelsExposeQueryBuilderMethods]
 * @method static \Illuminate\Database\Eloquent\Builder find(\$id, \$columns = ["*"]) // [via ModelsExposeQueryBuilderMethods]
 * @method static \Illuminate\Database\Eloquent\Builder findOrFail(\$id, \$columns = ["*"]) // [via ModelsExposeQueryBuilderMethods]
 * @method static \Illuminate\Database\Eloquent\Builder testMethod() // [via ModelsExposeQueryBuilderMethods]
 */
class Model
{
}
EOL;

    expect((string) $docs)->toBe($expect);
});
