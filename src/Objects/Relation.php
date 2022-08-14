<?php

namespace DocWatch\Objects;

use DocWatch\Generator;
use Illuminate\Database\Eloquent\Model as EloquentModel;
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
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\Collection;

class Relation extends AbstractObject
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
        BelongsToMany::class => true,
        HasMany::class => true,
        HasManyThrough::class => true,
        HasOne::class => false,
        HasOneOrMany::class => null,
        HasOneThrough::class => false,
        MorphMany::class => true,
        MorphOne::class => false,
        MorphOneOrMany::class => null,
        MorphPivot::class => false,
        MorphTo::class => false,
        MorphToMany::class => true,
        Pivot::class => false,
        BelongsTo::class => false,
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
        BelongsToMany::class => true,
        HasMany::class => true,
        HasManyThrough::class => true,
        HasOne::class => true,
        HasOneOrMany::class => true,
        HasOneThrough::class => true,
        MorphMany::class => false,
        MorphOne::class => false,
        MorphOneOrMany::class => false,
        MorphPivot::class => false,
        MorphTo::class => false,
        MorphToMany::class => false,
        Pivot::class => false,
        BelongsTo::class => true,
        // Default
        EloquentRelation::class => false,
    ];

    /** @var string The relation class */
    public string $relationType;

    /** @var string The related model class (or base model class) */
    public string $relatedModel;

    /** @var bool Does this relation return multiple models? */
    public bool $returnsMany;

    /** @var Typehint The return typehint for this relation */
    public Typehint $type;

    public function __construct(public Model $parent, public string $name)
    {
        $this->load();
    }

    /**
     * Load in more information
     *
     * @return void
     */
    public function load()
    {
        /** @var Relation $relation */
        $relation = $this->parent->model->{$this->name}();
        $hasModel = false;

        foreach (static::HAS_MANY_TYPES as $type => $hasMany) {
            // If the relation matches, or is a subclass of, then...
            if (($relation instanceof $type) || is_subclass_of($relation, $type)) {
                // ... we've found the type, so get the $hasModel definition too
                $hasModel = static::HAS_MODEL_TYPES[$type];

                // and break out, leaving "$hasMany" and "$type" variables as the source of truth
                break;
            }
        }

        $this->relationType = $type;
        $this->returnsMany = $hasMany;

        // Get the type of model returned. If the relation has a specific model, retrieve it, otherwise default Model is fine
        $modelType = EloquentModel::class;
        if ($hasModel) {
            /** @var BelongsToMany|HasMany|HasManyThrough|HasOne|HasOneOrMany|HasOneThrough|BelongsTo $relation */
            $modelType = get_class($relation->getQuery()->getModel());
        }

        $this->relatedModel = $modelType;

        if ($hasMany) {
            $modelType = Collection::class . "<int,\\{$modelType}>";
        }

        return $this->type = new Typehint($modelType);
    }

    /**
     * Generate the docblock line for this relation
     *
     * @return string
     */
    public function compile(): string
    {
        $methodOverride = '';

        if (Generator::useProxiedQueryBuilders()) {
            // If we're dealing with a relation that points to a rela
            if ($this->relatedModel !== EloquentModel::class) {
                $proxiedQueryBuilder = ModelQueryBuilder::getNamespace($this->relatedModel);
                $type = new Typehint($proxiedQueryBuilder);

                $methodOverride = <<<EOL
 * @method {$type} {$this->name}()

EOL;
            }
        }

        return <<<EOL
{$methodOverride} * @property {$this->type} \${$this->name}
EOL;
    }
}
