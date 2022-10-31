<?php

use App\Models\Brand;
use App\Models\Product;
use DocWatch\Argument;
use DocWatch\ArgumentList;
use DocWatch\ReflectionParameterItem;
use DocWatch\SomeEnum;
use DocWatch\TypeMultiple;
use DocWatch\TypeSingle;
use DocWatch\VariableString;

test('a typestring can be created from a single-type string', function () {
    $typestring1 = TypeMultiple::parse('integer');
    $typestring2 = TypeMultiple::parse(Product::class);

    expect($typestring1)->toBeInstanceOf(TypeSingle::class);
    expect($typestring1->type)->toBe('integer');

    expect($typestring2)->toBeInstanceOf(TypeSingle::class);
    expect($typestring2->type)->toBe(Product::class);
});

test('a typestring can be created from an array', function () {
    $typestring1 = TypeMultiple::parse(['integer', 'string']);
    $typestring2 = TypeMultiple::parse([Product::class, Brand::class]);

    expect($typestring1)->toBeInstanceOf(TypeMultiple::class);
    expect($typestring1->toArray())->toBe([
        'or' => [
            [ 'type' => 'integer' ],
            [ 'type' => 'string' ],
        ],
    ]);

    expect($typestring2)->toBeInstanceOf(TypeMultiple::class);
    expect($typestring2->toArray())->toBe([
        'or' => [
            [ 'type' => Product::class ],
            [ 'type' => Brand::class ],
        ]
    ]);

    $typestring2 = TypeMultiple::parse([Product::class, Brand::class], union: false);

    expect($typestring2)->toBeInstanceOf(TypeMultiple::class);
    expect($typestring2->toArray())->toBe([
        'and' => [
            [ 'type' => Product::class ],
            [ 'type' => Brand::class ],
        ]
    ]);
});

test('a typestring can be created from a reflection parameter', function () {
    require_once __DIR__ . '/fixtures/ReflectionParameterItem.php';

    $class = new ReflectionClass(ReflectionParameterItem::class);
    $method = $class->getMethod('method');
    
    $args = array_map(
        fn (ReflectionParameter $param) => TypeMultiple::parse($param)->toArray(),
        $method->getParameters(),
    );

    expect($args)->toBe([
        // string $basic
        [
            'type' => 'string',
        ],
        // Stringable&JsonSerializable $nonUnion
        [
            'and' => [
                [ 'type' => Stringable::class ],
                [ 'type' => JsonSerializable::class ],
            ],
        ],
        // ?int $nullable = null
        [
            'or' => [
                [ 'type' => 'int' ],
                [ 'type' => 'null' ],
            ],
        ],
        // SomeEnum $enum
        [
            'type' => SomeEnum::class,
        ],
        // string|int $union = 'default'
        [
            'or' => [
                [ 'type' => 'string' ],
                [ 'type' => 'int' ],
            ],
        ],
    ]);
});

it('can form an argument list', function () {
    $class = new ReflectionClass(ReflectionParameterItem::class);
    $method = $class->getMethod('method');

    $args = new ArgumentList(array_map(
        fn (ReflectionParameter $param) => Argument::parse($param),
        $method->getParameters(),
    ));

    expect((string) $args)->toBe('string $basic, \Stringable&\JsonSerializable $nonUnion, int|null $nullable = null, \DocWatch\SomeEnum $enum = \DocWatch\SomeEnum::AA, string|int $union = "default"');
});

it('can form a variable string', function () {
    expect((string) VariableString::parse(null))->toBe('null');
    expect((string) VariableString::parse(true))->toBe('true');
    expect((string) VariableString::parse('Test String'))->toBe('"Test String"');
    expect((string) VariableString::parse(SomeEnum::AA))->toBe('\DocWatch\SomeEnum::AA');
    expect((string) VariableString::parse([ 'a' => 'b', ]))->toBe('["a" => "b"]');
});