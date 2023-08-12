@extends('layouts.app')

@section('content')

    <div class="d-flex mb-3">
        <h2 class="col mb-0"><a class="text-body" href="{{ route('purchases.index') }}">Ankäufe</a><span class="d-none d-md-inline"> > {{ $model->source_id }}<span class="d-none d-lg-inline"> - {{ $model->state }}</span></span></h2>
        <div class="d-flex align-items-center">
        <form action="{{ route('woocommerce.order.store') }}" class="ml-1" method="POST">
                @csrf
                @method('POST')

                <input type="hidden" name="id" value="{{ $model->source_id }}">
                <button type="submit" class="btn btn-sm btn-secondary" title="Importieren"><i class="fas fa-fw fa-sync"></i></button>
            </form>
            <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
        </div>
    </div>

    @if ($model->articles_on_hold_count > 0)
        <div class="alert alert-warning">
            <i class="fas fa-fw fa-pause"></i> {{ $model->articles_on_hold_count }}/{{ $model->articles_count }} Artikel sind zurückgestellt.
        </div>
    @endif

    <div class="row">

        <div class="col-md-6">

            <div class="card mb-3">
                <div class="card-header">{{ $model->source_id }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>{{ __('order.id') }}</b></div>
                        <div class="col-value">{{ $model->source_id }}</div>
                    </div>
                    @if ($model->seller)
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.seller') }}</b></div>
                            <div class="col-value">{{ $model->seller->firstname }} {{ $model->seller->name }}</div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-label"><b>{{ __('app.cards') }}</b></div>
                        <div class="col-value">{{ $model->articles_count }}</div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-6">

            <div class="card mb-3">
                <div class="card-header">{{ __('order.shipping_address') }}</div>
                <div class="card-body">
                    <div>{!! nl2br($model->shippingAddressText) !!}</div>
                    @if ($model->tracking_number)
                        <div class="row">
                            <div class="col-label">&nbsp;</div>
                            <div class="col-value"></div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>Sendungsnummer</b></div>
                            <div class="col-value">{{ $model->tracking_number }}</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ __('app.articles') }}</div>
        <div class="card-body">
            <purchase-article-index :model="{{ json_encode($model) }}" :conditions="{{ json_encode($conditions) }}" :languages="{{ json_encode($languages) }}"></purchase-article-index>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" role="dialog" id="message-create">
        <div class="modal-dialog" role="document">
            <form action="/order/{{ $model->id }}/message" method="POST">
                @csrf

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('order.show.message_modal.title', ['buyer' => ($model->buyer ? $model->buyer->username : '')]) }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="message-text">{{ __('app.message') }}</label>
                            <textarea class="form-control" id="message-text" name="message-text" rows="15"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('app.actions.send') }}</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('app.actions.cancel') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection