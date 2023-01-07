@extends('layouts.app')

@section('content')

    <div>

        <div class="d-flex mb-1">
            <h2 class="col mb-0 pl-0"><a class="text-body" href="{{ route('article.storing_history.index') }}">Einlagerungen</a><span class="d-none d-md-inline"> > {{ $model->created_at->format('d.m.Y H:i') }}</span></h2>
            <div class="d-flex align-items-center">
                <a href="{{ route('article.storing_history.pdf.show', ['storing_history' => $model->id]) }}" target="_blank" class="btn btn-sm btn-secondary">PDF</a>
                <a href="{{ route('article.storing_history.index') }}" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
            </div>
        </div>

        <article-storing-history-show-table :model="{{ json_encode($model) }}"
            :conditions="{{ json_encode($conditions) }}"
            :expansions="{{ json_encode($expansions) }}"
            :games="{{ json_encode($games) }}"
            :languages="{{ json_encode($languages) }}"
            :rarities="{{ json_encode($rarities) }}"
            :rules="{{ json_encode($rules) }}"
            :storages="{{ json_encode($storages) }}"
        ></article-storing-history-show-table>

    </div>

@endsection