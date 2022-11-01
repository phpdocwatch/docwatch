<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\ArgumentList;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class ModelsExposeQueryBuilderMethods extends AbstractLaravelParser
{
    public array $config = [
        'model' => 'Illuminate\\Database\\Eloquent\\Model',
        'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
    ];

    public function parse(File $file): Doc|Docs|null
    {
        return null;
    }

    /**
     * Parse all columns from the database via artisan model:show command
     */
    public function standalone(): Doc|Docs|null
    {
        $docs = new Docs();
        $model = $this->get('model');
        $builder = $this->get('builder');
        $ignore = array_map(
            fn (ReflectionMethod $method) => $method->getName(),
            (new ReflectionClass($model))->getMethods(),
        );
        $builderReturn = TypeMultiple::parse($builder);

        $nonBuilderMethods = [
            'make' => ['static'],
            'firstWhere' => ['static', 'null'],
            'find' => ['static', 'null'],
            'findOrNew' => ['static'],
            'findOr' => ['static', 'mixed'],
            'findOrFail' => ['static'],
            'firstOrNew' => ['static'],
            'firstOrCreate' => ['static'],
            'updateOrCreate' => ['static'],
            'firstOr' => ['static', 'mixed'],
            'firstOrFail' => ['static'],
            'create' => ['static'],
            'forceCreate' => ['static'],
            'newModelInstance' => ['static'],
            'getModel' => ['static'],
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

            if (isset($nonBuilderMethods[$name])) {
                $returnType = TypeMultiple::parse($nonBuilderMethods[$name]);
            }

            $docs->push(
                new Doc(
                    $model,
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
}
