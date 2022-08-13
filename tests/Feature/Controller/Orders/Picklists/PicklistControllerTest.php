<?php

namespace Tests\Feature\Controller\Orders\Picklists;

use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Orders\Order;
use Illuminate\Http\Response;
use App\Models\Articles\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PicklistControllerTest extends TestCase
{
    /**
     * @test
     */
    public function it_works()
    {
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_create_a_order_from_a_csv_file()
    {
        $this->markTestIncomplete();

        $this->signIn();

        $cache_key = 'orders.picklist.' . $this->user->id . '.article_ids';

        $filename = 'imports/articles_in_orders/articles_in_orders.csv';
        $path = Storage::disk('local')->path($filename);

        $response = $this->post('/order/picklist', [
            'articles_in_orders' => new UploadedFile($path, $filename, null, null, true),
        ]);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertCount(1, Order::all());
        $this->assertCount(2, Article::all());
        $this->assertCount(2, Card::all());
        $this->assertTrue(Cache::has($cache_key));
        $this->assertEquals([1,2], Cache::get($cache_key));

    }
}
