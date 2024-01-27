<?php

namespace App\Console\Commands\Article\Imports\WooCommerce;

use App\User;
use Generator;
use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Models\Articles\Article;
use App\Models\Articles\ExternalId;
use App\Models\Localizations\Language;

class SyncCommand extends Command
{
    protected $signature = 'article:imports:woocommerce:sync {user}';

    protected $description = 'Syncs Articles from WooCommerce and sets the sync_action';

    public function handle()
    {
        $user = User::findOrFail($this->argument('user'));
        $article_ids = [];
        foreach ($this->getProducts() as $woocommerce_product) {
            $this->info('Syncing product: ' . $woocommerce_product['name']);
            $article = $user->articles()->with([
                'externalIdsWoocommerce',
            ])->where('is_sellable', 1)
            ->where('number', $woocommerce_product['sku'])
            ->first();

            $sync_action = 'NUMBER';
            $sync_status = Article::SYNC_STATE_SUCCESS;

            if (!$article) {
                $this->error('Article not found: ' . $woocommerce_product['sku']);

                $meta_data = array_reduce($woocommerce_product['meta_data'], function ($carry, $item) {
                    $carry[$item['key']] = $item['value'];
                    return $carry;
                }, []);

                if (! Arr::get($meta_data, 'card_id', false)) {
                    $this->error('Keine Card ID vorhanden: ' . $woocommerce_product['sku']);
                    continue;
                }

                $sync_action = 'CREATED';
                $sync_status = Article::SYNC_STATE_ERROR;

                $card = Card::firstOrImport(Arr::get($meta_data, 'card_id'));
                $article = Article::create([
                    'user_id' => $user->id,
                    'card_id' => $card->id,
                    'language_id' => Arr::get($meta_data, 'language_id', Language::DEFAULT_ID),
                    'condition' => Arr::get($meta_data, 'condition', ''),
                    'unit_price' => $woocommerce_product['price'],
                    'is_foil' => Arr::get($meta_data, 'is_foil', '') === 'Nein',
                    'is_reverse_holo' => Arr::get($meta_data, 'is_reverse_holo', '') === 'Nein',
                    'is_first_edition' => Arr::get($meta_data, 'is_first_edition', '') === 'Nein',
                    'is_signed' => Arr::get($meta_data, 'is_signed', '') === 'Nein',
                    'is_altered' => Arr::get($meta_data, 'is_altered', '') === 'Nein',
                    'is_playset' => Arr::get($meta_data, 'is_playset', '') === 'Nein',
                    'cardmarket_comments' => Arr::get($meta_data, 'cardmarket_comments', null),
                    'number' => $woocommerce_product['sku'],
                    'is_sellable' => 1,
                    'is_sellable_since' => now(),
                ]);
            }

            $article_ids[] = $article->id;

            if ($article->externalIdsWoocommerce?->sync_action === 'CREATED') {
                $sync_action = 'CREATED';
                $sync_status = Article::SYNC_STATE_ERROR;
            }

            $article->externalIdsWoocommerce()->updateOrCreate([
                'user_id' => $article->user_id,
                'external_type' => 'woocommerce',
            ], [
                'external_id' => $woocommerce_product['id'],
                'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                'sync_status' => $sync_status,
                'sync_message' => null,
                'sync_action' => $sync_action,
            ]);
        }

        $deleted_rest_count = ExternalId::where('external_type', 'woocommerce')
            ->where('user_id', $user->id)
            ->whereNotNull('external_id')
            ->whereHas('article', function ($query) {
                return $query->where('is_sellable', 1);
            })
            ->whereNotIn('article_id', $article_ids)->update([
                'sync_status' => Article::SYNC_STATE_ERROR,
                'sync_message' => null,
                'sync_action' => 'DELETED_REST',
        ]);

        $this->info('Deleted ' . $deleted_rest_count . ' articles');

        return self::SUCCESS;
    }

    private function getProducts(): Generator
    {
        $WooCommerce = new \App\APIs\WooCommerce\WooCommerceOrder();
        $page = 1;
        $total_pages = 1;
        do {
            $response = $WooCommerce->products([
                'page' => $page,
                'per_page' => 100,
            ]);

            $total_pages = $response->header('X-WP-TotalPages');

            foreach ($response->json() as $product) {
                yield $product;
            }

            $page++;
        } while ($page <= $total_pages);

        return $WooCommerce->orders()->json();
    }
}
