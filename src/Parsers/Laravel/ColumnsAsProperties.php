<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;

class ColumnsAsProperties extends AbstractLaravelParser
{
    /**
     * Parse all columns from the database via artisan model:show command
     */
    public function parse(File $file): Doc|Docs|null
    {
        if ($file->isModel() === false) {
            return null;
        }
        
        $docs = new Docs();
        $data = static::getModelData($file->namespace);

        foreach ($data['attributes'] ?? [] as $attribute) {
            $cast = $attribute['cast'] ?? null;
            $name = $attribute['name'] ?? null;
            $type = $attribute['type'] ?? null;

            if (($cast === 'attribute') || ($cast === 'accessor')) {
                continue;
            }
            
            $type = static::explodeModelShowFragment($type ?? '');

            if ($cast !== null) {
                $type = $attribute['cast'];
            }

            $type = static::parseTypes($type, $attribute['nullable'] ?? false);

            $docs->push(
                new Doc(
                    $file->namespace,
                    'property',
                    $attribute['name'],
                    isStatic: false,
                    schemaType: $type,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }
}