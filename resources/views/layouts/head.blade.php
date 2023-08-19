<link rel="manifest" href="manifest.json">
<meta charset="utf-8">
<meta name="description" content="Ein Tool zum Optimieren deines Cardmarket.com Kontos. Kalkuliere deine Bestellungen und verwalte deine Artikel mit der Software."/>

<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="application-name" content="Cardmonitor">
<meta name="apple-mobile-web-app-title" content="Cardmonitor">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="msapplication-starturl" content="/">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link rel="icon" sizes="192x192" href="{{ Storage::url('icons/Android/Icon-192.png') }}">
<link rel="apple-touch-icon" sizes="196x196" href="{{ Storage::url('icons/iOS/Icon-196.png') }}">

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'Laravel') }}</title>

<script>window.Laravel = {!! json_encode(['locale' => \App::getLocale()]) !!}</script>

    <script>
        window.user = {
            id: {{ Auth::user()->id ?? 0}}
        };
    </script>

<!-- Scripts -->
<script src="{{ mix('js/app.js') }}" defer></script>

<!-- Fonts -->
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

<!-- Styles -->
<link href="{{ mix('css/app.css') }}" rel="stylesheet">