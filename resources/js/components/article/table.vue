<template>
    <div>
        <div class="row">
            <div class="col mb-1 mb-sm-0">
                <a href="/article/create" class="btn btn-sm btn-primary"><i class="fas fa-plus-square"></i></a>
            </div>
            <div class="col-auto d-flex">
                <div class="form-group" style="margin-bottom: 0;">
                    <filter-search v-model="filter.searchtext" @input="search()"></filter-search>
                </div>
                <button class="btn btn-sm btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
                <button class="btn btn-sm btn-secondary ml-1" @click="sync" :disabled="syncing.status == 1" v-if="false"><i class="fas fa-sync" :class="{'fa-spin': syncing.status == 1}"></i></button>
                <button type="button" class="btn btn-sm btn-primary text-overflow-ellipsis ml-1" :title="$t('rule.apply')" data-toggle="modal" data-target="#confirm-rule-apply" :disabled="applying.status == 1" v-if="false">
                    <i class="fas fa-spinner fa-spin mr-1" v-show="applying.status == 1"></i>{{ $t('rule.apply') }}
                </button>
                <button type="button" class="btn btn-sm btn-secondary ml-1" :disabled="syncing.status == 1" data-toggle="modal" data-target="#tcg-powertools-import">
                    TCG PowerTools Import
                </button>
                <button type="button" class="btn btn-sm btn-secondary ml-1" :disabled="syncing.status == 1" data-toggle="modal" data-target="#magic-sorter-import">
                    Magic Sorter Import
                </button>
            </div>
        </div>

        <form v-if="filter.show" id="filter" class="mt-1">
            <div  class="form-row">

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-sync">{{ $t('filter.sync.label') }}</label>
                        <select class="form-control form-control-sm" id="filter-sync" v-model="filter.sync" @change="search">
                            <option :value="-1">{{ $t('filter.all') }}</option>
                            <option :value="2">Nicht hochgeladen</option>
                            <option :value="1">{{ $t('filter.sync.error') }}</option>
                            <option :value="0">{{ $t('filter.sync.success') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-sold">{{ $t('filter.sold.label') }}</label>
                        <select class="form-control form-control-sm" id="filter-sold" v-model="filter.sold" @change="search">
                            <option :value="-1">{{ $t('filter.all') }}</option>
                            <option :value="0">{{ $t('filter.sold.not_sold') }}</option>
                            <option :value="1">{{ $t('filter.sold.sold') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto">
                    <filter-game :initial-value="filter.game_id" :options="games" :game-id="filter.game_id" :show-label="true" v-model="filter.game_id" @input="fetch()"></filter-game>
                </div>

                <div class="col-auto">
                    <filter-expansion :options="expansions" v-model="filter.expansion_id" @input="search"></filter-expansion>
                </div>
                <div class="col-auto d-none">
                    <filter-rarity :options="rarities" v-model="filter.rarity" @input="search"></filter-rarity>
                </div>
                <div class="col-auto d-none">
                    <filter-language :options="languages" v-model="filter.language_id" @input="search"></filter-language>
                </div>
                <div class="col-auto">
                    <filter-storage :options="storages" v-model="filter.storage_id" @input="search"></filter-storage>
                </div>
                <div class="col-auto d-none">
                    <filter-rule :options="rules" v-model="filter.rule_id" @input="search" v-if="rules != null"></filter-rule>
                </div>
                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-unit_price_min">{{ $t('filter.price.min') }}</label>
                        <input class="form-control form-control-sm" id="filter-unit_price_min" type="text" v-model="filter.unit_price_min" @input="search">
                    </div>
                </div>
                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-unit_price_max">{{ $t('filter.price.max') }}</label>
                        <input class="form-control form-control-sm" id="filter-unit_price_max" type="text" v-model="filter.unit_price_max" @input="search">
                    </div>
                </div>
                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-unit_cost_min">{{ $t('filter.price_buying.min') }}</label>
                        <input class="form-control form-control-sm" id="filter-unit_cost_min" type="text" v-model="filter.unit_cost_min" @input="search">
                    </div>
                </div>
                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-unit_cost_max">{{ $t('filter.price_buying.max') }}</label>
                        <input class="form-control form-control-sm" id="filter-unit_cost_max" type="text" v-model="filter.unit_cost_max" @input="search">
                    </div>
                </div>

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-is-numbered">Nummer vergeben</label>
                        <select class="form-control form-control-sm" id="filter-is-numbered" v-model="filter.is_numbered" @change="search">
                            <option :value="-1">{{ $t('filter.all') }}</option>
                            <option :value="0">Ohne Nummer</option>
                            <option :value="1">Mit Nummer</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-is-stored">Eingelagert</label>
                        <select class="form-control form-control-sm" id="filter-is-stored" v-model="filter.is_stored" @change="search">
                            <option :value="-1">{{ $t('filter.all') }}</option>
                            <option :value="0">Nicht Eingelagert</option>
                            <option :value="1">Eingelagert</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto d-none">
                    <div class="form-group">
                        <label for="filter-product-type">Artikeltyp</label>
                        <select class="form-control form-control-sm" id="filter-product-type" v-model="filter.product_type" @change="search">
                            <option :value="null">{{ $t('filter.all') }}</option>
                            <option :value="1">Einzelkarten</option>
                            <option :value="0">Andere Artikel</option>
                        </select>
                    </div>
                </div>

            </div>
        </form>

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
                        <th class="text-center d-none d-lg-table-cell w-icon">{{ $t('article.sync') }}</th>
                        <th class="text-right d-none d-xl-table-cell w-icon"></th>
                        <th class="" width="100%">{{ $t('app.name') }}</th>
                        <th class="w-icon"></th>
                        <th class="text-center d-none d-xl-table-cell w-icon"></th>
                        <th class="text-center d-none d-lg-table-cell w-formatted-number">{{ $t('app.condition') }}</th>
                        <th class="d-none d-xl-table-cell" style="width: 100px;"></th>
                        <th class="text-right d-none d-sm-table-cell w-formatted-number">{{ $t('app.price_abbr') }}</th>
                        <th class="d-none d-xl-table-cell" style="width: 150px;">{{ $t('storages.storage') }}</th>
                        <th class="text-right d-none d-xl-table-cell w-formatted-number">Nummer</th>
                        <th class="text-right d-none d-sm-table-cell" style="width: 175px;">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :key="item.id" :uri="uri" :conditions="conditions" :languages="languages" :storages="storages" :selected="(selected.indexOf(item.id) == -1) ? false : true" @input="toggleSelected" @updated="updated(index, $event)" @show="showImgbox($event)" @hide="hideImgbox()" @deleted="remove(index)"></row>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="align-middle">{{ items.length }} von {{ paginate.total }}</td>
                        <td class="align-middle" colspan="5">
                            <select class="form-control form-control-sm" v-model="actionForm.action">
                                <optgroup label="Bearbeiten">
                                    <option value="setNumber">Nummern automatisch setzen</option>
                                    <option value="resetNumber">Nummern entfernen</option>
                                </optgroup>
                                <optgroup label="Einlagern">
                                    <option value="storing" :disabled="!(filter.is_numbered === 1 && filter.is_stored === 0)">Einlagern</option>
                                </optgroup>
                                <optgroup label="Cardmarket">
                                    <option value="syncCardmarket" :disabled="filter.is_numbered !== 1">Upload zu Cardmarket</option>
                                </optgroup>
                                <optgroup label="Lagerplatz" v-if="storages.length">
                                    <option value="setStorage">Lagerplatz setzen</option>
                                    <option value="resetStorage">Lagerplatz entfernen</option>
                                </optgroup>
                            </select>
                        </td>
                        <td class="align-middle" colspan="2">
                            <select class="form-control form-control-sm" v-model="actionForm.storage_id" v-show="actionForm.action == 'setStorage'">
                                <option :value="storage.id" v-for="(storage, index) in storages">{{ storage.full_name }}</option>
                            </select>
                        </td>
                        <td class="align-middle text-right">
                            <button class="btn btn-sm btn-secondary" style="width: 132px;" :disabled="actioning.status === true" @click="action">
                                <i class="fas fa-spinner fa-spin mr-1" v-show="actioning.status === true"></i>Ausführen
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('article.alerts.no_data') }}</center></div>
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center" v-show="paginate.lastPage > 1">
                <li class="page-item" v-show="paginate.prevPageUrl">
                    <a class="page-link" href="#" @click.prevent="filter.page--">{{ $t('app.paginate.previous') }}</a>
                </li>

                <li class="page-item" v-for="(n, i) in pages" v-bind:class="{ active: (n == filter.page) }"><a class="page-link" href="#" @click.prevent="filter.page = n">{{ n }}</a></li>

                <li class="page-item" v-show="paginate.nextPageUrl">
                    <a class="page-link" href="#" @click.prevent="filter.page++">{{ $t('app.paginate.next') }}</a>
                </li>
            </ul>
        </nav>
        <div id="imgbox">
            <img :src="imgbox.src" v-show="imgbox.show">
        </div>
        <div class="modal fade" id="confirm-rule-apply" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $t('rule.apply') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ $t('rule.modal_apply.body.question') }}</p>
                        <p>{{ $t('rule.modal_apply.body.comment') }}</p>
                        <div class="alert alert-danger" role="alert">
                            {{ $t('rule.modal_apply.body.alert.text') }}<br /><br />
                            {{ $t('rule.modal_apply.body.alert.danger') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ $t('app.actions.cancel') }}</button>
                        <button type="button" class="btn btn-sm btn-secondary" @click="apply(false)">{{ $t('rule.simulate') }}</button>
                        <button type="button" class="btn btn-sm btn-primary" @click="apply(true)">{{ $t('rule.apply') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import filterExpansion from "../filter/expansion.vue";
    import filterGame from "../filter/game.vue";
    import filterLanguage from "../filter/language.vue";
    import filterRarity from "../filter/rarity.vue";
    import filterRule from "../filter/rule.vue";
    import filterSearch from "../filter/search.vue";
    import filterStorage from "../filter/storage.vue";
    import row from "./row.vue";

    export default {

        components: {
            filterExpansion,
            filterGame,
            filterLanguage,
            filterRarity,
            filterRule,
            filterSearch,
            filterStorage,
            row,
        },

        props: {
            conditions: {
                type: Object,
                required: true,
            },
            languages: {
                required: true,
                type: Object,
            },
            expansions: {
                type: Array,
                required: true,
            },
            games: {
                type: Object,
                required: true,
            },
            isApplyingRules: {
                required: true,
                type: Number,
            },
            isSyncingArticles: {
                required: true,
                type: Number,
            },
            rarities: {
                type: Array,
                required: true,
            },
            rules: {
                type: Array,
                required: true,
            },
            storages: {
                required: true,
                type: Array,
            },
        },

        data () {
            return {
                uri: '/article',
                items: [],
                isLoading: true,
                applying: {
                    status: this.isApplyingRules,
                    interval: null,
                },
                syncing: {
                    status: this.isSyncingArticles,
                    interval: null,
                },
                actioning: {
                    status: false,
                },
                imgbox: {
                    src: null,
                    top: 0,
                    left: 0,
                    show: true,
                },
                paginate: {
                    nextPageUrl: null,
                    prevPageUrl: null,
                    lastPage: 0,
                    total: 0,
                },
                filter: {
                    cardmarket_comments: '',
                    expansion_id: 0,
                    game_id: 0,
                    language_id: 0,
                    page: 1,
                    product_type: null,
                    rule_id: 0,
                    searchtext: '',
                    show: true,
                    sold: null,
                    is_sellable: 1,
                    is_numbered: -1,
                    is_stored: -1,
                    storage_id: 0,
                    sync: -1,
                    unit_cost_max: 0,
                    unit_cost_min: 0,
                    unit_price_max: 0,
                    unit_price_min: 0,
                },
                actionForm: {
                    articles: 'filtered-to-storage_id',
                    storage_id: this.storages[0].id || 0,
                    action: 'setNumber',
                },
                selected: [],
                errors: {},
            };
        },

        mounted() {

            this.fetch();
            if (this.isApplyingRules) {
                this.checkIsApplyingRules();
            }
            if (this.isSyncingArticles) {
                this.checkIsSyncingArticles();
            }

        },

        watch: {
            page () {
                this.fetch();
            },
        },

        computed: {
            page() {
                return this.filter.page;
            },
            selectAll: {
                get: function () {
                    return this.items.length ? this.items.length == this.selected.length : false;
                },
                set: function (value) {
                    this.selected = [];
                    if (value) {
                        for (let i in this.items) {
                            this.selected.push(this.items[i].id);
                        }
                    }
                },
            },
            pages() {
                var pages = [];
                for (var i = 1; i <= this.paginate.lastPage; i++) {
                    if (this.showPageButton(i)) {
                        const lastItem = pages[pages.length - 1];
                        if (lastItem < (i - 1) && lastItem != '...') {
                            pages.push('...');
                        }
                        pages.push(i);
                    }
                }

                return pages;
            },
        },

        methods: {
            action() {
                var component = this;
                component.actioning.status = true;
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
                        component.actionForm.action = 'setNumber';
                        component.fetch();
                    }
                })
                .catch( function (error) {
                    Vue.error('Aktion konnte nicht ausgeführt werden!');
                    console.log(error);
                })
                .finally( function () {
                    component.actioning.status = false;
                });
            },
            apply(sync) {
                $('#confirm-rule-apply').modal('hide');
                var component = this;
                axios.post('/rule/apply', {
                    sync: sync,
                })
                    .then(function (response) {
                        component.applying.status = 1;
                        component.checkIsApplyingRules();
                        Vue.success('Regeln werden im Hintergrund ' + (sync ? 'angewendet' : 'simuliert') + '.');
                    })
                    .catch(function (error) {
                        Vue.error('Regeln konnten nicht angewendet werden!');
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            checkIsApplyingRules() {
                var component = this;
                this.applying.interval = setInterval( function () {
                    component.getIsApplyingRules()
                }, 3000);
            },
            getIsApplyingRules() {
                var component = this;
                axios.get('/rule/apply')
                    .then(function (response) {
                        component.applying.status = response.data.is_applying_rules;
                        if (component.applying.status == 0) {
                            clearInterval(component.applying.interval)
                            component.applying.interval = null;
                            component.fetch();
                            Vue.success('Regeln wurden angewendet.');
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            checkIsSyncingArticles() {
                var component = this;
                this.syncing.interval = setInterval(function () {
                    component.getIsSyncingArticles()
                }, 3000);
            },
            getIsSyncingArticles() {
                var component = this;
                axios.get(component.uri + '/sync')
                    .then(function (response) {
                        component.syncing.status = response.data.is_syncing_articles;
                        if (component.syncing.status == 0) {
                            clearInterval(component.syncing.interval)
                            component.syncing.interval = null;
                            component.fetch();
                            Vue.success('Artikel wurden synchronisiert.');
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get(component.uri, {
                    params: component.filter
                })
                    .then(function (response) {
                        component.items = response.data.data;
                        component.filter.page = response.data.current_page;
                        component.paginate.nextPageUrl = response.data.next_page_url;
                        component.paginate.prevPageUrl = response.data.prev_page_url;
                        component.paginate.lastPage = response.data.last_page;
                        component.paginate.total = response.data.total;
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        Vue.error('Artikel konnten nicht geladen werden!');
                        console.log(error);
                    });
            },
            sync() {
                var component = this;
                axios.put(component.uri + '/sync')
                    .then(function (response) {
                        component.syncing.status = 1;
                        component.checkIsSyncingArticles();
                        Vue.success('Artikel werden im Hintergrund aktualisiert.');
                    })
                    .catch(function (error) {
                        Vue.error('Artikel konnten nicht synchronisiert werden! Ist das Cardmarket Konto verbunden?');
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            search() {
                this.filter.page = 1;
                this.fetch();
            },
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            toggleSelected (id) {
                var index = this.selected.indexOf(id);
                if (index == -1) {
                    this.selected.push(id);
                }
                else {
                    this.selected.splice(index, 1);
                }
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
            remove(index) {
                this.items.splice(index, 1);
            },
            showPageButton(page) {
                if (page == 1 || page == this.paginate.lastPage) {
                    return true;
                }

                if (page <= this.filter.page + 2 && page >= this.filter.page - 2) {
                    return true;
                }

                return false;
            },
        },
    };
</script>