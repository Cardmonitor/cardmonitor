@extends('layouts.app')

@section('content')

    <div class="d-flex mb-1">
        <h2 class="col mb-0"><a class="text-body" href="/item">{{ __('app.nav.settings') }}</a><span class="d-none d-md-inline"> > {{ $model->name }}</span></h2>
        <div class="d-flex align-items-center">
            <a href="/home" class="btn btn-sm btn-secondary ml-1">{{ __('app.overview') }}</a>
        </div>
    </div>
        <div class="row">
            <div class="col-md-6">
                <form action="{{ $model->path }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card mb-5">
                        <div class="card-header">{{ __('user.edit.personalization') }}</div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label col-form-label-sm" for="prepared_message">{{ __('user.edit.locale') }}</label>
                                <div class="col-sm-8">
                                    <select class="form-control form-control-sm" id="locale" name="locale">
                                        @foreach($locales as $locale)
                                            <option value="{{ $locale['lang'] }}" {{ ($model->locale == $locale['lang'] ? 'selected="selected"' : '') }}>{{ $locale['name'] }} - {{ $locale['name_orig'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('locale')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('app.actions.save') }}</button>
                        </div>
                    </div>
                </form>

                <form action="{{ $model->path }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card mb-5">
                        <div class="card-header">{{ __('auth.password') }}</div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label col-form-label-sm" for="password">{{ __('auth.password') }}</label>
                                <div class="col-sm-8">
                                    <input id="password" type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label col-form-label-sm" for="password-confirm">{{ __('auth.password_reset_password_confirm') }}</label>
                                <div class="col-sm-8">
                                    <input id="password-confirm" type="password" class="form-control form-control-sm" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('app.actions.save') }}</button>
                        </div>
                    </div>
                </form>

                <form action="/user/settings/api_token" method="POST">
                    @csrf

                    <div class="card mb-5">
                        <div class="card-header">API Token</div>
                        <div class="card-body">
                            {{ $model->api_token ?: 'Kein Token vorhanden' }}
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-sm btn-primary">Token generieren</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form action="{{ $model->path }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card mb-5">
                        <div class="card-header">{{ __('user.edit.prepared_message') }}</div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="col-form-label col-form-label-sm" for="prepared_message">{{ __('user.edit.personalization') }}</label>
                                <textarea class="form-control form-control-sm @error('password') is-invalid @enderror" id="prepared_message" name="prepared_message" rows="12">{!! $model->prepared_message !!}</textarea>
                                @error('prepared_message')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('app.actions.save') }}</button>
                        </div>
                    </div>
                </form>

                <div class="card mb-5">
                    <div class="card-header">Verbindungen</div>
                    <div class="card-body">
                        <a class="btn btn-sm btn-secondary mb-3" href="{{ route('login.provider.redirect', ['provider' => 'dropbox']) }}">Mit Dropbox verknüpfen</a>
                        @if ($model->providers->count())
                            <table class="table table-sm table-fixed table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th width="100%">Provider</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($model->providers as $provider)
                                        <tr>
                                            <td>{{ $provider->provider_type }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-sm btn-secondary" title="Provider löschen" onclick="event.preventDefault(); document.getElementById('provider_{{ $provider->id }}_destroy').submit();"><i class="fas fa-trash"></i></button>
                                                </div>
                                                <form action="{{ route('login.provider.destroy', ['provider' => $provider->id]) }}" method="POST" id="provider_{{ $provider->id }}_destroy">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

        </div>

@endsection