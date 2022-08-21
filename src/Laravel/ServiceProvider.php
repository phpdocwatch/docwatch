<?php

namespace DocWatch\DocWatch\Laravel;

use Carbon\Carbon;
use DocWatch\DocWatch\Commands\Clear;
use DocWatch\DocWatch\Commands\Generate;
use DocWatch\DocWatch\Commands\Watch;
use DocWatch\DocWatch\Generator;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

/**
 * @requires Laravel
 */
class ServiceProvider extends SupportServiceProvider
{
    public const DOCWATCH_VERSION = '0.1.0';

    /**
     * Register any application services.
     *
     * @return void
     */
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
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/docwatch.php' => config_path('docwatch.php'),
        ], 'docwatch');

        AboutCommand::add('Doc Watch', fn () => [
            'DocWatch Version' => static::DOCWATCH_VERSION,
            'Has Generated?' => ($exists = file_exists($file = Generator::getOutputFile())) ? '<fg=green;>YES</>' : '<fg=bright-yellow>NO</>',
            'Output File' => $file,
            'Last Updated At' => ($exists)
                ? Carbon::parse(filemtime($file))->setTimezone('Australia/Perth')->format('d M Y @ h:ia')
                : '--',
        ]);
    }
}
