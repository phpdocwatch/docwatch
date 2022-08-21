<?php

namespace DocWatch\DocWatch\Items;

use ReflectionException;
use ReflectionParameter;
use Stringable;

class Argument implements Stringable
{
    public function __construct(
        public ReflectionParameter|null $parameter = null,
        public string|null $name = null,
        public Typehint|null $type = null,
        public Typehint|string|null $default = null,
        public bool $variadic = false,
    )
    {
        if ($parameter === null && $name === null) {
            throw new \Exception('Argument must be a reflection parameter or have a specified name');
        }

        if ($parameter !== null) {
            $default = null;
            $type = $parameter->getType();

            try {
                $default = new Typehint($parameter->getDefaultValue(), literalValue: true);
            } catch (ReflectionException $e) {
                // No default value
            }

            $this->name = $parameter->getName();
            $this->type = ($type === null) ? null : new Typehint($type);
            $this->default = ($default === null) ? null : $default;
            $this->variadic = $parameter->isVariadic();
        }
    }

    public function __toString()
    {
        $parts = [];

        if ($this->type !== null) {
            $parts[] = (string) $this->type;
            $parts[] = ' ';
        }

        if ($this->variadic) {
            $parts[] = '...';
        }

        $parts[] = '$';
        $parts[] = $this->name;

        if ($this->default !== null) {
            $parts[] = ' = ';
            $parts[] = (string) $this->default;
        }

        return implode('', $parts);
    }
}
