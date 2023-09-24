<?php

namespace App\Console\Commands\Expansion\Skryfall;

use App\Models\Expansions\Expansion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class SyncCommand extends Command
{
    protected $signature = 'expansion:skryfall:sync';

    protected $description = 'Sets the Skyrfall Expansion ID for all expansions.';

    public function handle()
    {
        $api = App::make('SkryfallApi');

        $expansions = Expansion::all();
        $expansions_count = count($expansions);
        $updated_count = 0;
        foreach ($expansions as $key => $expansion) {
            // dump('Cardmarket Name: ' . $expansion->name);
            try {
                $skryfallExpansion = $api->set->findByCode($expansion->abbreviation);
                usleep(100000);
            }
            catch (\Exception $exc) {
                continue;
            }
            // dump('Skryfall Name: ' . $skryfallExpansion['name']);
            // dump('Cardmarket Released: ' . $expansion->released_at->format('Y-m-d'), 'Skryfall Released: ' . $skryfallExpansion['released_at']);
            if ($expansion->released_at->format('Y-m-d') != $skryfallExpansion['released_at'] && $expansion->name != $skryfallExpansion['name']) {
                continue;
            }
            $expansion->update([
                'skryfall_expansion_id' => $skryfallExpansion['id'],
                'icon_svg_uri' => $skryfallExpansion['icon_svg_uri'],
            ]);
            $updated_count++;
        }

        dump('Updated: ' . $updated_count . '/' . $expansions_count);
    }
}
