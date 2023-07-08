<?php

namespace Tests\Unit\Transformers\Articles\Csvs;

use Tests\TestCase;
use App\Support\Csv\Csv;
use App\Models\Games\Game;
use App\Transformers\Articles\Csvs\Mtg;
use App\Transformers\Articles\Csvs\PCG;
use App\Transformers\Articles\Csvs\YGO;
use App\Transformers\Articles\Csvs\Transformer;

class TransformerTest extends TestCase
{
    const DATA_MAGIC = [
        1,2,3,4,5,6,7,8,9,10,11,12,13,14,15
    ];

    /**
     * @test
     */
    public function it_gets_the_right_transformer()
    {
        $cardmarketGames = json_decode(file_get_contents('tests/snapshots/cardmarket/games/get.json'), true);;
        foreach ($cardmarketGames['game'] as $key => $cardmarketGame) {
            $game = Game::updateOrCreate(['id' => $cardmarketGame['idGame']], [
                'name' => $cardmarketGame['name'],
                'abbreviation' => $cardmarketGame['abbreviation'],
                'is_importable' => in_array($cardmarketGame['idGame'], [1,3,6]),
            ]);
        }

        $this->assertInstanceOf(MtG::class, Transformer::transformer(Game::ID_MAGIC));
        $this->assertInstanceOf(YGO::class, Transformer::transformer(Game::ID_YUGIOH));
        $this->assertInstanceOf(PCG::class, Transformer::transformer(Game::ID_POKEMON));
    }

    /**
     * @test
     */
    public function it_throws_an_error_if_the_game_is_not_importable()
    {
        $this->expectException(\InvalidArgumentException::class);

        Transformer::transformer(-1);
    }

    /**
     * @test
     */
    public function it_transforms_the_mtg_stockfile()
    {
        $filepath = 'tests/snapshots/cardmarket/articles/stock-1.csv';
        $separator = ';';
        $header = [];
        $article_rows = [];
        $row_counter = 0;

        $transformer = new Transformer();

        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $header = Csv::parseHeader(str_getcsv($raw_string, $separator));

                $this->assertArrayHasKey('idarticle', $header);
                $this->assertArrayHasKey('idproduct', $header);
                $this->assertArrayHasKey('english_name', $header);
                $this->assertArrayHasKey('local_name', $header);
                $this->assertArrayHasKey('exp', $header);
                $this->assertArrayHasKey('exp_name', $header);
                $this->assertArrayHasKey('price', $header);
                $this->assertArrayHasKey('language', $header);
                $this->assertArrayHasKey('condition', $header);
                $this->assertArrayHasKey('foil', $header);
                $this->assertArrayHasKey('signed', $header);
                $this->assertArrayHasKey('playset', $header);
                $this->assertArrayHasKey('altered', $header);
                $this->assertArrayHasKey('comments', $header);
                $this->assertArrayHasKey('amount', $header);
                $this->assertArrayHasKey('onsale', $header);

                $transformer->setHeader($header);

                $row_counter++;
                continue;
            }

            $article_rows[] = str_getcsv($raw_string, $separator);

