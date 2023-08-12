@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">WooCommerce Bestellungen > {{ $order['id'] }}</h2>
    </div>

    <woocommerce-order-show
        :cards="{{ json_encode($cards) }}"
        :conditions="{{ json_encode($conditions) }}"
        :order="{{ json_encode($order) }}"
    ></woocommerce-order-show>

@endsection