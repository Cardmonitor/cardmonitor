@extends('layouts.app')

@section('content')

    <div class="d-flex mb-3">
        <h2 class="col mb-0"><a class="text-body" href="/order">{{ __('app.nav.order') }}</a><span class="d-none d-md-inline"> > {{ $model->cardmarket_order_id }}<span class="d-none d-lg-inline"> - {{ $model->stateFormatted }}</span></span></h2>
        <div class="d-flex align-items-center">
            <button class="btn btn-sm btn-secondary ml-1" data-toggle="modal" data-target="#message-create" data-model-id="{{ $model->id }}"><i class="fas fa-envelope"></i></button>
            <form action="{{ $model->path . '/sync' }}" class="ml-1" method="POST">
                @csrf
                @method('PUT')

                <button type="submit" class="btn btn-sm btn-secondary" title="Aktualisieren"><i class="fas fa-fw fa-sync"></i></button>
            </form>
            <form action="{{ $model->path . '/articles/state' }}" class="ml-1" method="POST">
                @csrf
                @method('PUT')

                @if ($model->articles_on_hold_count === 0)
                    <input type="hidden" name="state" value="{{ \App\Models\Articles\Article::STATE_ON_HOLD }}">
                    <button type="submit" class="btn btn-sm btn-secondary" title="Zurückstellen"><i class="fas fa-fw fa-pause"></i></button>
                @else
                    <input type="hidden" name="state" value="">
                    <button type="submit" class="btn btn-sm btn-secondary" title="Picken"><i class="fas fa-fw fa-play"></i></button>
                @endif
            </form>
            <a href="{{ url('/order') }}" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
            <a href="{{ route('order.cardmarket.show', ['order' => $model->id]) }}" target="_blank" class="btn btn-sm btn-secondary ml-1">Cardmarket Data</a>
        </div>
    </div>

    @if ($model->articles_on_hold_count > 0)
        <div class="alert alert-warning">
            <i class="fas fa-fw fa-pause"></i> {{ $model->articles_on_hold_count }}/{{ $model->articles_count }} Artikel sind zurückgestellt.
        </div>
    @endif

    <div class="row align-items-stretch mb-3">

        <div class="col-6 col-sm mb-3 mb-sm-0">
            <div class="card font-weight-bold text-light h-100">
                <div class="card-body {{ (is_null($model->bought_at) ? '' : 'bg-primary') }}">
                    {{ __('order.states.bought') }}{{ (is_null($model->bought_at) ? '' : ' : ' . $model->bought_at->format('d.m.Y H:i'))}}
                </div>
            </div>
        </div>

        <div class="col-6 col-sm mb-3 mb-sm-0">
            <div class="card font-weight-bold text-light h-100">
                <div class="card-body {{ (is_null($model->paid_at) ? '' : 'bg-primary') }}">
                    {{ __('order.states.paid') }}{{ (is_null($model->paid_at) ? '' : ' : ' . $model->paid_at->format('d.m.Y H:i'))}}
                </div>
            </div>
        </div>

        <div class="col-6 col-sm">
            <div class="card font-weight-bold text-light h-100">
                <div class="card-body {{ (is_null($model->sent_at) ? '' : 'bg-primary') }}">
                    {{ __('order.states.sent') }}{{ (is_null($model->sent_at) ? '' : ' : ' . $model->sent_at->format('d.m.Y H:i'))}}
                </div>
            </div>
        </div>

        <div class="col-6 col-sm">
            <div class="card font-weight-bold text-light h-100">
                <div class="card-body {{ (is_null($model->received_at) ? '' : 'bg-primary') }}">
                    {{ __('order.states.received') }}{{ (is_null($model->received_at) ? '' : ' : ' . $model->received_at->format('d.m.Y H:i'))}}
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-md-6">

            <div class="card mb-3">
                <div class="card-header">{{ $model->cardmarket_order_id }}</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-label"><b>{{ __('order.id') }}</b></div>
                        <div class="col-value">{{ $model->cardmarket_order_id }}</div>
                    </div>
                    @if ($model->buyer)
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.buyer') }}</b></div>
                            <div class="col-value">{{ $model->buyer->username }}</div>
                        </div>
                    @endif
                    @if ($model->seller)
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.seller') }}</b></div>
                            <div class="col-value">{{ $model->seller->username }}</div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-label"><b>{{ __('app.cards') }}</b></div>
                        <div class="col-value">{{ $model->articles_count }}</div>
                    </div>
                    @if ($model->evaluation)
                        <div class="row">
                            <div class="col-label">&nbsp;</div>
                            <div class="col-value"></div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.evaluations.grade') }}</b></div>
                            <div class="col-value"><evaluation-icon :value="{{ $model->evaluation->grade }}"></evaluation-icon></div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.evaluations.item_description') }}</b></div>
                            <div class="col-value"><evaluation-icon :value="{{ $model->evaluation->item_description }}"></evaluation-icon></div>
                        </div>
                        <div class="row">
                            <div class="col-label"><b>{{ __('order.evaluations.packaging') }}</b></div>
                            <div class="col-value"><evaluation-icon :value="{{ $model->evaluation->packaging }}"></evaluation-icon></div>
                        </div>
                        @if ($model->evaluation->comment)
                            <div class="row">
                                <div class="col-label"><b>{{ __('order.evaluations.comment') }}</b></div>
                                <div class="col-value">{{ $model->evaluation->comment }}</div>
                            </div>
                        @endif
                        @if (! empty($model->evaluation->complaint))
                            @foreach ($model->evaluation->complaint as $complaint)
                                <div class="row">
                                    <div class="col-label font-weight-bold text-danger">{{ ($loop->first ? __('order.evaluations.complaint') : '') }}</div>
                                    <div class="col-value">{{ $complaint }}</div>
                                </div>
                            @endforeach
                        @endif
                    @endif
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
            <order-article-index :model="{{ json_encode($model) }}"></order-article-index>
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