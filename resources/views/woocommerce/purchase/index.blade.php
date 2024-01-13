@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Ankäufe Import</h2>
        <div class="d-flex align-items-center">
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-secondary">Ankäufe</a>
        </div>
    </div>

    <woocommerce-purchase-table></woocommerce-purchase-table>

@endsection