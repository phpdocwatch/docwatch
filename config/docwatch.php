<?php

return [
    /**
     * A list of directories to find models that should be scanned when the generator is used.
     *
     * @var array<string>|string
     */
    'modelPaths' => [
        'app/Models',
    ],

    /**
     * A list of directories to find events that should be scanned when the generator is used.
     *
     * @var array<string>|string
     */
    'eventPaths' => [
        'app/Events',
    ],

    /**
     * A list of directories to find jobs that should be scanned when the generator is used.
     *
     * @var array<string>|string
     */
    'jobPaths' => [
        'app/Jobs',
    ],

    /**
     * The output file that stores all doc block definitions.
     *
     * If prefixed with / it will be considered absolute, otherwise relative to base_path()
     *
     * @var string
     */
    'outputFile' => 'bootstrap/docwatch.php',

    /**
     * Should this generator create proxied query builders?
     *
     * By default, intelephense won't be able to understand a model's relation query builder instance,
     * for example: when calling `$category->products()` it only understands a few generic Builder
     * class methods.
     *
     * With this enabled, the return type of the relations will remain as a standard Relation Builder
     * instance, but intelephense will think it's actually ProxiedQueries\{ModelNamespace}\Builder
     * which will provide docblocks to help intelephense understand the available scopes.
     *
     * Don't reference these proxied query builders in your code though, as they don't exist!
     *
     * @var boolean
     */
    'useProxiedQueryBuilders' => true,

    /**
     * This will convert the filemtime of the `outputFile` to the given timezone when the "last
     * generated" timestamp is displayed in the `artisan about` command.
     *
     * @var string
     */
    'timezone' => 'UTC',
];
