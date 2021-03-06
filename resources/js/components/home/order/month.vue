<template>
    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <div class="col">{{ $t('order.home.month.title') }}</div>
            <div class="form-group mr-1 mb-0">
                <select class="form-control" v-model="form.month" @change="fetch">
                    <option :value="index" v-for="(month, index) in $t('app.months')">{{ month }}</option>
                </select>
            </div>
            <div class="form-group mb-0">
                <select class="form-control" v-model="form.year" @change="fetch">
                    <option value="2018">2018</option>
                    <option value="2019">2019</option>
                    <option value="2020">2020</option>
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div v-if="isLoading" class="mt-3 p-5">
                <center>
                    <span style="font-size: 48px;">
                        <i class="fas fa-spinner fa-spin"></i><br />
                    </span>
                    {{ $t('app.loading') }}
                </center>
            </div>
            <div class="alert alert-dark mt-3" role="alert" v-else-if="statistics.orders_count == 0">
                {{ $t('order.home.month.errors.no_data', { month: month_name }) }}
            </div>
        </div>
        <div class="card-body row">
            <div class="col col-xl-6">
                <highcharts :options="chartOptions" v-if="statistics.orders_count > 0"></highcharts>
            </div>
            <div class="col-xl-6 d-none d-xl-block">
                <table class="table table-hover table-striped" v-if="statistics.orders_count > 0">
                    <thead>
                        <tr>
                            <th width="20%"></th>
                            <th class="text-right" width="20%">{{ $t('app.total') }}</th>
                            <th class="text-right" width="20%">{{ $t('order.home.per.day') }}</th>
                            <th class="text-right" width="20%">{{ $t('order.home.per.order') }}</th>
                            <th class="text-right" width="20%">{{ $t('order.home.per.card') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $t('order.plural') }}</td>
                            <td class="text-right">{{ statistics.orders_count }}</td>
                            <td class="text-right">Ø {{ (statistics.orders_count / statistics.periods_count).format(2, ',', '.') }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>{{ $t('app.cards') }}</td>
                            <td class="text-right">{{ statistics.cards_count }}</td>
                            <td class="text-right">Ø {{ (statistics.cards_count / statistics.periods_count).format(2, ',', '.') }}</td>
                            <td class="text-right">Ø {{ (statistics.cards_count / statistics.orders_count).format(2, ',', '.') }}</td>
                            <td class="text-right">-</td>
                        </tr>
                        <tr>
                            <td>{{ $t('app.revenue') }}</td>
                            <td class="text-right">{{ statistics.revenue_sum.format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.revenue_sum / statistics.periods_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.revenue_sum / statistics.orders_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.revenue_sum / statistics.cards_count).format(2, ',', '.') }} €</td>
                        </tr>
                        <tr>
                            <td>{{ $t('app.costs') }}</td>
                            <td class="text-right">{{ statistics.cost_sum.format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.cost_sum / statistics.periods_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.cost_sum / statistics.orders_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.cost_sum / statistics.cards_count).format(2, ',', '.') }} €</td>
                        </tr>
                        <tr>
                            <td>{{ $t('app.profit') }}</td>
                            <td class="text-right">{{ statistics.profit_sum.format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.profit_sum / statistics.periods_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.profit_sum / statistics.orders_count).format(2, ',', '.') }} €</td>
                            <td class="text-right">Ø {{ (statistics.profit_sum / statistics.cards_count).format(2, ',', '.') }} €</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    import {Chart} from 'highcharts-vue'

    export default {

        components: {
            highcharts: Chart
        },

        data() {
            var date = new Date();

            return {
                isLoading: true,
                form: {
                    month: date.getMonth() + 1,
                    year: date.getFullYear(),
                },
                month_name: '',
                chartOptions: {
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [],
                    },
                    yAxis: [
                        {
                            min: 0,
                            title: {
                                text: 'Euro (€)'
                            },
                            stackLabels: {
                                enabled: true,
                                style: {
                                    fontWeight: 'bold',
                                },
                                formatter: function () {
                                    return (this.total ? Highcharts.numberFormat(this.total, 2) :  '');
                                },
                            },
                        },
                        {
                            allowDecimals: false,
                            min: 0,
                            title: {
                                text: 'Karten',
                            },
                            opposite: true,
                        },
                    ],
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true,
                                format: '{point.y:,.2f}',
                            },
                        }
                    },
                    tooltip: {
                        headerFormat: '<b>{point.key}</b><br/>',
                        pointFormat: '{series.name}: {point.y:,.2f} €<br/>Total: {point.stackTotal:,.2f} €'
                    },
                    title: {
                        text: '',
                    },
                    series: [],
                },
                statistics: {
                    cards_count: 0,
                    cost_sum: 0,
                    orders_count: 0,
                    profit_sum: 0,
                    periods_count: 0,
                    revenue_sum: 0,
                },
            };
        },

        mounted() {
            this.fetch();
        },

        methods: {
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get('/home/order/month/' + component.form.year + '/' + component.form.month)
                    .then( function (response) {
                        component.chartOptions.xAxis.categories = response.data.categories;
                        component.chartOptions.series = response.data.series;
                        component.chartOptions.title = response.data.title;
                        component.statistics = response.data.statistics;
                        component.month_name = response.data.month_name;
                        component.isLoading = false;
                    });
            },
        },
    };
</script>