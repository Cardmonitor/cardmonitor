<template>
    <div class="" v-if="items.length > 0">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <div class="col">{{ $t('order.home.paid.title') }}</div>
                <div><a class="text-body" href="/order/picklist">Pickliste</a></div>
                <div class="ml-3 pointer" @click="syncCardmarket"><i class="fas fa-sync" :class="{'fa-spin': syncing.status == 1}" :disabled="syncing.status == 1"></i> Cardmarket</div>
                <div class="ml-3 pointer" @click="syncWooCommerce"><i class="fas fa-sync" :class="{'fa-spin': syncing.status == 1}" :disabled="syncing.status == 1"></i> WooCommerce</div>
                <div class="ml-3"><i class="fas fa-download pointer" @click="download" :disabled="syncing.status == 1"></i></div>
                <div class="ml-3" v-if="false"><a href="/order/export/dropbox" class="text-body"><i class="fab fa-dropbox pointer"></i></a></div>
                <div class="ml-3"><i data-toggle="modal" data-target="#import-sent" class="fas fa-upload pointer"></i></div>
            </div>

            <div class="card-body">
                <div v-if="isLoading" class="mt-3 p-5">
                    <center>
                        <span style="font-size: 48px;">
                            <i class="fas fa-spinner fa-spin"></i><br />
                        </span>
                        {{ $t('app.loading') }}
                    </center>
                </div>
                <table class="table table-sm table-striped table-hover" v-else>
                    <thead>
                        <tr>
                            <th>Bezahlt</th>
                            <th>Bestellung</th>
                            <th class="text-right">Umsatz</th>
                            <th class="text-right">{{ $t('app.article') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in items" :class="{'table-warning': item.articles_on_hold_count > 0}">
                            <td class="align-middle d-none d-md-table-cell">{{ item.paid_at_formatted }}</td>
                            <td class="align-middle">
                                <a :href="item.path">{{ item.source_id }}</a>
                                <div class="text-muted" v-if="item.buyer">{{ item.buyer.name }}</div>
                            </td>
                            <td class="align-middle d-none d-sm-table-cell text-right">
                                {{ item.revenue_formatted }} €
                            </td>
                            <td class="align-middle d-none d-sm-table-cell text-right">
                                {{ item.articles_count }}
                                <div class="text-muted" v-if="item.articles_on_hold_count"><i class="fas fa-fw fa-pause"></i> {{ item.articles_on_hold_count }}/{{ item.articles_count }}</div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td class="align-middle d-none d-md-table-cell">{{ items.length }} Bestellungen</td>
                            <td class="align-middle"></td>
                            <td class="align-middle d-none d-sm-table-cell text-right">
                                {{ revenue.format(2, ',') }} €
                            </td>
                            <td class="align-middle d-none d-sm-table-cell text-right">
                                {{ articles_count }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
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
        </div>
    </div>
</template>

<script>
    export default {

        props: {
            initialBackgroundTasks: {
                type: Object,
                required: true,
            },
        },

        data() {
            return {
                uri: '/order',
                isLoading: true,
                syncing: {
                    status: false,
                    interval: null,
                    timeout: null,
                },
                filter: {
                    page: 1,
                    state: 'paid',
                    presale: 0,
                },
                items: [],
                paginate: {
                    nextPageUrl: null,
                    prevPageUrl: null,
                    lastPage: 0,
                },
            };
        },

        computed: {
            revenue: function () {
                return this.items.reduce((a, b) => a + Number(b.revenue), 0);
            },
            articles_count: function () {
                return this.items.reduce((a, b) => a + Number(b.articles_count), 0);
            },
            page() {
                return this.filter.page;
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

        mounted() {
            this.checkBackgroundTasks(this.initialBackgroundTasks);
            Bus.$on('update-background-tasks', function (background_tasks) {
                this.checkBackgroundTasks(background_tasks);
            }.bind(this));
            this.fetch();
        },

        watch: {
            page () {
                this.fetch();
            },
        },

        methods: {
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
                        // setTimeout( function () {
                        //     component.sync();
                        // }, 1000 * 60 * 60);
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.loaded'));
                        console.log(error);
                    });
            },
            checkBackgroundTasks(background_tasks) {
                const component = this;
                component.syncing.status = this.getOrderSyncingStatus(background_tasks);
            },
            getOrderSyncingStatus(background_tasks) {
                const keys = [
                    'user',
                    window.user.id,
                    'order',
                    'sync',
                ];
                let check_object = background_tasks;

                // check if all keys exist
                for (let i in keys) {
                    if (check_object[keys[i]] === undefined) {
                        return false;
                    }
                    check_object = check_object[keys[i]];
                }

                return !!background_tasks['user'][window.user.id]['order']['sync'] || false;;
            },
            send(item) {
                var component = this;
                axios.post(item.path + '/send')
                    .then(function (response) {
                        component.fetch();
                        Vue.success(component.$t('order.successes.synced'));
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            syncCardmarket() {
                var component = this;
                if (component.syncing.status == 1) {
                    return;
                }
                clearTimeout(component.syncing.timeout);
                axios.put('/order/sync', component.filter)
                    .then(function (response) {
                        component.syncing.status = 1;
                        Vue.success('Bestellungen von Cardmarket werden im Hintergrund aktualisiert.');
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
            syncWooCommerce() {
                var component = this;
                if (component.syncing.status == 1) {
                    return;
                }
                clearTimeout(component.syncing.timeout);
                axios.get('/order/woocommerce/import', {
                    params: component.filter
                })
                    .then(function (response) {
                        component.syncing.status = 1;
                        Vue.success('Bestellungen von WooCommerce werden im Hintergrund aktualisiert.');
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                    });
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
