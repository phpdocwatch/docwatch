<?php

use DocWatch\Argument;
use DocWatch\ArgumentList;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use DocWatch\VariableString;

test('can create a doc type for a standard property', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'property',
        name: 'name',
        schemaType: TypeMultiple::parse('string'),
        schemaDefault: VariableString::parse('John Doe'),
        description: 'The name of the user',
    );

    expect($doc->compile())->toBe('@property string $name = "John Doe" // The name of the user');
});

test('can create a doc type for a nullable property', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'property',
        name: 'name',
        schemaType: TypeMultiple::parse(['string', 'null']),
        schemaDefault: VariableString::parse(null),
        description: 'The name of the user',
    );

    expect($doc->compile())->toBe('@property string|null $name = null // The name of the user');
});

test('can create a doc type for a class property', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'property',
        name: 'user',
        schemaType: TypeMultiple::parse('App\Models\User'),
        description: 'The user',
    );

    expect($doc->compile())->toBe('@property \App\Models\User $user // The user');
});


test('can create a doc type for an enum property', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'property',
        name: 'thing',
        schemaType: TypeMultiple::parse('DocWatch\SomeEnum'),
        schemaDefault: VariableString::parse(\DocWatch\SomeEnum::BB),
    );

    expect($doc->compile())->toBe('@property \DocWatch\SomeEnum $thing = \DocWatch\SomeEnum::BB');
});

test('can create a doc type for an array property', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'property',
        name: 'things',
        schemaType: TypeMultiple::parse('array'),
        schemaDefault: VariableString::parse(['a', 'b', 'c']),
    );

    expect($doc->compile())->toBe('@property array $things = ["a", "b", "c"]');
});

test('can create a doc type for a standard method', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getName',
        schemaReturn: TypeMultiple::parse('string'),
        description: 'Get the name of the user',
    );

    expect($doc->compile())->toBe('@method string getName() // Get the name of the user');
});

test('can create a doc type for a nullable method', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getName',
        schemaReturn: TypeMultiple::parse(['string', 'null']),
        description: 'Get the name of the user',
    );

    expect($doc->compile())->toBe('@method string|null getName() // Get the name of the user');
});

test('can create a doc type for a class method', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getUser',
        schemaReturn: TypeMultiple::parse('App\Models\User'),
        description: 'Get the user',
    );

    expect($doc->compile())->toBe('@method \App\Models\User getUser() // Get the user');
});

test('can create a doc type for an enum method', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getThing',
        schemaReturn: TypeMultiple::parse('DocWatch\SomeEnum'),
    );

    expect($doc->compile())->toBe('@method \DocWatch\SomeEnum getThing()');
});

test('can create a doc type for an array method', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getThings',
        schemaReturn: TypeMultiple::parse('array'),
    );

    expect($doc->compile())->toBe('@method array getThings()');
});

test('can create a doc type for a method with arguments', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getThings',
        schemaReturn: TypeMultiple::parse('array'),
        schemaArgs: new ArgumentList([
            new Argument(
                'user',
                TypeMultiple::parse('App\Models\User'),
                VariableString::parse(null),
            ),
            new Argument(
                'enum',
                TypeMultiple::parse(\DocWatch\SomeEnum::class),
                VariableString::parse(\DocWatch\SomeEnum::BB),
            ),
        ]),
    );

    expect($doc->compile())->toBe('@method array getThings(\App\Models\User $user = null, \DocWatch\SomeEnum $enum = \DocWatch\SomeEnum::BB)');
});

test('can create a doc type for a method with a variadic argument', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getThings',
        schemaReturn: TypeMultiple::parse('array'),
        schemaArgs: new ArgumentList([
            new Argument(
                'things',
                null,
                VariableString::parse(null),
                variadic: true,
            ),
        ]),
    );

    expect($doc->compile())->toBe('@method array getThings(...$things)');
});

test('can create a doc type for a method with a referenced argument', function () {
    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'getThings',
        schemaReturn: TypeMultiple::parse('array'),
        schemaArgs: new ArgumentList([
            new Argument(
                'things',
                TypeMultiple::parse('string'),
                reference: true,
            ),
        ]),
    );

    expect($doc->compile())->toBe('@method array getThings(string &$things)');
});

test('can merge docs together', function () {
    $docs = new Docs([
        new Doc(
            namespace: 'App\Models\Brand',
            type: 'method',
            name: '1',
            schemaReturn: TypeMultiple::parse('array'),
            schemaArgs: new ArgumentList([
                new Argument(
                    'things',
                    TypeMultiple::parse('string'),
                    reference: true,
                ),
            ]),
        ),
        new Doc(
            namespace: 'App\Models\Brand',
            type: 'method',
            name: 'b',
            schemaReturn: TypeMultiple::parse('array'),
            schemaArgs: new ArgumentList([
                new Argument(
                    'things',
                    null,
                    VariableString::parse(null),
                    variadic: true,
                ),
            ]),
        ),
    ]);

    $docs2 = new Docs([
        new Doc(
            namespace: 'App\Models\Brand',
            type: 'method',
            name: 'c',
            schemaReturn: TypeMultiple::parse('array'),
            schemaArgs: new ArgumentList([
                new Argument(
                    'things',
                    null,
                    VariableString::parse(null),
                    variadic: true,
                ),
            ]),
        ),
    ]);

    $docs->merge($docs2);

    expect($docs->items)->toHaveCount(3);

    $doc = new Doc(
        namespace: 'App\Models\Brand',
        type: 'method',
        name: 'd',
        schemaReturn: TypeMultiple::parse('array'),
        schemaArgs: new ArgumentList([
            new Argument(
                'things',
                null,
                VariableString::parse(null),
                variadic: true,
            ),
        ]),
    );

    $docs->merge($doc);

    expect($docs->items)->toHaveCount(4);
});
