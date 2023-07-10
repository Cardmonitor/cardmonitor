<?php

namespace App\Console\Commands\Expansion;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ImportImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expansion:import-images
        {--import : Imports the missing images.}
        {--user= : Imports only the expansions of the given user.}';

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
        $expansions = $this->getExpansions();

        foreach ($expansions as $expansion) {
            $image_path = Storage::disk('public')->path('items/' . $expansion->game_id . '/' . $expansion->id);
            $files = glob($image_path . '/*.jpg');
            $images_count = count($files);

            if ($images_count === $expansion->cards_count) {
                continue;
            }

            $this->line($expansion->id . ' ' . $expansion->name . ' (' . $expansion->abbreviation . '): ' . $images_count . '/' . $expansion->cards_count . ' images');

            if ($this->option('import')) {
                Artisan::call('expansion:import', [
                    'expansion' => $expansion->id,
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function getExpansions(): LazyCollection
    {
        $query = Expansion::withCount('cards');

        if ($this->option('user')) {
            $query->whereHas('cards.articles', function ($query) {
                return $query->where('user_id', $this->option('user'));
            });
        }

        return $query->cursor();
    }
}
