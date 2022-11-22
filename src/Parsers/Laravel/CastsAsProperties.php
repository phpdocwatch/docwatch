<?php

namespace DocWatch\Parsers\Laravel;

use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
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
            if (! in_array($type, TypeSingle::PRIMITIVE_TYPES)) {
                try {
                    $reflection = new ReflectionClass($type);

                    if ($reflection->implementsInterface(CastsAttributes::class)) {
                        $return = $reflection->getMethod('get')->getReturnType();

                        if ($return instanceof ReflectionNamedType) {
                            $type = $return->getName();
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            $type = [
                $type,
            ];

            // Assumption: If a field with the same name as the accessor exists, it's the same field 
            $field = $fields[$property] ?? [];
            
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