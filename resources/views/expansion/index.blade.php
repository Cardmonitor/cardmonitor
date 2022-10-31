@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Expansion</h2>
        <div></div>
    </div>
    <expansion-table :games="{{ json_encode($games) }}"></expansion-table>

@endsection