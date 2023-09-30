@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Ankäufe > {{ $order['id'] }}</h2>
        <div class="d-flex align-items-center">
            <a href="{{ route('purchases.import.index') }}" class="btn btn-sm btn-secondary">Übersicht</a>
        </div>
    </div>

    <woocommerce-order-show
        :cards="{{ json_encode($cards) }}"
        :conditions="{{ json_encode($conditions) }}"
        :order="{{ json_encode($order) }}"
    ></woocommerce-order-show>

@endsection