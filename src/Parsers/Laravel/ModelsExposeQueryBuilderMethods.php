<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\ArgumentList;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use ReflectionClass;
use ReflectionMethod;

class ModelsExposeQueryBuilderMethods extends AbstractLaravelParser
{
    public const DEFAULT_BUILDER = 'Illuminate\\Database\\Eloquent\\Builder';
    public const NEW_BUILDER_METHOD = 'newEloquentBuilder';

    public array $config = [
        'model' => 'Illuminate\\Database\\Eloquent\\Model',
        'builder' => null,
    ];

    public function parse(File $file): Doc|Docs|null
    {
        $customBuildersReturnExactModel = $this->get('customBuildersReturnExactModel');

        /**
         * Ignore file if not model
         */
        if ($file->isModel() === false) {
            return null;
        }

        /**
         * Ignore file if no 'newEloquentBuilder' method exists (it should)
         */
        $reflection = $file->reflection();

        if ($reflection->hasMethod(static::NEW_BUILDER_METHOD) === false) {
            return null;
        }

        /**
         * Ignore file if builder has no return type or not customer
         */
        $builder = $reflection->getMethod(static::NEW_BUILDER_METHOD);

        if ($builder->hasReturnType() === false) {
            return null;
        }

        if (($builderClass = $builder->getReturnType()) === static::DEFAULT_BUILDER) {
            return null;
        }

        /**
         * Build a list of methods that return the default builder (which should
         * then be replaced with the custom builder)
         */

        /** @var array<string,ReflectionMethod> */
        $methodsReturningBuilder = [];

        foreach ($file->methods() as $method) {
            if ($this->methodReturnsBuilder($method)) {
                $methodsReturningBuilder[$method->getName()] = $method;
            }
        }

        /**
         * Create doc blocks for all of these builder methods as if they
         * return an instance of the custom builder
         */

        $builderReturn = TypeMultiple::parse($builderClass);
        $docs = new Docs();

        foreach ($methodsReturningBuilder as $name => $method) {
            $docs->push(new Doc(
                $file->namespace,
                'method',
                $name,
                isStatic: $method->isStatic(),
                schemaReturn: $builderReturn,
                schemaArgs: ArgumentList::parse($method->getParameters()),
                description: $this->viaDescription(),
            ));
        }

        /**
         * Add docblocks to the custom builder that suggest certain methods
         * like first|find|etc return an instance of this model (presuming
         * this builder is specific to the model...)
         */
        if ($customBuildersReturnExactModel) {
            $docs->merge(
                $this->parseStandalone($file->namespace, $builderClass, methodsOn: 'builder'),
            );
        }

        return $docs;
    }

    /**
     * Parse all columns from the database via artisan model:show command
     */
    public function standalone(): Doc|Docs|null
    {
        $model = $this->get('model');
        $builder = $this->get('builder', static::DEFAULT_BUILDER);

        return $this->parseStandalone($model, $builder);
    }

    public function parseStandalone(string $model, string $builder, string $methodsOn = 'model'): Doc|Docs|null
    {
        $docs = new Docs();
        $ignore = array_map(
            fn (ReflectionMethod $method) => $method->getName(),
            (new ReflectionClass($model))->getMethods(),
        );
        $builderReturn = TypeMultiple::parse($builder);

        $static = 'static';

        if ($methodsOn === 'builder') {
            $static = $model;
            $builderReturn = TypeMultiple::parse('self');
        }

        $nonBuilderMethods = [
            'make' => [$static],
            'find' => [$static, 'null'],
            'findOrNew' => [$static],
            'findOr' => [$static, 'mixed'],
            'findOrFail' => [$static],
            'firstOrNew' => [$static],
            'firstOrCreate' => [$static],
            'updateOrCreate' => [$static],
            'firstWhere' => [$static, 'null'],
            'first' => [$static, 'null'],
            'firstOr' => [$static, 'mixed'],
            'firstOrFail' => [$static],
            'create' => [$static],
            'forceCreate' => [$static],
            'newModelInstance' => [$static],
            'getModel' => [$static],
        ];

        $collections = [
            'get',
        ];

        $clone = (new \ReflectionClass($builder))->getMethods();

        foreach ($clone as $method) {
            $name = $method->getName();

            if (in_array($name, $ignore)) {
                continue;
            }

            if (substr($name, 0, 2) === '__') {
                continue;
            }

            $returnType = $method->hasReturnType()
                ? TypeMultiple::parse($method->getReturnType())
                : $builderReturn;

            if (in_array($name, $collections)) {
                $returnType = TypeMultiple::parse('\Illuminate\Database\Eloquent\Collection')
                    ->genericsKey('int')
                    ->genericsValue($model);
            }

            if (isset($nonBuilderMethods[$name])) {
                $returnType = TypeMultiple::parse($nonBuilderMethods[$name]);
            }

            $docs->push(
                new Doc(
                    ($methodsOn === 'model') ? $model : $builder,
                    'method',
                    $name,
                    isStatic: true,
                    schemaArgs: ArgumentList::parse($method->getParameters()),
                    schemaReturn: $returnType,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }

    public function methodReturnsBuilder(ReflectionMethod $method): bool
    {
        if ($method->hasReturnType()) {
            $type = TypeMultiple::parse($method->getReturnType());

            if ($type->is(static::DEFAULT_BUILDER)) {
                return $type->is(static::DEFAULT_BUILDER);
            }
        }

        if (($doc = $method->getDocComment()) !== false) {
            if (preg_match("/@return (.+)/", $doc, $return)) {
                $returns = collect(explode('|', (string) $return[1]))
                    ->map(fn (string $returnType) => trim(trim($returnType), '\\'));

                $returnsBuilder = $returns->contains(static::DEFAULT_BUILDER);

                return $returnsBuilder;
            }
        }

        return false;
    }
}
