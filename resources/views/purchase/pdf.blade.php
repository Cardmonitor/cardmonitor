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

        table th, table td
        {
            padding: 3px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: top;
            line-height: 24px;
        }

        table
        {
            border-collapse: collapse;
        }

        table tfoot
        {
            margin-top: 10px;
        }

        table thead td, table tfoot td
        {
            font-weight: bold;
            border-top: 1px solid #e5e5e5;
        }

        .text-muted {
            color: #6c757d !important;
        }

    </style>
</head>
<body style="position: relative;">

    <div style="margin: 0 10mm;">
        <h1>Ankauf {{ $order->source_id }}</h1>

        @empty($order->articles)
            <p>Keine Artikel vorhanden.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <td class="text-left">Name</td>
                        <td class="text-left">Erweiterung</td>
                        <td class="text-right">#</td>
                        <td class="text-left">Zustand</td>
                        <td class="text-left">Sprache</td>
                        <td class="text-right">Preis</td>
                        <td class="text-left">Kommentar</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->articles as $article)
                        <tr class="text-warning">
                            <td class="align-middle">
                                <div>{{ $article->local_name }}</div>
                                @if ($article->language_id != \App\Models\Localizations\Language::DEFAULT_ID)
                                    <div class="text-muted">{{ $article->card->name }}</div>
                                @endif
                            </td>
                            <td class="align-middle text-right">{{ $article->card->expansion->name }} ({{ $article->card->expansion->abbreviation }})</td>
                            <td class="align-middle text-right">{{ $article->card->number }}</td>
                            <td class="align-middle">
                                {{ $article->condition }}
                                @if ($article->is_foil)
                                    <div class="text-muted">Foil</div>
                                @endif
                            </td>
                            <td class="align-middle">{{ $article->language->name }}</td>
                            <td class="align-middle text-right">{{ number_format($article->unit_cost, 2, ',', '.') }} €</td>
                            <td class="align-middle">{{ $article->state_comments }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">{{ $order->articles->count() }} Einzelkarten</td>
                        <td class="text-right">{{ number_format($order->articles->sum('unit_cost'), 2, ',', '.') }} €</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endempty
    </div>
</body>
</html>