<?php

return [
    /**
     * List of directories to search for classes to create dockblocks for
     *
     * path        - The path to the directory to search
     * type        - The type of class to search for (model, event, job)
     * extends     - Resolved PHP classes must extend one or more of the given class names
     * traits      - Resolved PHP classes must use one or more of the given trait names
     * parsers     - List of extractor rules to run when generating docblocks for the methods and properties
     *
     * @var array<array>
     */
    'rules' => [
        [
            'path' => 'app' . DIRECTORY_SEPARATOR . 'Models',
            'type' => 'model',
            'extends' => \Illuminate\Database\Eloquent\Model::class,
            'parsers' => [
                \DocWatch\DocWatch\Parse\Laravel\DatabaseColumnsAsProperties::class => [
                    'command' => 'db:table',
                ],
                \DocWatch\DocWatch\Parse\Laravel\RelationsAsProperties::class => [
                    'except' => [],
                ],
                \DocWatch\DocWatch\Parse\Laravel\AccessorsAsProperties::class => [
                    'oldStyle' => true,
                    'newStyle' => true,
                    'differentiateReadWrite' => true,
                    'case' => 'snake',
                ],
                \DocWatch\DocWatch\Parse\Laravel\RelationsAsQueryBuilderMethods::class => [],
                \DocWatch\DocWatch\Parse\Laravel\ScopesAsQueryBuilderMethods::class => [],
                \DocWatch\DocWatch\Parse\DocblockPropertyOverrides::class => [
                    'property' => true,
                    'class' => true,
                    'delete' => true,
                ],
                \DocWatch\DocWatch\Parse\DockblockMethodOverrides::class => [
                    'method' => true,
                    'class' => true,
                    'delete' => true,
                ],
            ],
        ],
        [
            'path' => 'app' . DIRECTORY_SEPARATOR . 'Events',
            'type' => 'event',
            'traits' => \Illuminate\Foundation\Events\Dispatchable::class,
            'parsers' => [
                \DocWatch\DocWatch\Parse\CloneArgsFromMethod::class => [
                    'src' => '__construct',
                    'dst' => [
                        'dispatch' => true,
                        'dispatchIf' => '$arguments',
                        'dispatchUnless' => '$arguments',
                    ],
                    'returnFrom' => 'dst',
                ],
                \DocWatch\DocWatch\Parse\DockblockMethodOverrides::class => [
                    'method' => true,
                    'class' => true,
                ],
            ],
        ],
        [
            'path' => 'app' . DIRECTORY_SEPARATOR . 'Jobs',
            'type' => 'job',
            'traits' => \Illuminate\Foundation\Bus\Dispatchable::class,
            'parsers' => [
                \DocWatch\DocWatch\Parse\CloneArgsFromMethod::class => [
                    'src' => '__construct',
                    'dst' => [
                        'dispatch' => true,
                        'dispatchIf' => '$arguments',
                        'dispatchUnless' => '$arguments',
                        'dispatchSync' => true,
                        'dispatchNow' => true,
                        'dispatchAfterResponse' => true,
                    ],
                    'returnFrom' => 'dst',
                ],
                \DocWatch\DocWatch\Parse\DockblockMethodOverrides::class => [
                    'method' => true,
                    'class' => true,
                ],
            ],
        ],
        /**
         * Example custom handler that reads a command's options (\My\Custom\Extractor\ExtractCommandOptions) and
         * extracts the variables to be used as parameters for the command's static constructor.
         *
         *      // Example command signature:
         *      'command-name {user} {--date=} {--message=}'
         *
         *      // The above arguments would then be typehinted to your IDE as:
         *      CommandName::dispatchNow(int $user, string $date = null, string $message = null));
         *
         *      // Then you use it like this:
         *      CommandName::dispatchNow($user->id, $date->format('Y-m-d'), (string) $message);
         */
        [
            'path' => 'app' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands',
            'type' => 'command',
            'extends' => [
                \Illuminate\Console\Command::class,
            ],
            'parsers' => [
                \DocWatch\DocWatch\Parse\Laravel\ExtractCommandOptions::class => [
                    'method' => 'dispatchNow',
                ],
                \DocWatch\DocWatch\Parse\DockblockMethodOverrides::class => [
                    'method' => false,
                    'class' => true,
                ],
            ],
        ],
        [
            'path' => 'app' . DIRECTORY_SEPARATOR . 'Providers',
            'type' => 'macro',
            'extends' => \Illuminate\Support\ServiceProvider::class,
            'parsers' => [
                \DocWatch\DocWatch\Parse\Laravel\MacrosAsMethods::class => [
                    'method' => 'macro',
                    'macroable' => [
                        \Illuminate\Support\Traits\Macroable::class,
                        \Carbon\Traits\Macro::class,
                    ],
                ],
            ],
        ],
    ],

    /**
     * The designated reader interface that the system should use when scanning for PHP files in the
     * given directories. The reader is also responsible for resolving namespaces from the found
     * PHP files as well as determining which classes match the constraints
     *  - Extends - @see `ReaderInterface::classExtends()`
     *  - Implements - @see `ReaderInterface::classImplements()`
     *  - Traits - @see `ReaderInterface::classUses()`
     *
     * @var string
     */
    'reader' => \DocWatch\DocWatch\Reader\DefaultReader::class,

    /**
     * The designated writer interface that the system should use when writing docblocks to the
     * outputFile. The sribe is also responsible for dictating how to format classes, methods,
     * properties and method arguments.
     *
     * @var string
     */
    'writer' => \DocWatch\DocWatch\Writer\DefaultWriter::class,

    /**
     * The designated output file path to write the dockblocks to.
     *
     * If prefixed with a slash it will be treated as an absolute path
     * If not then:
     *  - If Laravel application: this is relative to base_path()
     *  - If not Laravel application: this is two directories up
     *
     * @var string
     */
    'outputFile' => 'bootstrap' . DIRECTORY_SEPARATOR . 'docwatch-generated.php',
];
