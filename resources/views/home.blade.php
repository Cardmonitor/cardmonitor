@extends('layouts.app')

@section('content')
    <div class="row mb-3 align-items-stretch">

        @if ($cardmarketConnectLink)
            <div class="col">
                <div class="card h-100">
                    <div class="card-header">Cardmarket Konto</div>
                    <div class="card-body">
                        @if ($cardmarketConnectLink)
                            <a href="{{ $cardmarketConnectLink }}">Verbinde dein Cardmarket Konto mit Cardmonitor</a>
                        @else
                            <div>{{ $cardmarketAccount['username'] }} {{ $cardmarketAccount['reputation'] }}</div>
                            <div>{{ number_format($cardmarketAccount['moneyDetails']['totalBalance'], 2, ',', '.') }} €</div>
                            @if ($cardmarketAccount['unreadMessages'])
                                <div>{{ $cardmarketAccount['unreadMessages'] }} ungelesene Nachrichten</div>
                            @endif
                            <div>Gültig bis: {{ $invalid_at->format('d.m.Y H:i') }} <a href="{{ route('cardmarket.callback.update') }}" class="btn btn-sm btn-link" title="Erneuern"><i class="fas fa-fw fa-sync"></i></a></div>
                            <form action="{{ route('cardmarket.callback.destroy') }}" class="ml-1 mt-3" method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-secondary" title="Aktualisieren">Konto trennen</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @else

            <home-order-paid class="col-12 mb-3" :is-syncing-orders="{{ $is_syncing_orders }}"></home-order-paid>
            @include('order.import.sent.create')

            <home-article-index class="col-12 col-lg mb-3 mb-xl-0" :is-syncing-articles="{{ $is_syncing_articles }}"></home-article-index>

            <div class="col-12 col-xl d-none d-xl-block">
                <div class="card h-100">
                    <div class="card-header">Letzte Bewertungen</div>
                    <div class="card-body" style="max-height: 250px; overflow: auto;">
                        @if (count($evaluations))
                            <table class="table table-striped table-hover">
                                <tbody>
                                    @foreach ($evaluations as $evaluation)
                                        <tr>
                                            <td class="align-middle">{{ $evaluation->order->received_at->format('d.m.Y H:i') }}</td>
                                            <td class="align-middle">
                                                <a href="{{ $evaluation->order->path }}">{{ $evaluation->order->cardmarket_order_id }}</a>
                                                <div class="text-muted">{{ $evaluation->order->buyer->name }}</div>
                                            </td>
                                            <td class="align-middle text-center">
                                                <div class="d-flex justify-content-around">
                                                    <div><evaluation-icon :value="{{ $evaluation->grade }}"></evaluation-icon></div>
                                                    <div><evaluation-icon :value="{{ $evaluation->item_description }}"></evaluation-icon></div>
                                                    <div><evaluation-icon :value="{{ $evaluation->packaging }}"></evaluation-icon></div>
                                                </div>
                                                <div class="text-overflow-ellipsis">{{ $evaluation->comment }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="alert alert-dark" role="alert">
                                Noch keine Bewertungen vorhanden.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
    <home-order-index></home-order-index>
@endsection
