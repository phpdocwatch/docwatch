<?php

namespace DocWatch\DocWatch\Block;

use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use Reflection;
use ReflectionMethod;
use ReflectionParameter;

class MethodDocblock implements BlockInterface
{
    /**
     * Constructor.
     *
     * @param string $name
     * @param array<Argument> $args
     * @param Typehint $returnType
     * @param string $modifiers
     */
    public function __construct(
        public ReflectionMethod|null $method = null,
        public string|null $name = null,
        public array|null $args = null,
        public Typehint|null $returnType = null,
        public string|null $modifiers  = null,
        public array $comments = [],
        public Typehint|null $defaultReturnType = null,
    )
    {
        if ($method === null && $name === null) {
            throw new \Exception('You must specify a method or a name');
        }

        if ($method !== null) {
            $this->name = $method->getName();
            $this->args = array_map(
                fn (ReflectionParameter $parameter) => new Argument($parameter),
                $method->getParameters(),
            );
            $this->returnType = (($return = $method->getReturnType()) !== null) ? new Typehint($return) : $this->defaultReturnType;

            $this->modifiers = $method->isStatic() ? 'static' : null; // public vs protected is not supported by docblocks/intelephense?
        }

        // if ($method && $method->getName() === 'prepareNestedWithRelationships') {
        //     dd($this, $method->getParameters());
        // }
    }

    public function compile(): array
    {
        $parts = [];

        if ($this->modifiers !== null) {
            $parts[] = $this->modifiers;
        }

        if ($this->returnType !== null) {
            $parts[] = (string) $this->returnType;
        }

        $parts[] = $this->name . '(' . implode(', ', array_map(fn (Argument $arg) => (string) $arg, $this->args ?? [])) . ')';

        return [
            implode(' ', $parts),
            ...$this->comments,
        ];
    }

    public function setArgs(array $args)
    {
        $this->args = $args;
    }

    public function setModifiers(string $modifiers = 'public')
    {
        $this->modifiers = $modifiers;
    }

    public function setReturnType(Typehint $type)
    {
        $this->returnType = $type;
    }
}
