<?php

namespace DocWatch\DocWatch\Reader;

interface ReaderInterface
{
    /**
     * Scan the given directory/directories for any PHP files and return an array of paths to those PHP files
     *
     * @param iterable|string $directories
     * @param boolean $recursive
     * @return iterable
     */
    public function scanDirectory(iterable|string $directories, bool $recursive = false): iterable;

    /**
     * Resolve the class from the given path (if a class; interfaces, traits and enums are not resolved)
     *
     * @param string $path
     * @return string|null
     */
    public function resolveClass(string $path): ?string;

    /**
     * Determine if the given $class extends the given $extends class
     * 
     * The DefaultReader implementation will treat this as an "OR" check, however your own reader class
     * can implement this as an "AND" check, or however you prefer.
     *
     * @param string $class
     * @param iterable<string> $extends
     * @return boolean
     */
    public function classExtends(string $class, iterable $extends): bool;

    /**
     * Determine if the given $class implements the given $interface
     * 
     * The DefaultReader implementation will treat this as an "OR" check, however your own reader class
     * can implement this as an "AND" check, or however you prefer.
     *
     * @param string $class
     * @param iterable<string> $interfaces
     * @return boolean
     */
    public function classImplements(string $class, iterable $interfaces): bool;

    /**
     * Determine if the given $class uses the given $trait
     * 
     * The DefaultReader implementation will treat this as an "OR" check, however your own reader class
     * can implement this as an "AND" check, or however you prefer.
     *
     * @param string $class
     * @param iterable<string> $traits
     * @return boolean
     */
    public function classUses(string $class, iterable $traits): bool;
}
