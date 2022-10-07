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
        <h1>Picklist</h1>

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
                    <th class="text-right">#</th>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td class="align-middle">
                                <img src="{{ $article->card->skryfall_image_small }}" width="75">
                            </td>
                            <td class="align-middle">{{ $article->card->color_name }}</td>
                            <td class="align-middle">{{ $article->card->cmc }}</td>
                            <td class="align-middle">
                                <div>{{ $article->local_name }}</div>
                                <div class="text-muted">{{ $article->card->name }}</div>
                            </td>
                            <td class="align-middle text-right">{{ $article->amount_picklist }}</td>
                            <td class="align-middle">{{ $article->condition }}</td>
                            <td class="align-middle">{{ $article->language->name }}</td>
                            <td class="align-middle text-center">{{ $article->card->rarity }}</td>
                            <td class="align-middle text-right">{{ $article->card->number }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endempty
    </div>
</body>
</html>