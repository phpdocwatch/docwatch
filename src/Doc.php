<?php

namespace DocWatch;

class Doc
{
    public function __construct(
        public string $namespace,
        public string $type,
        public string $name,
        public ?bool $isStatic = false,
        public TypeMultiple|TypeSingle|null $schemaType = null,
        public ?VariableString $schemaDefault = null,
        public ?ArgumentList $schemaArgs = null,
        public TypeMultiple|TypeSingle|null $schemaReturn = null,
        public ?string $description = null,
    ) {
    }

    public function __toString()
    {
        return $this->compile();
    }

    public function compile(): string
    {
        if ($this->type === 'property' || $this->type === 'property-read' || $this->type === 'property-write') {
            return $this->compileProperty();
        } elseif ($this->type === 'method') {
            return $this->compileMethod();
        }
    }

    public function compileProperty(): string
    {
        $parts = [];

        $parts[] = '@' . $this->type; // property|property-read|property-write

        if ($this->schemaType) {
            $parts[] = (string) $this->schemaType;
        }

        $parts[] = '$' . $this->name;

        if ($this->schemaDefault) {
            $parts[] = '= ' . (string) $this->schemaDefault;
        }

        if ($this->description) {
            $parts[] = '// ' . $this->description;
        }

        return implode(' ', $parts);
    }

    public function compileMethod(): string
    {
        $parts = [];

        $parts[] = '@method';

        if ($this->isStatic) {
            $parts[] = 'static';
        }

        if ($this->schemaReturn) {
            $parts[] = (string) $this->schemaReturn;
        }

        $args = [];
        if ($this->schemaArgs && $this->schemaArgs->isNotEmpty()) {
            $args[] = (string) $this->schemaArgs;
        }

        $parts[] = $this->name . '(' . implode(', ', $args) . ')';

        if ($this->description) {
            $parts[] = '// ' . $this->description;
        }

        return implode(' ', $parts);
    }
}
