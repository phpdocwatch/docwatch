<?php

namespace DocWatch\Parsers\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;

class RelationsAsProperties extends AbstractLaravelParser
{
    /**
     * Parse all relations via artisan model:show command
     */
    public function parse(File $file): Doc|Docs|null
    {
        if ($file->isModel() === false) {
            return null;
        }
        
        $docs = new Docs();
        $data = static::getModelData($file->namespace);

        $supported = [
            'hasmany',
            'hasmanythrough',
            'hasonethrough',
            'belongstomany',
            'hasone',
            'belongsto',
            'morphone',
            'morphto',
            'morphmany',
            'morphtomany',
            'morphedbymany',
        ];

        $hasManyRelations = [
            'hasmany',
            'hasmanythrough',
            'belongstomany',
            'morphmany',
            'morphtomany',
            'morphedbymany',
        ];

        $unknownModelRelations = [
            'morphto',
            'morphtomany',
        ];

        foreach ($data['relations'] ?? [] as $relation) {
            $type = strtolower($relation['type'] ?? null);
            $nullable = true;

            if (!in_array($type, $supported)) {
                continue;
            }

            if (in_array($type, $unknownModelRelations)) {
                $relation['related'] = Model::class;

                foreach ($data['attributes'] as $attribute) {
                    if (($attribute['name'] === ($relation['name'] . '_type')) || ($attribute['name'] === ($relation['name'] . '_id'))) {
                        $nullable = $attribute['nullable'];
                        break;
                    }
                }
            }

            if (in_array($type, $hasManyRelations)) {
                $type = TypeMultiple::parse(Collection::class)->genericsKey('int')->genericsValue($relation['related']);
            } else {
                $types = [
                    $relation['related'],
                ];

                if ($nullable) {
                    $types[] = TypeMultiple::TYPE_NULL;
                }

                $type = TypeMultiple::parse($types);
            }

            $docs->push(
                new Doc(
                    $file->namespace,
                    'property',
                    $relation['name'],
                    isStatic: false,
                    schemaType: $type,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }
}