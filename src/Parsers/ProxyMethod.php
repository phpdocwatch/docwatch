<?php

namespace DocWatch\Parsers;

use DocWatch\ArgumentList;
use DocWatch\Doc;
use DocWatch\File;
use DocWatch\TypeMultiple;
use ReflectionParameter;

class ProxyMethod extends AbstractParser
{
    public function parse(File $file): Doc|null
    {
        $doc = null;

        /** @var array<string,string|array> $proxies */
        $proxies = $this->get('proxy', []);

        foreach ($file->methods() as $method) {
            $proxy = $proxies[$method->getName()] ?? [];
            $exclude = $proxy['exclude'] ?? [];
            $cloneTo = $proxy['to'] ?? $proxy;
            $cloneTo = is_array($cloneTo) ? $cloneTo : [$cloneTo];

            foreach ($cloneTo as $to) {
                $toReflection = null;
                try {
                    $toReflection = new \ReflectionMethod($file->namespace, $to);
                } catch (\ReflectionException) {
                    // method doesn't exist, probably __call magic
                }

                $params = $method->getParameters();

                if (!empty($exclude)) {
                    $params = array_filter(
                        $params,
                        fn (ReflectionParameter $param) => !in_array($param->getName(), $exclude),
                    );                    
                }


                $doc = new Doc(
                    $file->namespace,
                    'method',
                    $to,
                    isStatic: ($toReflection) ? $toReflection->isStatic() : false,
                    schemaReturn: ($toReflection) ? TypeMultiple::parse($toReflection->getReturnType()) : null,
                    schemaArgs: ArgumentList::parse($params),
                );
            }
        }

        return $doc;
    }
}