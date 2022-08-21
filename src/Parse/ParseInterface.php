<?php

namespace DocWatch\DocWatch\Parse;

use DocWatch\DocWatch\Docs;
use ReflectionClass;

interface ParseInterface
{
    /**
     * Parse the given $class for properties or methods that include some level of magic that
     * would otherwise mean your IDE has no clue about.
     *
     * Read the properties and methods from the given $class
     * Use $config to control the settings for the parser
     * Write the results to the given $docs object via the method $writer->addDocblock()
     * Return true if anything was added to the $docs object
     *
     * @param Docs $docs
     * @param ReflectionClass $class
     * @param array $config
     * @return boolean
     */
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool;
}
