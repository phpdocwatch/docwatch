<?php

namespace DocWatch\DocWatch\Parse;

use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Writer\WriterInterface;
use ReflectionClass;
use ReflectionException;

class CloneArgsFromMethod implements ParseInterface
{
    /**
     * Parse the given $class for the configured source method and destination methods, then
     * clone the arguments from the source method to the destination methods.
     *
     * E.g:    __construct($a, $b, $c) and static dispatchIf($boolean, ...$arguments)
     *
     * Result: static dispatchIf($boolean, $a, $b, $c)
     *
     * @param Docs $docs
     * @param WriterInterface $writer
     * @param ReflectionClass $class
     * @param array $config
     * @return bool Did this do anything?
     */
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        if (empty($sourceMethod = $config['src'])) {
            return false;
        }

        // Find destination methods
        $destinationMethods = [];
        foreach (is_array($config['dst']) ? $config['dst'] : [$config['dst']] as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = true;
            }

            // Not sure why, but if the value is false assume they don't want this to be done.
            if ($value === false) {
                continue;
            }

            $destinationMethods[$key] = $value;
        }

        // No destination methods found?
        if (empty($destinationMethods)) {
            return false;
        }

        try {
            // With the source method create a new MethodDocblock to reference for the destination methods.
            $source = new MethodDocblock(
                $class->getMethod($sourceMethod)
            );
        } catch (ReflectionException $e) {
            return false;
        }

        $ran = false;

        /**
         * For each destination method (e.g. "dispatchIf($boolean, ...$arguments)") clone
         * the source method (e.g. "__construct($a, $b, $c)") while inheriting the traits
         * of the destination method (e.g. is it static, whats its name, etc).
         *
         * If the $injection value is not true (i.e. a string) then this means "don't replace
         * the entire argument list, instead, merge the arguments in at the position where
         * you find the $injection named argument"
         */
        foreach ($destinationMethods as $destinationMethod => $injection) {
            // Get the destination ReflectionMethod, e.g. for "dispatchIf"
            try {
                $method = $class->getMethod($destinationMethod);
            } catch (ReflectionException $e) {
                // Method not found?
                continue;
            }
            // Create a MethodDocblock for it
            $destination = new MethodDocblock(
                $method,
                comments: ['from:CloneArgsFromMethod'],
                defaultReturnType: Typehint::mixedVoid(),
            );

            // If the injection points to a specific parameter...
            if (is_string($injection)) {
                // Remove the dollar symbol, it's moreso just for readability
                $injection = str_replace('$', '', $injection);

                // Get all parameters in the destination method (e.g. $boolean, $arguments)
                $arguments = $method->getParameters();

                /** @var array<Argument> $mergedArguments */
                $mergedArguments = [];

                // Iterate each one
                foreach ($arguments as $key => $value) {
                    // If the name matches (e.g. it will for "arguments" but not for "boolean")
                    if ($value->getName() === $injection) {
                        // If so, merge the source method's arguments into the destination method's arguments
                        foreach ($source->args as $arg) {
                            $mergedArguments[] = $arg;
                        }
                    } else {
                        // If not, just add the original argument (e.g. "boolean")
                        $mergedArguments[] = new Argument($value);
                    }
                }

                $destination->args = $mergedArguments;
            } else {
                // Otherwise replace all args with args from $sourceMethod
                $destination->args = $source->args;
            }

            if (($config['returnFrom'] ?? '') === 'dst') {
                // $destination->return =
            } else {
                $destination->return = $source->return;
            }

            // Create a new DocblockTag for this class + method
            $docs->addDocblock($class->getName(), new DocblockTag('method', $method->getName(), $destination));

            $ran = true;
        }

        return $ran;
    }
}
