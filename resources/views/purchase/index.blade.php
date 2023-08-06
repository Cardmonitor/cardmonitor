@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Ankäufe</h2>
        <div>

        </div>
    </div>
    <purchase-table :is-syncing-orders="{{ $is_syncing_orders }}" :states="{{ json_encode($states) }}"></purchase-table>

@endsection