<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use Closure;
use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\Docblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * @requires Laravel
 */
class ModelQueriesAsQueryBuilderMethods implements ParseInterface
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($className = $class->getName());

        if ($model === null) {
            return false;
        }

        $originalBuilder = Builder::class;
        $queryBuilder = 'ProxiedQueries\\' . $className . '\\Builder';

        $methods = collect($class->getMethods())
            ->filter(function (ReflectionMethod $method) use ($originalBuilder, $queryBuilder, $className, $docs) {
                $docblocks = new Docblock($method);

                $return = collect($docblocks->tags)->where('type', 'return')->first();

                if ($return === null) {
                    return null;
                }
                /** @var DockblockTag $return */

                if (Str::contains((string) $return, $originalBuilder)) {
                    $override = new MethodDocblock(
                        $method,
                    );

                    $return = new Typehint(str_replace($originalBuilder, $queryBuilder, $return->lines[0]));
                    $override->setReturnType($return);

                    $docs->addDocblock(
                        $className,
                        new DocblockTag(
                            'method',
                            $method->getName(),
                            $override,
                        ),
                    );

                    return true;
                }

                return false;
            });

        return $methods->isNotEmpty();
    }
}
