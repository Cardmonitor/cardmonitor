@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">{{ __('app.nav.article') }}</h2>
        <div>
            <a class="btn btn-sm btn-secondary" href="{{ route('article.index') }}">Übersicht</a>
        </div>
    </div>

    <article-storing-history-table></article-storing-history-table>

@endsection