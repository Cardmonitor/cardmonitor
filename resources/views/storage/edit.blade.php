@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0"><a class="text-body" href="/item">{{ __('app.nav.storages') }}</a><span class="d-none d-md-inline"> > {{ $model->full_name }}</span></h2>
        <div class="d-flex align-items-center">
            <a href="{{ $model->path }}" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
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
                            <label for="name">{{ __('app.name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('app.name') }}" value="{{ $model->name }}">
                        </div>
                        <div class="form-group">
                            <label for="slots">Slots</label>
                            <input type="text" class="form-control" id="slots" name="slots" placeholder="Slots" value="{{ $model->slots }}">
                        </div>
                        <div class="form-group">
                            <label for="parent_id">{{ __('storages.main_storage') }}</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">{{ __('storages.main_storage') }}</option>
                                @foreach ($storages as $storage)
                                    @if ($storage->id != $model->id)
                                        <option value="{{ $storage->id }}" {{ $model->parent_id == $storage->id ? 'selected="selected"' : '' }}>{!! $storage->indentedName !!}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('app.actions.save') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </form>

@endsection