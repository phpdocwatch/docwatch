<?php

namespace DocWatch\DocWatch;

use DocWatch\DocWatch\Block\BlockInterface;
use Stringable;

/**
 * A docblock tag repsents a single docblock item, such as a "property", "method", etc.
 *
 * The tag may be comprised of one or more lines. If subsequent lines are provided they are
 * indented to the first character of the first line.
 *
 * For example:
 *
 * @property string $type The type of the tag (property, method, etc)
 *                        More information can be provided in the $lines array
 *
 * For example with identation of 30:
 *
 * @property string $type        The type of the tag (property, method, etc)
 *                               More information can be provided in the $lines array
 */
class DocblockTag implements Stringable
{
    /**
     * Constructor.
     *
     * @param string $type
     * @param string $name Not used for generating the tag, only used for differentating tags of the same type and name
     * @param array|BlockInterface|null $lines
     * @param integer $padLength Optional. The length of the indentation of all lines of the tag (after the type)
     */
    public function __construct(public string $type, public string $name, public array|BlockInterface|null $lines = null, public $padLength = 0)
    {
    }

    /**
     * Convert this object to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->compile();
    }

    /**
     * Render this docblock tag to a string. If the compilation of this docblock tag
     * is undesired then it can be overridden by the WriterInterface class.
     *
     * @return string
     */
    public function compile(): string
    {
        $length = max(strlen($this->type) + 1, $this->padLength);
        $type = '@' . str_pad($this->type, $length, ' ', STR_PAD_RIGHT);
        $pad = str_pad(' ', strlen($type));
        $lines = $this->lines ?? [];

        if ($this->lines instanceof BlockInterface) {
            $lines = (array) $this->lines->compile();
        }

        foreach ($lines as $index => $line) {
            $lines[$index] = ' * ' . (($index === 0) ? $type : $pad) . $line;
        }

        return implode("\n", array_values($lines));
    }
}
