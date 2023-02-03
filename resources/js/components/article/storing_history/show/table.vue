<template>
    <div>
        <div class="row">
            <div class="col mb-1 mb-sm-0">

            </div>
            <div class="col-auto d-flex">
                <div class="form-group" style="margin-bottom: 0;">
                    <filter-search v-model="filter.searchtext" @input="search()"></filter-search>
                </div>
                <button class="btn btn-sm btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
            </div>
        </div>

        <form v-if="filter.show" id="filter" class="mt-1">
            <div  class="form-row">

                <div class="col-auto">
                    <div class="form-group">
                        <label for="filter-sync">{{ $t('filter.sync.label') }}</label>
                        <select class="form-control form-control-sm" id="filter-sync" v-model="filter.sync" @change="search">
                            <option :value="-1">{{ $t('filter.all') }}</option>
                            <option :value="1">{{ $t('filter.sync.error') }}</option>
                            <option :value="0">{{ $t('filter.sync.success') }}</option>
                        </select>
                    </div>
                </div>

                <div class="col-auto">
                    <filter-game :initial-value="filter.game_id" :options="games" :game-id="filter.game_id" :show-label="true" v-model="filter.game_id" @input="fetch()"></filter-game>
                </div>

                <div class="col-auto">
                    <filter-expansion :options="expansions" v-model="filter.expansion_id" @input="search"></filter-expansion>
                </div>

                <div class="col-auto">
                    <filter-storage :options="storages" v-model="filter.storage_id" @input="search"></filter-storage>
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
                        <th class="text-right w-formatted-number">Nummer</th>
                        <th class="d-none d-xl-table-cell w-icon"></th>
                        <th class="text-center d-none d-xl-table-cell w-icon"></th>
                        <th class="text-center d-none d-lg-table-cell w-formatted-number">{{ $t('app.condition') }}</th>
                        <th class="d-none d-xl-table-cell" style="width: 100px;"></th>
                        <th class="text-right w-formatted-number">Lagernummer</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :key="item.id" @show="showImgbox($event)" @hide="hideImgbox()"></row>
                    </template>
                </tbody>
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
    </div>
</template>

<script>
    import filterExpansion from "../../../filter/expansion.vue";
    import filterGame from "../../../filter/game.vue";
    import filterLanguage from "../../../filter/language.vue";
    import filterRarity from "../../../filter/rarity.vue";
    import filterRule from "../../../filter/rule.vue";
    import filterSearch from "../../../filter/search.vue";
    import filterStorage from "../../../filter/storage.vue";
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
            model: {
                type: Object,
                required: true,
            },
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
                items: [],
                isLoading: true,
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
                    rule_id: 0,
                    searchtext: '',
                    show: false,
                    sold: -1,
                    is_numbered: -1,
                    storage_id: 0,
                    sync: -1,
                    unit_cost_max: 0,
                    unit_cost_min: 0,
                    unit_price_max: 0,
                    unit_price_min: 0,
                },
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
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get(component.model.path, {
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
            search() {
                this.filter.page = 1;
                this.fetch();
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