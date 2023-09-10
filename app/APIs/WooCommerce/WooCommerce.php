<?php

namespace App\APIs\WooCommerce;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WooCommerce
{
    private $url;
    private $consumer_key;
    private $consumer_secret;

    public function __construct()
    {
        $this->url = config('services.woocommerce.url');
        $this->consumer_key = config('services.woocommerce.consumer_key');
        $this->consumer_secret = config('services.woocommerce.consumer_secret');
    }

    public function orders(array $parameters = []): array
    {
        $response = $this->getClient()->get('/wp-json/wc/v3/orders', $parameters);

        $headers = $response->headers();

        return [
            'data' => $response->json(),
            'pagination' => [
                'total' => (int)$headers['X-WP-Total'][0],
                'total_pages' => (int)$headers['X-WP-TotalPages'][0],
            ],
        ];
    }

    public function order(int $id): array
    {
        $response = $this->getClient()->get('/wp-json/wc/v3/orders/' . $id, [

        ]);

        return [
            'data' => $response->json(),
            'headers' => $response->headers(),
        ];
    }

    public function updateOrder(int $id, array $data): array
    {
        $response = $this->getClient()->put('/wp-json/wc/v3/orders/' . $id, $data);

        return [
            'data' => $response->json(),
            'headers' => $response->headers(),
        ];
    }

    public function updateOrderState(int $id, Status $status): array
    {
        return $this->updateOrder($id, [
            'status' => $status->value,
        ]);
    }

    private function getClient(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->withBasicAuth($this->consumer_key, $this->consumer_secret);
    }
}