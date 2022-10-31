<?php

namespace DocWatch\Parsers\Laravel;

use Illuminate\Support\Facades\Artisan;
use DocWatch\Parsers\AbstractParser;
use DocWatch\TypeMultiple;
use DocWatch\TypeSingle;

abstract class AbstractLaravelParser extends AbstractParser
{
    /**
     * Laravel type mapping
     */
    public const LARAVEL_TYPES = [
        'datetime' => \Carbon\Carbon::class,
    ];

    /**
     * Parse the output from the `artisan model:show {$model}` command
     */
    public static function getModelData(string $model): array
    {
        Artisan::call('model:show', [
            'model' => $model,
            '--json' => true,
        ]);

        $output = Artisan::output();
        $data = json_decode($output, true);

        return $data ?? [];
    }

    /**
     * Explode a fragment from the `artisan model:show {$model}` command
     */
    protected static function explodeModelShowFragment(string $value): array
    {
        $value = preg_replace('/[:\(].*$/', '', $value);

        return array_values(array_unique(preg_split('/[,\/ ]+/', $value, flags: PREG_SPLIT_NO_EMPTY)));
    }

    /**
     * Parse the given array of types into a TypeMultiple or TypeSingle.
     */
    public static function parseTypes(array|string $types, bool $nullable = false): TypeMultiple|TypeSingle
    {
        // Standardise as array
        $types = (array) $types;

        // Standardise types where possible
        $types = array_map(function (string $type) {
            $clean = strtolower($type);
            
            $clean = preg_replace('/[:\(].*$/', '', $clean);
            $clean = static::TYPES[$clean] ?? null;

            if ($clean === null) {
                return $type;
            }

            return static::LARAVEL_TYPES[$clean] ?? $clean;
        }, $types);

        // Add nullable type if the trait was found
        if ($nullable) {
            $types[] = 'null';
        }

        // Parse all types, remove duplicates
        $types = TypeMultiple::parse($types);

        return $types;
    }
}