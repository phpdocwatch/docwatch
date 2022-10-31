<?php

namespace DocWatch\Laravel\Commands;

use DocWatch\Documentor;
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
        $config = Documentor::readConfig();

        $config['artisan'] ??= base_path('artisan');
        $config['watchJs'] ??= base_path('vendor/bradietilley/docwatch/watch.js');
        $config['watchJson'] ??= base_path('vendor/bradietilley/docwatch/watch.json');

        file_put_contents($config['watchJson'], json_encode($config));

        $process = new Process(['node', $config['watchJs']], timeout: 86400);
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
