<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * @requires Laravel
 */
class DatabaseColumnsAsProperties extends AbstractModelShowDataParser
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $data = static::parseModel($model);

        foreach ($data['attributes'] as $data) {
            // Create a new DocblockTag for this class + method
            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'property',
                    $data['name'],
                    new PropertyDocblock(
                        $data['name'],
                        new Typehint($data['type']),
                        comments: ['from:DatabaseColumnsAsProperties']
                    )
                )
            );
        }

        return !empty($data['attributes']);
    }
}
