<?php

namespace DocWatch\Objects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModelQueryBuilder extends AbstractObject
{
    public const ROOT_NAMESPACE = 'ProxiedQueries\\';

    public function __construct(public Model $model, public Collection $scopes)
    {
    }

    /**
     * Get the proxied query builder namespace from an eloquent model
     *
     * @param EloquentModel $model
     * @return string
     */
    public static function getNamespace(EloquentModel|string $model): string
    {
        return static::ROOT_NAMESPACE . (is_string($model) ? $model : get_class($model)) . '\\Builder';
    }

    /**
     * Create docblocks for this proxy query builder
     *
     * @return void
     */
    public function compile()
    {
        $fullnamespace = static::getNamespace($this->model->model);
        $name = Str::afterLast($fullnamespace, '\\');
        $namespace = Str::beforeLast($fullnamespace, '\\');

        $base = '\\' . Builder::class;
        $methods = $this->scopes->map(fn (Scope $scope) => $scope->compileForQueryBuilder())->implode("\n");

return <<<EOL
/**
{$methods}
 */
namespace {$namespace};
class {$name} extends {$base} {}
EOL;
    }
}
