<?php

namespace DocWatch\Commands;

use Illuminate\Console\Command;
use DocWatch\Generator;
use DocWatch\Objects\Model;

class Info extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docwatch:info {directories?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get information for all your models';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $directories = $this->argument('directories');
        if ($directories !== null) {
            $directories = explode(',', $directories);
        }

        // Get the docblock models
        Generator::instance()
            ->directories($directories)
            ->models()
            ->sortBy(fn (Model $model) => $model->namespace)
            ->each(function (Model $model) {
                $this->line('');
                $this->line('');
                $this->line('');
                $this->info($model->namespace);
                $this->line('');

                $rows = [];

                foreach ($model->columns ?? [] as $column) {
                    $rows[] = [
                        'DB Column',
                        $column->name,
                        $column->type,
                    ];
                }

                foreach ($model->accessors ?? [] as $accessor) {
                    $rows[] = [
                        'Accessor',
                        $accessor->name,
                        $accessor->type,
                    ];
                }

                foreach ($model->relations ?? [] as $relation) {
                    $rows[] = [
                        'Relation',
                        $relation->name,
                        $relation->type,
                    ];
                }

                foreach ($model->scopes ?? [] as $scope) {
                    $rows[] = [
                        'Scope',
                        $scope->name . "({$scope->argsString()})",
                        $scope->type,
                    ];
                }

                $this->table([
                    'Type',
                    'Name',
                    'Return',
                ], $rows);
            });
    }
}
