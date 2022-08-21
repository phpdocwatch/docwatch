<?php

namespace DocWatch\DocWatch;

/**
 * A singleton class for managing all docblock tags to generate
 */
class Docs
{
    /**
     * The Singleton instance
     *
     * @var Docs|null
     */
    public static ?Docs $instance = null;

    /**
     * The container for the docblocks.
     *
     * @var array
     */
    public $container = [];

    /**
     * Add a docblock to the container
     *
     * @param string $classNamespace
     * @param DocblockTag|null $tag The tag to add
     * @param array $class Information about the class, e.g. extends, implements, etc.
     * @param bool $replace should this replace an existing definition if there is one?
     * @return static
     */
    public function addDocblock(string $classNamespace, DocblockTag|null $tag = null, array $class = [], bool $replace = true): static
    {
        $this->container[$classNamespace] ??= [];

        if ($tag !== null) {
            $this->container[$classNamespace][$tag->type] ??= [];

            // If this tag already exists and we shouldn't replace, then ignore it
            if (($replace === false) && isset($this->container[$classNamespace][$tag->type][$tag->name])) {
                return $this;
            }

            // Otherwise set it
            $this->container[$classNamespace][$tag->type][$tag->name] = $tag;
        } else {
            // If the class definition already exists and we shouldn't replace, then ignore it
            if (($replace === false) && isset($this->container[$classNamespace]['class'])) {
                return $this;
            }

            $this->container[$classNamespace]['class'] = array_replace(
                $this->container[$classNamespace]['class'] ?? [],
                $class,
            );
        }

        return $this;
    }

    /**
     * Get the instance for this class.
     *
     * @return static
     */
    public static function instance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
