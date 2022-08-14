<?php

namespace DocWatch\Objects;

use Closure;
use Illuminate\Database\Eloquent\Casts\Attribute;
use ReflectionFunction;
use ReflectionMethod;

class Accessor extends AbstractObject
{
    public Typehint|string|null $type;

    public function __construct(public Model $parent, public string $method, public string $name)
    {
        $this->load();
    }

    /**
     * Load in more information
     *
     * @return void
     */
    public function load()
    {
        $this->type = static::getAccessorReturnType($this->parent, $this->method) ?? Typehint::mixed();
    }

    /**
     * Determine the return type of the accessor method, if available
     *
     * @param DocumentorModel $model
     * @param string $method
     * @return Typehint|string|null
     */
    public static function getAccessorReturnType(Model $model, string $method)
    {
        $reflect = new ReflectionMethod($model->model, $method);

        // Inherit original $type if it's an old school getter
        if (preg_match('/^get(.+)Attribute$/', $method)) {
            return Typehint::guessType($reflect->getReturnType());
        }

        // Get the Attribute class (not the attribute's resolved value)
        /** @var Attribute $attribute */
        $attribute = $model->model->{$method}();

        // Extract the getter callback
        $callback = $attribute->get;

        if ($callback instanceof Closure) {
            $reflect = new ReflectionFunction($callback);

            return Typehint::guessType($reflect->getReturnType());
        }

        return Typehint::mixed();
    }

    /**
     * Generate the docblock line for this accessor
     *
     * @return string
     */
    public function compile(): string
    {
        return <<<EOL
 * @property {$this->type} \${$this->name}
EOL;
    }
}
