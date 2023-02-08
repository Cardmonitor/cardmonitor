<template>
    <div>
        <div class="row">
            <div class="col d-flex align-items-start mb-1 mb-sm-0">
                <div class="form-group mb-0 mr-1">
                    <div>
                        <input type="text" class="form-control form-control-sm" :class="'name' in errors ? 'is-invalid' : ''" v-model="form.name" placeholder="Name" @keydown.enter="create">
                        <div class="invalid-feedback" v-text="'name' in errors ? errors.name[0] : ''"></div>
                    </div>
                </div>
                <button class="btn btn-sm btn-primary" @click="create"><i class="fas fa-plus-square"></i></button>
            </div>
            <div class="col-auto d-flex align-items-start">
                <div class="form-group mb-0">
                    <filter-search v-model="filter.searchtext" @input="fetch()"></filter-search>
                </div>
                <button class="btn btn-sm btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
                <button class="btn btn-sm btn-secondary text-overflow-ellipsis ml-1" :disabled="isAssigning" title="Lagerplätze neu zuweisen" @click="assign">{{ $t('storages.actions.assign') }}</button>
            </div>
        </div>

        <div v-if="isLoading" class="mt-3 p-5">
            <center>
                <span style="font-size: 48px;">
                    <i class="fas fa-spinner fa-spin"></i><br />
                </span>
                {{ $t('app.loading') }}
            </center>
        </div>
        <div class="table-responsive mt-3" v-else-if="items.length">
            <table class="table table-sm table-hover table-striped bg-white">
                <thead>
                    <tr>
                        <th width="5%">
                            <label class="form-checkbox" for="checkall"></label>
                            <input id="checkall" type="checkbox" v-model="selectAll">
                        </th>
                        <th width="40%">{{ $t('app.name') }}</th>
                        <th class="text-right d-none d-md-table-cell" width="15%">{{ $t('storages.content.plural') }}</th>
                        <th class="text-right d-none d-sm-table-cell" width="15%">{{ $t('app.article') }}</th>
                        <th class="text-right d-none d-sm-table-cell" width="15%">{{ $t('app.price') }}</th>
                        <th class="text-right w-action">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :storages="items" :key="item.id" :uri="uri" :selected="(selected.indexOf(item.id) == -1) ? false : true" @updated="fetch" @deleted="remove(index)" @input="toggleSelected"></row>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('storages.alerts.no_data') }}</center></div>
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

        data () {
            return {
                isAssigning: false,
                uri: '/storages',
                items: [],
                isLoading: true,
                filter: {

                },
                form: {
                    name: '',
                },
                selected: [],
                errors: {},
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
        },

        methods: {
            assign() {
                var component = this;
                component.isAssigning = true;
                axios.post(component.uri + '/assign')
                    .then(function (response) {
                        Vue.success(component.$t('app.successes.assigned'));
                    })
                    .catch( function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.assigned'));
                    })
                    .finally( function () {
                        component.isAssigning = false;
                });
            },
            create() {
                var component = this;
                axios.post(component.uri, component.form)
                    .then(function (response) {
                        location.href = response.data.path;
                    })
                    .catch( function (error) {
                        component.errors = error.response.data.errors;
                        // Vue.error('Interaktion konnte nicht erstellt werden!');
                });
            },
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get(component.uri, {
                    params: component.filter
                })
                    .then(function (response) {
                        component.items = response.data;
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        // Vue.error('Interaktionen konnten nicht geladen werden!');
                        console.log(error);
                    });
            },
            remove(index) {
                this.items.splice(index, 1);
                // Vue.success('Interaktion gelöscht.');
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
        },
    };
</script>