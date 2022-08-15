<?php

namespace DocWatch\Objects;

use DocWatch\Generator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;
use Illuminate\Support\Str;
use ReflectionMethod;

class Model extends AbstractObject
{
    public string $namespace;

    public ?Collection $columns;

    public ?Collection $accessors;

    public ?Collection $relations;

    public ?Collection $scopes;

    public function __construct(public EloquentModel $model, public string $path)
    {
        $this->load();
    }

    /**
     * Given a .php file path, create a Model instance. Validate the namespace exists, is instantiable, etc.
     *
     * @param string $path
     * @return static|null
     */
    public static function createFromPath(string $path): ?static
    {
        // Extract Namespace
        $namespace = Generator::extractFullNamespace($path);

        // Ignore if the namespace couldn't be extracted
        if ($namespace === null) {
            return null;
        }

        // Ignore if it doesn't exist (which it should, but still check)
        if (class_exists($namespace) === false) {
            return null;
        }

        // Ignore if it's not an eloquent model (what is it doing in the models dir?)
        if (is_subclass_of($namespace, EloquentModel::class) === false) {
            return null;
        }

        // Ignore if it is an abstract class
        $reflection = new ReflectionClass($namespace);
        if ($reflection->isAbstract()) {
            return null;
        }

        // Spawn Eloquent Model
        $eloquent = resolve($namespace);

        // Return self
        return new static($eloquent, $path);
    }

    /**
     * Load in further information
     *
     * @return void
     */
    public function load()
    {
        $this->namespace = get_class($this->model);
        $this->table = $this->model->getTable();

        try {
            $this->parseColumns();
            $this->parseAccessors();
            $this->parseRelations();
            $this->parseScopes();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::debug('DOCWATCH Failure: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            // Most likely new model not yet migrated into DB (fails on parseColumns).
            // Reset all fields, relations, scopes

            $this->columns = collect();
            $this->accessors = collect();
            $this->relations = collect();
            $this->scopes = collect();
        }
    }

    /**
     * Parse this model's DB column fields using artisan's "db:table"
     *
     * @return void
     */
    public function parseColumns()
    {
        Artisan::call('db:table', [
            'table' => $this->table,
        ]);

        $foundColumn = false;
        $foundIndex = false;

        $columns = collect(preg_split('/\n+/', Artisan::output()))
            ->filter(function ($row) use (&$foundColumn, &$foundIndex) {
                $row = trim($row);
                $start = Str::before($row, ' ');

                if ($start === 'Column') {
                    $foundColumn = true;

                    return false;
                }

                if ($foundColumn === true && $foundIndex === false) {
                    if ($start === 'Index') {
                        $foundIndex = true;

                        return false;
                    }

                    return true;
                }

                return false;
            })
            ->map(function (string $line) {
                $line = trim($line);

                $name = Str::before($line, ' ');
                $type = Str::afterLast($line, ' ');
                $nullable = Str::contains(Str::after($line, ' '), 'nullable');

                return [
                    'name' => $name,
                    'type' => $type,
                    'nullable' => $nullable,
                ];
            })
            ->filter(fn (array $data) => !empty($data['name']));

        $this->columns = $columns
            ->map(fn (array $data) => new Column($this, $data['name'], $data['type'], $data['nullable']))
            ->sortBy(fn (Column $column) => $column->name);
    }

    /**
     * Parse this model's virtual accessor fields using reflection methods
     *
     * @return void
     */
    public function parseAccessors()
    {
        $class = new ReflectionClass($this->model);

        $this->accessors = collect($class->getMethods())
            ->map(function (ReflectionMethod $method) {
                if (preg_match('/^get(.+)Attribute$/', $name = $method->getName(), $m)) {
                    return [
                        'name' => Str::snake($m[1]),
                        'method' => $name,
                    ];
                }

                if ($type = $method->getReturnType()) {
                    if (Attribute::class === $type->getName()) {
                        return [
                            'name' => Str::snake($method->getName()),
                            'method' => $method->getName(),
                        ];
                    }
                }

                return false;
            })
            ->filter()
            ->values()
            ->map(fn (array $method) => new Accessor($this, $method['method'], $method['name']))
            ->sortBy(fn (Accessor $accessor) => $accessor->name);
    }

    /**
     * Parse this model's relation accessor fields using reflection methods
     *
     * @return void
     */
    public function parseRelations()
    {
        $class = new ReflectionClass($this->model);

        $this->relations = collect($class->getMethods())
            ->filter(function (ReflectionMethod $method) {
                if ($type = $method->getReturnType()) {
                    return \is_subclass_of($type->getName(), EloquentRelation::class);
                }

                return false;
            })
            ->map(fn (ReflectionMethod $method) => new Relation($this, $method->getName()))
            ->sortBy(fn (Relation $relation) => $relation->name);
    }

    /**
     * Parse this model's scope functions using reflection methods
     *
     * @return void
     */
    public function parseScopes()
    {
        $class = new ReflectionClass($this->model);

        $this->scopes = collect($class->getMethods())
            ->map(fn (ReflectionMethod $method) => (preg_match('/^scope([A-Z].+)$/', $name = $method->getName(), $m)) ? $name : null)
            ->filter()
            ->map(fn (string $method) => new Scope($this, $method))
                ->sortBy(fn (Scope $scope) => $scope->name);
    }

    /**
     * Generate the model's docblocks
     *
     * @return string
     */
    public function compile(): string
    {
        $doc = [];

        $doc[] = '/**';

        if ($this->columns->isNotEmpty()) {
            $doc[] = ' * // Database Columns';

            foreach ($this->columns as $column) {
                $doc[] = (string) $column;
            }

            $doc[] = ' * ';
        }

        if ($this->accessors->isNotEmpty()) {
            $doc[] = ' * // Virtual Accessors';

            foreach ($this->accessors as $accessor) {
                $doc[] = (string) $accessor;
            }

            $doc[] = ' * ';
        }

        if ($this->relations->isNotEmpty()) {
            $doc[] = ' * // Eloquent Relations';

            foreach ($this->relations as $relation) {
                $doc[] = (string) $relation;
            }

            $doc[] = ' * ';
        }

        if ($this->scopes->isNotEmpty()) {
            $doc[] = ' * // Scopes';

            foreach ($this->scopes as $scope) {
                $doc[] = (string) $scope;
            }

            $doc[] = ' * ';
        }

        $doc[] = '*/';
        $doc[] = 'namespace ' . Str::beforeLast($this->namespace, '\\') . ';';
        $doc[] = 'class ' . Str::afterLast($this->namespace, '\\') . ' {}';

        return implode("\n", $doc);
    }
}
