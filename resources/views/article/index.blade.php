@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col pl-0">{{ __('app.nav.article') }}</h2>
        <div>
            <a class="btn btn-sm btn-secondary" href="{{ route('article.storing_history.index') }}">Einlagerungen</a>
            @if ($log_file_exists)
                <a class="btn btn-sm btn-secondary" href="{{ route('article.stock.logfile.index') }}">Log-Datei</a>
            @endif
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
        :initial-background-tasks="{{ json_encode($background_tasks) }}"
    ></article-table>

    @include('article.tcg-powertools-import.create')
    @include('article.magic-sorter-import.create')

@endsection