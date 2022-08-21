<?php

namespace DocWatch\DocWatch;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionProperty;
use Stringable;

class Docblock implements Stringable
{
    /** @var string The primary description of the docblock (has no @ prefix) */
    public ?string $body = null;

    /** @var array<DocblockTag> Each docblock line (with @ prefix) */
    public array $tags = [];

    /**
     * Constructor.
     *
     * @param ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $item
     * @param integer $padLength
     */
    public function __construct(public ReflectionClass|ReflectionFunctionAbstract|ReflectionProperty $item, public $padLength = 0)
    {
        $this->unpack();
    }

    /**
     * Unpack the docblock into the body and tags.
     *
     * @return void
     */
    public function unpack()
    {
        $content = trim($this->item->getDocComment());

        $content = preg_replace('/^\/\*\*\s*/', '', $content);
        $content = preg_replace('/\s*\*\/$/', '', $content);

        $rows = array_map(fn ($row) => preg_replace('/^\s*\*\s*/', '', $row), preg_split('/[\n\r]+/', $content));
        $rows = array_filter($rows, fn ($row) => $row !== '');

        $joined = [];
        $tag = '__body';

        foreach ($rows as $row) {
            if (preg_match('/^\@([a-z]+)\s*(.*)/i', $row, $m)) {
                $tag = $m[1];
                $row = $m[2];
            }

            $joined[$tag] = $joined[$tag] ?? [];
            $joined[$tag][] = $row;
        }

        $this->body = implode("\n", $joined['__body'] ?? []);
        unset($joined['__body']);

        $this->tags = [];
        foreach ($joined as $type => $lines) {
            // get name from line

            preg_match('/(\$[^ ]+|[^ ]+\()/i', $lines[0], $m);
            $name = $m[1] ?? $lines[0];
            $name = str_replace(['$', '('], '', $name);

            /**
             * Here we're gonna try something hacky. If the first line is a var tag, and it doesn't have a $ in it
             * then we're going to split the first line after the first space and inject the property name there.
             *
             * e.g. "@var string|null something here"        on the property "$foo" will become:
             *      "@var string|null $foo something here"
             */
            if ($this->item instanceof ReflectionProperty) {
                if (($type === 'var') && !preg_match('/\$[^ ]+/', $lines[0])) {
                    $lines[0] = trim(preg_replace('/^([^ ]+)\s*(.*)/', '$1 $' . $this->item->getName() . ' $2', $lines[0]));
                }
            }

            $this->tags[] = new DocblockTag($type, $name, $lines, $this->padLength);
        }
    }

    /**
     * Convert the object to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->compile();
    }

    /**
     * Render this docblock to a string.
     *
     * @return string
     */
    public function compile(): string
    {
        $content = [];

        $content[] = '/**';

        if ($this->hasBody()) {
            $content[] = ' * ' . $this->body;
        }

        if ($this->hasTags()) {
            if ($this->hasBody()) {
                $content[] = ' * ';
            }

            foreach ($this->tags as $tag) {
                $content[] = (string) $tag;
            }
        }

        $content[] = ' */';

        if ($this->isSingleLine() && $this->item instanceof ReflectionProperty) {
            // ' * ' => '* '
            $tag = ltrim((string) $this->tags[0]);

            $content = [
                "/*$tag */",
            ];
        }

        return implode("\n", $content);
    }

    /**
     * Is this docblock a single line?
     * - has no body
     * - only has one tag
     *
     * @return boolean
     */
    public function isSingleLine(): bool
    {
        return !$this->hasBody() && count($this->tags) === 1;
    }

    /**
     * Does this docblock have a body?
     *
     * @return boolean
     */
    public function hasBody(): bool
    {
        return ($this->body !== '') && ($this->body !== null);
    }
    
    /**
     * Does this docblock have any tags?
     *
     * @return boolean
     */
    public function hasTags(): bool
    {
        return !empty($this->tags);
    }
}
