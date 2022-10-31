<?php

namespace DocWatch;

use DocWatch\Parsers\ParserInterface;

class Documentor
{
    public static ?array $config = null;

    public static bool $faked = false;

    public static function readConfig(): array
    {
        if (static::$config === null) {
            if (static::$faked === false) {
                static::$config = \Illuminate\Support\Facades\Config::get('docwatch');
            }

            static::$config ??= require __DIR__ . '/../config/docwatch.php';
        }

        return static::$config;
    }

    /**
     * Override the configuration
     */
    public static function withConfig(array $config): void
    {
        static::$config = $config;
    }

    /**
     * For internal use only, for testing purposes
     */
    public static function fake()
    {
        static::$faked = true;
        static::$config = null;
    }

    /**
     * For internal use only, for testing purposes. Unfake the Documentor.
     */
    public static function unfake()
    {
        static::$faked = false;
        static::$config = null;
    }

    /**
     * Convert the relative path to absolute.
     */
    public static function getPath(string $relativePath): string
    {
        return base_path($relativePath);
    }

    /**
     * Get the output file path
     */
    public static function getOutputFile(): string
    {
        return static::getPath(static::readConfig()['output'] ?? 'docwatch_generated.php');
    }

    /**
     * Run all configured directories + parsers and return a summary of the results.
     */
    public function run(): Docs
    {
        $docs = new Docs();
        $docwatchConfig = static::readConfig();

        foreach ($docwatchConfig['directories'] ?? [] as $directory => $data) {
            // Convert relative path to absolute
            $directory = static::getPath($directory);

            foreach ($data['parsers'] ?? [] as $parser => $config) {
                // Disable parser if value is false, e.g: "My\ParserClass::class => false"
                if ($config === false) {
                    continue;
                }

                // Allow passing the parser class name as the value without config, e.g. "My\ParserClass::class,"
                if (is_int($parser)) {
                    $parser = (string) $config;
                    $config = [];
                }

                // Default parser config to empty array if config is set to true, e.g. "My\ParserClass::class => true"
                if ($config === true) {
                    $config = [];
                }

                // Create a new parser instance
                $parser = new $parser();
                /** @var ParserInterface $parser */
                
                // Give it the config
                $parser->withConfig($config);
                
                // Give it the Docs
                $parser->withDocs($docs);

                // Resolve each file in the directory
                foreach (Resolver::getFiles($directory) as $file) {
                    /** @var File $file */
                    // Parse the file and merge the docs
                    $docs->merge($parser->parse($file, $docs));
                }
            }
        }

        foreach ($docwatchConfig['standalones'] ?? [] as $parser => $config) {
            // Disable parser if value is false, e.g: "My\ParserClass::class => false"
            if ($config === false) {
                continue;
            }

            // Allow passing the parser class name as the value without config, e.g. "My\ParserClass::class,"
            if (is_int($parser)) {
                $parser = (string) $config;
                $config = [];
            }

            // Default parser config to empty array if config is set to true, e.g. "My\ParserClass::class => true"
            if ($config === true) {
                $config = [];
            }

            if (isset($config[0]) && is_array($config[0])) {
                $config = $config;
            } else {
                $config = [
                    $config,
                ];
            }

            foreach ($config as $ruleConfig) {
                // Create a new parser instance
                $parser = new $parser();
                /** @var ParserInterface $parser */
                
                // Give it the config
                $parser->withConfig($ruleConfig);
                
                // Give it the Docs
                $parser->withDocs($docs);
    
                // Parse the file and merge the docs
                $docs->merge($parser->standalone($docs));
            }
        }

        return $docs;
    }

    /**
     * Generate and write to output file
     */
    public static function generate(): Docs
    {
        $docs = (new static())->run()->trim();
        $path = static::getOutputFile();

        // Create the directory if it doesn't exist
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, "<?php\n\n" . (string) $docs);

        return $docs;
    }
}