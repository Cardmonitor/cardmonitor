<?php

namespace App\Console\Traits;

use App\Support\BackgroundTasks;

trait HasLogger
{
    private \Psr\Log\LoggerInterface $log;

    private function makeLogger(string $task, string $filename)
    {
        $this->log = BackgroundTasks::makeLogger($task, $filename);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        parent::line($string, $style, $verbosity);

        switch ($style) {
            case 'error': $this->log->error($string); break;
            default: $this->log->info($string); break;
        }

    }
}