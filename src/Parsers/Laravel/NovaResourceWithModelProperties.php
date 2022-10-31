<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class NovaResourceWithModelProperties extends AbstractLaravelParser
{
    public array $config = [
        'resource' => 'Laravel\\Nova\\Resource',
    ];

    public function parse(File $file): Doc|Docs|null
    {
        $resource = $this->get('resource');

        if ($file->isSubclassOf($resource) === false) {
            return null;
        }

        $resourceName = $file->namespace;
        $modelName = $resourceName::$model ?? null;

        if (($modelName === null) || !class_exists($modelName)) {
            return null;
        }

        $docs = new Docs();
        $existing = $this->docs->forClass($modelName);

        $docs->push(
            new Doc(
                $resourceName,
                'property',
                'resource',
                schemaType: TypeMultiple::parse($modelName),
            ),
        );

        if (empty($existing)) {
            return $docs->orNull();
        }

        $ignore = [
            'property' => array_map(
                fn (ReflectionProperty $property) => $property->getName(),
                array_filter(
                    (new ReflectionClass($resource))->getProperties(),
                    fn (ReflectionProperty $property) => $property->isStatic() === false,
                ),
            ),
            'method' => array_map(
                fn (ReflectionMethod $method) => $method->getName(),
                (new ReflectionClass($resource))->getMethods(),
            ),
        ];

        foreach ($existing as $doc) {
            // Ignore any properties or methods that belong to the Nova Resource model. These should be ignored
            if (in_array($doc->name, $ignore[$doc->type] ?? [])) {
                continue;
            }

            $doc = clone $doc;
            
            /** @var Doc $doc */
            $doc->namespace = $resourceName;
            $doc->description = $this->viaDescription() . ' --> ' . $doc->description;

            $docs->push($doc);
        }

        return $docs->orNull();
    }
}