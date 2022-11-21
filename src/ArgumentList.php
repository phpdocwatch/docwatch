<?php

namespace DocWatch;

class ArgumentList
{
    public function __construct(
        public array $arguments
    ) {
    }

    public static function parse(array $parameters): ?ArgumentList
    {
        if (empty($parameters)) {
            return null;
        }

        return new static(array_map(
            fn ($argument) => Argument::parse($argument),
            $parameters
        ));
    }

    public function __toString(): string
    {
        return implode(', ', array_map(
            fn (Argument $argument) => (string) $argument,
            $this->arguments,
        ));
    }

    public function isNotEmpty(): bool
    {
        return ! empty($this->arguments);
    }

    public function isEmpty(): bool
    {
        return empty($this->arguments);
    }
}
