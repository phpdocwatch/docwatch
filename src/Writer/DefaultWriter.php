<?php

namespace DocWatch\DocWatch\Writer;

use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Generator;

class DefaultWriter implements WriterInterface
{
    /** @var resource the fopen resouce */
    protected $file;

    /** @var int Length of prefixed whitespace in each docblock (incls @ definition in length) */
    public $padLength = 30;

    /**
     * Open the file stream for the output file.
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function open(string $path, Docs $docs)
    {
        $this->file = fopen(Generator::getOutputFile(), 'w+');

        fputs($this->file, '<?php' . PHP_EOL);
    }

    /**
     * Write all docs to the previously opened stream.
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function write(string $path, Docs $docs)
    {
        foreach ($docs->container as $namespace => $docblocks) {
            $this->writeClass(
                $namespace,
                $docblocks['class'] ?? [],
                $docblocks['method'] ?? [],
                [
                    ...($docblocks['property'] ?? []),
                    ...($docblocks['property-read'] ?? []),
                    ...($docblocks['property-write'] ?? []),
                ]
            );
        }
    }

    /**
     * Close the previously opened file stream
     *
     * @param string $path
     * @param Docs $docs
     * @return void
     */
    public function close(string $path, Docs $docs)
    {
        if ($this->file) {
            fclose($this->file);
        }
    }

    /**
     * Write a single class's docblocks to the file stream.
     *
     * @param string $namespace
     * @param array $class
     * @param array $methods
     * @param array $properties
     * @return void
     */
    public function writeClass(string $namespace, array $class, array $methods, array $properties)
    {
        // Get the parent namespace and class name
        $classNamespace = substr($namespace, 0, strrpos($namespace, '\\'));
        $className = substr($namespace, strrpos($namespace, '\\') + 1);

        $lines = [
            'namespace ' . $classNamespace . ';',
            '/**',
        ];

        foreach ($methods as $method) {
            /** @var DocblockTag $method */

            if ($this->padLength !== null) {
                $method->padLength = $this->padLength;
            }

            $lines[] = $method->__toString();
        }

        foreach ($properties as $property) {
            /** @var DocblockTag $property */

            if ($this->padLength !== null) {
                $property->padLength = $this->padLength;
            }

            $lines[] = $property->__toString();
        }

        $lines[] = '  */';

        $classLine = implode(' ', array_filter([
            'class',
            $className,
            (isset($class['extends'])) ? 'extends ' . $class['extends'] : null,
            (isset($class['implements'])) ? 'implements ' . implode(', ', $class['implements']) : null,
            '{}',
        ]));

        $lines[] = $classLine;

        fputs($this->file, implode("\n", $lines) . "\n\n\n");
    }
}
