<?php

namespace DocWatch;

use Closure;

class ClassWithProxyMethod
{
    public function __construct(
        public string $foo,
        public int $bar,
        public bool $baz = false,
    ) {
    }

    /**
     * Standard test
     */
    public static function spawn(): ClassWithProxyMethod
    {
        return new static(...func_get_args());
    }

    public function __construct2(
        ...$items,
    ) {
    }

    /**
     * Test variadic argument in __construct2
     */
    public static function spawn2(): ClassWithProxyMethod
    {
        return new static(...func_get_args());
    }

    public function __construct3(
        ...$items
    ) {
    }

    /**
     * Test variadic argument in __construct3 with ignored argument ($spawnIf)
     * Non static as well just to test things
     */
    public function spawn3(bool|Closure $spawnIf, ...$args): ?ClassWithProxyMethod
    {
        if ($spawnIf instanceof Closure) {
            $spawnIf = $spawnIf();
        }

        if ($spawnIf) {
            return new static(...$args);
        }

        return null;
    }
}