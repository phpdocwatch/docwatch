<?php

namespace DocWatch;

use JsonSerializable;
use Stringable;

class ReflectionParameterItem
{
    public function method(
        string $basic,
        Stringable&JsonSerializable $nonUnion,
        ?int $nullable = null,
        SomeEnum $enum = SomeEnum::AA,
        string|int $union = 'default',
    ){
    }
}