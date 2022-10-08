<template>
    <div class="" v-if="items.length > 0">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center">
                <div class="col">{{ $t('order.home.paid.title') }}</div>
                <div><a class="text-body" href="/order/picklist">Pickliste</a></div>
                <div class="ml-3"><i class="fas fa-sync pointer" @click="sync" :class="{'fa-spin': syncing.status == 1}"></i></div>
                <div class="ml-3" ><i class="fas fa-download pointer" @click="download" :disabled="syncing.status == 1"></i></div>
                <div class="ml-3" ><a href="/order/export/dropbox" class="text-body"><i class="fab fa-dropbox pointer"></i></a></div>
                <div class="ml-3" ><i data-toggle="modal" data-target="#import-sent" class="fas fa-upload pointer"></i></div>
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
                    <tbody>
                        <tr v-for="(item, key) in items">
                            <td class="align-middle d-none d-md-table-cell">{{ item.paid_at_formatted }}</td>
                            <td class="align-middle">
                                <a :href="item.path">{{ item.cardmarket_order_id }}</a>
                                <div class="text-muted" v-if="item.buyer">{{ item.buyer.name }}</div>
                            </td>
                            <td class="align-middle d-none d-sm-table-cell">
                                <div>{{ item.revenue_formatted }} € </div>
                                <div>{{ item.articles_count }} {{ $t('app.article') }}</div>
                            </td>
                            <td class="align-middle text-right">
                                <button class="btn btn-sm btn-primary":title="$t('app.actions.send')" @click="send(item)">{{ $t('app.actions.send') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    export default {

        props: {
            isSyncingOrders: {
                required: true,
                type: Number,
            },
        },

        data() {
            return {
                uri: '/order',
                isLoading: true,
                syncing: {
                    status: this.isSyncingOrders,
                    interval: null,
                    timeout: null,
                },
                filter: {
                    state: 'paid',
                    presale: 0,
                },
                items: [],
            };
        },

        mounted() {
            if (this.isSyncingOrders) {
                this.checkIsSyncingOrders();
            }
            else {
                // this.sync();
                this.fetch();
            }
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
            checkIsSyncingOrders() {
                var component = this;
                this.syncing.interval = setInterval(function () {
                    component.getIsSyncingOrders()
                }, 3000);
            },
            getIsSyncingOrders() {
                var component = this;
                axios.get('/order/sync')
                    .then(function (response) {
                        component.syncing.status = response.data.is_syncing_articles;
                        if (component.syncing.status == 0) {
                            clearInterval(component.syncing.interval)
                            component.syncing.interval = null;
                            component.fetch();
                            Vue.success('Bestellungen wurden synchronisiert.');
                        }
                    })
                    .catch(function (error) {
                        console.log(error);
                    })
                    .finally ( function () {

                    });
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
            sync() {
                var component = this;
                if (component.syncing.status == 1) {
                    return;
                }
                clearTimeout(component.syncing.timeout);
                axios.put('/order/sync', component.filter)
                    .then(function (response) {
                        component.syncing.status = 1;
                        component.checkIsSyncingOrders();
                        Vue.success('Bestellungen werden im Hintergrund aktualisiert.');
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                    });
            },
        },

    };
</script>