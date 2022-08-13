<?php

namespace Tests\Feature\Controller\Orders\Import;

use Mockery;
use App\Models\Orders\Order;
use Cardmonitor\Cardmarket\Order as CardmarketOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SentControllerTest extends TestCase
{
    protected function tearDown() : void
    {
        Mockery::close();
        Storage::disk('local')->delete('import.csv');
    }

    /**
     * @test
     */
    public function it_can_mark_orders_as_sent()
    {
        // $this->markTestIncomplete();

        $this->signIn();

        $returnValue = json_decode(file_get_contents('tests/snapshots/cardmarket/order/get_seller.json'), true);
        $returnValue['order']['state']['state'] = 'sent';
        $returnValue['order']['state']['dateSent'] = $returnValue['order']['state']['datePaid'];
        $orderId = $returnValue['order']['idOrder'];

        $productMock = Mockery::mock('overload:' . CardmarketOrder::class);
        $productMock->shouldReceive('send')
            ->with($orderId)
            ->andReturn($returnValue);

        $order = factory(Order::class)->create([
            'cardmarket_order_id' => $orderId,
            'user_id' => $this->user->id,
            'state' => 'paid',
        ]);
        $tracking_number = '1234567';
        $filename = 'import.csv';
        $path = Storage::disk('local')->path($filename);

        $this->assertEquals('paid', $order->state);
        $this->assertNull($order->tracking_number);

        $this->assertFileDoesNotExist($path);
        Storage::disk('local')->put($filename, "order_id;tracking_number\n" . $order->id . ";" . $tracking_number);
        $this->assertFileExists($path);

        $response = $this->post('/order/import/sent', [
            'import_sent_file' => new UploadedFile($path, $filename, filesize($path), null, true),
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEquals('sent', $order->fresh()->state);
        $this->assertEquals($tracking_number, $order->fresh()->tracking_number);

        Storage::disk('local')->delete($filename);
        $this->assertFileDoesNotExist($path);

        Mockery::close();
    }
}
