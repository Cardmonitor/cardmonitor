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
                        @if ($last_section != $article->explodedNumber['section'])
                            <tr>
                                <td align="center" colspan="8" class="text-center" style="border-bottom: 1px solid black;border-top: 1px solid black;">
                                    <h2>{{ $article->explodedNumber['section'] }}</h2>
                                </td>
                            </tr>
                            @php
                                $last_section = $article->explodedNumber['section'];
                            @endphp
                        @endif
                        <tr>
                            <td class="align-middle">
                                <img src="{{ $article->card->image_path }}" alt="{{ $article->card->name }}" width="75">
                            </td>
                            <td class="align-middle text-right"><h3>{{ $article->explodedNumber['number'] }}</h3></td>
                            <td class="align-middle text-right">
                                <div>{{ $article->order->number }}</div>
                                <div class="text-muted">{{ $article->order->source_id }}</div>
                                <div class="text-muted">{{ $article->order->shipping_name }}</div>
                            </td>
                            <td class="align-middle">
                                <div>{{ $article->local_name }}</div>
                                @if ($article->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                    <div class="text-muted">{{ $article->card->name }}</div>
                                @endif
                            </td>
                            <td class="align-middle">
                                {{ $article->condition }}
                                @if ($article->is_foil)
                                    <div class="text-muted">Foil</div>
                                @endif
                            </td>
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