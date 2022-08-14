<?php

namespace DocWatch\Commands;

use Illuminate\Console\Command;
use DocWatch\Generator;
use DocWatch\Objects\Model;
use DocWatch\Objects\ModelQueryBuilder;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docwatch:generate {directories?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate doc blocks for all your models';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Optional override for the list of directories to scan. Defaults to config value
        $directories = $this->argument('directories');
        if ($directories !== null) {
            $directories = explode(',', $directories);
        }

        // Get the docblock models
        $models = Generator::instance()
            ->directories($directories)
            ->models()
            ->sortBy(fn (Model $model) => $model->namespace);

        // Generate the docblocks for the models
        $docs = $models->map(fn (Model $model) => (string) $model);

        if (Generator::useProxiedQueryBuilders()) {
            // Find models with scopes
            $builders = $models->filter(fn (Model $model) => $model->scopes->isNotEmpty())
                ->map(fn (Model $model) => new ModelQueryBuilder($model, $model->scopes));

            // If there are scopes/builders
            if ($builders->isNotEmpty()) {
                $docs = $docs->concat($builders->map(fn (ModelQueryBuilder $builder) => (string) $builder));
            }
        }

        // Write the docblocks
        file_put_contents(Generator::outputFile(), "<?php\n" . $docs->implode("\n\n\n"));
    }
}
