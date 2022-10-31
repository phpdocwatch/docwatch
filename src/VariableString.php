<?php

namespace DocWatch;

class VariableString
{
    public function __construct(
        public string $literal,
    ) {
    }

    public function __toString(): string
    {
        return $this->literal;
    }

    public static function parse($value): VariableString
    {
        if (is_array($value)) {
            return new static(static::arrayToString($value));
        }

        if ($value instanceof \UnitEnum) {
            return new static('\\' . get_class($value) . '::' . $value->name);
        }

        return new static(json_encode($value));
    }

    private static function arrayToString(array $value): string
    {
        $parts = [];
        $keyInOrder = true;

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = static::arrayToString($item);
            } else {
                $item = json_encode($item);
            }

            if (is_string($key)) {
                $key = json_encode($key);
            }

            // First should be (true && 0), second is (true && 1), third is (true && 2), etc
            // As soon as one of them is false, we know the keys are not in order (anymore)
            $keyInOrder = $keyInOrder && ($key === count($parts));

            if (is_int($key) && $keyInOrder) {
                $parts[] = $item;
            } else {
                $parts[] = $key . ' => ' . $item;
            }
        }

        return '[' . implode(', ', $parts) . ']';
    }
}