<?php

namespace DocWatch;

use DocWatch\Objects\Model;
use DocWatch\Objects\Event;
use DocWatch\Objects\Job;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus as BusFacade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB as DBFacade;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue as QueueFacade;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Illuminate\Support\Str;
use SplFileInfo;

class Generator
{
    public array $modelPaths = [];

    public array $eventPaths = [];

    public array $jobPaths = [];

    public function __construct()
    {
        // Ensure nothing is written to the database, no storage modifications are made, no events are fired, etc
        DBFacade::beginTransaction();
        StorageFacade::fake();
        BusFacade::fake();
        EventFacade::fake();
        QueueFacade::fake();
    }

    /**
     * Create new generator instance
     *
     * @return static
     */
    public static function instance()
    {
        return new static();
    }

    /**
     * Specify the directory or directories to read models from
     *
     * @param array|string|null $modelPaths
     * @return self
     */
    public function modelPaths(array|string $modelPaths = null)
    {
        $this->modelPaths = Arr::wrap(
            $modelPaths ?? Config::get('docwatch.modelPaths', []),
        );

        return $this;
    }

    /**
     * Specify the directory or directories to read models from
     *
     * @param array|string|null $eventPaths
     * @return self
     */
    public function eventPaths(array|string $eventPaths = null)
    {
        $this->eventPaths = Arr::wrap(
            $eventPaths ?? Config::get('docwatch.eventPaths', []),
        );

        return $this;
    }

    /**
     * Specify the directory or directories to read models from
     *
     * @param array|string|null $jobPaths
     * @return self
     */
    public function jobPaths(array|string $jobPaths = null)
    {
        $this->jobPaths = Arr::wrap(
            $jobPaths ?? Config::get('docwatch.jobPaths', []),
        );

        return $this;
    }

    /**
     * Get all models
     *
     * @param array|string|null $modelPaths
     * @return Collection
     */
    public function models(array|string $modelPaths = null): Collection
    {
        $this->modelPaths($modelPaths);

        $all = Collection::make($this->modelPaths)
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
     * Get all events
     *
     * @param array|string|null $eventPaths
     * @return Collection
     */
    public function events(array|string $eventPaths = null): Collection
    {
        $this->eventPaths($eventPaths);

        $all = Collection::make($this->eventPaths)
            ->map(fn (string $path) => Str::startsWith($path, '/') ? $path : base_path($path))
            ->filter(fn (string $path) => is_dir($path))
            ->map(function (string $directory) {
                return Collection::make(File::allFiles($directory))
                    ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
                    ->map(fn (SplFileInfo $file) => Event::createFromPath($file->getRealPath()))
                    ->filter(); // ignore those that failed validation (aren't events, aren't instantiable, etc)
            })
            ->collapse();

        return $all;
    }

    /**
     * Get all jobs
     *
     * @param array|string|null $jobPaths
     * @return Collection
     */
    public function jobs(array|string $jobPaths = null): Collection
    {
        $this->jobPaths($jobPaths);

        $all = Collection::make($this->jobPaths)
            ->map(fn (string $path) => Str::startsWith($path, '/') ? $path : base_path($path))
            ->filter(fn (string $path) => is_dir($path))
            ->map(function (string $directory) {
                return Collection::make(File::allFiles($directory))
                    ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
                    ->map(fn (SplFileInfo $file) => Job::createFromPath($file->getRealPath()))
                    ->filter(); // ignore those that failed validation (aren't jobs, aren't instantiable, etc)
            })
            ->collapse();

        return $all;
    }

    /**
     * Extract the full namespace of the given model by its path. This will iterate all lines
     * until it finds a namespace definition line, and a class name line.
     *
     * @param string $path
     * @return string|null
     */
    public static function extractFullNamespace(string $path): ?string
    {
        $f = fopen($path, 'r');

        $namespace = null;
        $class = null;

        while (($line = fgets($f, 1000)) !== false) {
            if (($namespace === null) && preg_match('/^namespace (.+);$/', $line, $m)) {
                $namespace = $m[1];
            }

            if (($class === null) && preg_match('/^(?:readonly|abstract|final)?\s*class ([^ ]+)/', $line, $m)) {
                $class = $m[1];

                // Class comes after namespace so once we see this line, bail immediately
                break;
            }
        }

        fclose($f);

        // We need both namespace and class or it's invalid
        if ($namespace === null || $class === null) {
            return null;
        }

        return $namespace . '\\' . $class;
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
