@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Ankäufe</h2>
        <div class="d-flex align-items-center">
            <a href="{{ route('purchases.import.index') }}" class="btn btn-sm btn-secondary">Import</a>
        </div>
    </div>
    <purchase-table :states="{{ json_encode($states) }}"></purchase-table>

@endsection