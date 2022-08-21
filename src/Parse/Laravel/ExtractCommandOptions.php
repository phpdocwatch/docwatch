<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @requires Laravel
 */
class ExtractCommandOptions implements ParseInterface
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
        $methods = Arr::wrap($config['method']);

        /** @var Command $command */
        $command = app($class->getName());

        $arguments = $command->getDefinition()->getArguments();
        $options = $command->getDefinition()->getOptions();

        $all = collect($arguments)
            ->merge($options)
            ->map(function (InputOption|InputArgument $argument) {
                $default = new Typehint($argument->getDefault());

                // Arguments cannot have defaults.
                if ($argument instanceof InputArgument) {
                    $default = null;
                }

                return new Argument(
                    null,
                    $argument->getName(),
                    null,
                    ($default === null) ? null : $default,
                );
            })
            ->all();

        foreach ($methods as $method) {
            /** @var string $method */

            try {
                $source = $class->getMethod($method);
                $returnType = (($returnType = $source->getReturnType()) !== null) ? new Typehint($returnType) : null;

                $modifiers = $source->isStatic() ? 'static' : '';
            } catch (ReflectionException $e) {
                // method not exists, lets assume the method really does exist (maybe more magic involved?)
                $returnType = Typehint::mixedVoid();
                $modifiers = null;
            }

            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'method',
                    $method,
                    new MethodDocblock(
                        name: $method,
                        args: $all,
                        returnType: $returnType,
                        modifiers: $modifiers,
                        comments: ['from:ExtractCommandOptions'],
                    )
                )
            );
        }

        return !empty($methods);
    }
}
