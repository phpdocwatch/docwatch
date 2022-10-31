<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;

class CastsAsProperties extends AbstractLaravelParser
{
    /**
     * Parse all properties from the `casts` array (via `getCasts()` method)
     */
    public function parse(File $file): Doc|Docs|null
    {
        if ($file->isModel() === false) {
            return null;
        }
        
        $docs = new Docs();
        $instance = $file->instance();
        $data = static::getModelData($file->namespace);
        $fields = collect($data['attributes'] ?? []);

        foreach ($instance->getCasts() as $property => $type) {
            $type = [
                $type,
            ];

            $field = $fields->firstWhere('name', $property);
            
            if ($field['nullable'] ?? false) {
                $type[] = 'null';
            }
            
            $type = static::parseTypes($type);

            $docs->push(
                new Doc(
                    $file->namespace,
                    'property',
                    $property,
                    schemaType: $type,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }
}