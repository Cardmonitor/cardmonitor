<?php

namespace App\Console\Commands\Expansion;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ImportImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:import-images {--import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports all missing images for all cards';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $expansions = Expansion::withCount('cards')->get();

        foreach ($expansions as $expansion ) {
            $image_path = Storage::disk('public')->path('items/' . $expansion->game_id . '/' . $expansion->id);
            $files = glob($image_path . '/*.jpg');
            $images_count = count($files);

            if ($images_count === $expansion->cards_count) {
                continue;
            }

            $this->line($expansion->id . ' ' . $expansion->name . ' (' . $expansion->abbreviation . '): ' . $images_count . '/' . $expansion->cards_count . ' images');

            if ($this->option('import')) {
                Artisan::queue('expansion:import', [
                    'expansion' => $expansion->id,
                ]);
            }
        }
    }
}
