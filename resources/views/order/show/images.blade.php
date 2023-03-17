@if($model->canHaveImages())
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex">
                <div class="col pl-0">{{ __('app.images') }}</div>
                <div><a class="text-body" href="{{ $model->path . '/images' }}" title="{{ __('app.images') }}"><i class="fas fa-fw fa-images"></i></a></div>
            </div>
        </div>
        <div class="card-body">
            <imageable-table :model="{{ json_encode($model) }}" token="{{ csrf_token() }}"></imageable-table>
        </div>
    </div>
@endif