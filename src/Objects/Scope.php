<?php

namespace DocWatch\Objects;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ReflectionMethod;
use ReflectionParameter;
use DocWatch\Generator;

class Scope extends AbstractObject
{
    // public Typehint $type;

    public Collection $args;

    /** @var Typehint|null The return type (Builder or custom) */
    public Typehint|null $type;

    public function __construct(public Model $parent, public string $method)
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
        // Convert scopeName => name
        $this->name = lcfirst(preg_replace('/^scope/', '', $this->method));

        $reflect = new ReflectionMethod($this->parent->model, $this->method);

        $this->args = collect($reflect->getParameters())
            ->filter(fn ($arg, $index) => $index !== 0)
            ->map(fn (ReflectionParameter $param) => Argument::fromParameter($param));

        $return = $reflect->getReturnType() ?? Builder::class;
        $this->type = new Typehint($return);

        if (Generator::useProxiedQueryBuilders()) {
            if ((string) $this->type === '\\' . Builder::class) {
                $proxiedQueryBuilder = ModelQueryBuilder::getNamespace($this->parent->model);

                $this->type = new Typehint($proxiedQueryBuilder);
            }
        }
    }

    /**
     * Get the scope's arguments in string format (e.g. "int $var1, string $var2 = null")
     *
     * @return string
     */
    public function argsString(): string
    {
        return $this->args->map(fn (Argument $arg) => (string) $arg)->implode(', ');
    }

    /**
     * Generate the docblock line for this scope within the Model docblock
     *
     * @return string
     */
    public function compile(): string
    {
        return <<<EOL
 * @method static {$this->type} {$this->name}({$this->argsString()})
EOL;
    }

    /**
     * Generate the docblock line for this scope within the Proxied Query Builder docblock
     *
     * @return string
     */
    public function compileForQueryBuilder(): string
    {
        $statics = [
            '\\' . Builder::class,
            '\\' . ModelQueryBuilder::getNamespace($this->parent->model),
        ];
        $type = (string) $this->type;

        // A builder or proxied query builder may as well return "self" instead of a typehint pointing to the same class
        if (in_array($type, $statics)) {
            $type = 'self';
        }

        return <<<EOL
 * @method {$type} {$this->name}({$this->argsString()})
EOL;
    }
}
