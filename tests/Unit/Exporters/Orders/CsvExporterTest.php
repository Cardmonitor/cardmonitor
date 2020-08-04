<?php

namespace Tests\Unit\Exporters\Orders;

use App\Exporters\Orders\CsvExporter;
use App\Models\Articles\Article;
use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Orders\Order;
use Illuminate\Http\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvExporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_export_an_order()
    {
        Storage::fake('public');

        $path = 'export/' . $this->user->id . '/order/orders.csv';
        Storage::disk('public')->makeDirectory(dirname($path));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $order = factory(Order::class)->create([
            'user_id' => $this->user->id,
            'state' => 'paid',
        ]);

        $this->assertCount(0, $order->articles);

        $order->articles()->attach($article->id);

        $this->assertCount(1, $order->fresh()->articles);

        $order->load([
            'articles.language',
            'articles.card.expansion',
            'buyer',
        ]);

        $orders = new Collection();
        $orders->push($order);

        Storage::disk('public')->assertMissing($path);

        $url = CsvExporter::all($this->user->id, $orders, $path);

        $this->assertEquals(Storage::disk('public')->url($path), $url);

        Storage::disk('public')->assertExists($path);

        $file = new \SplFileObject(Storage::disk('public')->path($path));
        $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $rows = [];
        while (! $file->eof()) {
            $rows[] = $file->fgetcsv(';');
        }

        $this->assertCount(4, $rows);
        $this->assertEquals(array_merge(CsvExporter::BUYER_ATTRIBUTES, CsvExporter::ORDER_ATTRIBUTES, CsvExporter::ARTICLE_ATTRIBUTES), $rows[0]);
        $this->assertEquals($article->sku, $rows[1][25]);
        $this->assertEquals('Artikel', $rows[1][30]);
        $this->assertEquals('', $rows[2][25]);
        $this->assertEquals('Versandposition', $rows[2][30]);

        Storage::disk('public')->delete($path);

        Storage::disk('public')->assertMissing($path);
    }

    /**
     * @test
     */
    public function it_can_group_articles()
    {
        Storage::fake('public');

        $path = 'export/' . $this->user->id . '/order/orders.csv';
        Storage::disk('public')->makeDirectory(dirname($path));

        $order = factory(Order::class)->create([
            'user_id' => $this->user->id,
            'state' => 'paid',
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(0, $order->articles);

        $order->articles()->attach($article->id);

        $this->assertCount(1, $order->fresh()->articles);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);
        $order->articles()->attach($article->id);

        $this->assertCount(2, $order->fresh()->articles);

        $article = Article::create($article->getOriginal());

        $order->articles()->attach($article->id);

        $this->assertCount(3, $order->fresh()->articles);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);
        $order->articles()->attach($article->id);

        $this->assertCount(4, $order->fresh()->articles);

        $order->load([
            'articles.language',
            'articles.card.expansion',
            'buyer',
        ]);

        $orders = new Collection();
        $orders->push($order);

        Storage::disk('public')->assertMissing($path);

        $url = CsvExporter::all($this->user->id, $orders, $path);

        $this->assertEquals(Storage::disk('public')->url($path), $url);

        Storage::disk('public')->assertExists($path);

        $file = new \SplFileObject(Storage::disk('public')->path($path));
        $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $rows = [];
        while (! $file->eof()) {
            $rows[] = $file->fgetcsv(';');
        }

        $this->assertCount(6, $rows);
        $this->assertEquals(array_merge(CsvExporter::BUYER_ATTRIBUTES, CsvExporter::ORDER_ATTRIBUTES, CsvExporter::ARTICLE_ATTRIBUTES), $rows[0]);
        $this->assertEquals(1, $rows[1][29]);
        $this->assertEquals(2, $rows[2][29]);
        $this->assertEquals(1, $rows[3][29]);
        $this->assertEquals('Artikel', $rows[1][30]);
        $this->assertEquals('Artikel', $rows[2][30]);
        $this->assertEquals('Artikel', $rows[3][30]);
        $this->assertEquals('Versandposition', $rows[4][30]);

        Storage::disk('public')->delete($path);

        Storage::disk('public')->assertMissing($path);
    }

    /**
     * @test
     */
    public function it_deos_not_export_presale_orders()
    {
        $this->markTestSkipped('SQL Lite error');

        Storage::fake('public');

        $path = 'export/' . $this->user->id . '/order/orders.csv';
        Storage::disk('public')->makeDirectory(dirname($path));

        $expansion = factory(Expansion::class)->create([
            'released_at' => null,
        ]);

        $card = factory(\App\Models\Cards\Card::class)->create([
            'expansion_id' => $expansion->id,
        ]);

        $order = factory(Order::class)->create([
            'user_id' => $this->user->id,
            'state' => 'paid',
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $order->user_id,
            'card_id' => $card->id,
        ]);

        $order->articles()->attach($article->id);

        $this->assertCount(1, $order->articles);
        $this->assertTrue($order->isPresale());

        $order->load([
            'articles.language',
            'articles.card.expansion',
            'buyer',
        ]);

        $orders = $this->user->orders()->presale('0')->get();

        Storage::disk('public')->assertMissing($path);

        $url = CsvExporter::all($this->user->id, $orders, $path);

        $this->assertEquals(Storage::disk('public')->url($path), $url);

        Storage::disk('public')->assertExists($path);

        $file = new \SplFileObject(Storage::disk('public')->path($path));
        $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $rows = [];
        while (! $file->eof()) {
            $rows[] = $file->fgetcsv(';');
        }

        $this->assertCount(2, $rows);

        Storage::disk('public')->delete($path);

        Storage::disk('public')->assertMissing($path);
    }
}
