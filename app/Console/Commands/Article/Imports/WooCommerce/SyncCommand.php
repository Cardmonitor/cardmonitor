<?php

namespace App\Console\Commands\Article\Imports;

use App\User;
use Generator;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use App\Models\Articles\Article;
use App\Models\Articles\ExternalId;

class WooCommerceCommand extends Command
{
    protected $signature = 'article:imports:woocommerce:sync {user}';

    protected $description = 'Syncs Articles from WooCommerce and sets the sync_action';

    public function handle()
    {
        $user = User::findOrFail($this->argument('user'));
        $article_ids = [];
        foreach ($this->getProducts() as $woocommerce_product) {
            $this->info('Syncing product: ' . $woocommerce_product['name']);
            $article = $user->articles()->where('is_sellable', 1)->where('number', $woocommerce_product['sku'])->first();

            if (!$article) {
                $this->error('Article not found: ' . $woocommerce_product['sku']);
                continue;
            }

            $article_ids[] = $article->id;

            $article->externalIdsWoocommerce()->updateOrCreate([
                'user_id' => $article->user_id,
                'external_type' => 'woocommerce',
            ], [
                'external_id' => $woocommerce_product['id'],
                'external_updated_at' => Carbon::createFromFormat('Y-m-d\TH:i:s', $woocommerce_product['date_modified']),
                'sync_status' => Article::SYNC_STATE_SUCCESS,
                'sync_message' => null,
                'sync_action' => 'NUMBER',
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
