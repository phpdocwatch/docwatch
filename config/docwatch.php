<?php

return [
    'directories' => [
        'app/Models' => [
            'type' => 'model',
            'parsers' => [
                \DocWatch\Parsers\Laravel\AccessorsAsProperties::class => true,
                \DocWatch\Parsers\Laravel\ColumnsAsProperties::class => true,
                \DocWatch\Parsers\Laravel\CastsAsProperties::class => true,
                \DocWatch\Parsers\Laravel\ModelHasFactoryReturnsFactoryClass::class => true,
                \DocWatch\Parsers\Laravel\RelationsAsProperties::class => true,
                \DocWatch\Parsers\Laravel\ScopesAsMethods::class => true,
                \DocWatch\Parsers\Laravel\ModelsExposeQueryBuilderMethods::class => [
                    'customBuildersReturnExactModel' => true,
                ],
            ],
        ],

        'app/Providers' => [
            'type' => 'provider',
            'parsers' => [
                \DocWatch\Parsers\Laravel\MacrosAsMethods::class => true,
            ],
        ],

        'app/Http/Requests' => [
            'type' => 'request',
            'parsers' => [
                \DocWatch\Parsers\Laravel\RequestParametersAsProperties::class => true,
            ]
        ],

        'app/Nova' => [
            'type' => 'resource',
            'parsers' => [
                \DocWatch\Parsers\Laravel\NovaResourceWithModelProperties::class => true,
            ],
        ],
    ],

    'standalones' => [
        \DocWatch\Parsers\Laravel\ModelsExposeQueryBuilderMethods::class => true,
        \DocWatch\Parsers\Laravel\FixUserModelResolvers::class => [
            [
                'request' => 'Illuminate\Http\Request',
            ],
            [
                'request' => 'Illuminate\Support\Facades\Auth',
                'static' => true,
            ],
            [
                'request' => 'Illuminate\Support\Facades\Request',
                'static' => true,
            ],
        ],
    ],

    'output' => 'bootstrap/docwatch.php',
];