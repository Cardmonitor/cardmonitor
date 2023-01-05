@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0 pl-0"><a class="text-body" href="/article">{{ __('app.nav.article') }}</a><span class="d-none d-md-inline"> > {{ $model->local_name }}</span></h2>
        <div class="d-flex align-items-center">
            <a href="{{ $model->editPath }}" class="btn btn-sm btn-primary" title="{{ __('app.actions.edit') }}"><i class="fas fa-edit"></i></a>
            <a href="/article" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
            @if ($model->isDeletable())
                <form action="{{ $model->path }}" class="ml-1" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('app.actions.delete') }}"><i class="fas fa-trash"></i></button>
                </form>
            @endif
        </div>
    </div>

    <div class="row align-items-stretch">

        <div class="col-md-6 mb-3">
            <div class="card mb-3">
                <div class="card-header">{{ $model->local_name }}</div>
                <div class="card-body">
                    <div class="col-12 col-sm text-center p-3">
                        <img class="img-fluid" src="{{ $model->card->imagePath }}">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Cardmonitor</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>Nummer</b></div>
                        <div class="col-value">{{ $model->number }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Lagerplatz</b></div>
                        <div class="col-value">{{ $model->storage_id ? $model->storage->full_name : 'Kein Lagerplatz' }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Einkaufspreis</b></div>
                        <div class="col-value">{{ $model->unit_cost_formatted }} €</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Erstellt</b></div>
                        <div class="col-value">{{ $model->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Bearbeitet</b></div>
                        <div class="col-value">{{ $model->updated_at->format('d.m.Y H:i') }}</div>
                    </div>
                    @if ($model->source_slug)
                        <div class="row">
                            <div class="col-label"><b>Herkunft</b></div>
                            <div class="col-value">{{ $model->source_slug }}</div>
                        </div>
                    @endif
                    @if ($model->source_id)
                        <div class="row">
                            <div class="col-label"><b>Herkunft ID</b></div>
                            <div class="col-value">{{ $model->source_id }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">

            <div class="card">
                <div class="card-header">Cardmarket</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>{{ __('app.name') }}</b></div>
                        <div class="col-value">
                            <a href="https://www.cardmarket.com{{ $model->card->website }}" target="_blank">{{ $model->local_name }}</a>
                            @if ($model->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                <div class="text-muted">{{ $model->card->name }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Produkt ID</b></div>
                        <div class="col-value">{{ $model->card->cardmarket_product_id ?: 'nicht vorhanden' }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Artikel ID</b></div>
                        <div class="col-value">{{ $model->cardmarket_article_id ?: 'nicht vorhanden' }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Bearbeitet</b></div>
                        <div class="col-value">{{ $model->cardmarket_last_edited ? $model->cardmarket_last_edited->format('d.m.Y H:i') : 'Nicht bearbeitet' }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Sprache</b></div>
                        <div class="col-value">{{ $model->language->name }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Zustand</b></div>
                        <div class="col-value">{{ $model->condition }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Foil</b></div>
                        <div class="col-value">{{ $model->is_foil }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Verkaufspreis</b></div>
                        <div class="col-value">{{ $model->unit_price_formatted }} €</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col">

            <div class="card">
                <div class="card-header">Historie</div>
                <div class="card-body">
                    TODO
                </div>
            </div>

        </div>


    </div>

@endsection