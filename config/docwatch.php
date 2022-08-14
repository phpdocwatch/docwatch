<?php

return [
    /**
     * A list of directories that should be scanned when the generator is used.
     * 
     * Default (if none is supplied) is 'app/Models'
     * 
     * @var array<string>|string
     */
    'directories' => [
        'app/Models',
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
     */
    'timezone' => 'UTC',
];
