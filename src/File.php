<?php

namespace DocWatch;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class File
{
    public ?ReflectionClass $reflection = null;

    public function __construct(
        public string $path,
        public string $namespace,
    ) {
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'namespace' => $this->namespace,
        ];
    }

    /**
     * Does this class exist?
     */
    public function exists(): bool
    {
        return class_exists($this->namespace);
    }

    /**
     * Is this file an Eloquent Model
     *
     * @requires Laravel
     */
    public function isModel(): bool
    {
        return $this->isSubclassOf(\Illuminate\Database\Eloquent\Model::class);
    }

    /**
     * Get the class's ReflectionClass
     */
    public function reflection(): ReflectionClass
    {
        return $this->reflection ??= new ReflectionClass($this->namespace);
    }

    /**
     * Is the class a trait?
     */
    public function isTrait(): bool
    {
        return $this->reflection()->isTrait();
    }

    /**
     * Is the class an interface?
     */
    public function isInterface(): bool
    {
        return $this->reflection()->isInterface();
    }

    /**
     * Is the class an abstract class?
     */
    public function isAbstract(): bool
    {
        return $this->reflection()->isAbstract();
    }

    /**
     * Get all methods from this class
     *
     * @return array<ReflectionMethod>
     */
    public function methods(int|null $filter = null): array
    {
        return $this->reflection()->getMethods($filter);
    }

    /**
     * Get all properties from this class
     *
     * @return array<ReflectionProperty>
     */
    public function properties(int|null $filter = null): array
    {
        return $this->reflection()->getProperties($filter);
    }

    /**
     * Get a class instance of the file
     */
    public function instance()
    {
        $namespace = $this->namespace;

        return new $namespace;
    }

    /**
     * Read each line by line
     */
    public function lines(Closure $callback): void
    {
        $file = fopen($this->path, 'r');

        while (($line = fgets($file)) !== false) {
            $response = $callback($line);

            if ($response === false) {
                break;
            }
        }

        fclose($file);
    }

    /**
     * Get the contents of this file
     */
    public function contents(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Is this file class a subclass of the given class?
     */
    public function isSubclassOf(string $class): bool
    {
        return is_subclass_of($this->namespace, $class);
    }

    /**
     * Does this file class use the given trait?
     */
    public function hasTrait(string $trait): bool
    {
        return in_array($trait, $this->reflection()->getTraitNames());
    }

    public function name(): string
    {
        return $this->reflection()->getShortName();
    }

    public function namespaceName(): string
    {
        return $this->reflection()->getNamespaceName();
    }

    /**
     * Get the reflection method for the given method name
     * 
     * @throws ReflectionException
     */
    public function method(string $method): ReflectionMethod
    {
        return $this->reflection()->getMethod($method);
    }
}