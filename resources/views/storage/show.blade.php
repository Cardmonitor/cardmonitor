@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0"><a class="text-body" href="/storages">{{ __('app.nav.storages') }}</a><span class="d-none d-md-inline"> > {{ $model->full_name }}</span></h2>
        <div class="d-flex align-items-center">
            <a href="{{ $model->editPath }}" class="btn btn-sm btn-primary" title="{{ __('app.actions.edit') }}"><i class="fas fa-edit"></i></a>
            <a href="/storages" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
            @if ($model->isDeletable())
                <form action="{{ $model->path }}" class="ml-1" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('app.actions.delete') }}"><i class="fas fa-trash"></i></button>
                </form>
            @endif
        </div>
    </div>

    @if ($model->is_uploaded)
        <div class="alert alert-info">
            Alle Artikel wurden hochgeladen und der Lagerplatz ist nicht mehr im Lagerplatzfilter verfügbar.
        </div>
    @endif

    <div class="row align-items-stretch">

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">{{ $model->full_name }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-label"><b>{{ __('app.name') }}</b></div>
                                <div class="col-value">{{ $model->name }}</div>
                            </div>
                            @if ($model->parent)
                                <div class="row">
                                    <div class="col-label"><b>{{ __('storages.main_storage') }}</b></div>
                                    <div class="col-value"><a class="text-body" href="{{ $model->parent->path }}">{{ $model->parent->name }}</a></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card mb-3">
                <div class="card-header">{{ __('storages.articles') }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>{{ __('storages.articles') }}</b></div>
                        <div class="col-value">{{ $model->articleStats->count_formatted }}</div>
                    </div>
                    <div class="row">
                        <div class="col-label"><b>{{ __('storages.price') }}</b></div>
                        <div class="col-value">{{ $model->articleStats->price_formatted }} €</div>
                    </div>
                </div>
            </div>

            @if (count($model->descendants))
                <div class="card mb-3">
                    <div class="card-header">{{ __('storages.sub_storages') }}</div>
                    <div class="card-body">
                        @foreach ($model->descendants as $descendant)
                            <div class="">
                                <h6><a class="text-body" href="{{ $descendant->path }}">{{ $descendant->full_name }}</a></h6>
                            </div>
                            <div class="row">
                                <div class="col-label"><b>{{ __('storages.articles') }}</b></div>
                                <div class="col-value">{{ $descendant->articleStats->count_formatted }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-label"><b>{{ __('storages.price') }}</b></div>
                                <div class="col-value">{{ $descendant->articleStats->price_formatted }} €</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>



    </div>

    <div class="row align-items-stretch">

        <div class="col">

            @if($model->slots && count($model->articles))
                <div class="card mb-3">
                    <div class="card-header">Slots</div>
                    <div class="card-body">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Slot</th>
                                    <th>Artikel</th>
                                    <th>Preis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($model->articles as $article)
                                    <tr>
                                        <td class="align-middle">{{ $article->slot }}</td>
                                        <td class="align-middle">
                                            <span class="fi fi-{{ $article->language->code }}" title="{{ $article->language->name }}"></span> {{ $article->local_name }} ({{ $article->card->number }})
                                            @if ($article->should_show_card_name)
                                                <div class="text-muted">{{ $article->card_name }}</div>
                                            @endif
                                        </td>
                                        <td class="align-middle">{{ $article->unit_price_formatted }} €</td>
                                    </tr>
                                @endforeach
                            </tbody>
                    </div>
                </div>
            @endif

        </div>

    </div>


@endsection