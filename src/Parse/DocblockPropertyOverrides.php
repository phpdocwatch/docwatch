<?php

namespace DocWatch\DocWatch\Parse;

use DocWatch\DocWatch\Docblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Writer\WriterInterface;
use ReflectionClass;
use ReflectionProperty;

class DocblockPropertyOverrides implements ParseInterface
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
                if ($tag->type !== 'property') {
                    continue;
                }

                $tag->lines[] = 'from:DocblockPropertyOverrides via class';

                $docs->addDocblock(
                    $class->getName(),
                    $tag,
                    replace: true,
                );

                $ran = true;
            }
        }

        // Should we look at the properties' docblocks?
        if ($config['property'] ?? false) {
            foreach ($class->getProperties() as $property) {
                /** @var ReflectionProperty $property */
                $docblocks = new Docblock($property);

                foreach ($docblocks->tags as $tag) {
                    /** @var DocblockTag $tag */

                    // We only care about properties ('@var' in this case)
                    if ($tag->type !== 'var') {
                        continue;
                    }

                    $tag->type = 'property';
                    $tag->lines[] = 'from:DocblockPropertyOverrides via property';

                    $docs->addDocblock(
                        $class->getName(),
                        $tag,
                        replace: true,
                    );

                    $ran = true;
                }
            }
        }

        return $ran;
    }
}
