<?php

namespace App\Importers\Orders;

use App\Models\Cards\Card;
use App\Models\Games\Game;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Models\Localizations\Language;

class ArticlesInOrdersCsvImporter
{
    const KEY_GAME_ID = 0; // Was ist in der ersten Spalte? Es ist nicht die Game ID
    const KEY_CARDMARKET_PRODUCT_ID = 1;
    const KEY_ARTICLE_COUNT = 2;
    const KEY_ARTICLE_NAME = 3;
    const KEY_ARTICLE_LOCALIZED_NAME = 4;
    const KEY_EXPANSION = 5;
    const KEY_PRICE = 6;
    const KEY_COMMENTS = 7;
    const KEY_CONDITION = 8;
    const KEY_RARITY = 9;
    const KEY_COLLECTOR_NUMBER = 10;
    const KEY_LANGUAGE = 11;
    const KEY_ORDER_ID = 12;

    public static function importFromFilePath(int $user_id, string $filepath): array
    {
        $article_rows = self::parseCsv($filepath);
        return self::import($user_id, $article_rows);
    }

    public static function parseCsv(string $filepath): array
    {
        $article_rows = [];
        $handle = fopen($filepath, "r");
        $article_rows_counter = 0;
        while (($raw_string = trim(fgets($handle))) !== false) {
            if (empty($raw_string)) {
                break;
            }

            $row = str_getcsv($raw_string, ';');
            $article_rows[] = $row;
            $article_rows_counter++;
        }
        fclose($handle);

        return $article_rows;
    }

    public static function import(int $user_id, array $article_rows): array
    {
        $importer = new self();
        $importer->ensureCardsExists($article_rows);
        $cardmarket_orders = $importer->toCardmarketOrders($article_rows);
        $orders = $importer->createOrders($user_id, $cardmarket_orders);
        return $orders;
    }

    public function ensureCardsExists(array $article_rows): array
    {
        foreach ($article_rows as $key => $article_row) {
            if ($key == 0) {
                continue;
            }

            $this->ensureCardExists($article_row);
        }

        return $article_rows;
    }

    public function ensureCardExists(array $article_row)
    {
        $card = Card::where('cardmarket_product_id', $article_row[self::KEY_CARDMARKET_PRODUCT_ID])->first();
        $card = Card::updateOrCreate([
            'cardmarket_product_id' => $article_row[self::KEY_CARDMARKET_PRODUCT_ID],
        ], [
            'name' => $article_row[self::KEY_ARTICLE_NAME],
            'game_id' => Game::ID_MAGIC,
            'number' => $article_row[self::KEY_COLLECTOR_NUMBER],
            'rarity' => $article_row[self::KEY_RARITY],
            'website' => '',
            'image' => '',
        ]);

        if ($article_row[self::KEY_LANGUAGE] != Language::GERMAN_TO_IDS[Language::DEFAULT_ID]) {
            $language_id = Language::getIdByGermanName($article_row[self::KEY_LANGUAGE]);
            if ($language_id) {
                $card->localizations()->firstOrCreate([
                    'language_id' => $language_id,
                ], [
                    'name' => $article_row[self::KEY_ARTICLE_LOCALIZED_NAME],
                ]);
            }
        }

        // Card gefunden && hat Skryfall Daten -> return
        if ($card->hasSkryfallData) {
            return $card;
        }

        $card->updateFromSkryfallByCardmarketId($article_row[self::KEY_CARDMARKET_PRODUCT_ID]);

        return $card;
    }

    public function toCardmarketOrders(array $article_rows): array
    {
        $cardmarketOrders = [
            'order' => [],
        ];
        foreach ($article_rows as $key => $article_row) {
            if ($key == 0) {
                continue;
            }

            $order_id = $article_row[self::KEY_ORDER_ID];
            if (! Arr::has($cardmarketOrders, 'order.' . $order_id)) {
                $cardmarketOrders['order'][$order_id] = [
                    'idOrder' => $order_id,
                    'articleCount' => 0,
                    'articleValue' => 0,
                    'article' => [],
                    'state' => [
                        'state' => 'paid',
                    ],
                    'totalValue' => 0,
                ];
            }

            $cardmarketOrders['order'][$order_id]['article'][] = [
                'idArticle' => null,
                'idProduct' => $article_row[self::KEY_CARDMARKET_PRODUCT_ID],
                'count' => $article_row[self::KEY_ARTICLE_COUNT],
                'language' => [
                    'idLanguage' => Language::getIdByGermanName($article_row[self::KEY_LANGUAGE]),
                    'name' => Language::DEFAULT_NAME,
                ],
                'comments' => $article_row[self::KEY_COMMENTS],
                'condition' => $article_row[self::KEY_CONDITION],
                'price' => $article_row[self::KEY_PRICE],
            ];

            $cardmarketOrders['order'][$order_id]['articleCount'] += $article_row[self::KEY_ARTICLE_COUNT];
            $cardmarketOrders['order'][$order_id]['articleValue'] += ($article_row[self::KEY_ARTICLE_COUNT] * $article_row[self::KEY_PRICE]);
        }

        return $cardmarketOrders;
    }

    public function createOrders(int $user_id, array $cardmarketOrders): array
    {
        $orders = [];
        foreach ($cardmarketOrders['order'] as $key => $cardmarketOrder) {
            if ($key == 0) {
                continue;
            }

            $orders[] = $this->createOrder($user_id, $cardmarketOrder);
        }

        return $orders;
    }

    public function createOrder(int $user_id, array $cardmarketOrder): Order
    {
        $values = [
            'cardmarket_order_id' => $cardmarketOrder['idOrder'],
            'buyer_id' => null,
            'seller_id' => null,
            'shipping_method_id' => 0,
            'state' => $cardmarketOrder['state']['state'],
            'shippingmethod' => '',
            'shipping_name' => '',
            'shipping_extra' => '',
            'shipping_street' => '',
            'shipping_zip' => '',
            'shipping_city' => '',
            'shipping_country' => '',
            'shipment_revenue' => 0.3,
            'articles_count' => $cardmarketOrder['articleCount'],
            'articles_revenue' => $cardmarketOrder['articleValue'],
            'revenue' => $cardmarketOrder['totalValue'],
            'user_id' => $user_id,
            'bought_at' => null,
            'canceled_at' => null,
            'paid_at' => null,
            'received_at' => null,
            'sent_at' => null,
        ];

        $order = Order::updateOrCreate(['cardmarket_order_id' => $cardmarketOrder['idOrder']], $values);

        $order->findItems();
        $order->addArticlesFromCardmarket($cardmarketOrder);

        $order->calculateProfits()
            ->save();

        return $order;
    }


}