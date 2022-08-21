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
     * Inherit the docblocks for properties from the $class.
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

        /**
         * If delete mode is enabled, it will delete all existing docblocks that DocWatch has generated for the given
         * properties. This is helpful for when you want to inherit the docblocks from the parent class verbatim without
         * any overides from DocWatch.
         */
        $delete = $config['delete'] ?? false;

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
                    delete: $delete,
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
                        delete: $delete,
                    );

                    $ran = true;
                }
            }
        }

        return $ran;
    }
}
