<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\ArgumentList;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
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

        $clone = (new \ReflectionClass($builder))->getMethods();

        foreach ($clone as $method) {
            if (in_array($method->getName(), $ignore)) {
                continue;
            }

            if (substr($method->getName(), 0, 2) === '__') {
                continue;
            }

            $docs->push(
                new Doc(
                    $model,
                    'method',
                    $method->getName(),
                    isStatic: true,
                    schemaArgs: ArgumentList::parse($method->getParameters()),
                    schemaReturn: $method->hasReturnType()
                        ? TypeMultiple::parse($method->getReturnType())
                        : $builderReturn,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }
}