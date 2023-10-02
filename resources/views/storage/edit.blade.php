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
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="name">{{ __('app.name') }}</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="name" name="name" placeholder="{{ __('app.name') }}" value="{{ $model->name }}">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="parent_id">Hochgeladen</label>
                            <div class="col-sm-8">
                                <select class="form-control form-control-sm @error('is_uploaded') is-invalid @enderror" id="is_uploaded" name="is_uploaded">
                                    <option value="0" {{ $model->is_uploaded == 0 ? 'selected="selected"' : '' }}>Nicht alle Karten hochgeladen</option>
                                    <option value="1"{{ $model->is_uploaded == 1 ? 'selected="selected"' : '' }}>Alle Karten hochgeladen</option>
                                </select>
                                @error('is_uploaded')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="parent_id">{{ __('storages.main_storage') }}</label>
                            <div class="col-sm-8">
                                <select class="form-control form-control-sm @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                    <option value="">{{ __('storages.main_storage') }}</option>
                                    @foreach ($storages as $storage)
                                        @if ($storage->id != $model->id)
                                            <option value="{{ $storage->id }}" {{ $model->parent_id == $storage->id ? 'selected="selected"' : '' }}>{!! $storage->indentedName !!}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
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