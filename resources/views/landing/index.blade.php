@extends('layouts.guest')

@section('content')

    <div class="alert alert-warning" role="alert">
        <div class="container">
            <div class="row">
                Cardmarket hat die API abgestellt. Cardmonitor funktioniert deshalb aktuell nicht. <br />Sobald die neue API verfügbar ist, werde ich sie implementieren.
            </div>
        </div>
    </div>

    <div class="masthead bg-dark p-5">

        <div class="container h-100">

            <div class="row h-100 align-items-center justify-content-center text-center">

                <div class="col-lg-10 align-self-end">
                    <h1 class="text-uppercase text-white font-weight-bold">Optimiere dein</h1>
                    <a href="https://cardmarket.com"><img alt="Cardmarket.com" class="img-fluid" src="{{ Storage::url('cardmarket/CardmarketLogoWhite1.png') }}" width="300px;"></a>
                    <h1 class="text-uppercase text-white font-weight-bold">Konto</h1>
                    <hr class="divider my-4">

                </div>

                <div class="col-lg-8 align-self-baseline">

                    <h2 class="text-light font-weight-light mb-5">Kalkuliere deine Bestellungen und verwalte deine Artikel</h2>
                    <img class="img-fluid rounded my-3" src="{{ Storage::url('landing/dashboard.png') }}">
                    <a class="btn btn-success mt-5 btn-lg" href="{{ route('register') }}">Starte jetzt</a>

                </div>

            </div>

        </div>

    </div>

    <section id="services" class="page-section">

        <div class="container">

            <h2 class="text-center mt-0">Funktionen</h2>

            <hr class="divider my-4">

            <div class="row">

                <div class="col-lg-3 col-md-6 text-center">

                    <div class="mt-5">

                        <a class="text-body text-decoration-none" href="#overview">
                            <i class="fas fa-4x fa-chart-line text-primary mb-4"></i>
                            <h3 class="h4 mb-2">Behalte den Überblick</h3>
                            <p class="text-muted mb-2">
                                Umsatz, Kosten und Gewinn auf einen Blick.
                            </p>
                        </a>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6 text-center">

                    <div class="mt-5">

                        <a class="text-body text-decoration-none" href="#order">
                            <i class="fas fa-4x fa-shipping-fast text-primary mb-4"></i>
                            <h3 class="h4 mb-2">Bestellungen vorbereiten</h3>
                            <p class="text-muted mb-2">
                                Bereite deine Bestellungen schneller vor.
                            </p>
                        </a>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6 text-center">

                    <div class="mt-5">

                        <a class="text-body text-decoration-none" href="#article">
                            <i class="fas fa-4x fa-plus-square text-primary mb-4"></i>
                            <h3 class="h4 mb-2">Artikel anlegen</h3>
                            <p class="text-muted mb-2">
                                Erstelle neue Artikel schneller ohne die Maus zu nutzen.
                            </p>
                        </a>

                    </div>

                </div>

                <div class="col-lg-3 col-md-6 text-center">

                    <div class="mt-5">

                        <a class="text-body text-decoration-none" href="#price">
                            <i class="fas fa-4x fa-euro-sign text-primary mb-4"></i>
                            <h3 class="h4 mb-2">Verkaufspreise</h3>
                            <p class="text-muted mb-2">
                                Ändere die Preise aller deiner Artikel auf Knopfdruck.
                            </p>
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <section class="p-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Dich erwartet noch einiges mehr!</h2>
            <a class="btn btn-light btn-xl" href="{{ route('register') }}">Melde die jetzt kostenlos an</a>
        </div>
    </section>

    <section id="overview" class="page-section">

        <div class="container">

            <h2 class="text-center mt-0">Behalte den Überblick</h2>

            <hr class="divider my-4">

            <div class="row">

                <div class="col">

                    <img class="img-fluid rounded my-3" src="{{ Storage::url('landing/overview.png') }}">

                </div>

                <div class="col d-flex flex-column justify-content-center text-center">

                    <p>Alle Verkäufe, Kosten und Gewinne nach Zeitraum auf einen Blick.</p>

                    <p>Als Chart und Tabelle.</p>

                    <p>Dazu der aktuele Wert der angebotenen Karten, neue bezahlte Bestellungen und die aktuellen Bewertungen.</p>

                </div>

            </div>

        </div>

    </section>

    <section class="p-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Umsatz, Kosten und Gewinn auf einen Blick.</h2>
            <a class="btn btn-light btn-xl" href="{{ route('register') }}">Melde die jetzt kostenlos an</a>
        </div>
    </section>

    <section id="order" class="page-section">

        <div class="container">

            <h2 class="text-center mt-0">Bestellungen vorbereiten</h2>

            <hr class="divider my-4">

            <div class="row">

                <div class="col d-flex flex-column justify-content-center text-center">

                    <p>Gehe alle Karten der Bestellung durch und halte möglich Probleme fest. Du siehst alle Infos zur aktuellen Karte inklusive Lagerplatz.</p>

                    <p>Lade Bilder zu deiner Bestellung hoch.</p>

                    <p>Verschicke eine automatisch generierte Nachricht mit Link zur Bildergalerie und eventuellen Problemen an den Käufer.</p>

                </div>

                <div class="col">

                    <img class="img-fluid rounded my-3" src="{{ Storage::url('landing/order.png') }}">

                </div>

            </div>

        </div>

    </section>

    <section class="p-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Bereite deine Bestellungen schneller vor.</h2>
            <a class="btn btn-light btn-xl" href="{{ route('register') }}">Melde die jetzt kostenlos an</a>
        </div>
    </section>

    <section id="article" class="page-section">

        <div class="container">

            <h2 class="text-center mt-0">Artikel anlegen</h2>

            <hr class="divider my-4">

            <div class="row">

                <div class="col">

                    <img class="img-fluid rounded my-3" src="{{ Storage::url('landing/article.png') }}">

                </div>

                <div class="col d-flex flex-column justify-content-center text-center">

                    <p>Lege Artikel schneller an, ohne die Maus zu benutzen.</p>

                    <p>Setze Standard Werte um sämtliche Angaben automatisch auszufüllen.</p>

                    <p>Es werden automatisch die aktuellsten Preise von Cardmarket.com vorgeschlagen.</p>

                </div>

            </div>

        </div>

    </section>

    <section class="p-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Erstelle neue Artikel schneller ohne die Maus zu nutzen.</h2>
            <a class="btn btn-light btn-xl" href="{{ route('register') }}">Melde die jetzt kostenlos an</a>
        </div>
    </section>

    <section id="price" class="page-section">

        <div class="container">

            <h2 class="text-center mt-0">Verkaufspreise</h2>

            <hr class="divider my-4">

            <div class="row">

                <div class="col d-flex flex-column justify-content-center text-center">

                    <p>Erstelle beliebig viele Regeln um deine Verkaufspreise anzupassen.</p>

                    <p>Führe alle Regeln gesammelt aus und aktualisiere so sämtlich Preise deiner angebotenen Karten.</p>

                    <p>Vor dem Ausführen der Regeln kannst Du sie simulieren.</p>

                </div>

                <div class="col">

                    <img class="img-fluid rounded my-3" src="{{ Storage::url('landing/price.png') }}">

                </div>

            </div>

        </div>

    </section>

    <section class="p-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ändere die Preise aller deiner Artikel auf Knopfdruck.</h2>
            <a class="btn btn-light btn-xl" href="{{ route('register') }}">Melde die jetzt kostenlos an</a>
        </div>
    </section>

@endsection