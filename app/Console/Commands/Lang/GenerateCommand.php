<?php

namespace App\Console\Commands\Lang;

use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    protected $signature = 'lang:generate';

    protected $description = 'Generate i18n json assets';

    public function handle()
    {
        $dirs = new \DirectoryIterator(resource_path('lang').'/');

        foreach ($dirs as $dir) {
            if (! $dir->isDir()) {
                continue;
            }

            $lang = $dir->getFilename();
            if ($lang == '.' || $lang == '..') {
                continue;
            }

            $this->call('lang:js', [
                '--json' => true,
                '--source' => $dir->getPathname(),
                'target' => 'public/js/langs/'.$lang.'.json',
            ]);
        }
    }
}
