<?php

namespace DocWatch\DocWatch;

use DocWatch\DocWatch\Parse\ParseInterface;
use DocWatch\DocWatch\Reader\ReaderInterface;
use DocWatch\DocWatch\Writer\WriterInterface;
use ReflectionClass;

class Generator
{
    /**
     * Configuration cache
     *
     * @var array
     */
    public static array $config = [];

    /**
     * Stats - number of classes per type
     *
     * @var array
     */
    public static $stats = [];

    /**
     * Constructor. Optionally pass in a configuration array to REPLACE the global configuration.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        static::$config = $config;
    }

    /**
     * Boot up the generation process
     *
     * @return static
     */
    public static function generate(): static
    {
        return (new static())->run();
    }

    /**
     * Determine if this application is a Laravel app.
     *
     * To do this we're currently just checking if the Config facade exists
     * and if the base_path function exists.
     *
     * @return boolean
     */
    public static function isLaravel(): bool
    {
        return class_exists(\Illuminate\Support\Facades\Config::class) && function_exists('base_path');
    }

    /**
     * Read the configuration.
     *
     * If Laravel: This will read the config under the namespace "docwatch"
     *  Otherwise: This will read the config straight from config/docwatch.php
     *
     * @return array
     */
    public static function config(): array
    {
        if (static::isLaravel()) {
            static::$config = \Illuminate\Support\Facades\Config::get('docwatch', []);

            // If it loaded config then don't load the plugin's default
            if (!empty(static::$config)) {
                return static::$config;
            }
        }

        return static::$config = include __DIR__ . '/../config/docwatch.php';
    }

    /**
     * Get the reader class
     *
     * @return ReaderInterface
     */
    public static function getReader(): ReaderInterface
    {
        $reader = static::config()['reader'];

        return new $reader();
    }

    /**
     * Get the writer class
     *
     * @return WriterInterface
     */
    public static function getWriter(): WriterInterface
    {
        $writer = static::config()['writer'];

        return new $writer;
    }

    /**
     * Get the output file
     *
     * If absolute: The output file is an absolute path
     * If Laravel: The outputfile is relative to base_path()
     * If not: The outputfile is relative to the parent parent directory.
     *
     * @return string
     */
    public static function getOutputFile(): string
    {
        return static::getFilePath(static::config()['outputFile']);
    }

    /**
     * Get the output file path from the configuration
     *
     * @param string $file
     * @return string
     */
    public static function getFilePath(string $file): string
    {
        // Check if the file is a relative link
        if (substr($file, 0, 1) !== '/') {
            if (static::isLaravel()) {
                $file = base_path($file);
            } else {
                $file = dirname(dirname(__DIR__)) . '/' . $file;
            }
        }

        return $file;
    }

    /**
     * Get the rules to run
     *
     * @return array
     */
    public static function getRules(): array
    {
        return static::config()['rules'] ?? [];
    }

    /**
     * Because this runs rudimentary code for analysis, such as instantiating models
     * or relations, we need to ensure that the application is not going to do anything bad.
     * 
     * Some people do weird things in their apps like "on instantiation of a model, run a DB query"
     * or something alike - obviously this is not a good idea - so we'll fake everything to ensure nothing is run
     *
     * @return void
     */
    public static function safety()
    {
        //
    }

    /**
     * Run the generation process
     *
     * @return static
     */
    public function run(): static
    {
        static::safety();

        $rules = static::getRules();

        $docs = Docs::instance();
        $reader = static::getReader();
        $writer = static::getWriter();
        $file = static::getOutputFile();

        static::$stats = [];

        // Extract docblocks from the rules

        foreach ($rules as $rule) {
            // Get the path or paths to read from
            $path = $rule['path'] ?? null;
            $path = array_map(
                fn (string $path) => static::getFilePath($path),
                (is_array($path)) ? $path : [$path]
            );
            $recursive = $rule['recursive'] ?? false;

            // Get the parsers to use
            $parsers = $rule['parsers'] ?? [];

            // Get the type of class (for stats)
            $type = $rule['type'] ?? 'misc';
            static::$stats[$type] ??= [];

            // If the path is not set then skip this rule
            if (empty($path) || empty($parsers)) {
                continue;
            }

            // Scan the given path (or paths) for PHP files
            $files = $reader->scanDirectory($path, $recursive);

            // Convert each PHP file path to a fully qualified namespace (if available)
            $classes = array_map(
                fn (string $path) => $reader->resolveClass($path),
                (array) $files,
            );

            // Filter out any null namespaces (invalid files, etc)
            $classes = array_filter($classes);

            // Filter out any classes that don't extend the specified classes
            if (!empty($extends = $rule['extends'] ?? null)) {
                $extends = (is_array($extends)) ? $extends : [$extends];

                // Forward the "does this class extend x class" check to the reader
                $classes = array_filter(
                    $classes,
                    fn (string $class) => $reader->classExtends($class, $extends),
                );
            }

            // Filter out any classes that don't implement the specified interfaces
            if (!empty($implements = $rule['implements'] ?? null)) {
                $implements = (is_array($implements)) ? $implements : [$implements];

                // Forward the "does this class implement x interface" check to the reader
                $classes = array_filter(
                    $classes,
                    fn (string $class) => $reader->classImplements($class, $implements),
                );
            }

            // Filter out any classes that don't use the specified traits
            if (!empty($traits = $rule['traits'] ?? null)) {
                $traits = (is_array($traits)) ? $traits : [$traits];

                // Forward the "does this class use x trait" check to the reader
                $classes = array_filter(
                    $classes,
                    fn (string $class) => $reader->classUses($class, $traits),
                );
            }

            // Continue if there are no classes to process
            if (empty($classes)) {
                continue;
            }

            // Run each parser

            foreach ($parsers as $parser => $config) {
                // If the key is an integer then the value is the parser classname
                if (is_int($parser)) {
                    $parser = $config;
                    $config = [];
                }

                // continue if the class does not exist
                if (!class_exists($parser)) {
                    continue;
                }

                // continue if the class does not implement the parser interface
                if (!in_array(ParseInterface::class, class_implements($parser))) {
                    continue;
                }

                // Create an instance of the parser
                $parser = new $parser();
                /** @var ParseInterface $parser */

                foreach ($classes as $class) {
                    $parser->parse($docs, new ReflectionClass($class), $config);

                    static::$stats[$type][$class] = true;;
                }
            }
        }

        // Write the docs to the output file
        $writer->open($file, $docs);
        try {
            $writer->write($file, $docs);
        } catch (\Throwable $e) {
        } finally {
            $writer->close($file, $docs);
        }

        return $this;
    }
}
