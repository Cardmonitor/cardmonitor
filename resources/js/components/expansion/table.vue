<template>
    <div>
        <div class="row">

            <div class="col d-flex align-items-start mb-1 mb-sm-0">
                <div class="form-group mb-0 mr-1">
                    <div>
                        <input type="text" class="form-control form-control-sm" :class="'abbreviation' in errors ? 'is-invalid' : ''" v-model="form.abbreviation" placeholder="Abkürzung" @keydown.enter="create">
                        <div class="invalid-feedback" v-text="'abbreviation' in errors ? errors.abbreviation[0] : ''"></div>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" @click="create"><i class="fas fa-plus-square"></i></button>
            </div>
            <div class="col-auto d-flex align-items-start">
                <div class="form-group mb-0">
                    <filter-search v-model="filter.searchtext" @input="fetch()"></filter-search>
                </div>
                <button class="btn btn-sm btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
            </div>
        </div>

        <form v-if="filter.show" id="filter" class="mt-1">
            <div  class="form-row">

                <div class="col-auto">
                    <filter-game :initial-value="filter.game_id" :options="games" :game-id="filter.game_id" :show-label="true" v-model="filter.game_id" @input="fetch()"></filter-game>
                </div>

            </div>
        </form>

        <div v-if="isLoading" class="mt-3 p-5">
            <center>
                <span style="font-size: 48px;">
                    <i class="fas fa-spinner fa-spin"></i><br />
                </span>
                {{ $t('app.loading') }}s
            </center>
        </div>
        <div class="table-responsive mt-3" v-else-if="items.length">
            <table class="table table-hover table-striped bg-white">
                <thead>
                    <tr>
                        <th class="align-middle" width="10%">ID</th>
                        <th class="align-middle" width="35%">Name</th>
                        <th class="align-middle" width="15%">Abkürzung</th>
                        <th class="align-middle" width="20%">Veröffentlicht</th>
                        <th class="align-middle text-right" width="20%">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :index="index" :key="item.id" :uri="uri"></row>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('expansion.alerts.no_data') }}</center></div>
    </div>
</template>

<script>
    import row from "./row.vue";
    import filterGame from "../filter/game.vue";
    import filterSearch from "../filter/search.vue";

    export default {

        components: {
            filterGame,
            filterSearch,
            row,
        },

        props: {
            games: {
                type: Object,
                required: true,
            },
        },

        data () {
            return {
                uri: '/expansions',
                isLoading: false,
                items: [],
                filter: {
                    show: false,
                    page: 1,
                    game_id: 1,
                    searchtext: '',
                },
                errors: {},
                paginate: {
                    nextPageUrl: null,
                    prevPageUrl: null,
                    lastPage: 0,
                },
                form: {
                    abbreviation: '',
                    game_id: 1,
                },
            };
        },

        mounted() {

            this.fetch();

        },

        computed: {

        },

        methods: {
            create() {
                var component = this;
                component.game_id = component.filter.game_id;
                axios.post(component.uri, component.form)
                    .then(function (response) {
                        Vue.success('Erweiterung wird im Hintergrund importiert.');
                    })
                    .catch( function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.created'));
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
                        Vue.error('Erweiterungen konnten nicht geladen werden!');
                        console.log(error);
                    });
            },
        },
    };
</script>