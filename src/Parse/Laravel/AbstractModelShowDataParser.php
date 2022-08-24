<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use ReflectionClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * @requires Laravel
 */
abstract class AbstractModelShowDataParser implements ParseInterface
{
    public static function parseModel(Model $model)
    {
        Artisan::call('model:show', [
            'model' => get_class($model),
        ]);

        $output = Artisan::output();

        $all = [
            'attributes' => [],
            'relations' => [],
        ];
        $reference = null;
        $maptypes = [
            'increments' => 'integer',
            'unique' => false,
            'unsigned' => 'integer',
            'name' => false,
        ];

        foreach (preg_split('/[\n\r]+/', $output) as $line) {
            $line = trim($line);
            [$line, $meta] = preg_split('/(\s*\.+\s*|$)/', $line);

            $line = trim($line);

            if (strtolower($line) === 'attributes') {
                $reference = 'attributes';

                continue;
            } elseif (strtolower($line) === 'relations') {
                $reference = 'relations';

                continue;
            } elseif ($line === '') {
                continue;
            }

            if ($reference === null) {
                continue;
            }

            $name = Str::before($line, ' ');

            // Remove irrelevant types
            $types = collect(explode(' ', Str::before($meta, '/')))
                ->map(fn (string $type) => trim(preg_replace('/\(\d+\)/', '', $type)))
                ->map(fn (string $type) => $maptypes[$type] ?? $type)
                ->filter()
                ->map(fn (string $type) => new Typehint($type))
                ->map(fn (Typehint $type) => $type->types)
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // Remove irrelevant trait
            $traits = collect(preg_split('/, +/', Str::after($line, ' ')))
                ->map(fn (string $trait) => trim($maptypes[$trait] ?? $trait))
                ->filter()
                ->filter(fn (string $trait) => !in_array($trait, $types, true))
                ->unique()
                ->values()
                ->toArray();

            if ($reference === 'attributes') {
                if (in_array('nullable', $traits)) {
                    $types[] = 'null';
                }
            }

            $traits = collect($traits)->filter(fn (string $trait) => $trait !== 'nullable')->toArray();

            $all[$reference][] = [
                'name' => $name,
                'traits' => $traits,
                'type' => $types,
            ];
        }

        return $all;
    }
}
