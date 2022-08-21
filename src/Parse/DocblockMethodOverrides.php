<?php

namespace DocWatch\DocWatch\Parse;

use DocWatch\DocWatch\Docblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Writer\WriterInterface;
use ReflectionClass;

class DocblockMethodOverrides implements ParseInterface
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
        $ran = false;

        // Should we look at the class's docblocks?
        if ($config['class'] ?? false) {
            $docblocks = new Docblock($class);

            foreach ($docblocks->tags as $tag) {
                /** @var DocblockTag $tag */

                // We only care about properties
                if ($tag->type !== 'method') {
                    continue;
                }

                $tag->lines[] = 'from:DocblockMethodOverrides via class';

                $docs->addDocblock(
                    $class->getName(),
                    $tag,
                    replace: true,
                );

                $ran = true;
            }
        }

        // Should we look at the methods' docblocks?
        if ($config['method'] ?? false) {
            // TODO: Implement parsing for methods
        }

        return $ran;
    }
}
