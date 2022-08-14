<?php

namespace DocWatch\Objects;

use ReflectionParameter;
use Stringable;

class Argument implements Stringable
{
    /**
     * Create a new argument class
     *
     * @param string $name of the variable
     * @param Typehint|null $type of the variable (null = none)
     * @param Typehint|null $default value of the variable (null = none)
     */
    public function __construct(public string $name, public Typehint|null $type, public Typehint|null $default)
    {
    }

    /**
     * Cast this Argument to string format
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->compile();
    }

    /**
     * Compile this Argument to string format (e.g. "User|int $user = null")
     *
     * @return string
     */
    public function compile(): string
    {
        $pieces = [];

        if ($this->type !== null) {
            $pieces[] = (string) $this->type;
        }

        $pieces[] = '$' . $this->name;

        if ($this->default !== null) {
            $pieces[] = '=';
            $pieces[] = (string) $this->default;
        }

        return implode(' ', $pieces);
    }

    /**
     * Convert a ReflectionParameter to an Argument
     *
     * @param ReflectionParameter $param
     * @return static
     */
    public static function fromParameter(ReflectionParameter $param): static
    {
        try {
            $default = new Typehint($param->getDefaultValue());
        } catch (\Exception $e) {
            $default = null;
        }

        $type = null;
        if ($param->getType() !== null) {
            $type = $param->getType();

            $type = new Typehint($type);
        }

        return new static($param->getName(), $type, $default);
    }
}
