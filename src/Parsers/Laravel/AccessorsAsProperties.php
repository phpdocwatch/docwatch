<?php

namespace DocWatch\Parsers\Laravel;

use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class AccessorsAsProperties extends AbstractLaravelParser
{
    public function parse(File $file): Doc|Docs|null
    {
        $docs = new Docs();
        $case = (string) $this->get('case', 'snake');
        $oldStyle = (bool) $this->get('oldStyle', true);
        $newStyle = (bool) $this->get('newStyle', true);
        $differentiateReadWrite = (bool) $this->get('differentiateReadWrite', false);
        $all = [];

        try {
            /** @var Model|null $model */
            $model = $file->instance();
        } catch (Throwable $e) {
            return null;
        }

        $exclude = [
            'getEnumCastableAttribute',
            'getClassCastableAttribute',
            'setEnumCastableAttribute',
            'setClassCastableAttribute',
        ];

        foreach ($file->methods() as $method) {
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
                            $type = ($type === null) ? null : TypeMultiple::parse($type);
                        } catch (Throwable $e) {
                            // no argument? tsk tsk tsk
                        }
                    } else {
                        $type = TypeMultiple::parse($method->getReturnType());
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
                if (($type = $method->getReturnType()) && $method->isPublic()) {
                    if (($type instanceof ReflectionNamedType) && (Attribute::class === $type->getName())) {
                        // Get the name of the accessor property
                        $property = $name;

                        // Initialise this accessor property
                        $all[$property] ??= [
                            'set' => null,
                            'get' => null,
                        ];
                        
                        try {
                            // Get the Attribute class (not the attribute's resolved value)
                            /** @var Attribute $attribute */
                            $attribute = $model->{$name}();

                            // Is there a getter?
                            if ($callback = $attribute->get) {
                                if ($callback instanceof Closure) {
                                    $reflect = new ReflectionFunction($callback);
                                    $type = $reflect->hasReturnType() ? TypeMultiple::parse($reflect->getReturnType()) : null;

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
                                        $type = ($type === null) ? null : TypeMultiple::parse($type);
                                    } catch (Throwable $e) {
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
                        } catch (\Throwable $e) {
                            //
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
                        $docs->push(
                            new Doc(
                                $file->namespace,
                                'property-' . (($accessorType === 'set') ? 'write' : 'read'),
                                $name,
                                schemaType: $accessorMutator['type'],
                                description: $this->viaDescription() . '::' . $accessorMutator['method'] . '()',
                            ),
                        );
                    }
                }

                continue;
            }

            // Get the accessor (get) or mutator (set) method for this property
            $accessorMutator = $accessorMutators['get'] ?? $accessorMutators['set'];
            if ($accessorMutator !== null) {
                $docs->push(
                    new Doc(
                        $file->namespace,
                        'property',
                        $name,
                        schemaType: $accessorMutator['type'],
                        description: $this->viaDescription() . '::' . $accessorMutator['method'] . '()',
                    ),
                );
            }
        }

        return $docs->orNull();
    }
}