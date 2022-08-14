<?php

namespace DocWatch;

use Illuminate\Console\Application as Artisan;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use DocWatch\Commands\Generate;
use DocWatch\Commands\Info;
use Illuminate\Foundation\Console\AboutCommand;
use Carbon\Carbon;
use DocWatch\Commands\Clear;

class ServiceProvider extends BaseServiceProvider
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
                Info::class,
                Clear::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/docwatch.php' => config_path('docwatch.php'),
        ]);

        AboutCommand::add('Docwatch', fn () => [
            'DocWatch Version' => static::DOCWATCH_VERSION,
            'Has Generated?' => ($exists = file_exists($file = Generator::outputFile())) ? '<fg=green;>YES</>' : '<fg=bright-yellow>NO</>',
            'Last Updated At' => ($exists) ? Carbon::parse(filemtime($file))->setTimezone(Generator::timezone())->format('d M Y @ h:ia') : '--',
        ]);
    }
}
