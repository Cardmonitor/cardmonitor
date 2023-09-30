<template>
    <div>
        <div class="row">
            <div class="col d-flex align-items-start mb-1 mb-sm-0">

            </div>
            <div class="col-auto d-flex align-items-start">
                <button class="btn btn-sm btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
            </div>
        </div>

        <form v-if="filter.show" id="filter" class="mt-1">
            <div  class="form-row">

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-status">Status</label>
                        <select class="form-control form-control-sm" id="filter-status" v-model="filter.status" @change="search">
                            <option :value="status" v-for="status in statusses">{{ status }}</option>
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
                {{ $t('app.loading') }}
            </center>
        </div>
        <div class="table-responsive mt-3" v-else-if="items.length">
            <table class="table table-hover table-striped table-sm bg-white">
                <thead>
                    <tr>
                        <th class="align-middle" width="125">Datum</th>
                        <th class="align-middle" width="125">Ankauf</th>
                        <th class="align-middle" width="90%">Käufer</th>
                        <th class="align-middle" width="125">Status</th>
                        <th class="align-middle text-right" width="75">Artikel</th>
                        <th class="align-middle text-right" width="10%">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :key="item.id" :uri="uri"></row>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>Keine Ankäufe vorhanden</center></div>
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center" v-show="paginate.total_pages > 1">
                <li class="page-item" v-show="filter.page > 1">
                    <a class="page-link" href="#" @click.prevent="filter.page--">{{ $t('app.paginate.previous') }}</a>
                </li>

                <li class="page-item" v-for="n in pages" v-bind:class="{ active: (n == filter.page) }"><a class="page-link" href="#" @click.prevent="(n != '...' ? filter.page = n : null)">{{ n }}</a></li>

                <li class="page-item" v-show="filter.page < paginate.total_pages">
                    <a class="page-link" href="#" @click.prevent="filter.page++">{{ $t('app.paginate.next') }}</a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
    import row from "./row.vue";

    export default {

        components: {
            row,
        },

        data () {
            return {
                uri: '/woocommerce/order',
                items: [],
                isLoading: true,
                filter: {
                    page: 1,
                    status: 'on-hold',
                    show: false,
                },
                paginate: {
                    total: 0,
                    total_pages: 0,
                },
                form: {
                    name: '',
                },
                selected: [],
                errors: {},
                statusses: [
                    'any',
                    'pending',
                    'processing',
                    'on-hold',
                    'completed',
                    'cancelled',
                    'refunded',
                    'failed',
                    'trash',
                ],
            };
        },

        mounted() {

            this.fetch();

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
            pages() {
                var pages = [];
                for (var i = 1; i <= this.paginate.total_pages; i++) {
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
            search() {
                this.filter.page = 1;
                this.fetch();
            },
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get(component.uri, {
                    params: component.filter
                })
                    .then(function (response) {
                        component.items = response.data.data;
                        component.paginate = response.data.pagination;
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        // Vue.error('Interaktionen konnten nicht geladen werden!');
                        console.log(error);
                    });
            },
            showPageButton(page) {
                if (page == 1 || page == this.paginate.total_pages) {
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