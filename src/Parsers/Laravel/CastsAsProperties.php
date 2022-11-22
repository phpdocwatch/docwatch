<?php

namespace DocWatch\Parsers\Laravel;

use DeepCopy\TypeMatcher\TypeMatcher;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use DocWatch\TypeSingle;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use ReflectionClass;
use ReflectionNamedType;

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

        $fields = [];
        foreach ($data['attributes'] ?? [] as $attribute) {
            $fields[$attribute['name']] = $attribute;
        }

        foreach ($instance->getCasts() as $property => $type) {
            $readNullableFromDatabase = true;
            $nullable = false;

            if (! in_array($type, TypeSingle::PRIMITIVE_TYPES)) {
                try {
                    $reflection = new ReflectionClass($type);

                    if ($reflection->implementsInterface(CastsAttributes::class)) {
                        $return = $reflection->getMethod('get')->getReturnType();

                        $type = TypeMultiple::parse($return);

                        $readNullableFromDB = false;
                    }
                } catch (\Throwable $e) {
                }
            }

            $type = ($type instanceof TypeMultiple) ? $type->types : [
                $type,
            ];

            if ($readNullableFromDatabase) {
                // Assumption: If a field with the same name as the accessor exists, it's the same field 
                $field = $fields[$property] ?? [];
    
                // Is the DB field nullable?
                $nullable = ($field['nullable'] ?? false);
            }
            
            $type = static::parseTypes($type, $nullable);

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