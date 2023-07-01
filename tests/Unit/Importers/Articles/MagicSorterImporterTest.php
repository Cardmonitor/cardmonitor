<?php

namespace Tests\Unit\Importers\Orders;

use Tests\TestCase;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Importers\Articles\MagicSorterImporter;

class MagicSorterImporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_import_articles_from_magic_sorter_csv()
    {
        $this->markTestSkipped('no cardmarket connection.');

        $filepath = 'tests/snapshots/magic-sorter/results_details_20230629-1406.csv';
        $handle = fopen($filepath, "r");
        $condition = 'NM';
        $language_id = 1;
        $is_foil = false;
        $article_rows = [];
        $cards = [];
        $quantity = 0;
        $quantity_by_position = [];

        $parent_storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'Magic Sorter',
        ]);

        $storage_file = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => basename($filepath),
            'parent_id' => $parent_storage->id,
        ]);

        $header = [];
        $row_counter = 0;
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $header = MagicSorterImporter::parseHeader(str_getcsv($raw_string, ','));
                $row_counter++;
                continue;
            }

            $this->assertArrayHasKey('ecommerce-id', $header);
            $this->assertArrayHasKey('price', $header);
            $this->assertArrayHasKey('height', $header);
            $this->assertArrayHasKey('position', $header);

            $article_row = str_getcsv($raw_string, ',');

            if (empty($article_row[$header['ecommerce-id']])) {
                continue;
            }

            $article_rows[] = $article_row;
            $quantity++;
            $quantity_by_position[$article_row[$header['position']]] = ($quantity_by_position[$article_row[$header['position']]] ?? 0) + 1;

            if (!Arr::has($cards, $article_row[$header['ecommerce-id']])) {
                $cards[$article_row[$header['ecommerce-id']]] = factory(Card::class)->create([
                    'cardmarket_product_id' => $article_row[$header['ecommerce-id']],
                    'name' => $article_row[$header['title']],
                ]);
            }

            $row_counter++;
        }
        fclose($handle);

        MagicSorterImporter::import($this->user->id, $filepath, $condition, $language_id, $is_foil);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);

        $storages = Storage::where('parent_id', $storage_file->id)->get();
        $this->assertCount(count($quantity_by_position), $storages);
        foreach ($storages as $storage) {
            $articles = $storage->articles()->orderBy('source_sort', 'ASC')->get();
            $this->assertCount($quantity_by_position[$storage->name], $articles);
            foreach ($articles as $key => $article) {
                $this->assertEquals($key, $article->source_sort);
                $this->assertEquals($condition, $article->condition);
                $this->assertEquals($language_id, $article->language_id);
                $this->assertEquals($is_foil, $article->is_foil);
            }
        }

        MagicSorterImporter::import($this->user->id, $filepath, $condition, $language_id, $is_foil);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);

        $storages = Storage::where('parent_id', $storage_file->id)->get();
        $this->assertCount(count($quantity_by_position), $storages);
        foreach ($storages as $storage) {
            $articles = $storage->articles()->orderBy('source_sort', 'ASC')->get();
            $this->assertCount($quantity_by_position[$storage->name], $articles);
            foreach ($articles as $key => $article) {
                $this->assertEquals($key, $article->source_sort);
            }
        }
    }
}