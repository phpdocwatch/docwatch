<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
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
class RelationsAsProperties extends AbstractModelShowDataParser
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

    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $data = static::parseModel($model);

        foreach ($data['relations'] as $method) {
            $type = $method['traits'][0] ?? Relation::class;

            if (!class_exists($type)) {
                $type = 'Illuminate\\Database\\Eloquent\\Relations\\' . $type;
            }

            $returnOne = ($method['type'][0] ?? $class->getName());
            $hasMany = static::HAS_MANY_TYPES[$type] ?? null;

            $returnMany = implode('', [
                '\\',
                Collection::class,
                '<',
                $returnOne,
                '>',
            ]);

            $returnValue = match ($hasMany) {
                true => $returnMany,
                false => $returnOne,
                null => $returnOne . '|' . $returnMany,
            };

            // Create a new DocblockTag for this class + method
            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'property',
                    $method['name'],
                    new PropertyDocblock(
                        $method['name'],
                        new Typehint($returnValue),
                        comments: ['from:RelationsAsProperties'],
                    )
                )
            );
        }

        return !empty($data['relations']);
    }
}
