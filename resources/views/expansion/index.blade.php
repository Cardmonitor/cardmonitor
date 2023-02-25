@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Expansion</h2>
        <div></div>
    </div>
    <expansion-table :initial-background-tasks="{{ json_encode($background_tasks) }}" :games="{{ json_encode($games) }}"></expansion-table>

@endsection