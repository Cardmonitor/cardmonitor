<?php

namespace Tests\Unit\Importers\Articles;

use Tests\TestCase;
use App\Support\Csv\Csv;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Importers\Articles\TCGPowerToolsImporter;

class TCGPowerToolsImporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_import_mtg_articles_from_tcg_powertools()
    {
        $filepath = 'tests/snapshots/tcg-powertools/articles/mtg.csv';
        $handle = fopen($filepath, "r");
        $article_rows = [];
        $quantity = 0;

        $parent_storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'TCG Power Tools',
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
                $header = Csv::parseHeader(str_getcsv($raw_string, ','));

                $this->assertArrayHasKey('cardmarketid', $header);
                $this->assertArrayHasKey('comment', $header);
                $this->assertArrayHasKey('condition', $header);
                $this->assertArrayHasKey('isfoil', $header);
                $this->assertArrayHasKey('language', $header);
                $this->assertArrayHasKey('listedat', $header);
                $this->assertArrayHasKey('price', $header);
                $this->assertArrayHasKey('quantity', $header);

                $row_counter++;
                continue;
            }

            $article_row = str_getcsv($raw_string, ',');

            $quantity += (int)$article_row[$header['quantity']];
            $article_rows[] = $article_row;

            factory(Card::class)->create([
                'cardmarket_product_id' => $article_row[$header['cardmarketid']],
            ]);

            $row_counter++;
        }
        fclose($handle);

        TCGPowerToolsImporter::import($this->user->id, $filepath);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);
        $this->assertCount($quantity, $storage_file->articles);

        TCGPowerToolsImporter::import($this->user->id, $filepath);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);
        $this->assertCount($quantity, $storage_file->articles);

        $sorted_articles = Article::orderBy('source_sort', 'ASC')->get();

        $this->assertEquals(0, $sorted_articles[0]->source_sort);
        $this->assertEquals(0, $sorted_articles[1]->source_sort);
        $this->assertGreaterThan(0, $sorted_articles[2]->source_sort);
    }

    /**
     * @test
     */
    public function it_can_import_pcg_articles_from__powertools()
    {
        $filepath = 'tests/snapshots/tcg-powertools/articles/pcg.csv';
        $handle = fopen($filepath, "r");
        $article_rows = [];
        $quantity = 0;

        $parent_storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'TCG Power Tools',
        ]);

        $storage_file = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => basename($filepath),
            'parent_id' => $parent_storage->id,
        ]);

        $cards = [];
        $header = [];
        $row_counter = 0;
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $header = Csv::parseHeader(str_getcsv($raw_string, ','));

                $this->assertArrayHasKey('cardmarketid', $header);
                $this->assertArrayHasKey('comment', $header);
                $this->assertArrayHasKey('condition', $header);
                $this->assertArrayHasKey('isreverseholo', $header);
                $this->assertArrayHasKey('isfirsted', $header);
                $this->assertArrayHasKey('language', $header);
                $this->assertArrayHasKey('listedat', $header);
                $this->assertArrayHasKey('price', $header);
                $this->assertArrayHasKey('quantity', $header);

                $row_counter++;
                continue;
            }

            $article_row = str_getcsv($raw_string, ',');

            $data = Csv::combineHeaderAndRow($header, $article_row);

            $quantity += (int)$data['quantity'];
            $article_rows[] = $article_row;

            if (!Arr::has($cards, $data['cardmarketid'])) {
                $cards[$data['cardmarketid']] = factory(Card::class)->create([
                    'cardmarket_product_id' => $data['cardmarketid'],
                    'name' => $data['name'],
                ]);
            }

            $row_counter++;
        }
        fclose($handle);

        TCGPowerToolsImporter::import($this->user->id, $filepath);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);
        $this->assertCount($quantity, $storage_file->articles);

        TCGPowerToolsImporter::import($this->user->id, $filepath);

        $articles = Article::all();
        $this->assertCount($quantity, $articles);
        $this->assertCount($quantity, $storage_file->articles);

        $this->assertEquals('NM', $articles[0]->condition);
        $this->assertEquals(0, $articles[0]->is_first_edition);
        $this->assertEquals(1, $articles[0]->is_reverse_holo);
        $this->assertEquals(1, $articles[0]->is_sellable);
        $this->assertNotNull($articles[0]->is_sellable_since);

        $this->assertEquals(1, $articles[1]->is_first_edition);
        $this->assertEquals(0, $articles[1]->is_reverse_holo);
        $this->assertEquals(1, $articles[1]->is_sellable);
        $this->assertNotNull($articles[1]->is_sellable_since);

        $this->assertEquals(0, $articles[2]->is_first_edition);
        $this->assertEquals(0, $articles[2]->is_reverse_holo);
        $this->assertEquals(1, $articles[2]->is_sellable);
        $this->assertNotNull($articles[2]->is_sellable_since);

        $this->assertEquals(1, $articles[3]->is_first_edition);
        $this->assertEquals(1, $articles[3]->is_reverse_holo);
        $this->assertEquals(1, $articles[3]->is_sellable);
        $this->assertNotNull($articles[3]->is_sellable_since);

        $sorted_articles = Article::orderBy('source_sort', 'ASC')->get();

        $this->assertEquals(0, $sorted_articles[0]->source_sort);
        $this->assertGreaterThan(0, $sorted_articles[1]->source_sort);
        $this->assertGreaterThan($sorted_articles[1]->source_sort, $sorted_articles[2]->source_sort);
        $this->assertGreaterThan($sorted_articles[2]->source_sort, $sorted_articles[3]->source_sort);
    }
}