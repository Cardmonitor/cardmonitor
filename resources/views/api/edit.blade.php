<form action="{{ route('api.update', ['api' => $api]) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="form-group row">
        <label class="col-sm-4 col-form-label col-form-label-sm" for="app_token">App Token</label>
        <div class="col-sm-8">
            <input type="text" class="form-control form-control-sm" id="app_token" name="app_token" placeholder="App Token" value="{{ $api->app_token }}">
            @if ($errors->has('app_token'))
                <div class="invalid-feedback">
                    {{ $errors->first('app_token') }}
                </div>
            @endif
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-4 col-form-label col-form-label-sm" for="app_secret">App Secret</label>
        <div class="col-sm-8">
            <input type="text" class="form-control form-control-sm" id="app_secret" name="app_secret" placeholder="App Secret" value="{{ $api->app_secret }}">
            @if ($errors->has('app_secret'))
                <div class="invalid-feedback">
                    {{ $errors->first('app_secret') }}
                </div>
            @endif
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-4 col-form-label col-form-label-sm" for="access_token">Access Token</label>
        <div class="col-sm-8">
            <input type="text" class="form-control form-control-sm" id="access_token" name="access_token" placeholder="Access Token" value="{{ $api->access_token }}">
            @if ($errors->has('access_token'))
                <div class="invalid-feedback">
                    {{ $errors->first('access_token') }}
                </div>
            @endif
        </div>
    </div>
    <div class="form-group row">
        <label class="col-sm-4 col-form-label col-form-label-sm" for="access_token_secret">Access Token Secret</label>
        <div class="col-sm-8">
            <input type="text" class="form-control form-control-sm" id="access_token_secret" name="access_token_secret" placeholder="Access Token Secret" value="{{ $api->access_token_secret }}">
            @if ($errors->has('access_token_secret'))
                <div class="invalid-feedback">
                    {{ $errors->first('access_token_secret') }}
                </div>
            @endif
        </div>
    </div>

    <button type="submit" class="btn btn-sm btn-primary">Speichern</button>

</form>