<div class="card mb-3 hidden">
    <div class="card-header">{{ __('order.calculation') }}</div>
    <div class="card-body">
        <div class="row">
            <div class="col-label"><b>{{ __('app.revenue') }}</b></div>
            <div class="col-value"><b>{{ number_format($model->revenue, 2, ',', '.') }} €</b></div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.cards') }}</div>
            <div class="col-value">{{ number_format($model->articles_revenue, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.shipping') }}</div>
            <div class="col-value">{{ number_format($model->shipment_revenue, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">&nbsp;</div>
            <div class="col-value"></div>
        </div>

        <div class="row">
            <div class="col-label"><b>{{ __('app.costs') }}</b></div>
            <div class="col-value"><b>{{ number_format($model->cost, 2, ',', '.') }} €</b></div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.cards') }}</div>
            <div class="col-value">{{ number_format($model->articles_cost, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.provision') }}</div>
            <div class="col-value">{{ number_format($model->provision, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.shipping') }}</div>
            <div class="col-value">{{ number_format($model->shipment_cost, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.other') }}</div>
            <div class="col-value">{{ number_format($model->items_cost, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">&nbsp;</div>
            <div class="col-value"></div>
        </div>

        <div class="row">
            <div class="col-label"><b>{{ __('app.profit') }}</b></div>
            <div class="col-value"><b>{{ number_format($model->profit, 2, ',', '.') }} €</b></div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.cards') }}</div>
            <div class="col-value">{{ number_format($model->articles_profit, 2, ',', '.') }} €</div>
        </div>
        <div class="row">
            <div class="col-label">{{ __('app.shipping') }}</div>
            <div class="col-value">{{ number_format($model->shipment_profit, 2, ',', '.') }} €</div>
        </div>

    </div>
</div>