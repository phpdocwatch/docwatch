<?php

namespace DocWatch\Parsers\Laravel;

use Illuminate\Support\Str;
use DocWatch\Argument;
use DocWatch\ArgumentList;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\File;
use DocWatch\TypeMultiple;
use DocWatch\VariableString;

/**
 * @requires Laravel
 */
class MacrosAsMethods extends AbstractLaravelParser
{
    protected array $imports = [];

    protected ?string $rootNamespace = null;

    /**
     * Parse all columns from the database via artisan model:show command
     */
    public function parse(File $file): Doc|Docs|null
    {
        $method = $config['method'] ?? 'macro';
        $macroMethodRegex = static::macroMethodRegex($method);
        
        $this->imports = [];
        $this->rootNamespace = $file->reflection()->getNamespaceName();

        $docs = new Docs();

        $file->lines(function (string $line) {
            // If the line starts with a use statement, extract the full namespace
            if (preg_match('/^use ([a-z0-9\\\\_]+)/i', $line, $m)) {
                // Get after last backslash
                $name = substr($m[1], strrpos($m[1], '\\') + 1);

                $this->imports[$name] = $m[1];
            }

            // if the line opens a class definition, break
            if (preg_match('/^(?:abstract|final|readonly)?\s*class\s+([a-zA-Z0-9_]+)/i', $line, $matches)) {
                return false;
            }
        });

        // Read the entire file
        preg_match_all($macroMethodRegex, $file->contents(), $m);

        foreach (array_keys($m[1] ?? []) as $macroIndex) {
            $class = $this->guessNamespaceOfClass($m[1][$macroIndex]);

            // Get the name of the macro'd method
            $name = $m[2][$macroIndex];

            // Get the return type of the macro'd method
            $returnType = $m[4][$macroIndex] ?? '';

            if (!empty($returnType)) {
                $nullable = (substr($returnType, 0, 1) === '?');
                $returnType = ltrim($returnType, '?');
                $returnType = $this->guessNamespaceOfClass($returnType);
                $returnType = (array) $returnType;
    
                if ($nullable) {
                    $returnType[] = 'null';
                }
            }

            $return = (empty($returnType)) ? null : TypeMultiple::parse($returnType);

            // Get the args of the macro'd method
            $args = array_map(
                fn (string $arg) => $this->parseArgumentString($arg),
                explode(', ', $m[3][$macroIndex]),
            );
            $args = new ArgumentList($args);

            $docs->push(
                new Doc(
                    $class,
                    'method',
                    $name,
                    isStatic: true,
                    schemaArgs: $args,
                    schemaReturn: $return,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
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

    public function parseArgumentString(string $argument): Argument
    {
        // Default reference is false
        $reference = false;

        // typehint is before the dollar symbol
        $typehint = trim(Str::before($argument, '$'));

        // If the typehint is a reference, remove the ampersand and mark it as a reference
        $reference = Str::endsWith($typehint, '&');
        $typehint = trim(Str::before($typehint, '&'));

        $argument = Str::after($argument, '$');

        // Is this argument variadic?
        $variadic = Str::endsWith($argument, '...');
        $argument = Str::before($argument, '...');

        // Clean up typehint
        $typehint = empty($typehint) ? null : TypeMultiple::parse($typehint);

        // name is before space
        $name = Str::before($argument, ' ');
        $argument = Str::after($argument, ' ');

        // Default value is after equals sign
        $default = Str::contains($argument, '=', true) ? VariableString::parse(Str::after($argument, '=')) : null;

        return new Argument(
            name: $name,
            type: $typehint,
            default: $default,
            variadic: $variadic,
            reference: $reference,
        );
    }

    public static function macroMethodRegex(string $method): string
    {
        return '/([a-z0-9\\_]+)::' . $method . '\([\n\r\s]*[\'"]([^\'"]+)[\'"],\s*(?:fn|function)\s*\(([^\)]*)\)(?::\s([^\n\{\s]+))?/i';
    }
}
