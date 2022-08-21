<?php

namespace DocWatch\DocWatch\Block;

use DocWatch\DocWatch\Items\Typehint;

class PropertyDocblock implements BlockInterface
{
    public function __construct(
        public string $name,
        public Typehint|null $type = null,
        public Typehint|null $default = null,
        public string|null $modifiers = null,
        public array $comments = [],
    )
    {
    }

    public function compile(): array
    {
        $parts = [];

        if ($this->modifiers !== null) {
            $parts[] = $this->modifiers;
        }

        if ($this->type !== null) {
            $parts[] = (string) $this->type;
        }

        $parts[] = '$' . $this->name;

        if ($this->default !== null) {
            $parts[] = '= ' . (string) $this->default;
        }

        return [
            implode(' ', $parts),
            ...$this->comments,
        ];
    }
}
