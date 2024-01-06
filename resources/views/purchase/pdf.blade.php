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
            <table class="table table-bordered" style="margin-bottom: 30px;">
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
                                @if ($article->should_show_card_name)
                                    <div class="text-muted">{{ $article->card_name }}</div>
                                @endif
                            </td>
                            @if ($article->card_id)
                                <td class="align-middle text-right">{{ $article->card->expansion->name }} ({{ $article->card->expansion->abbreviation }})</td>
                                <td class="align-middle text-right">{{ $article->card->number }}</td>
                                <td class="align-middle">
                                    {{ $article->condition }}
                                    @if ($article->is_foil)
                                        <div class="text-muted">Foil</div>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $article->language->name }}</td>
                            @else
                                <td class="align-middle text-right"></td>
                                <td class="align-middle text-right"></td>
                                <td class="align-middle"></td>
                                <td class="align-middle"></td>
                            @endif
                            <td class="align-middle text-right">{{ number_format($article->unit_cost, 2, ',', '.') }} €</td>
                            <td class="align-middle">{!! nl2br($article->state_comments) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">{{ $order->articles->count() }} Positionen</td>
                        <td class="text-right">{{ number_format($order->articles->sum('unit_cost'), 2, ',', '.') }} €</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        @endempty

        <p>Keine einzelne Karte hat einen Ankaufspreis von 500€ oder mehr: {{ $articles_gt_500->count() ? 'NEIN' : 'JA' }}</p>

        @if ($articles_gt_500->count() > 0)
        <table class="table table-bordered" style="margin-bottom: 30px;">
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
                    @foreach ($articles_gt_500 as $article)
                        <tr class="text-warning">
                            <td class="align-middle">
                                <div>{{ $article->local_name }}</div>
                                @if ($article->should_show_card_name)
                                    <div class="text-muted">{{ $article->card_name }}</div>
                                @endif
                            </td>
                            @if ($article->card_id)
                                <td class="align-middle text-right">{{ $article->card->expansion->name }} ({{ $article->card->expansion->abbreviation }})</td>
                                <td class="align-middle text-right">{{ $article->card->number }}</td>
                                <td class="align-middle">
                                    {{ $article->condition }}
                                    @if ($article->is_foil)
                                        <div class="text-muted">Foil</div>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $article->language->name }}</td>
                            @else
                                <td class="align-middle text-right"></td>
                                <td class="align-middle text-right"></td>
                                <td class="align-middle"></td>
                                <td class="align-middle"></td>
                            @endif
                            <td class="align-middle text-right">{{ number_format($article->unit_cost, 2, ',', '.') }} €</td>
                            <td class="align-middle">{!! nl2br($article->state_comments) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p>Der Verkauf erfolgt ohne Ausweis von Umsatzsteuer: JA</p>

        <p>
            Kunde:<br />
            {{ $order->shipping_name }}, {{ $order->shipping_street }}, {{ $order->shipping_zip }} {{ $order->shipping_city }}
        </p>

        <p>
            Käufer:<br />
            Blank & Schmidt GbR, KEEPSEVEN, Große Burgstr. 25, 23552 Lübeck
        </p>

        <p>Datum des Gradings: {{ $order->created_at->format('d.m.Y') }}</p>

        <p>Auszahlung erfolgt am: ________________________ via ________________________</p>

        <table border="0" style="margin-top: 30px;margin-bottom: 30px;">
            <tbody>
                <tr>
                    <td style="border-bottom: 0;line-height: normal;padding: 0;">Unterschrift Kunde: ________________________</td>
                    <td style="border-bottom: 0;line-height: normal;padding: 0;text-align: center;">Unterschrift KEEPSEVEN: ________________________</td>
                </tr>
                <tr>
                    <td style="border-bottom: 0;line-height: normal;padding: 0;">({{ $order->shipping_name }})</td>
                    <td style="border-bottom: 0;line-height: normal;padding: 0;"></td>
                </tr>
            </tbody>
        </table>

        <p>Ich bestätige die AGB der Blank & Schmidt GbR.</p>

        <p>Ich bestätige mit meiner Unterschrift, dass ich rechtmäßiger Eigentümer der Ware bin und über die Legitimation verfüge, die Produkte zu verkaufen und das Eigentum daran zu verschaffen. Weiterhin versichere ich, dass die angebotenen Waren frei von Rechten Dritter sind. Darüber hinaus verpflichte ich mich, der Blank & Schmidt GbR von sämtlichen Ansprüchen Dritter gleich welcher Art und welchen Ursprungs an den angebotenen Waren freizustellen sowie zum Ersatz jeglicher Schäden, einschließlich der Kosten aus der ggf. notwendigen oder erforderlichen erscheinenden Inanspruchnahme rechtsanwaltlicher und/oder gerichtlicher Hilfe, die uns deswegen bzw. daraus entstehen. Zu dem bestätige ich, erscheinenden Inanspruchnahme rechtsanwaltlicher und/oder gerichtlicher Hilfe, die uns deswegen bzw. daraus entstehen. Zu dem bestätige ich, dass der Ankaufpreis keine Umsatzsteuer enthält und ich kein Unternehmer im Sinne des Umsatzsteuergesetzes bin.</p>
    </div>
</body>
</html>