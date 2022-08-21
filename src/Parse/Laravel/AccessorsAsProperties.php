<?php

namespace DocWatch\DocWatch\Parse\Laravel;

use Closure;
use DocWatch\DocWatch\Block\PropertyDocblock;
use DocWatch\DocWatch\DocblockTag;
use DocWatch\DocWatch\Docs;
use DocWatch\DocWatch\Items\Typehint;
use DocWatch\DocWatch\Parse\ParseInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @requires Laravel
 */
class AccessorsAsProperties implements ParseInterface
{
    public function parse(Docs $docs, ReflectionClass $class, array $config): bool
    {
        $case = $config['case'] ?? 'snake';
        $oldStyle = $config['oldStyle'] ?? false;
        $newStyle = $config['newStyle'] ?? false;
        $differentiateReadWrite = $config['differentiateReadWrite'] ?? false;

        /** @var Model|null $model */
        $model = app($class->getName());

        if ($model === null) {
            return false;
        }

        $all = [];
        $count = 0;

        $exclude = [
            'getEnumCastableAttribute',
            'getClassCastableAttribute',
            'setEnumCastableAttribute',
            'setClassCastableAttribute',
        ];

        foreach ($class->getMethods() as $method) {
            $name = $method->getName();

            if (in_array($name, $exclude)) {
                continue;
            }

            /** @var ReflectionMethod $method */
            if ($oldStyle) {
                if (preg_match('/^(set|get)(.+)Attribute$/', $name = $method->getName(), $m)) {
                    // Get the name of the accessor property
                    $property = $m[2];

                    // Initialise this accessor property
                    $all[$property] ??= [
                        'set' => null,
                        'get' => null,
                    ];

                    $type = null;
                    if ($m[1] === 'set') {
                        try {
                            $type = $method->getParameters()[0]->getType();
                            $type = ($type === null) ? null : Typehint::guessType($type);
                        } catch (\Exception $e) {
                            // no argument? tsk tsk tsk
                        }
                    } else {
                        $type = Typehint::guessType($method->getReturnType());
                    }

                    // Set the "get" or "set" version of this
                    $all[$property][$m[1]] = [
                        'name' => $property,
                        'method' => $name,
                        'type' => $type,
                        'from' => 'oldStyle',
                    ];
                }

                // end oldStyle
            }

            if ($newStyle) {
                if ($type = $method->getReturnType()) {
                    if (Attribute::class === $type->getName()) {
                        // Get the name of the accessor property
                        $property = $name;

                        // Initialise this accessor property
                        $all[$property] ??= [
                            'set' => null,
                            'get' => null,
                        ];

                        // Get the Attribute class (not the attribute's resolved value)
                        /** @var Attribute $attribute */
                        $attribute = $model->{$name}();

                        // Is there a getter?
                        if ($callback = $attribute->get) {
                            if ($callback instanceof Closure) {
                                $reflect = new ReflectionFunction($callback);
                                $type = Typehint::guessType($reflect->getReturnType());

                                $all[$property]['get'] = [
                                    'name' => $property,
                                    'method' => $name,
                                    'type' => $type,
                                    'from' => 'newStyle',
                                ];
                            }
                        }

                        if ($callback = $attribute->set) {
                            if ($callback instanceof Closure) {
                                $reflect = new ReflectionFunction($callback);
                                $type = null;

                                try {
                                    $type = $reflect->getParameters()[0]->getType();
                                    $type = ($type === null) ? null : Typehint::guessType($type);
                                } catch (\Exception $e) {
                                    // no argument? tsk tsk tsk
                                }

                                $all[$property]['set'] = [
                                    'name' => $property,
                                    'method' => $method->getName(),
                                    'type' => $type,
                                    'from' => 'newStyle',
                                ];
                            }
                        }
                    }
                }

                // end newStyle
            }

            // endforeach
        }

        foreach ($all as $name => $accessorMutators) {
            // Format the name of the property
            if ($case === 'snake') {
                $name = Str::snake($name);
            } elseif ($case === 'camel') {
                $name = Str::camel($name);
            }

            // Are we differentiating read/write properties?
            if ($differentiateReadWrite) {
                foreach ($accessorMutators as $accessorType => $accessorMutator) {
                    if ($accessorMutator !== null) {
                        // Create the property docblock for the read or write proeprty
                        $docs->addDocblock(
                            $class->getName(),
                            new DocblockTag(
                                'property-' . (($accessorType === 'set') ? 'write' : 'read'),
                                $name,
                                new PropertyDocblock(
                                    $name,
                                    $accessorMutator['type'],
                                    comments: [ 'from:AccesorsAsProperties via ' . $accessorMutator['from'] ],
                                ),
                            ),
                        );

                        $count++;
                    }
                }

                continue;
            }

            // Get the accessor (get) or mutator (set) method for this property
            $accessorMutator = $accessorMutators['get'] ?? $accessorMutators['set'];
            if ($accessorMutator !== null) {
                // Create the property docblock for the property
                $docs->addDocblock(
                    $class->getName(),
                    new DocblockTag(
                        'property',
                        $name,
                        new PropertyDocblock(
                            $name,
                            $accessorMutator['type'],
                            comments: [ 'from:AccesorsAsProperties via ' . $accessorMutator['from'] ],
                        ),
                    ),
                );

                $count++;
            }
        }

        return $count > 0;
    }
}
