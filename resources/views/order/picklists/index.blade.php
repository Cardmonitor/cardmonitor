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

    <div class="card">
        <div class="card-body">
            <form action="{{ route('order.picklist.store') }}" enctype="multipart/form-data" method="POST">
                @csrf

                <div class="form-group">
                    <label for="import_sent_file">CSV-Datei</label>
                    <input type="file" class="form-control-file form-control-sm" name="articles_in_orders" id="articles_in_orders" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-sm btn-primary">Importieren</button>
                </div>

            </form>
        </div>
    </div>

    @empty($articles)
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
            </thead>
            <tbody>
                @foreach ($articles as $article)
                    <tr>
                        <td class="align-middle">
                            <img src="{{ $article->card->skryfall_image_small }}" alt="{{ $article->card->name }}" width="146" height="204">
                        </td>
                        <td class="align-middle">{{ $article->card->color_name }}</td>
                        <td class="align-middle">{{ $article->card->cmc }}</td>
                        <td class="align-middle">
                            <div>{{ $article->local_name }}</div>
                            <div class="text-muted">{{ $article->card->name }}</div>
                        </td>
                        <td class="align-middle text-right">{{ $article->amount }}</td>
                        <td class="align-middle">{{ $article->condition }}</td>
                        <td class="align-middle">{{ $article->language->name }}</td>
                        <td class="align-middle text-center">{{ $article->card->rarity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endempty

@endsection