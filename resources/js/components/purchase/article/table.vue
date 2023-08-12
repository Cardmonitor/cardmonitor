<template>
    <div>
        <div v-if="isLoading" class="mt-3 p-5">
            <center>
                <span style="font-size: 48px;">
                    <i class="fas fa-spinner fa-spin"></i><br />
                </span>
                Lade Daten..
            </center>
        </div>
        <div class="table-responsive mt-3" v-else-if="items.length">
            <table class="table table-sm table-hover table-striped bg-white">
                <thead>
                    <tr>
                        <th class="d-none d-sm-table-cell" width="75"></th>
                        <th class="text-center w-icon"></th>
                        <th class="" width="100%">{{ $t('app.name') }}</th>
                        <th class="w-icon"></th>
                        <th class="text-center d-none d-lg-table-cell w-icon"></th>
                        <th class="text-center d-none d-xl-table-cell w-icon"></th>
                        <th class="d-none d-lg-table-cell" style="width: 100px;"></th>
                        <th class="text-right d-none d-sm-table-cell w-formatted-number">{{ $t('app.price_abbr') }}</th>
                        <th class="text-right d-none d-xl-table-cell w-formatted-number">{{ $t('app.price_buying_abbr') }}</th>
                        <th class="text-right d-none d-xl-table-cell w-formatted-number" :title="$t('app.profit_anticipated')">{{ $t('app.revenue') }}</th>
                        <th class="text-right d-none d-xl-table-cell w-formatted-number">Nummer</th>
                        <th class="text-right d-none d-sm-table-cell w-action">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :model="model" :item="item" :index="index" :key="item.id" :uri="uri" @deleted="remove(index)" @updated="updated(index, $event)" @show="showImgbox($event)" @hide="hideImgbox()" @toshow="toshow(index, item)"></row>
                    </template>
                </tbody>
                <tfoot>
                    <tr v-show="counts.open > 0">
                        <td class="d-none d-sm-table-cell"><b>{{ $t('order.article.table.open') }}</b></td>
                        <td class="text-center"><b>{{ counts.open }}</b></td>
                        <td class=""></td>
                        <td class=""></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                    </tr>
                    <tr v-show="counts.problem > 0">
                        <td class="d-none d-sm-table-cell"><b>{{ $t('order.article.show.problems.plural') }}</b></td>
                        <td class="text-center"><b>{{ counts.problem }}</b></td>
                        <td class=""></td>
                        <td class=""></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                    </tr>
                    <tr v-show="counts.ok > 0">
                        <td class="d-none d-sm-table-cell"><b>{{ $t('order.article.table.ok') }}</b></td>
                        <td class="text-center"><b>{{ counts.ok }}</b></td>
                        <td class=""></td>
                        <td class=""></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                    </tr>
                    <tr>
                        <td class="d-none d-sm-table-cell"><b></b></td>
                        <td class="text-center"><b>{{ counts.all }}</b></td>
                        <td class=""></td>
                        <td class=""></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-xl-table-cell"></td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td class="d-none d-sm-table-cell text-right font-weight-bold">{{ sums.unit_price.toFixed(2) }} €</td>
                        <td class="d-none d-xl-table-cell text-right font-weight-bold">{{ sums.unit_cost.toFixed(2) }} €</td>
                        <td class="d-none d-xl-table-cell text-right font-weight-bold">{{ sums.profit.toFixed(2) }} €</td>
                        <td class="d-none d-sm-table-cell"></td>
                        <td class="d-none d-sm-table-cell"></td>
                    </tr>
                    <tr>
                        <td class="align-middle" colspan="11">
                            <select class="form-control form-control-sm" v-model="actionForm.action">
                                <option :value="null">{{ $t('app.actions.action') }}</option>
                                <optgroup label="Bearbeiten">
                                    <option value="setNumber">Nummern automatisch setzen</option>
                                    <option value="resetNumber">Nummern entfernen</option>
                                </optgroup>
                                <optgroup label="Einlagern">
                                    <option value="storing" :disabled="!(all_items_are_numbered === true && all_items_are_stored === false)">Einlagern</option>
                                </optgroup>
                                <optgroup label="Cardmarket">
                                    <option value="syncCardmarket" :disabled="all_items_are_numbered === false">Upload zu Cardmarket</option>
                                </optgroup>
                            </select>
                        </td>
                        <td class="align-middle text-right">
                            <button class="btn btn-sm btn-secondary" @click="action">Ausführen</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('article.errors.no_data') }}</center></div>
        <div id="imgbox" style="position: absolute;" :style="{ top: imgbox.top,  left: imgbox.left }">
            <img :src="imgbox.src" v-show="imgbox.show">
        </div>
    </div>
</template>

<script>
    import row from "./row.vue";

    export default {

        components: {
            row,
        },

        props: {
            model: {
                required: true,
                type: Object,
            },
            initialItems: {
                required: true,
                type: Array,
            },
            counts: {
                required: true,
                type: Object,
            },
        },

        computed: {
            all_items_are_numbered() {
                return this.items.every(item => item.number !== null);
            },
            all_items_are_stored() {
                return this.items.every(item => item.storing_history_id > 1);
            },
            sums() {
                var profit = 0,
                    unit_price = 0,
                    unit_cost = 0,
                    provision = 0;
                for (var index in this.items) {
                    profit += (Number(this.items[index]['unit_price']) - Number(this.items[index]['unit_cost']) - Number(this.items[index]['provision']));
                    unit_price += Number(this.items[index]['unit_price']);
                    unit_cost += Number(this.items[index]['unit_cost']);
                    provision += Number(this.items[index]['provision']);
                }

                return {
                    profit: profit,
                    provision: provision,
                    unit_cost: unit_cost,
                    unit_price: unit_price,
                };
            },
        },

        data () {
            return {
                uri: this.model.path + '/articles',
                isLoading: false,
                items: this.initialItems,
                filter: {
                    order_id: this.model.id,
                    is_numbered: -1,
                    is_stored: -1,
                    product_type: 1,
                    rule_id: 0,
                    sold: 0,
                    sync: -1,
                },
                form: {

                },
                actionForm: {
                    action: null,
                    storage_id: null,
                },
                imgbox: {
                    src: null,
                    show: true,
                },
                errors: {},
            };
        },

        methods: {
            action() {
                var component = this;
                axios.post('/article/action', {
                    filter: component.filter,
                    ...component.actionForm,
                })
                .then( function (response) {
                    Vue.success(response.data.message);
                    if (response.data.model) {
                        location.href = response.data.model.path;
                    }
                    else {
                        component.actionForm.action = null;
                        component.fetch();
                    }
                });
            },
            fetch() {
                var component = this;
                axios.get(component.uri, component.filter)
                .then( function (response) {
                    component.items = response.data;
                });
            },
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            remove(index) {
                this.items.splice(index, 1);
                Vue.success('Interaktion gelöscht.');
            },
            toshow(index, item) {
                this.$emit('toshow', {
                    index: index,
                    item: item,
                });
            },
            showImgbox({src, top, left}) {
                this.imgbox.src = src;
                this.imgbox.top = top;
                this.imgbox.left = left;
                this.imgbox.show = true;
            },
            hideImgbox() {
                this.imgbox.show = false;
            },
        },
    };
</script>