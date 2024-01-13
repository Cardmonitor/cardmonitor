<?php

namespace App\APIs\WooCommerce;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

abstract class WooCommerce
{
    protected $url;
    protected $consumer_key;
    protected $consumer_secret;

    public abstract function __construct();

    public function orders(array $parameters = []): Response
    {
        return $this->getClient()->get('/wp-json/wc/v3/orders', $parameters);
    }

    public function order(int $id): Response
    {
        return $this->getClient()->get('/wp-json/wc/v3/orders/' . $id);
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

    public function products(array $filter = []): Response
    {
        return $this->getClient()->get('/wp-json/wc/v3/products', $filter);
    }

    public function createProduct(array $data): Response
    {
        return $this->getClient()->post('/wp-json/wc/v3/products', $data);
    }

    public function findProduct(int $product_id): Response
    {
        return $this->getClient()->get('/wp-json/wc/v3/products/' . $product_id);
    }

    public function updateProduct(int $product_id, array $data): Response
    {
        return $this->getClient()->put('/wp-json/wc/v3/products/' . $product_id, $data);
    }

    public function deleteProduct(int $product_id): Response
    {
        return $this->getClient()->delete('/wp-json/wc/v3/products/' . $product_id);
    }

    protected function getClient(): PendingRequest
    {
        return Http::baseUrl($this->url)
            ->withBasicAuth($this->consumer_key, $this->consumer_secret);
    }
}