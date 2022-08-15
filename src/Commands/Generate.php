<?php

namespace DocWatch\Commands;

use Illuminate\Console\Command;
use DocWatch\Generator;
use DocWatch\Objects\AbstractObject;
use DocWatch\Objects\Model;
use DocWatch\Objects\Event;
use DocWatch\Objects\Job;
use DocWatch\Objects\ModelQueryBuilder;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docwatch:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate doc blocks for all your models and events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $separator = "\n\n\n";

        // Get the docblock models
        $models = Generator::instance()->models()->sortBy(fn (Model $model) => $model->namespace);

        // Get the docblock events
        $events = Generator::instance()->events()->sortBy(fn (Event $event) => $event->namespace);

        // Get the docblock jobs
        $jobs = Generator::instance()->jobs()->sortBy(fn (Job $job) => $job->namespace);

        // Get the docblock query builders
        $builders = collect();

        if (Generator::useProxiedQueryBuilders()) {
            // Find models with scopes
            $builders = $models->filter(fn (Model $model) => $model->scopes->isNotEmpty())
                ->map(fn (Model $model) => new ModelQueryBuilder($model, $model->scopes));
        }

        $docs = collect([
            $models,
            $events,
            $jobs,
            $builders,
        ])
            ->collapse()
            ->map(fn (AbstractObject $object) => (string) $object)
            ->implode($separator);

        // Write the docblocks
        file_put_contents(Generator::outputFile(), "<?php\n" . $docs);
    }
}
