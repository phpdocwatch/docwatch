<?php

namespace DocWatch\DocWatch\Reader;

class DefaultReader implements ReaderInterface
{
    /**
     * Scan the given directory/directories for any PHP files and return an array of paths to those PHP files
     *
     * @param iterable|string $directories
     * @param boolean $recursive
     * @return iterable
     */
    public function scanDirectory(iterable|string $directories, bool $recursive = false): iterable
    {
        if (is_string($directories)) {
            $directories = [$directories];
        }

        $all = [];

        foreach ($directories as $directory) {
            $files = scandir($directory);

            foreach ($files as $file) {
                $path = $directory . DIRECTORY_SEPARATOR . $file;

                if (is_dir($path)) {
                    if ($recursive) {
                        $all = array_merge($all, $this->scanDirectory($path, $recursive));
                    }
                } else {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);

                    if ($ext === 'php') {
                        $all[] = $path;
                    }
                }
            }
        }

        return $all;
    }

    /**
     * Resolve the class from the given path (if a class; interfaces, traits and enums are not resolved)
     *
     * @param string $path
     * @return string|null
     */
    public function resolveClass(string $path): ?string
    {
        $f = fopen($path, 'r');

        $namespace = null;
        $class = basename($path, '.php');

        while (($line = fgets($f, 1000)) !== false) {
            if (($namespace === null) && preg_match('/^namespace (.+);$/', $line, $m)) {
                $namespace = $m[1];

                break;
            }

            if (preg_match('/^(?:readonly|abstract|final)?\s*(class|interface|trait|enum) ([^ ]+)/', $line, $m)) {
                if ($m[2] !== 'class') {
                    $class = null; // not a class (trait enum or interface?)
                }

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
     * Determine if the given $class extends the given $extends class
     * Performs an "OR" check.
     *
     * @param string $class
     * @param iterable<string> $extends
     * @return boolean
     */
    public function classExtends(string $class, iterable $extends): bool
    {
        foreach ($extends as $extend) {
            if (is_subclass_of($class, $extend)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given $class implements the given $interface
     * Performs an "OR" check.
     *
     * @param string $class
     * @param iterable<string> $interfaces
     * @return boolean
     */
    public function classImplements(string $class, iterable $interfaces): bool
    {
        foreach ($interfaces as $interface) {
            if (in_array($interface, class_implements($class))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given $class uses the given $trait
     * Performs an "OR" check.
     *
     * @param string $class
     * @param iterable<string> $traits
     * @return boolean
     */
    public function classUses(string $class, iterable $traits): bool
    {
        foreach ($traits as $trait) {
            if (in_array($trait, class_uses($class))) {
                return true;
            }
        }

        return false;
    }
}
