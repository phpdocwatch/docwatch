<?php

namespace DocWatch\DocWatch\Commands;

use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * @requires Laravel
 */
class Generate extends Command
{
    public $signature = 'docwatch:generate';

    public function handle()
    {
        $this->newLine();
        $start = microtime(1);
        $this->line('Doc Watch generating...');

        Generator::generate();
        $end = microtime(1);
        $this->info('Doc Watch generated successfully in ' . round($end - $start, 2) . ' seconds');

        $this->newLine();
        $docs = Docs::instance();

        $classes = count(array_keys($docs->container));
        $methods = collect($docs->container)->sum(fn (array $class) => count($class['method'] ?? []));
        $properties = collect($docs->container)->sum(fn (array $class) => count($class['property'] ?? []));

        $this->fixedLine('No. of Classes', $classes);
        $this->fixedLine('     - Methods', $methods);
        $this->fixedLine('     - Properties', $properties);
        $this->fixedLine('     - Rules Run', count(Generator::getRules()));

        foreach (Generator::$stats as $type => $number) {
            $this->fixedLine('     - ' . Str::plural(Str::title($type)), count($number));
        }

        $this->newLine();
        $this->fixedLine('Output path', Generator::getOutputFile(), valueColor: 'white');
        $this->fixedLine('File size', static::humanBytes(filesize(Generator::getOutputFile())), valueColor: 'bright-green');
    }

    public function fixedLine(
        string $key,
        string|int $value,
        string $keyColor = 'bright-magenta',
        string $valueColor = 'bright-cyan'
    ) {
        $this->line(
            str_pad("<fg={$keyColor}>$key</> <fg=gray>", 80 - strlen($keyColor), '.') . "</> <fg={$valueColor}>{$value}</>",
        );
    }

    public static function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
