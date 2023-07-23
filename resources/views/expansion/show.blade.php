@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0"><a class="text-body" href="{{ route('expansions.index') }}">Erweiterung</a><span class="d-none d-md-inline"> > {{ $model->name }}</span></h2>
        <div class="d-flex align-items-center">
            <form action="{{ $model->path }}" method="POST">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-sm btn-primary ml-1">Importieren</button>
            </form>
            <a href="{{ route('expansions.index') }}" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
            <a href="{{ $model->cardmarket_path }}" target="_blank" class="btn btn-sm btn-secondary ml-1">Cardmarket Data</a>
        </div>
    </div>

    <div class="row align-items-stretch">

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">{{ $model->name }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>Name</b></div>
                        <div class="col-value"><expansion-icon :expansion="{{ json_encode($model) }}"></expansion-icon></div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Spiel</b></div>
                        <div class="col-value">{{ $model->game->name }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Kürzel</b></div>
                        <div class="col-value">{{ $model->abbreviation }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Veröffentlicht</b></div>
                        <div class="col-value">{{ $model->released_at->format('d.m.Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">Karten</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>Anzahl</b></div>
                        <div class="col-value">{{ $model->cards_count }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Bilder</b></div>
                        <div class="col-value">{{ $model->images_count }}/{{ $model->cards_count }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col">
            <div class="card">
            <div class="card-header">Karten</div>
                <div class="card-body">
                    <expansion-cards-index :cards="{{ json_encode($model->cards) }}"></expansion-cards-index>
                </div>
            </div>
        </div>
    </div>
@endsection