            $row_counter++;
        }
        fclose($handle);

        $cardmarket_article = $transformer->unify($article_rows[0]);
        $this->assertEquals(1, $cardmarket_article['amount']);
        $this->assertEquals(20927, $cardmarket_article['card_id']);
        $this->assertEquals(361169856, $cardmarket_article['cardmarket_article_id']);
        $this->assertEquals('', $cardmarket_article['cardmarket_comments']);
        $this->assertEquals('NM', $cardmarket_article['condition']);
        $this->assertEquals(false, $cardmarket_article['is_altered']);
        $this->assertEquals(false, $cardmarket_article['is_first_edition']);
        $this->assertEquals(false, $cardmarket_article['is_foil']);
        $this->assertEquals(false, $cardmarket_article['is_in_shoppingcard']);
        $this->assertEquals(false, $cardmarket_article['is_playset']);
        $this->assertEquals(false, $cardmarket_article['is_reverse_holo']);
        $this->assertEquals(false, $cardmarket_article['is_signed']);
        $this->assertEquals(3, $cardmarket_article['language_id']);
        $this->assertEquals('', $cardmarket_article['number_from_cardmarket_comments']);
        $this->assertEquals(3.45, $cardmarket_article['unit_price']);
    }

    /**
     * @test
     */
    public function it_transforms_the_pcg_stockfile()
    {
        $filepath = 'tests/snapshots/cardmarket/articles/stock-6.csv';
        $separator = ';';
        $header = [];
        $article_rows = [];
        $row_counter = 0;

        $transformer = new Transformer();

        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $header = Csv::parseHeader(str_getcsv($raw_string, $separator));

                $this->assertArrayHasKey('idarticle', $header);
                $this->assertArrayHasKey('idproduct', $header);
                $this->assertArrayHasKey('english_name', $header);
                $this->assertArrayHasKey('local_name', $header);
                $this->assertArrayHasKey('exp', $header);
                $this->assertArrayHasKey('exp_name', $header);
                $this->assertArrayHasKey('price', $header);
                $this->assertArrayHasKey('language', $header);
                $this->assertArrayHasKey('condition', $header);
                $this->assertArrayHasKey('reverseholo', $header);
                $this->assertArrayHasKey('signed', $header);
                $this->assertArrayHasKey('firsted', $header);
                $this->assertArrayHasKey('playset', $header);
                $this->assertArrayHasKey('altered', $header);
                $this->assertArrayHasKey('comments', $header);
                $this->assertArrayHasKey('amount', $header);
                $this->assertArrayHasKey('onsale', $header);

                $transformer->setHeader($header);

                $row_counter++;
                continue;
            }

            $article_rows[] = str_getcsv($raw_string, $separator);

            $row_counter++;
        }
        fclose($handle);

        $cardmarket_article = $transformer->unify($article_rows[0]);
        $this->assertEquals(1472631460, $cardmarket_article['cardmarket_article_id']);
        $this->assertEquals(715707, $cardmarket_article['card_id']);
        $this->assertEquals(2.78, $cardmarket_article['unit_price']);
        $this->assertEquals(3, $cardmarket_article['language_id']);
        $this->assertEquals('NM', $cardmarket_article['condition']);
        $this->assertEquals(false, $cardmarket_article['is_altered']);
        $this->assertEquals(false, $cardmarket_article['is_first_edition']);
        $this->assertEquals(false, $cardmarket_article['is_foil']);
        $this->assertEquals(false, $cardmarket_article['is_in_shoppingcard']);
        $this->assertEquals(false, $cardmarket_article['is_playset']);
        $this->assertEquals(false, $cardmarket_article['is_reverse_holo']);
        $this->assertEquals(false, $cardmarket_article['is_signed']);
        $this->assertEquals('PALDE - LGS in Lübeck | Fast & secure shipping', $cardmarket_article['cardmarket_comments']);
        $this->assertEquals('', $cardmarket_article['number_from_cardmarket_comments']);
        $this->assertEquals(1, $cardmarket_article['amount']);
    }

    /**
     * @test
     */
    public function it_transforms_the_fab_stockfile()
    {
        $filepath = 'tests/snapshots/cardmarket/articles/stock-16.csv';
        $separator = ';';
        $header = [];
        $article_rows = [];
        $row_counter = 0;

        $transformer = new Transformer();

        $handle = fopen($filepath, "r");
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            if ($row_counter === 0) {
                $header = Csv::parseHeader(str_getcsv($raw_string, $separator));

                $this->assertArrayHasKey('idarticle', $header);
                $this->assertArrayHasKey('idproduct', $header);
                $this->assertArrayHasKey('english_name', $header);
                $this->assertArrayHasKey('local_name', $header);
                $this->assertArrayHasKey('exp', $header);
                $this->assertArrayHasKey('exp_name', $header);
                $this->assertArrayHasKey('price', $header);
                $this->assertArrayHasKey('language', $header);
                $this->assertArrayHasKey('condition', $header);
                $this->assertArrayHasKey('signed', $header);
                $this->assertArrayHasKey('altered', $header);
                $this->assertArrayHasKey('comments', $header);
                $this->assertArrayHasKey('amount', $header);
                $this->assertArrayHasKey('onsale', $header);

                $transformer->setHeader($header);

                $row_counter++;
                continue;
            }

            $article_rows[] = str_getcsv($raw_string, $separator);

            $row_counter++;
        }
        fclose($handle);

        $cardmarket_article = $transformer->unify($article_rows[0]);
        $this->assertEquals(1338155034, $cardmarket_article['cardmarket_article_id']);
        $this->assertEquals(654932, $cardmarket_article['card_id']);
        $this->assertEquals(1.23, $cardmarket_article['unit_price']);
        $this->assertEquals(1, $cardmarket_article['language_id']);
        $this->assertEquals('NM', $cardmarket_article['condition']);
        $this->assertEquals(false, $cardmarket_article['is_altered']);
        $this->assertEquals(false, $cardmarket_article['is_first_edition']);
        $this->assertEquals(false, $cardmarket_article['is_foil']);
        $this->assertEquals(false, $cardmarket_article['is_in_shoppingcard']);
        $this->assertEquals(false, $cardmarket_article['is_playset']);
        $this->assertEquals(false, $cardmarket_article['is_reverse_holo']);
        $this->assertEquals(false, $cardmarket_article['is_signed']);
        $this->assertEquals('', $cardmarket_article['cardmarket_comments']);
        $this->assertEquals('', $cardmarket_article['number_from_cardmarket_comments']);
        $this->assertEquals(2, $cardmarket_article['amount']);
    }
}
