<?php

namespace DocWatch;

class Resolver
{
    public static $cache = [];

    public static function getFiles(string $directory): array
    {
        if (!isset(static::$cache[$directory])) {
            $files = is_dir($directory) ? scandir($directory) : [];

            // ignore non-php files
            $files = array_filter($files, fn (string $file) => substr($file, -4) === '.php');

            // Convert to absolute paths
            $files = array_map(fn (string $file) => $directory . '/' . $file, $files);

            // Convert to File instances
            $files = array_map(fn (string $file) => new File($file, static::getNamespace($file)), $files);

            $directories = array_map(
                fn (string $file) => $directory . '/' . $file,
                array_filter(
                    is_dir($directory) ? scandir($directory) : [],
                    fn (string $file) => is_dir($directory . '/' . $file) && $file !== '.' && $file !== '..',
                ),
            );

            foreach ($directories as $directory) {
                foreach (static::getFiles($directory) as $file) {
                    $files[] = $file;
                }
            }

            // Cache
            static::$cache[$directory] = array_values($files);
        }

        return static::$cache[$directory];
    }

    /**
     * Resolve the PHP class namespace from the file path
     */
    public static function getNamespace(string $path): string
    {
        $namespace = null;
        $class = basename($path, '.php');
        $file = fopen($path, 'r');

        while ($line = fgets($file, 1000)) {
            if (preg_match('/^namespace\s+(.+);/', $line, $matches)) {
                $namespace = $matches[1];
                break;
            }
        }

        fclose($file);

        return trim($namespace . '\\' . $class, '\\');
    }

    public static function getModels(string $directory): array
    {
        return array_values(array_filter(static::getFiles($directory), fn (File $file) => $file->isModel()));
    }
}