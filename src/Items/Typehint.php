<?php

namespace DocWatch\DocWatch\Items;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Stringable;

class Typehint implements Stringable
{
    public const TYPES = [
        'bigincrements' => 'integer',
        'bigint' => 'integer',
        'binary' => 'integer',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'char' => 'string',
        'datetimetz' => 'datetime',
        'datetime' => 'datetime',
        'date' => 'datetime',
        'decimal' => 'float',
        'double' => 'float',
        'enum' => 'casts:string',
        'float' => 'float',
        'foreignid' => 'integer',
        'foreignidfor' => 'string',
        'foreignuuid' => 'string',
        'geometrycollection' => 'string',
        'geometry' => 'string',
        'id' => 'integer',
        'increments' => 'integer',
        'int' => 'integer',
        'ipaddress' => 'string',
        'json' => 'array',
        'jsonb' => 'array',
        'linestring' => 'string',
        'longtext' => 'string',
        'macaddress' => 'string',
        'mediumincrements' => 'integer',
        'mediumint' => 'integer',
        'mediumtext' => 'string',
        'morphs' => 'string',
        'multilinestring' => 'string',
        'multipoint' => 'string',
        'multipolygon' => 'string',
        'nullablemorphs' => 'string',
        'nullabletimestamps' => 'datetime',
        'nullableuuidmorphs' => 'string',
        'point' => 'string',
        'polygon' => 'string',
        'remembertoken' => 'string',
        'set' => 'string',
        'smallincrements' => 'integer',
        'smallint' => 'integer',
        'softdeletestz' => 'datetime',
        'softdeletes' => 'datetime',
        'string' => 'string',
        'text' => 'string',
        'timetz' => 'string',
        'time' => 'string',
        'timestamptz' => 'datetime',
        'timestamp' => 'datetime',
        'timestampstz' => 'datetime',
        'timestamps' => 'datetime',
        'tinyincrements' => 'integer',
        'tinyint' => 'integer',
        'tinytext' => 'string',
        'unsignedbigint' => 'integer',
        'unsigneddecimal' => 'float',
        'unsignedint' => 'integer',
        'unsignedmediumint' => 'integer',
        'unsignedsmallint' => 'integer',
        'unsignedtinyint' => 'integer',
        'uuidmorphs' => 'string',
        'uuid' => 'string',
        'year' => 'integer',
    ];

    /** @var string Regex used to identify and extract any generics */
    public const GENERICS_REGEX = '/<([^>]+)>/';

    /** @var string Compiled list of types */
    public string $types;

    /**
     * Create a new typehint class
     *
     * @param ReflectionType|ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|string|bool|null|mixed $type
     */
    public function __construct($type, bool $literalValue = false)
    {
        $this->types = static::parseTypes($type, literalValue: $literalValue);
    }

    /**
     * Cast this Typehint to string (get the $types)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->types;
    }

    /**
     * Guess the type based on the given type, ready for docblocks.
     *
     * @param mixed $type
     * @return string
     */
    public static function guessType($type = null, bool $nullable = false): static
    {
        if (is_string($type)) {
            $type = explode('|', $type);

            if ($nullable) {
                $type[] = null;
            }
        }

        return new static($type);
    }

    /**
     * Standardise all various types of typehints into a collection of strings.
     *
     * Classes -> string namespace
     * string|bool|null|true|false|etc -> stringified
     *
     * @param ReflectionType|ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|string|bool|null|mixed $type
     * @param bool $nested = false
     * @param bool $literalValue = false
     * @return string
     */
    public static function parseTypes($type, bool $nested = false, bool $literalValue = false): string
    {
        if ($literalValue) {
            if (is_array($type) || is_int($type) || is_string($type)) {
                return json_encode($type);
            }

            // fallthrough?
        }

        $generics = '';
        if (is_string($type) && preg_match(static::GENERICS_REGEX, $type, $m)) {
            $type = preg_replace(static::GENERICS_REGEX, '', $type);

            $generics = $m[0];
        }

        $open = ($nested) ? '(' : '';
        $close = ($nested) ? ')' : '';

        // A&B
        if ($type instanceof ReflectionIntersectionType) {
            return implode('', [
                $open,
                implode(
                    '&',
                    array_map(
                        fn (ReflectionType $type) => static::parseTypes($type, nested: true),
                        $type->getTypes(),
                    ),
                ),
                $close,
            ]);
        }

        // A|B
        if ($type instanceof ReflectionUnionType) {
            return implode('', [
                $open,
                implode(
                    '|',
                    array_map(
                        fn (ReflectionType $type) => static::parseTypes($type, nested: true),
                        $type->getTypes(),
                    ),
                ),
                $close,
            ]);
        }

        // Array format
        if (is_array($type)) {
            $type = implode(
                '|',
                array_map(
                    fn ($type) => static::parseTypes($type, nested: true),
                    $type
                ),
            );
        }

        // A
        if ($type instanceof ReflectionNamedType) {
            $type = $type->getName() . ($type->allowsNull() ? '|null' : '');
        }

        // Void -- just return immediately I guess, no nesting needed?
        if ($type === 'void' || $type === 'mixed' || $type === 'static' || $type === 'self' || $type === 'mixed|void') {
            return $type;
        }

        // Clean up variants of bool|int
        if (is_string($type)) {
            $type = preg_replace('/boolean$/', 'bool', $type);
            $type = preg_replace('/integer$/', 'int', $type);

            //  Convert db types to docblock types
            $type = static::TYPES[$type] ?? $type;
        }

        // Typically class names:
        if (is_string($type) && (class_exists($type) || (strpos($type, '\\') !== false))) {
            $type = '\\' . ltrim($type, '\\');
        }

        // Convert literal booleans and nulls into string format
        if (is_bool($type) || is_null($type)) {
            $type = json_encode($type);
        }

        return ((string) $type) . $generics;
    }

    /**
     * Get the mixed typehint
     *
     * @return static
     */
    public static function mixed()
    {
        return new static('mixed');
    }

    /**
     * Get the static typehint
     *
     * @return static
     */
    public static function static()
    {
        return new static('static');
    }

    /**
     * Get the self typehint
     *
     * @return static
     */
    public static function self()
    {
        return new static('self');
    }

    /**
     * Get the self typehint
     *
     * @return static
     */
    public static function mixedVoid()
    {
        return new static('mixed|void');
    }
}
