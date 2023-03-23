<?php

namespace Tests\Unit\Importers\Orders;

use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Importers\Articles\TCGPowerToolsImporter;

class TCGPowerToolsImporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_import_articles_from_tcg_powertools()
    {
        $this->markTestSkipped('no cardmarket connection.');

        $filepath = 'tests/snapshots/tcg-powertools/articles/export.csv';
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

        $row_counter = 0;
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $row_counter++;
                continue;
            }

            $article_row = str_getcsv($raw_string, ',');

            $quantity += (int)$article_row[TCGPowerToolsImporter::COLUMN_QUANTITY];
            $article_rows[] = $article_row;

            factory(Card::class)->create([
                'cardmarket_product_id' => $article_row[TCGPowerToolsImporter::COLUMN_CARDMARKET_PRODUCT_ID],
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
}