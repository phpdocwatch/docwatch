<?php

namespace DocWatch\Parsers;

use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\File;

abstract class AbstractParser implements ParserInterface
{
    /**
     * Framework agnostic type mapping
     * 
     * @var array<string,string>
     */
    public const TYPES = [
        'array' => 'array',
        'bigincrements' => 'integer',
        'bigint' => 'integer',
        'binary' => 'integer',
        'bool' => 'bool',
        'boolean' => 'bool',
        'char' => 'string',
        'datetimetz' => 'datetime',
        'datetime' => 'datetime',
        'date' => 'datetime',
        'decimal' => 'float',
        'double' => 'float',
        'enum' => 'mixed',
        'float' => 'float',
        'foreignid' => 'integer',
        'foreignidfor' => 'string',
        'foreignuuid' => 'string',
        'geometrycollection' => 'string',
        'geometry' => 'string',
        'id' => 'integer',
        'increments' => 'integer',
        'int' => 'integer',
        'integer' => 'integer',
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
        'unsigned' => 'integer',
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

    /**
     * Configuration for this parser
     *
     * @var array
     */
    public array $config = [];

    /**
     * All docs
     */
    public ?Docs $docs = null;

    /**
     * Provide configuration for this parser
     */
    public function withConfig(array $config): self
    {
        $this->config = array_replace($this->config, $config);

        return $this;
    }

    /**
     * Provide context for this parser as to previous docs that have been generated
     */
    public function withDocs(Docs $docs): self
    {
        $this->docs = $docs;

        return $this;
    }

    /**
     * Get a config value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Is this config value set?
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Get the description segment in the format of "[via ParserClassName]"
     */
    public function viaDescription(): string
    {
        return '[via ' . basename(str_replace('\\', DIRECTORY_SEPARATOR, get_class($this))) . ']';
    }

    /**
     * Parse the given file, by default this won't do anything
     */
    public function parse(File $file): Doc|Docs|null
    {
        return null;
    }

    /**
     * Generate docs for a standlone class that does not necessarily relate to a given File in a directory.
     */
    public function standalone(): Doc|Docs|null
    {
        return null;
    }
}