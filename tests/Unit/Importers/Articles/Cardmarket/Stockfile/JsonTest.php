<?php
namespace Tests\Unit\Importers\Articles;

use Tests\TestCase;
use App\Importers\Articles\Cardmarket\Stockfile\Json;

class JsonTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_import_articles_from_cardmarket_json_file()
    {
        $this->markTestSkipped('No connection to cardmarket API');
        $Json = new Json($this->user->id, 'articles/stock/StockExport-20571.json');
        $Json->setCardmarketCards();
    }
}