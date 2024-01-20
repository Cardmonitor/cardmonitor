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
            @if ($model->cardmarket_article_id)
                <a href="{{ route('article.cardmarket.show', ['article' => $model->id]) }}" target="_blank" class="btn btn-sm btn-secondary ml-1">Cardmarket Data</a>
            @endif
        </div>
    </div>

    <div class="row align-items-stretch">

        <div class="col-md-6 mb-3">
            <div class="card mb-3">
                <div class="card-header">Karte</div>
                <div class="card-body">
                    <div class="col-12 col-sm text-center p-3">
                        <img class="img-fluid" src="{{ $model->card->imagePath }}">
                    </div>
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
                        <div class="col-label"><b>Seltenheit</b></div>
                        <div class="col-value">{{ $model->card->rarity }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Skryfall ID</b></div>
                        <div class="col-value">
                            @if ($model->card->skryfall_card_id)
                                <a href="https://scryfall.com/card/{{ $model->card->skryfall_card_id }}" target="_blank">{{ $model->card->skryfall_card_id }}</a>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Farbe</b></div>
                        <div class="col-value">
                            @if ($model->card->colors)
                                {{ implode(', ', $model->card->colors) }} ({{ $model->card->color_name }})
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>CMC</b></div>
                        <div class="col-value">{{ $model->card->cmc }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Erweiterung</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>{{ __('app.name') }}</b></div>
                        <div class="col-value">{{ $model->card->expansion->name }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Abkürung</b></div>
                        <div class="col-value">{{ $model->card->expansion->abbreviation }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>ID</b></div>
                        <div class="col-value">{{ $model->card->expansion->id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Skryfall ID</b></div>
                        <div class="col-value">{{ $model->card->expansion->skryfall_expansion_id }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Veröffentlicht</b></div>
                        <div class="col-value">{{ $model->card->expansion->released_at ? $model->card->expansion->released_at->format('d.m.Y H:i') : 'Noch nicht veröffentlicht' }}</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-6 mb-3">

            <div class="card mb-3">
                <div class="card-header">Cardmarket</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>Produkt ID</b></div>
                        <div class="col-value">{{ $model->card->cardmarket_product_id ?: 'nicht vorhanden' }}</div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-label"><b>Artikel ID</b></div>
                        <div class="col-value d-flex align-items-center justify-content-between">
                            {{ $model->cardmarket_article_id ?: 'nicht vorhanden' }}
                            @if ($model->can_upload_to_cardmarket)
                                <form action="{{ route('article.cardmarket.update', ['article' => $model->id]) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <button type="submit" class="btn btn-sm btn-secondary ml-1" title="{{ __('app.actions.update') }}"><i class="fas fa-fw fa-cloud-upload-alt"></i></button>
                                </form>
                            @endif
                            @if ($model->externalIdsCardmarket?->external_id)
                                <a href="{{ route('article.cardmarket.show', ['article' => $model->id]) }}" target="_blank" class="btn btn-sm btn-secondary ml-1"><i class="fas fa-fw fa-eye"></i></a>
                                <form action="{{ route('article.cardmarket.destroy', ['article' => $model->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-danger ml-1" title="{{ __('app.actions.delete') }}"><i class="fas fa-fw fa-trash"></i></button>
                                </form>
                            @endif
                        </div>
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
                        <div class="col-label"><b>Reverse Holo</b></div>
                        <div class="col-value">{{ $model->is_reverse_holo }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>First Edition</b></div>
                        <div class="col-value">{{ $model->is_first_edition }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Verkaufspreis</b></div>
                        <div class="col-value">{{ $model->unit_price_formatted }} €</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Kommentar</b></div>
                        <div class="col-value">{{ $model->cardmarket_comments }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>Nummer aus Kommentar</b></div>
                        <div class="col-value">{{ $model->number_from_cardmarket_comments }}</div>
                    </div>
                    @if ($model->externalIdsCardmarket)
                        <div class="row">
                            <div class="col-label">&nbsp;</div>
                            <div class="col-value">&nbsp;</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Status</b></div>
                            <div class="col-value">{{ $model->externalIdsCardmarket->sync_status_name }}</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Action</b></div>
                            <div class="col-value">{{ $model->externalIdsCardmarket->sync_action ?: '-' }}</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Message</b></div>
                            <div class="col-value">{{ $model->externalIdsCardmarket->sync_message ?: '-' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">WooCommerce</div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-label"><b>Produkt ID</b></div>
                        <div class="col-value d-flex align-items-center justify-content-between">
                            {{ $model->externalIdsWooCommerce?->external_id ?? 'nicht vorhanden' }}
                            @if ($model->can_upload_to_cardmarket)
                                <form action="{{ route('article.woocommerce.update', ['article' => $model->id]) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <button type="submit" class="btn btn-sm btn-secondary ml-1" title="{{ __('app.actions.update') }}"><i class="fas fa-fw fa-cloud-upload-alt"></i></button>
                                </form>
                            @endif
                            @if ($model->externalIdsWooCommerce?->external_id)
                                <a href="{{ route('article.woocommerce.show', ['article' => $model->id]) }}" target="_blank" class="btn btn-sm btn-secondary ml-1"><i class="fas fa-fw fa-eye"></i></a>
                                <form action="{{ route('article.woocommerce.destroy', ['article' => $model->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-danger ml-1" title="{{ __('app.actions.delete') }}"><i class="fas fa-fw fa-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @if ($model->externalIdsWooCommerce)
                        <div class="row">
                            <div class="col-label">&nbsp;</div>
                            <div class="col-value">&nbsp;</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Status</b></div>
                            <div class="col-value">{{ $model->externalIdsWooCommerce->sync_status_name }}</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Action</b></div>
                            <div class="col-value">{{ $model->externalIdsWooCommerce->sync_action ?: '-' }}</div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sync Message</b></div>
                            <div class="col-value">{{ $model->externalIdsWooCommerce->sync_message ?: '-' }}</div>
                        </div>
                    @endif
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