<?php

namespace App\Console\Traits;

use Illuminate\Support\Facades\Log;

trait HasLogger
{
    private \Psr\Log\LoggerInterface $log;

    private function makeLogger(string $path)
    {
        $this->log = Log::build([
            'driver' => 'single',
            'path' => storage_path($path),
        ]);
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

        $this->log->info($string);
    }
}