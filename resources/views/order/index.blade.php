@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">{{ __('app.nav.order') }}</h2>
        <div>
            <a href="{{ route('order.picklist.index') }}" class="btn btn-sm btn-secondary">Pickliste</a>
        </div>
    </div>
    <order-table :initial-background-tasks="{{ json_encode($background_tasks) }}" :states="{{ json_encode($states) }}"></order-table>

    @include('order.import.sent.create')

@endsection