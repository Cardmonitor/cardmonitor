@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0"><span class="d-none d-md-inline">Log > {{ $task }}</span></h2>
        <div class="d-flex align-items-center"></div>
    </div>

    <user-backgroundtask-show task="{{ $task }}"></user-backgroundtask-show>

@endsection