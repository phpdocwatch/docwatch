<?php

namespace DocWatch;

use Stringable;

class TypeSingle implements Stringable
{
    public const PRIMITIVE_TYPES = [
        'string',
        'bool',
        'boolean',
        'int',
        'integer',
        'float',
        'array',
        'object',
        'resource',
        'mixed',
        'true',
        'false',
        'void',
        'null',
        'never',
    ];

    public TypeSingle|null $genericsKey = null;

    public TypeSingle|TypeMultiple|null $genericsValue = null;

    public function __construct(public string $type)
    {
        $this->type = static::parseType($type);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    public static function parseType(string $type): string
    {
        $type = ltrim($type, '\\');

        return $type;
    }

    public function __toString(): string
    {
        $type = (($this->isClass()) ? '\\' : '') . $this->type;

        if ($this->genericsKey || $this->genericsValue) {
            $generics = [];
            $generics[] = '<';

            if ($this->genericsValue) {
                if ($this->genericsKey) {
                    $generics[] = (string) $this->genericsKey;
                    $generics[] = ',';
                }
                
                $generics[] = (string) $this->genericsValue;
            }

            $generics[] = '>';

            $type .= implode('', $generics);
        }

        return $type;
    }

    public function isClass(): bool
    {
        return ! in_array($this->type, static::PRIMITIVE_TYPES);
    }

    public function getEnum(): ?string
    {
        // if primitive type return
        if (in_array($this->type, static::PRIMITIVE_TYPES)) {
            return null;
        }

        try {
            $reflection = new \ReflectionClass($this->type);

            if ($reflection->isEnum()) {
                return $this->type;
            }
        } catch (\Exception $e) {
            // Class not exists (most likely), assume not enum
        }

        return null;
    }

    public function isEnum(): bool
    {
        return $this->getEnum() !== null;
    }

    /**
     * Specify the generics key
     */
    public function genericsKey(string|TypeSingle $keyType = null): self
    {
        if (is_string($keyType)) {
            $keyType = new TypeSingle($keyType);
        }

        $this->genericsKey = $keyType;

        return $this;
    }

    /**
     * Specify the generics key
     */
    public function genericsValue(string|TypeMultiple|TypeSingle $valueTypes = null): self
    {
        if (is_string($valueTypes)) {
            $valueTypes = TypeMultiple::parse($valueTypes);
        }

        $this->genericsValue = $valueTypes;

        return $this;
    }

    public function is(string $type): bool
    {
        if ($this->type === $type) {
            return true;
        }

        if ($this->isClass() && ! in_array($type, static::PRIMITIVE_TYPES)) {
            return is_subclass_of($this->type, $type);
        }

        return false;
    }
}