<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Writer\WriterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

/**
 * @requires Laravel
 */
class RelationsAsQueryBuilderMethods extends RelationsAsProperties
{
    /**
     * Parse the given class for relation methods and convert them to docblock properties
     *
     * @param Docs $docs
     * @param WriterInterface $writer
     * @param ReflectionClass $class
     * @param array $config
     * @return boolean
     */
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $relations = static::getRelations($class);

        foreach ($relations as $method) {
            $relation = static::parseRelation($model, $method);

            $queryBuilder = '\\ProxiedQueries\\' . $relation['returnModel'] . '\\Builder';

            // Create a new DocblockTag for this class + method
            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'method',
                    $relation['name'],
                    new MethodDocblock(
                        null,
                        $relation['name'],
                        null,
                        new Typehint($queryBuilder),
                        comments: ['from:RelationsAsQueryBuilderMethods'],
                    )
                )
            );

            $docs->addDocblock(
                ltrim($queryBuilder, '\\'),
                null,
                [
                    'extends' => '\\' . Builder::class,
                ],
            );
        }

        return $relations->isNotEmpty();
    }
}
