<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use DocWatch\DocWatch\Writer\WriterInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

/**
 * @requires Laravel
 */
class RelationsAsProperties implements ParseInterface
{
    /**
     * Map of which relations have "many" related entities and will return a Collection
     *
     * True = Many, returns Collection
     * False = One, returns Model
     * Null = Both, returns Either
     *
     * @var array<string,bool|null>
     */
    public const HAS_MANY_TYPES = [
        // Ones
        MorphTo::class => false,
        BelongsTo::class => false,
        HasOne::class => false,
        MorphOne::class => false,
        HasOneThrough::class => false,
        // Manys
        BelongsToMany::class => true,
        MorphToMany::class => true,
        HasMany::class => true,
        HasManyThrough::class => true,
        MorphMany::class => true,
        // OneOrManys
        HasOneOrMany::class => null,
        MorphOneOrMany::class => null,
        // Pivots
        MorphPivot::class => false,
        Pivot::class => false,
        // Default
        EloquentRelation::class => null,
    ];

    /**
     * Map of which relations have "many" related entities and will return a Collection
     *
     * True = Yes, Has *Specific* Model
     * False = No, No *Specific* Model
     *
     * @var array<string,bool|null>
     */
    public const HAS_MODEL_TYPES = [
        // Ones
        BelongsTo::class => true,
        MorphTo::class => false,
        HasOne::class => true,
        MorphOne::class => false,
        HasOneOrMany::class => true,
        // Manys
        BelongsToMany::class => true,
        MorphToMany::class => false,
        HasMany::class => true,
        HasManyThrough::class => true,
        MorphMany::class => true,
        // OneOrManys
        HasOneThrough::class => true,
        MorphOneOrMany::class => false,
        // Pivots
        MorphPivot::class => false,
        Pivot::class => false,
        // Default
        EloquentRelation::class => false,
    ];

    /**
     * Get all relations from the given model class
     *
     * @param ReflectionClass $class
     * @return Collection<ReflectionMethod>
     */
    public static function getRelations(ReflectionClass $class): Collection
    {
        return collect($class->getMethods())
            ->filter(function (ReflectionMethod $method) {
                if ($type = $method->getReturnType()) {
                    return \is_subclass_of($type->getName(), Relation::class);
                }

                return false;
            });
    }

    /**
     * Parse the relation's details
     *
     * @param Model $model
     * @param ReflectionMethod $method
     * @return array
     */
    public static function parseRelation(Model $model, ReflectionMethod $method): array
    {
        /** @var ReflectionMethod $method */
        $returnModel = Model::class;
        $hasModel = false;
        $hasMany = false;
        $type = false;
        $name = $method->getName();

        try {
            $relation = $model->{$name}();

            foreach (static::HAS_MANY_TYPES as $type => $hasMany) {
                // If the relation matches, or is a subclass of, then...
                if (($relation instanceof $type) || is_subclass_of($relation, $type)) {
                    // ... we've found the type, so get the $hasModel definition too
                    $hasModel = static::HAS_MODEL_TYPES[$type];

                    // and break out, leaving "$hasMany" and "$type" variables as the source of truth
                    break;
                }
            }

            if ($hasModel) {
                /** @var Relation $relation */
                $returnModel = get_class($relation->getQuery()->getModel());
            }
        } catch (\Exception $e) {
        }

        return [
            'name' => $name,
            'type' => $type,
            'hasMany' => $hasMany,
            'hasModel' => $hasModel,
            'returnModel' => $returnModel,
        ];
    }

    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $relations = static::getRelations($class);

        foreach ($relations as $method) {
            $relation = static::parseRelation($model, $method);

            if ($relation['hasMany']) {
                $relation['returnModel'] = sprintf('\\Illuminate\\Support\\Collection<\\%s>', $relation['returnModel']);
            }

            // Create a new DocblockTag for this class + method
            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'property',
                    $relation['name'],
                    new PropertyDocblock(
                        $relation['name'],
                        new Typehint($relation['returnModel']),
                        comments: ['from:RelationsAsProperties'],
                    )
                )
            );
        }

        return $relations->isNotEmpty();
    }
}
