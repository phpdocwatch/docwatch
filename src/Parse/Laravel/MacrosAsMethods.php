<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use DocWatch\DocWatch\Block\MethodDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Argument;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use Illuminate\Support\Arr;
use ReflectionClass;
use Illuminate\Support\Str;

/**
 * @requires Laravel
 */
class MacrosAsMethods implements ParseInterface
{
    protected array $imports = [];

    protected ?string $rootNamespace = null;

    protected array $macroable = [];

    /**
     * Parse the given class for relation methods and convert them to docblock properties
     *
     * @param Docs $docs
     * @param WriterInterface $writer
     * @param ReflectionClass $class
     * @param array $config
     * @return boolean
     */
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        $method = $config['method'] ?? 'macro';
        $regex = static::regex($method);
        $this->macroable = Arr::wrap($config['macroable'] ?? \Illuminate\Support\Traits\Macroable::class);
        $this->rootNamespace = $class->getNamespaceName();

        // Extract all full namespaces from the class
        // Read file contents line by line
        $file = fopen($class->getFileName(), 'r');
        $this->imports = [];

        while (($line = fgets($file)) !== false) {
            // If the line starts with a use statement, extract the full namespace
            if (preg_match('/^use ([a-z0-9\\\\_]+)/i', $line, $m)) {
                // Get after last backslash
                $name = substr($m[1], strrpos($m[1], '\\') + 1);

                $this->imports[$name] = $m[1];
            }

            // if the line opens a class definition, break
            if (preg_match('/^(?:abstract|final|readonly)?\s*class\s+([a-zA-Z0-9_]+)/i', $line, $matches)) {
                break;
            }
        }

        fclose($file);

        // Read the entire file
        $contents = file_get_contents($class->getFileName());
        preg_match_all($regex, $contents, $m);
        $all = collect();

        foreach (array_keys($m[1] ?? []) as $macroIndex) {
            $item = [
                'class' => $this->guessNamespaceOfClass($m[1][$macroIndex]),
                'method' => $m[2][$macroIndex],
                'args' => collect(explode(', ', $m[3][$macroIndex]))
                    ->map(fn (string $arg) => $this->parseArguments($arg)),
                'return' => $this->parseType($m[4][$macroIndex]),
            ];

            // Does the class have the macroable trait?
            if (!$this->hasMacroableTrait($item['class'])) {
                continue;
            }

            $docs->addDocblock(
                $item['class'],
                new DocblockTag(
                    'method',
                    $item['method'],
                    new MethodDocblock(
                        name: $item['method'],
                        args: collect($item['args'])
                            ->map(fn (array $arg) => new Argument(
                                name: $arg['name'],
                                type: $arg['type'],
                                default: $arg['default'] ?? null,
                                variadic: $arg['variadic'] ?? false,
                            ))
                            ->all(),
                        returnType: $item['return'],
                        modifiers: 'static',
                    ),
                ),
            );

            $all[] = $item;
        }

        return !empty($all);
    }

    public function guessNamespaceOfClass(string $class): string
    {
        // If the class starts with a backslash, it's a fully qualified class name
        if ((substr($class, 0, 1) === '\\')) {
            return $class;
        }

        // Otherwise check to see if the class was imported, if so, use it
        if (isset($this->imports[$class])) {
            return $this->imports[$class];
        }

        return $this->rootNamespace . '\\' . $class;
    }

    public function parseArguments(string $argument)
    {
        // typehint is before the dollar symbol
        $typehint = trim(Str::before($argument, '$'));
        $argument = Str::after($argument, '$');

        // Is this argument variadic?
        $variadic = Str::endsWith($argument, '...');
        $argument = Str::before($argument, '...');

        // Clean up typehint
        $typehint = empty($typehint) ? null : $this->parseType($typehint);

        // name is before space
        $name = Str::before($argument, ' ');
        $argument = Str::after($argument, ' ');

        // Default value is after equals sign
        $default = Str::contains($argument, '=', true) ? Str::after($argument, '=') : null;

        return [
            'type' => $typehint,
            'name' => $name,
            'default' => $default,
            'variadic' => $variadic,
        ];
    }

    public function parseType(string|null $type = null): ?Typehint
    {
        if ($type === null) {
            return null;
        }

        $primitive = [
            'string',
            'integer',
            'int',
            'bool',
            'array',
            'object',
            'stdClass',
            'float',
            'double',
            'resource',
            'callable',
            'void',
            'null',
            'mixed',
            'iterable',
            'false',
            'true',
            'self',
            'static',
        ];

        $nullable = false;
        $parts = collect(explode('|', $type))
            ->map(function (string $part) use ($primitive, &$nullable) {
                if (Str::startsWith($part, '?')) {
                    $nullable = true;
                    $part = Str::after($part, '?');
                }

                return in_array($part, $primitive) ? $part : $this->guessNamespaceOfClass($part);
            })
            ->toArray();

        if ($nullable) {
            $parts[] = 'null';
        }

        return new Typehint($parts);
    }

    public function hasMacroableTrait(string $class)
    {
        $all = class_uses_recursive($class);

        foreach ($this->macroable as $macroable) {
            if (in_array($macroable, $all)) {

                return true;
            }
        }

        return false;
    }

    public static function regex(string $method): string
    {
        return '/([a-z0-9\\_]+)::' . $method . '\([\n\r\s]*[\'"]([^\'"]+)[\'"],\s*(?:fn|function)\s*\(([^\)]*)\):\s([^\n\{\s]+)/i';
    }
}
