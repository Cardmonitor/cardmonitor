@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">Pickliste Gruppiert</h2>
        <div>
            @if (count($grouped_articles))
                <a href="{{ route('order.picklist.grouped.pdf.index') }}" target="_blank" class="btn btn-sm btn-secondary">PDF</a>
            @endif
        </div>
    </div>

    @empty($grouped_articles)
        <p>Keine Artikel vorhanden.</p>
    @else

        <table class="table table-striped table-hover table-sm">
            <thead>
                <th>Bild</th>
                <th>Farbe</th>
                <th>CMC</th>
                <th>Name</th>
                <th class="text-right">Menge</th>
                <th>Zustand</th>
                <th>Sprache</th>
                <th class="text-center">Seltenheit</th>
                <th class="text-right">#</th>
            </thead>
            <tbody>
                @foreach ($grouped_articles as $grouped_article)
                    <tr>
                        <td class="align-middle">
                            <img src="{{ $grouped_article->card->image_path }}" alt="{{ $grouped_article->card->name }}" width="146" height="204">
                        </td>
                        <td class="align-middle">{{ $grouped_article->card->color_name }}</td>
                        <td class="align-middle">{{ $grouped_article->card->cmc }}</td>
                        <td class="align-middle">
                            <div>{{ $grouped_article->local_name }}</div>
                            @if ($grouped_article->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                <div class="text-muted">{{ $grouped_article->card->name }}</div>
                            @endif
                        </td>
                        <td class="align-middle text-right">{{ $grouped_article->amount_picklist }}</td>
                        <td class="align-middle">{{ $grouped_article->condition }}</td>
                        <td class="align-middle">{{ $grouped_article->language->name }}</td>
                        <td class="align-middle text-center">{{ $grouped_article->card->rarity }}</td>
                        <td class="align-middle text-right">{{ $grouped_article->card->number }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endempty

@endsection