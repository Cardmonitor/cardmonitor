@extends('layouts.app')

@section('content')

    <article-edit :conditions="{{ json_encode($conditions) }}" :model="{{ json_encode($model) }}" :languages="{{ json_encode($languages) }}" :storages="{{ json_encode($storages) }}"></article-edit>

@endsection