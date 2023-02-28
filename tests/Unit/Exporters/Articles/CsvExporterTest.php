<?php

namespace Tests\Unit\Exporters\Articles;

use Tests\TestCase;
use App\Models\Articles\Article;
use App\Exporters\Articles\CsvExporter;
use Illuminate\Support\Facades\Storage;

class CsvExporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_export_an_order()
    {
        Storage::fake('public');

        $filename = 'articles-' . now() . '.csv';
        $path = 'export/' . $this->user->id . '/articles/' . $filename;
        Storage::disk('public')->makeDirectory(dirname($path));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'number' => 'A001.001',
        ]);

        $articles = Article::where('user_id', $this->user->id)
            ->sold(0)
            ->with([
                'card.expansion',
            ])
            ->orderBy('articles.number', 'ASC')
            ->cursor();

        Storage::disk('public')->assertMissing($path);

        $url = CsvExporter::all($articles, $path);

        $this->assertEquals(Storage::disk('public')->url($path), $url);

        Storage::disk('public')->assertExists($path);

        $file = new \SplFileObject(Storage::disk('public')->path($path));
        $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $rows = [];
        while (! $file->eof()) {
            $rows[] = $file->fgetcsv(';');
        }

        $rows = array_filter($rows);

        $this->assertCount(2, $rows);
        $this->assertEquals(array_merge(CsvExporter::EXPANSION_ATTRIBUTES, CsvExporter::CARD_ATTRIBUTES, CsvExporter::ARTICLE_ATTRIBUTES), $rows[0]);

        $article_row = $rows[1];
        $article_row_count = count(CsvExporter::EXPANSION_ATTRIBUTES) + count(CsvExporter::CARD_ATTRIBUTES) + count(CsvExporter::ARTICLE_ATTRIBUTES);

        $this->assertCount($article_row_count, $article_row);

        $article_row_key = 0;
        foreach (CsvExporter::EXPANSION_ATTRIBUTES as $attribute) {
            // echo $article->card->expansion->$attribute . ' - ' . $article_row[$article_row_key] . PHP_EOL;
            $this->assertEquals($article->card->expansion->$attribute, $article_row[$article_row_key]);
            $article_row_key++;
        }
        foreach (CsvExporter::CARD_ATTRIBUTES as $attribute) {
            // echo $article->card->$attribute . ' - ' . $article_row[$article_row_key] . PHP_EOL;
            $this->assertEquals($article->card->$attribute, $article_row[$article_row_key]);
            $article_row_key++;
        }
        foreach (CsvExporter::ARTICLE_ATTRIBUTES as $attribute) {
            if (in_array($attribute, ['unit_price', 'unit_cost'])) {
                $this->assertEquals(round($article->$attribute, 2), round($article_row[$article_row_key], 2));
            }
            else {
                $this->assertEquals($article->$attribute, $article_row[$article_row_key]);
            }
            $article_row_key++;
        }

        Storage::disk('public')->delete($path);

        Storage::disk('public')->assertMissing($path);
    }
}
