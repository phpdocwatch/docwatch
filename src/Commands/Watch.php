<?php

namespace DocWatch\DocWatch\Commands;

use DocWatch\DocWatch\Generator;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * @requires Laravel
 */
class Watch extends Command
{
    public $signature = 'docwatch:watch';

    public function handle()
    {
        $config = Generator::config();
        $config['artisan'] = base_path('artisan');

        $watchJs = base_path('vendor/docwatch/docwatch/watch.js');
        $watchJson = base_path('vendor/docwatch/docwatch/watch.json');

        file_put_contents($watchJson, json_encode($config));

        $process = new Process(['node', $watchJs], timeout: 86400);
        $process->setTty(true);

        $process->run(function ($type, $buffer) {
            $buffer = trim($buffer);
            if (empty($buffer)) {
                return;
            }

            if ($type === Process::ERR) {
                $this->warn($buffer);
            } else {
                $this->line($buffer);
            }
        });
    }
}
