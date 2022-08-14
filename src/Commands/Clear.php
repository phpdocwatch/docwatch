<?php

namespace DocWatch\Commands;

use Illuminate\Console\Command;
use DocWatch\Generator;

class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docwatch:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the docwatch file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = Generator::outputFile();

        if (file_exists($file)) {
            unlink($file);

            $this->info('Docwatch cleared');
        } else {
            $this->warn('Docwatch file doesn\'t exist; if conflicts persist check previous output file locations');
        }
    }
}
