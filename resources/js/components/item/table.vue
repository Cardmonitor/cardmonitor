<template>
    <div>
        <div class="row">
            <div class="col d-flex align-items-start mb-1 mb-sm-0">
                <div class="form-group mb-0 mr-1">
                    <div>
                        <input type="text" class="form-control" :class="'name' in errors ? 'is-invalid' : ''" v-model="form.name" placeholder="Name" @keydown.enter="create">
                        <div class="invalid-feedback" v-text="'name' in errors ? errors.name[0] : ''"></div>
                    </div>
                </div>
                <button class="btn btn-primary" @click="create"><i class="fas fa-plus-square"></i></button>
            </div>
            <div class="col-auto d-flex align-items-start">
                <div class="form-group mb-0">
                    <filter-search v-model="filter.searchtext" @input="fetch()"></filter-search>
                </div>
                <button class="btn btn-secondary ml-1" @click="filter.show = !filter.show"><i class="fas fa-filter"></i></button>
                <button class="btn btn-secondary text-overflow-ellipsis ml-1" :title="$t('item.actions.assign')" @click="reload">{{ $t('item.actions.assign') }}</button>
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
            <table class="table table-hover table-striped bg-white">
                <thead>
                    <tr>
                        <th width="5%">
                            <label class="form-checkbox" for="checkall"></label>
                            <input id="checkall" type="checkbox" v-model="selectAll">
                        </th>
                        <th width="55%">{{ $t('app.name') }}</th>
                        <th class=" d-none d-sm-table-cell" width="30%">{{ $t('app.costs') }}</th>
                        <th class="text-right" width="10%">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :item="item" :key="item.id" :uri="uri" :selected="(selected.indexOf(item.id) == -1) ? false : true" @deleted="remove(index)" @input="toggleSelected"></row>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('item.alerts.no_data') }}</center></div>
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
                uri: '/item',
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
            reload() {
                var component = this;
                axios.post(component.uri + '/reload')
                    .then(function (response) {
                        Vue.success(component.$t('item.successes.reload_background'));
                    })
                    .catch( function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('item.errors.reloaded'));
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