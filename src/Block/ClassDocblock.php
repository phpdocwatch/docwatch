<?php

namespace DocWatch\DocWatch\Block;

/**
 * A container for docblock tags for a single class.
 */
class ClassDocblock implements BlockInterface
{
    /**
     *
     * @param string $namespace
     * @param array<string,PropertyDocblock> $properties
     * @param array<string,MethodDocblock> $methods
     */
    public function __construct(
        public string $namespace,
        public array $properties,
        public array $methods,
    )
    {
        $this->setMethods($methods);
        $this->setProperties($properties);
    }

    public function setMethods(iterable $methods)
    {
        foreach ($methods as $method) {
            $this->setMethod($method);
        }
    }

    public function setProperties(iterable $properties)
    {
        foreach ($properties as $property) {
            $this->setProperty($property);
        }
    }

    public function setMethod(MethodDocblock $docblock)
    {
        $this->methods[$docblock->name] = $docblock;
    }

    public function setProperty(PropertyDocblock $docblock)
    {
        $this->properties[$docblock->name] = $docblock;
    }
}
