<?php

namespace DocWatch;

use Stringable;

class Docs implements Stringable
{
    public array $items = [];

    public function __construct(
        array $docs = []
    ) {
        $this->set($docs);
    }

    public function compile(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        $docs = [];
        $namespaces = [];

        foreach ($this->items as $doc) {
            $namespaces[$doc->namespace] ??= [];
            $namespaces[$doc->namespace][] = $doc;
        }

        foreach ($namespaces as $namespace => $items) {
            $parentNamespace = substr($namespace, 0, strrpos($namespace, '\\'));
            $childNamespace = substr($namespace, strrpos($namespace, '\\') + 1);

            $docs[] = sprintf('namespace %s;', $parentNamespace);
            $docs[] = '/**';
            $docs[] = ' * ' . implode("\n * ", $items);
            $docs[] = ' */';
            $docs[] = sprintf('class %s%s{%s}', $childNamespace, PHP_EOL, PHP_EOL);
            $docs[] = "\n\n\n";
        }

        return trim(implode("\n", $docs));
    }

    public function set(iterable $docs): self
    {
        foreach ($docs as $doc) {
            $this->push($doc);
        }
        
        return $this;
    }

    public function push(Doc $doc): self
    {
        $this->items[] = $doc;

        return $this;
    }

    /**
     * Get this Docs class, or null if empty
     */
    public function orNull(): ?Docs
    {
        return $this->items ? $this : null;
    }

    /**
     * Merge two docs together
     */
    public function merge(Docs|Doc|null $docs = null): self
    {
        if ($docs === null) {
            return $this;
        }

        if ($docs instanceof Doc) {
            $docs = new Docs([
                $docs,
            ]);
        }

        $this->items = array_merge(
            $this->items,
            $docs->items,
        );

        return $this;
    }

    /**
     * Remove duplicate docs by namespace, type and name. If multiple exist,
     * the more recently added one will be kept.
     */
    public function trim(): self
    {
        $unique = [];

        foreach ($this->items as $doc) {
            /** @var Doc $doc */
            $key = implode('|', [$doc->namespace, $doc->type, $doc->name]);

            $unique[$key] = $doc;
        }

        $this->items = array_values($unique);

        return $this;
    }

    public function forClass(string $class): array
    {
        return array_filter(
            $this->items,
            fn (Doc $doc) => $doc->namespace === $class,
        );
    }
}