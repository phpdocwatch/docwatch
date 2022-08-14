<?php

namespace DocWatch;

use DocWatch\Objects\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class Generator
{
    public array $directories = [];

    public function __construct(array|string $directories = null)
    {
        // Ensure nothing is written to the database, no storage modifications are made, no events are fired, etc
        DB::beginTransaction();
        Storage::fake();
        Bus::fake();
        Event::fake();
        Queue::fake();

        $this->directories($directories);
    }

    /**
     * Create new generator instance
     *
     * @return static
     */
    public static function instance(array|string $directories = null)
    {
        return new static($directories);
    }

    /**
     * Specify the directory or directories to read models from
     *
     * @param array|string|null $directories
     * @return self
     */
    public function directories(array|string $directories = null)
    {
        $this->directories = Arr::wrap(
            $directories ?? Config::get('docwatch.directories', 'app/Models'),
        );

        return $this;
    }

    /**
     * Get all models
     *
     * @return Collection
     */
    public function models(): Collection
    {
        $all = Collection::make($this->directories)
            ->map(fn (string $path) => Str::startsWith($path, '/') ? $path : base_path($path))
            ->filter(fn (string $path) => is_dir($path))
            ->map(function (string $directory) {
                return Collection::make(scandir($directory))
                    ->filter(fn ($model) => substr($model, -4) === '.php')
                    ->map(fn ($filename) => $directory . DIRECTORY_SEPARATOR . $filename)
                    ->map(fn ($path) => Model::createFromPath($path))
                    ->filter(); // ignore those that failed validation (aren't models, aren't instantiable, etc)
            })
            ->collapse();

        return $all;
    }

    /**
     * Get the path of which the generated docblock file should be outputted to.
     *
     * @return string
     */
    public static function outputFile(): string
    {
        $path = Config::get('docwatch.outputFile', 'bootstrap/docwatch.php');
        $path = Str::startsWith($path, '/') ? $path : base_path($path);

        return $path;
    }

    /**
     * Should this generate "proxied" query builders.
     *
     * A proxied query query builder is a virtual query builder class which
     * only exists in docblock form to assist intelephense / intellisense
     * understand what methods/scopes each query builder instance has
     * access to. Proxied query builders cannot be used for hints
     * nor can it be referenced in the codebase (e.g. checking
     * for inheritance, instanceof, etc).
     *
     * @return boolean
     */
    public static function useProxiedQueryBuilders(): bool
    {
        return Config::get('docwatch.useProxiedQueryBuilders', true);
    }

    /**
     * Get the timezone of the developer, to be used in the `artisan about` command
     *
     * @return string
     */
    public static function timezone(): string
    {
        return Config::get('docwatch.timezone', 'UTC');
    }
}
