<?php

namespace Illuminate\Database\Eloquent;

class Model
{
    public function getTable(): string
    {
        return $this->table;
    }

    public function getCasts(): array
    {
        return property_exists($this, 'casts') ? $this->casts : [];
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function newQuery(): Builder
    {
        return $this->newEloquentBuilder(null);
    }

    public static function query(): Builder
    {
        return (new static())->newEloquentBuilder(null);
    }
}

namespace Illuminate\Database\Eloquent;

class Builder
{
    public \Illuminate\Database\Query\Builder $query;

    /**
     * Remove all or passed registered global scopes.
     *
     * @param  array|null  $scopes
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null)
    {
        return $this;
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function whereKey($id)
    {
        return $this;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
     */
    public function find($id, $columns = ['*'])
    {
      return null;
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static|static[]
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<\Illuminate\Database\Eloquent\Model>
     */
    public function findOrFail($id, $columns = ['*'])
    {
        return null;
    }

    /**
     * Test method
     *
     * @return $this
     */
    public function testMethod()
    {
        return $this;
    }
}

namespace Illuminate\Database\Eloquent\Casts;

class Attribute
{
    public function __construct(
        public ?\Closure $get = null,
        public ?\Closure $set = null,
    ) {
    }
}

namespace Illuminate\Database\Eloquent\Relations;

class Pivot extends \Illuminate\Database\Eloquent\Model
{
}

namespace Illuminate\Database\Eloquent\Factories;

trait HasFactory
{
    public static function factory(array $attributes = [], int $count = 1)
    {
        return null;
    }
}

namespace Illuminate\Database\Query;

class Builder
{
}

namespace Illuminate\Contracts\Database\Eloquent;

interface CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes);

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes);
}