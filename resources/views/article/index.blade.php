@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">{{ __('app.nav.article') }}</h2>
        <div>
            <a class="btn btn-sm btn-secondary" href="/article/stock">Bestände</a>
            <a class="btn btn-sm btn-secondary" href="">TODO: Einlagerungen (Historie)</a>
        </div>
    </div>
    <article-table
        :conditions="{{ json_encode($conditions) }}"
        :expansions="{{ json_encode($expansions) }}"
        :games="{{ json_encode($games) }}"
        :is-applying-rules="{{ $is_applying_rules }}"
        :is-syncing-articles="{{ $is_syncing_articles }}"
        :languages="{{ json_encode($languages) }}"
        :rarities="{{ json_encode($rarities) }}"
        :rules="{{ json_encode($rules) }}"
        :storages="{{ json_encode($storages) }}"
    ></article-table>

@endsection