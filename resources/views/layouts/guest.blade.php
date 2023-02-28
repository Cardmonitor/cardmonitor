<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.head')
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <span class="navbar-text mr-auto">
                        {{ __('app.description') }}
                    </span>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('auth.login') }}</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link" href="/home">{{ Auth::user()->name }}</a>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>

        <footer class="p-5 bg-dark text-white">
            <div class="container text-center d-flex justify-content-between">
                <div>Cardmonitor - made with <i class="fas fa-fw fa-heart"></i> by <a class="text-white" href="https://d15r.de" target="_blank">D15r</a></div>
                <a class="text-white" href="/impressum">{{ __('app.impressum.link') }}</a>
            </div>
        </footer>

        <flash-message :initial-message="{{ session()->has('status') ? json_encode(session('status')) : 'null' }}"></flash-message>
    </div>
</body>
</html>
