<?php

namespace Carbon;

use DateTime;

class Carbon extends DateTime
{
    public function addDays(int $days): self
    {
        return $this;
    }

    public function addDay(): self
    {
        return $this;
    }

    public static function parse($time = null, $tz = null): self
    {
        return new static();
    }
}