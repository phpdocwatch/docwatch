<?php

namespace DocWatch\Laravel;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use DocWatch\Documentor;
use Illuminate\Foundation\Console\AboutCommand;
use DocWatch\Laravel\Commands\Clear;
use DocWatch\Laravel\Commands\Generate;
use DocWatch\Laravel\Commands\Watch;

class DocWatchServiceProvider extends ServiceProvider
{
    public const DOCWATCH_VERSION = '0.1.0';

    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Generate::class,
                Clear::class,
                Watch::class,
            ]);
        }

        $this->app['events']->listen('cache:cleared', function () {
            if ($this->app->runningInConsole()) {
                [$class, $message] = Clear::runDelete();

                $output = new \Symfony\Component\Console\Output\ConsoleOutput();
                $output->writeln("<fg={$class}>{$message}</>");
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/docwatch.php' => config_path('docwatch.php'),
        ], 'docwatch');

        AboutCommand::add('Doc Watch', fn () => [
            'Doc Watch Version' => static::DOCWATCH_VERSION,
            'Has Generated?' => ($exists = file_exists($file = Documentor::getOutputFile())) ? '<fg=green;>YES</>' : '<fg=bright-yellow>NO</>',
            'Output File' => $file,
            'Last Updated At' => ($exists)
                ? Carbon::parse(filemtime($file))->setTimezone('Australia/Perth')->format('d M Y @ h:ia')
                : '--',
        ]);
    }
}