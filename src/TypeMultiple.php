<?php

namespace DocWatch;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Stringable;

/**
 * @property array<TypeMultiple|TypeSingle> $types
 */
class TypeMultiple implements Stringable
{
    public const TYPE_MIXED = 'mixed';
    public const TYPE_NULL = 'null';
    public const TYPE_VOID = 'void';
    public const TYPE_SELF = 'self';

    public function __construct(
        public array $types,
        public bool $union = true,
    )
    {
        foreach ($this->types as $key => $type) {
            if ($type instanceof TypeMultiple || $type instanceof TypeSingle) {
                $this->types[$key] = $type;
            } else {
                $this->types[$key] = static::parse($type);
            }
        }
    }

    public function toArray(): array
    {
        return [
            ($this->union ? 'or' : 'and') => array_map(
                fn (TypeMultiple|TypeSingle $type) => $type->toArray(),
                $this->types
            ),
        ];
    }

    public function __toString(): string
    {
        return $this->compile();
    }

    public function compile(): string
    {
        $parts = array_map(
            fn (TypeMultiple|TypeSingle $type) => (string) $type,
            $this->types,
        );

        return implode(
            $this->union ? '|' : '&',
            $parts,
        );
    }

    public static function parse($type, bool $union = true): TypeMultiple|TypeSingle
    {
        $types = [];

        if ($type instanceof ReflectionParameter) {
            $type = $type->getType();
        }

        if (is_array($type)) {
            // Array is multiple in union
            $types = array_map(
                fn ($type) => static::parse($type),
                $type,
            );
        } elseif ($type instanceof ReflectionIntersectionType) {
            $types = array_map(
                fn ($type) => static::parse($type),
                $type->getTypes()
            );
            $union = false;
        } elseif ($type instanceof ReflectionUnionType) {
            $types = array_map(
                fn ($type) => static::parse($type),
                $type->getTypes()
            );
        } elseif ($type instanceof ReflectionNamedType) {
            $types = [
                $type->getName(),
            ];

            if ($type->allowsNull()) {
                $types[] = static::TYPE_NULL;
            }
        } elseif (is_string($type)) {
            if (substr($type, 0, 1) === '?') {
                $types = [
                    substr($type, 1),
                    static::TYPE_NULL,
                ];
            } else {
                $types = [
                    $type,
                ];
            }
        }

        if (empty($types)) {
            return new TypeSingle(static::TYPE_MIXED);
        }

        $types = array_values(array_unique($types));

        if (count($types) === 1) {
            return new TypeSingle($types[0]);
        }

        return new static($types, $union);
    }

    public function getEnum(): ?string
    {
        foreach ($this->types as $type) {
            if (($enum = $type->getEnum()) !== null) {
                return $enum;
            }
        }

        return null;
    }

    public function isEnum(): bool
    {
        return $this->getEnum() !== null;
    }

    /**
     * Specify the generics key for all child types
     * Warning: you may only want to apply this to one type, not all?
     */
    public function genericsKey(string|TypeSingle $keyType = null): self
    {
        foreach ($this->types as $type) {
            $type->genericsKey($keyType);
        }

        return $this;
    }

    /**
     * Specify the generics key for all child types
     * Warning: you may only want to apply this to one type, not all?
     */
    public function genericsValue(string|TypeMultiple|TypeSingle $valueTypes = null): self
    {
        foreach ($this->types as $type) {
            $type->genericsValue($valueTypes);
        }

        return $this;
    }

    public function is(string $type): bool
    {
        foreach ($this->types as $type) {
            if ($type->is($type)) {
                return true;
            }
        }

        return false;
    }
}