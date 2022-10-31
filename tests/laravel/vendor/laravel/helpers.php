<?php

function class_uses_recursive($class, $autoload = true)
{
    $traits = [];

    do {
        $traits = array_merge(class_uses($class, $autoload), $traits);
    } while ($class = get_parent_class($class));

    return array_unique($traits);
}