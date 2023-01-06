@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Pickliste</h2>
        <div>
            @if (count($articles))
                <a href="{{ route('order.picklist.pdf.index') }}" target="_blank" class="btn btn-sm btn-secondary">PDF</a>
            @endif
        </div>
    </div>

    @empty($articles)
        <p>Keine Artikel vorhanden.</p>
    @else

        <table class="table table-striped table-hover table-sm">
            <thead>
                <th>Bild</th>
                <th class="text-right">Lagernummer</th>
                <th class="text-right">Bestellung</th>
                <th>Name</th>
                <th>Zustand</th>
                <th>Sprache</th>
                <th class="text-center">Seltenheit</th>
                <th class="text-right">#</th>
            </thead>
            <tbody>
                @foreach ($articles as $article)
                    <tr>
                        <td class="align-middle">
                            <img src="{{ $article->card->image_path }}" alt="{{ $article->card->name }}" width="146" height="204">
                        </td>
                        <td class="align-middle text-right">{{ $article->number }}</td>
                        <td class="align-middle text-right">
                            <div>{{ $article->order->number }}</div>
                            <div class="text-muted">{{ $article->order->id }}</div>
                            <div class="text-muted">{{ $article->order->shipping_name }}</div>
                        </td>
                        <td class="align-middle">
                            <div>{{ $article->local_name }}</div>
                            @if ($article->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                <div class="text-muted">{{ $article->card->name }}</div>
                            @endif
                        </td>
                        <td class="align-middle">{{ $article->condition }}</td>
                        <td class="align-middle">{{ $article->language->name }}</td>
                        <td class="align-middle text-center">{{ $article->card->rarity }}</td>
                        <td class="align-middle text-right">{{ $article->card->number }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endempty

@endsection