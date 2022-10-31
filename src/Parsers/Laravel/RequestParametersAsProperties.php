<?php

namespace DocWatch\Parsers\Laravel;

use Illuminate\Foundation\Http\FormRequest;
use DocWatch\File;
use DocWatch\Doc;
use DocWatch\Docs;
use DocWatch\TypeMultiple;
use DocWatch\TypeSingle;
use ReflectionProperty;

class RequestParametersAsProperties extends AbstractLaravelParser
{
    /**
     * Base configuration
     */
    public array $config = [
        'class' => FormRequest::class,
    ];

    /**
     * Parse all relations via artisan model:show command
     */
    public function parse(File $file): Doc|Docs|null
    {
        $class = $this->get('class');

        if ($file->isSubclassOf($class) === false) {
            return null;
        }
        
        $docs = new Docs();
        /** @var FormRequest|mixed $instance */
        $instance = $file->instance();
        $real = array_map(
            fn (ReflectionProperty $property) => $property->getName(),
            $file->properties(),
        );

        try {
            $rules = $instance->rules();
        } catch (\Throwable $e) {
            // Probably a request that interacts with the request object, database or something else
            $rules = [];
        }

        foreach ($rules as $field => $rules) {
            if (in_array($field, $real)) {
                continue;
            }

            // nested not yet supported
            if (str_contains($field, '.')) {
                continue;
            }

            $type = $this->getFormRuleType($rules);

            $docs->push(
                new Doc(
                    $file->namespace,
                    'property',
                    $field,
                    schemaType: $type,
                    description: $this->viaDescription(),
                ),
            );
        }

        return $docs->orNull();
    }

    public function getFormRuleType($rules): TypeSingle|TypeMultiple
    {
        $rules = is_array($rules) ? $rules : (is_object($rules) ? [$rules] : explode('|', $rules));
        $type = [];
        $nullable = null;

        $typeMap = [
            'string' => [
                'active_url',
                'alpha',
                'alpha_dash',
                'alpha_numeric',
                'email',
                'before', // date = string
                'string',
                'password',
                'date',
                'date_format',
                'date_equals',
                'datetime',
                'timestamp',
                'ip',
                'ipv4',
                'ipv6',
                'mac_address',
                'timezone',
                'url',
                'uuid',
            ],
            'boolean' => [
                'bool',
                'boolean',
                'accepted',
            ],
            'array' => [
                'array',
            ],
            'float|int' => [
                'numeric',
                'digits',
                'multiple_of',
            ],
            'float' => [
                'decimal',
                'float',
            ],
            'int' => [
                'integer',
                'int',
            ],
            \Illuminate\Http\UploadedFile::class => [
                'file',
                'image',
                'dimensions',
                'mimes',
                'mimetypes',
            ],
        ];

        foreach ($rules as $rule) {
            if (is_object($rule)) {
                $rule = basename(str_replace('\\', DIRECTORY_SEPARATOR, strtolower(get_class($rule))));
            }

            if ($rule === 'required') {
                $nullable = false;
            } elseif ($rule === 'nullable') {
                $nullable = true;
            }

            $rule = preg_replace('/^(doesnt)_/', '', $rule);
            $rule = preg_replace('/_(if|when|unless|or_equal)$/', '', $rule);

            foreach ($typeMap as $matchedType => $rules) {
                if (in_array($rule, $rules)) {
                    foreach (explode('|', $matchedType) as $typeToAdd) {
                        $type[] = $typeToAdd;
                    }
                }
            }
        }

        // the payload is variable hence the ruleset, therefore we cannot be certain it's of the given $type
        $type[] = TypeMultiple::TYPE_MIXED;

        if ($nullable) {
            $type[] = TypeMultiple::TYPE_NULL;
        }

        return TypeMultiple::parse($type);
    }
}