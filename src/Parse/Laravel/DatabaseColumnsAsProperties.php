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
class DatabaseColumnsAsProperties implements ParseInterface
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        Artisan::call($config['command'] ?? 'db:table', [
            'table' => $model->getTable(),
        ]);

        $foundColumn = false;
        $foundIndex = false;

        $columns = collect(preg_split('/\n+/', Artisan::output()))
            ->filter(function ($row) use (&$foundColumn, &$foundIndex) {
                $row = trim($row);
                $start = Str::before($row, ' ');

                if ($start === 'Column') {
                    $foundColumn = true;

                    return false;
                }

                if ($foundColumn === true && $foundIndex === false) {
                    if ($start === 'Index') {
                        $foundIndex = true;

                        return false;
                    }

                    return true;
                }

                return false;
            })
            ->map(function (string $line) {
                $line = trim($line);

                $name = Str::before($line, ' ');
                $type = Str::afterLast($line, ' ');
                $nullable = Str::contains(Str::after($line, ' '), 'nullable');

                $type = [
                    $type,
                ];

                if ($nullable) {
                    $type[] = 'null';
                }

                return [
                    'name' => $name,
                    'type' => $type,
                ];
            })
            ->filter(fn (array $data) => !empty($data['name']));

        foreach ($columns as $data) {
            // Create a new DocblockTag for this class + method
            $docs->addDocblock(
                $class->getName(),
                new DocblockTag(
                    'property',
                    $data['name'],
                    new PropertyDocblock(
                        $data['name'],
                        new Typehint($data['type']),
                        comments: ['from:DatabaseColumnsAsProperties']
                    )
                )
            );
        }

        return $columns->isNotEmpty();
    }
}
