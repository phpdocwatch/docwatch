<?php

namespace DocWatch\Laravel\Commands;

use DocWatch\Documentor;
use Illuminate\Console\Command;

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

        Documentor::generate();
        
        $end = microtime(1);
        $this->info('Doc Watch generated successfully in ' . round($end - $start, 2) . ' seconds');

        $this->fixedLine('Output path', Documentor::getOutputFile(), valueColor: 'white');
        $this->fixedLine('File size', static::humanBytes(filesize(Documentor::getOutputFile())), valueColor: 'bright-green');
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
