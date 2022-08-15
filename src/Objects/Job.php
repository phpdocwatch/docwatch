<?php

namespace DocWatch\Objects;

use DocWatch\Generator;
use Illuminate\Support\Collection;
use ReflectionClass;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionParameter;

class Job extends AbstractObject
{
    public Collection $args;

    public bool $hasDispatchMethods;

    public bool $hasDispatchMethodsExtended;

    public function __construct(public ReflectionClass $job, public string $namespace, public string $path)
    {
        $this->load();
    }

    /**
     * Given a .php file path, create a Model instance. Validate the namespace exists, is instantiable, etc.
     *
     * @param string $path
     * @return static|null
     */
    public static function createFromPath(string $path): ?static
    {
        // Extract Namespace
        $namespace = Generator::extractFullNamespace($path);

        // Ignore if the namespace couldn't be extracted
        if ($namespace === null) {
            return null;
        }

        // Ignore if it doesn't exist (which it should, but still check)
        if (class_exists($namespace) === false) {
            return null;
        }

        // Ignore if it is an abstract class
        $reflection = new ReflectionClass($namespace);
        if ($reflection->isAbstract()) {
            return null;
        }

        // Ignore if there's no construct
        if ($reflection->hasMethod('__construct') === false) {
            return null;
        }

        // Return self
        return new static($reflection, $namespace, $path);
    }

    /**
     * Load in further information
     *
     * @return void
     */
    public function load()
    {
        try {
            $constructor = new ReflectionMethod($this->namespace, '__construct');
        } catch (\Exception $e) {
            // ?
        }

        $this->args = collect($constructor->getParameters())
            ->map(fn (ReflectionParameter $param) => Argument::fromParameter($param));

        $this->hasDispatchMethods = in_array(
            \Illuminate\Foundation\Jobs\Dispatchable::class,
            $traits = class_uses_recursive($this->namespace)
        );

        $this->hasDispatchMethodsExtended = in_array(
            \Illuminate\Foundation\Bus\Dispatchable::class,
            $traits,
        );
    }

    /**
     * Get the scope's arguments in string format (e.g. "int $var1, string $var2 = null")
     *
     * @return string
     */
    public function argsString(): string
    {
        return $this->args->map(fn (Argument $arg) => (string) $arg)->implode(', ');
    }

    /**
     * Generate the model's docblocks
     *
     * @return string
     */
    public function compile(): string
    {
        $namespace = Str::beforeLast($this->namespace, '\\');
        $name = Str::afterLast($this->namespace, '\\');

        $doc = [
            "namespace {$namespace};",
            '/**'
        ];

        $argsString = $this->argsString();

        if ($this->hasDispatchMethods) {
            $doc[] = " * @method static static dispatch({$argsString})";
            $doc[] = " * @method static static dispatchIf(\$boolean, {$argsString})";
            $doc[] = " * @method static static dispatchUnless(\$boolean, {$argsString})";
        }

        if ($this->hasDispatchMethodsExtended) {
            $doc[] = " * @method static static dispatchSync({$argsString})";
            $doc[] = " * @method static static dispatchNow({$argsString})";
            $doc[] = " * @method static static dispatchAfterResponse({$argsString})";
        }

        $doc[] = '*/';
        $doc[] = "class {$name} {}";

        return implode("\n", $doc);
    }
}
