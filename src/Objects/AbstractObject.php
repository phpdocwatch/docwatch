<?php

namespace DocWatch\Objects;

use Stringable;

abstract class AbstractObject implements Stringable
{
    /**
     * Load in more information
     *
     * @return void
     */
    public function load()
    {
    }

    /**
     * Cast this object to string by generating the relevant docblock/docblock line
     *
     * @return string
     */
    public function __toString(): string
    {
        return method_exists($this, 'compile') ? $this->compile() : '';
    }
}
