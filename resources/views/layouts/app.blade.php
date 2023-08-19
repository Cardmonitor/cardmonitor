<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.head')
</head>
<body>
    <div class="wrapper" id="app">
        <nav id="nav">
            <div class="navbar">
                <a class="navbar-brand" href="#">Cardmonitor</a>
            </div>
            <ul class="col">
                <a href="/home"><li>{{ __('app.nav.home') }}</li></a>
                <a href="/article"><li>{{ __('app.nav.article') }}</li></a>
                <a href="/order"><li>{{ __('app.nav.order') }}</li></a>
                <a href="/storages"><li>{{ __('app.nav.storages') }}</li></a>
                <a href="/expansions"><li>Erweiterungen</li></a>
                <a href="/purchases"><li>Ankäufe</li></a>
                <a href="/woocommerce/order"><li>WooCommerce</li></a>
            </ul>
            <div class="px-3 text-white text-center"><p>Alle Daten sind von <a href="https://www.cardmarket.eu">Cardmarket</a></p></div>
            <div class="d-flex justify-content-around">
                <div class="px-3 text-white text-center"><p>by <a href="https://d15r.de" target="_blank">D15r</a></p></div>
                <div class="px-3 text-white text-center"><p><a href="https://github.com/Cardmonitor/cardmonitor" target="_blank">Github</a></p></div>
            </div>
            <div class="bg-secondary text-white p-2 d-flex justify-content-around">
                <a class="text-white" href="/impressum">{{ __('app.impressum.link') }}</a>
            </div>
        </nav>

        <div id="content-container">

            <nav class="navbar navbar-expand navbar-light bg-light sticky-top shadow-sm">
                <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
                    <span class="navbar-text" id="menu-toggle">
                        <i class="fas fa-bars pointer"></i>
                    </span>
                    <form class="form-inline col my-2 my-lg-0">
                        <!-- <input class="form-control mr-sm-2 col" type="search" placeholder="Search" aria-label="Search"> -->
                        <!-- <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button> -->
                    </form>
                    <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-xs-none d-sm-none d-md-inline d-lg-inline d-xl-inline">{{ Auth::user()->name }}</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                                <a class="dropdown-item" href="/user/settings">{{ __('app.nav.settings') }}</a>
                                <a class="dropdown-item" href="/user/reset">Hintergrundtask zurücksetzen</a>
                                <a class="dropdown-item" href=""
                                    onclick="event.preventDefault();
                                                 document.getElementById('cardmarket-account-logout').submit();">
                                    Cardmarketkonto trennen
                                </a>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                                 document.getElementById('logout-form').submit();">
                                    {{ __('auth.logout') }}
                                </a>
                                <form id="cardmarket-account-logout" action="{{ route('cardmarket.callback.destroy') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <div id="content" class="container-fluid mt-3" style="height: 100vh;">
                @yield('content')
            </div>

        </div>

        <flash-message :initial-message="{{ session()->has('status') ? json_encode(session('status')) : 'null' }}"></flash-message>
    </div>
</body>
</html>
