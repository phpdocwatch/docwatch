<?php

namespace DocWatch\Objects;

class Column extends AbstractObject
{
    public Typehint $type;

    public function __construct(public Model $parent, public string $name, public string $dbtype, public bool $nullable = false)
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
        // If the model has a cast that'll be the most accurate form of typehint (barring nullable-ness)
        // Otherwise guess the type based on the string
        $this->type = Typehint::guessType(
            $this->parent->model->getCasts()[$this->name] ?? $this->dbtype,
            $this->nullable
        );
    }

    /**
     * Generate the docblock line for this column field
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
