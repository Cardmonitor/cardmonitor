@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">{{ __('app.nav.order') }}</h2>
        <div>
            <a href="{{ route('order.picklist.grouped.index') }}" class="btn btn-sm btn-secondary">Pickliste Gruppiert</a>
        </div>
    </div>
    <order-table :is-syncing-orders="{{ $is_syncing_orders }}" :states="{{ json_encode($states) }}"></order-table>

    @include('order.import.sent.create')

@endsection