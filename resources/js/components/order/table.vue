<template>
    <div>
        <div class="row">
            <div class="col"></div>
            <div class="col-auto d-flex">
                <div class="form-group" style="margin-bottom: 0;">
                    <filter-search v-model="filter.searchtext" @input="search()"></filter-search>
                </div>
                <button class="btn btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
                <button class="btn btn-secondary ml-1" @click="sync" :disabled="syncing.status == 1"><i class="fas fa-sync" :class="{'fa-spin': syncing.status == 1}"></i></button>
                <button class="btn btn-secondary ml-1" @click="download" :disabled="syncing.status == 1"><i class="fas fa-download"></i></button>
                <button type="button" class="btn btn-secondary ml-1" data-toggle="modal" data-target="#import-sent">
                    <i class="fas fa-upload"></i>
                </button>
            </div>
        </div>

        <form v-if="filter.show" id="filter" class="mt-1">
            <div  class="form-row">

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-state">{{ $t('app.state') }}</label>
                        <select class="form-control" id="filter-state" v-model="filter.state" @change="search">
                            <option :value="null">{{ $t('filter.all') }}</option>
                            <option :value="id" v-for="(name, id) in states">{{ name }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-presale">Presale</label>
                        <select class="form-control" id="filter-presale" v-model="filter.presale" @change="search">
                            <option :value="null">{{ $t('filter.all') }}</option>
                            <option value="0">Ohne Presale</option>
                            <option value="1">Presale</option>
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
            <table class="table table-hover table-striped bg-white">
                <thead>
                    <tr>
                        <th class="d-none d-sm-table-cell" width="10%">{{ $t('app.date') }}</th>
                        <th width="15%">{{ $t('order.singular') }}</th>
                        <th class="text-right d-none d-md-table-cell" width="10%">{{ $t('app.cards') }}</th>
                        <th class="text-right d-none d-md-table-cell" width="10%">{{ $t('app.revenue') }}</th>
                        <th class="text-right d-none d-xl-table-cell" width="10%">{{ $t('app.costs') }}</th>
                        <th class="text-right d-none d-xl-table-cell" width="10%">{{ $t('app.profit') }}</th>
                        <th class="d-none d-md-table-cell" width="15%">{{ $t('app.state') }}</th>
                        <th class="text-center d-none d-lg-table-cell" width="10%">{{ $t('order.evaluations.singular') }}</th>
                        <th class="text-right" width="10%">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <row :item="item" :key="item.id" :uri="uri" :selected="(selected.indexOf(item.id) == -1) ? false : true" v-for="(item, index) in items" @input="toggleSelected" @updated="updated(index, $event)"></row>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('order.alerts.no_data') }}</center></div>
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

    </div>
</template>

<script>
    import row from "./row.vue";
    import filterSearch from "../filter/search.vue";

    export default {

        components: {
            row,
            filterSearch,
        },

        props: {
            isSyncingOrders: {
                required: true,
                type: Number,
            },
            states: {
                required: true,
                type: Object,
            },
        },

        data () {
            return {
                uri: '/order',
                items: [],
                isLoading: true,
                syncing: {
                    status: this.isSyncingOrders,
                    interval: null,
                },
                paginate: {
                    nextPageUrl: null,
                    prevPageUrl: null,
                    lastPage: 0,
                },
                filter: {
                    page: 1,
                    presale: null,
                    searchtext: '',
                    show: true,
                    state: null,
                },
                selected: [],
            };
        },

        mounted() {

            this.fetch();
            if (this.isSyncingOrders) {
                this.checkIsSyncingOrders();
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
            checkIsSyncingOrders() {
                var component = this;
                this.syncing.interval = setInterval(function () {
                    component.getIsSyncingOrders()
                }, 3000);
            },
            getIsSyncingOrders() {
                var component = this;
                axios.get(component.uri + '/sync')
                    .then(function (response) {
                        component.syncing.status = response.data.is_syncing_orders;
                        if (component.syncing.status == 0) {
                            clearInterval(component.syncing.interval)
                            component.syncing.interval = null;
                            component.fetch();
                            Vue.success(component.$t('order.successes.synced'));
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            download() {
                var component = this;
                axios.post(component.uri + '/export/download', component.filter)
                    .then(function (response) {
                        if (response.data.path) {
                            Vue.success('Datei heruntergeladen');
                            location.href = response.data.path;
                        }
                        else {
                            Vue.error(component.$t('order.errors.loaded'));
                        }
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.loaded'));
                        console.log(error);
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
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.loaded'));
                        console.log(error);
                    });
            },
            search() {
                this.filter.page = 1;
                this.fetch();
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
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            sync() {
                var component = this;
                axios.put(component.uri + '/sync')
                    .then(function (response) {
                        component.syncing.status = 1;
                        component.checkIsSyncingOrders();
                        Vue.success(component.$t('order.successes.syncing_background'));
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            showPageButton(page) {
                if (page == 1 || page == this.paginate.lastPage) {
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