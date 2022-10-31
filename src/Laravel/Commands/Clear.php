<?php

namespace DocWatch\Laravel\Commands;

use DocWatch\Documentor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * @requires Laravel
 */
class Clear extends Command
{
    public $signature = 'docwatch:clear';

    public function handle()
    {
        [$class, $message] = static::runDelete();

        $this->line("<fg={$class}>$message</>");
    }

    public static function runDelete()
    {
        $path = Documentor::getOutputFile();

        if (file_exists($path)) {
            $size = static::humanBytes(filesize($path));
            File::delete($path);

            return ['green', '>>> Doc Watch file successfully deleted (-' . $size . ')'];
        }

        return ['yellow', '>>> Doc Watch file does not exist; not deleted'];
    }

    public static function humanBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
