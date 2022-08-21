<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use ReflectionClass;
use Illuminate\Database\Eloquent\Model;

/**
 * @requires Laravel
 */
class CastsAsProperties implements ParseInterface
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($className = $class->getName());

        if ($model === null) {
            return false;
        }

        // Optionally use a different method for retrieving cast rules
        $method = $config['method'] ?? 'getCasts';

        // But ignore it if the method does not exist
        if (!method_exists($model, $method)) {
            return false;
        }

        $columns = collect($model->{$method}())
            ->map(function ($type, string $name) use ($docs, $className) {
                $existing = $docs->container[$className]['property'][$name] ?? [];
                $nullable = false;

                if (!empty($existing)) {
                    /** @var DocblockTag $existing */
                    if (isset($existing->lines) && is_array($existing->lines)) {
                        $property = $existing->lines[0] ?? null;
                    } elseif (isset($existing->lines) && $existing->lines instanceof PropertyDocblock) {
                        /** @var PropertyDocblock $property */
                        $property = $existing->lines;
                    }

                    if (!empty($property)) {
                        $nullable = $property->type && $property->type->isNullable();
                    }
                }

                $type = [
                    $type,
                ];

                if ($nullable) {
                    $type[] = 'null';
                }

                return [
                    'name' => $name,
                    'type' => $type,
                ];
            })
            ->each(
                fn (array $data) => $docs->addDocblock(
                    $className,
                    new DocblockTag(
                        'property',
                        $data['name'],
                        new PropertyDocblock(
                            $data['name'],
                            new Typehint($data['type']),
                            comments: ['from:CastsAsProperties']
                        )
                    )
                )
            );

        return $columns->isNotEmpty();
    }
}
