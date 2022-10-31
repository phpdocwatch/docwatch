<?php

namespace DocWatch;

use ReflectionParameter;

class Argument
{
    public function __construct(
        public string $name,
        public TypeMultiple|TypeSingle|null $type = null,
        public VariableString|null $default = null,
        public bool $variadic = false,
        public bool $reference = false,
    ) {
    }

    public function __toString(): string
    {
        $parts = [];

        if ($this->variadic) {
            $parts[] = '...';
        }

        if (!$this->variadic && $this->type) {
            $type = (string) $this->type;

            if ($type !== 'mixed') {
                $parts[] = $type . ' ';
            }
        }

        if (!$this->variadic && $this->reference) {
            $parts[] = '&';
        }

        $parts[] = '$' . $this->name;

        if (!$this->variadic && $this->default) {
            $parts[] = ' = ' . (string) $this->default;
        }

        return implode('', $parts);
    }

    public static function parse(ReflectionParameter $parameter): ?Argument
    {
        $name = $parameter->getName();

        $type = TypeMultiple::parse($parameter);

        $default = $parameter->isDefaultValueAvailable()
            ? VariableString::parse($parameter->getDefaultValue())
            : null;

        $variadic = $parameter->isVariadic();
        $reference = $parameter->isPassedByReference();

        return new Argument(
            name: $name,
            type: $type,
            default: $default,
            variadic: $variadic,
            reference: $reference
        );
    }
}