<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use Closure;
use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * @requires Laravel
 */
class ScopesAsQueryBuilderMethods implements ParseInterface
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $queryBuilder = 'ProxiedQueries\\' . get_class($model) . '\\Builder';

        collect((new ReflectionClass(Builder::class))->getMethods())
            ->map(fn (ReflectionMethod $method) => new DocblockTag(
                'method',
                $method->getName(),
                new MethodDocblock(
                    $method,
                    defaultReturnType: Typehint::self(),
                ),
            ))
            ->each(
                fn (DocblockTag $tag) => $docs->addDocblock($queryBuilder, $tag, replace: false),
            );

        $scopes = collect($class->getMethods())
            ->filter(fn (ReflectionMethod $method) => (bool) (preg_match('/^scope([A-Z].+)$/', $method->getName())))
            ->map(function (ReflectionMethod $method) use ($model, $docs, $queryBuilder) {
                // Convert scopeName => name
                $name = lcfirst(preg_replace('/^scope/', '', $method->getName()));

                $args = collect($method->getParameters())
                    ->filter(fn ($arg, $index) => $index !== 0)
                    ->map(fn (ReflectionParameter $param) => new Argument($param))
                    ->all();

                $return = $method->getReturnType() ?? Builder::class;
                $type = new Typehint($return);

                if (ltrim((string) $type, '\\') === Builder::class) {
                    $type = new Typehint($queryBuilder);
                }

                $docs->addDocblock(
                    $queryBuilder,
                    new DocblockTag(
                        'method',
                        $name,
                        new MethodDocblock(
                            null,
                            $name,
                            $args,
                            $type,
                            comments: ['from:ScopesAsQueryBuilderMethods'],
                        ),
                    ),
                );
            });

        return $scopes->isNotEmpty();
    }
}
