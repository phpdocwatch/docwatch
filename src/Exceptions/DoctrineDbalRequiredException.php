<?php

namespace DocWatch\Exceptions;

use Exception;

class DoctrineDbalRequiredException extends Exception
{
    public static function make(string $parser): self
    {
        return new self(
            message: sprintf(
                'The docwatch parser `%s` leverages `artisan model:show` which requires the `doctrine/dbal` package. Please run `artisan model:show {model}` and follow the prompts to install doctrine/dbal :)',
                $parser,
            ),
        );
    }
}