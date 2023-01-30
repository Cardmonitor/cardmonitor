<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page
        {

        }
        body
        {
            font-family: sans-serif;
            font-size: 12px;
        }

        table
        {
            width: 100%;
        }

        table.receipts th, table.receipts td
        {
            padding: 3px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: top;
            line-height: 24px;
        }

        table.receipts
        {
            border-collapse: collapse;
        }

        table.receipts tfoot
        {
            margin-top: 10px;
        }

        .text-muted {
            color: #6c757d !important;
        }

    </style>
</head>
<body style="position: relative;">

    <div style="margin: 0 10mm;">
        <h1>Einlagerung</h1>

        @empty($articles)
            <p>Keine Artikel vorhanden.</p>
        @else
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <th>Bild</th>
                    <th>Name</th>
                    <th class="text-right">Nummer</th>
                    <th>Zustand</th>
                    <th>Sprache</th>
                    <th class="text-center">Seltenheit</th>
                    <th class="text-center">Foil</th>
                    <th class="text-right">Lagernummer</th>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td class="align-middle">
                                <img src="{{ $use_image_storage_path ? $article->card->image_storage_path : $article->card->image_path }}" alt="{{ $article->card->name }}" width="75">
                            </td>
                            <td class="align-middle">
                                <div>{{ $article->local_name }}</div>
                                @if ($article->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                <div class="text-muted">{{ $article->card->name }}</div>
                                @endif
                            </td>
                            <td class="align-middle text-right">#{{ $article->card->number }}</td>
                            <td class="align-middle">{{ $article->condition }}</td>
                            <td class="align-middle">{{ $article->language->name }}</td>
                            <td class="align-middle text-center">{{ $article->card->rarity }}</td>
                            <td class="align-middle text-center">{{ $article->is_foil ? 'Foil' : '' }}</td>
                            <td class="align-middle text-right">{{ $article->number }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endempty
    </div>
</body>
</html>