<?php

namespace App\Http\Controllers\Orders\WooCommerce;

use App\APIs\WooCommerce\Status as WooCommerceStatus;
use App\Enums\Orders\Status;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $attributes = $request->validate([
            'state' => 'sometimes|nullable|in:' . implode(',', Status::values()),
        ]);

        $user = auth()->user();

        $woocommerce_state = Status::from($attributes['state'])->wooCommerceSlug();

        $woocommerce_states = [$woocommerce_state];
        if ($attributes['state'] == Status::PAID->value) {
            $woocommerce_states[] = WooCommerceStatus::PENDING->value;
            $woocommerce_states[] = WooCommerceStatus::ON_HOLD->value;
        }

        Artisan::queue('order:woocommerce:import', [
            'user' => $user->id,
            '--states' => $woocommerce_states,
        ]);

        return [
            'state' => $attributes['state'],
            'woocommerce_state' => $woocommerce_state,
            'woocommerce_states' => $woocommerce_states,
        ];
    }

    public function show(Order $order)
    {
        if (!$order->is_woocommerce) {
            return back()->with('status', [
                'type' => 'error',
                'text' => 'Bestellung nicht von WooCommerce.',
            ]);
        }

        Artisan::call('order:woocommerce:import', [
            'user' => $order->user_id,
            '--order' => $order->source_id,
        ]);

        return back()->with('status', [
            'type' => 'success',
            'text' => 'Bestellung aktualisiert.',
        ]);
    }
}
