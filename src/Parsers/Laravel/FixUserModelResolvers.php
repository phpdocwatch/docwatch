<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;

class FixUserModelResolvers extends AbstractLaravelParser
{
    public array $config = [
        'request' => 'Illuminate\\Http\\Request',
        'model' => 'App\\Models\\User',
        'method' => 'user',
        'nullable' => true,
        'static' => false,
    ];

    public function parse(File $file): Doc|Docs|null
    {
        return null;
    }

    public function standalone(): Doc|Docs|null
    {
        $docs = new Docs();

        $requests = $this->get('request');
        $requests = is_array($requests) ? $requests : [$requests];

        $models = $this->get('model');
        $models = is_array($models) ? $models : [$models];

        $static = (bool) $this->get('static');
        $method = (string) $this->get('method');

        foreach ($requests as $request) {
            foreach ($models as $model) {
                $type = [
                    $model,
                ];
        
                if ($this->get('nullable')) {
                    $type[] = TypeMultiple::TYPE_NULL;
                }

                $docs->push(
                    new Doc(
                        $request,
                        'method',
                        $method,
                        isStatic: $static,
                        schemaReturn: TypeMultiple::parse($type),
                        description: $this->viaDescription(),
                    ),
                );
            }
        }

        return $docs;
    }
}