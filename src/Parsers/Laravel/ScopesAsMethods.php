<?php

namespace DocWatch\Parsers\Laravel;

use Illuminate\Database\Eloquent\Builder;
use DocWatch\ArgumentList;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use DocWatch\TypeSingle;
use ReflectionMethod;

class ScopesAsMethods extends AbstractLaravelParser
{
    public function parse(File $file): Doc|Docs|null
    {
        if ($file->isModel() === false) {
            return null;
        }
        
        $docs = new Docs();

        // Filter out scopes by checking the naming convention
        $scopes = array_filter(
            $file->methods(),
            fn (ReflectionMethod $method) => preg_match('/^scope[A-Z]/', $method->getName()),
        );

        // Filter out scopes by checking the first parameter is a Builder instance
        $scopes = array_filter(
            $scopes,
            function (ReflectionMethod $method) {
                $parameters = $method->getParameters();

                if (count($parameters) === 0) {
                    return false;
                }

                $type = TypeMultiple::parse($parameters[0]);

                return $type->is(Builder::class);
            },
        );

        foreach ($scopes as $scope) {
            /** @var ReflectionMethod $scope */

            $name = lcfirst(preg_replace('/^scope/', '', $scope->getName()));
            $args = $scope->getParameters();
            array_shift($args);
            $args = ArgumentList::parse($args);

            $return = ($scope->hasReturnType()) ? TypeMultiple::parse($scope->getReturnType()) : new TypeSingle(Builder::class);

            $docs->push(
                new Doc(
                    $file->namespace,
                    'method',
                    $name,
                    isStatic: true,
                    schemaArgs: $args,
                    schemaReturn: $return,
                    description: $this->viaDescription(),
                ),
            );
        }
        
        return $docs->orNull();
    }
}