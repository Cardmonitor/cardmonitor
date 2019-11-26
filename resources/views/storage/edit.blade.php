@extends('layouts.app')

@section('content')

    <div class="d-flex">
        <h2 class="col"><a class="text-body" href="/item">Lagerplatz</a> > {{ $model->full_name }}</h2>
        <div class="d-flex align-items-center">
            <a href="{{ $model->path }}" class="btn btn-secondary ml-1">Übersicht</a>
        </div>
    </div>
    <form action="{{ $model->path }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-5">
                    <div class="card-header">{{ $model->name }}</div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" value="{{ $model->name }}">
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Hauptlagerplatz</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">Hauptlagerplatz</option>
                                @foreach ($storages as $storage)
                                    @if ($storage->id != $model->id)
                                        <option value="{{ $storage->id }}" {{ $model->parent_id == $storage->id ? 'selected="selected"' : '' }}>{!! $storage->indentedName !!}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Speichern</button>
                    </div>
                </div>
            </div>
        </div>

    </form>

@endsection