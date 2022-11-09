<template>
    <div>
        <div class="row">
            <div class="col">
                <filter-game :initial-value="filter.game_id" :options="games" :game-id="filter.game_id" :show-label="false" :option-all="false" v-model="filter.game_id"></filter-game>
            </div>

            <div class="col d-flex align-items-start">
                <v-select class="d-flex align-items-center" :clearable="false" :options="availableExpansions" label="name" :reduce="option => option.id" placeholder="Erweiterung hinzufügen" v-model="form.expansion_id" @input="create">
                    <template v-slot:option="option">
                        <expansion-icon :expansion="option" :name-ellipsis="true"></expansion-icon>
                    </template>
                </v-select>
            </div>
        </div>

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
                        <th class="align-middle" width="80%">{{ $t('app.expansion') }}</th>
                        <th class="align-middle text-right" width="20%">{{ $t('app.actions.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(item, index) in items">
                        <row :model="model" :item="item" :index="index" :key="item.id" :uri="uri" @deleted="remove(index)" @updated="updated(index, $event)"></row>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="alert alert-dark mt-3" v-else><center>{{ $t('expansion.alerts.no_data') }}</center></div>
    </div>
</template>

<script>
    import vSelect from 'vue-select';

    import row from "./row.vue";
    import expansionIcon from '../../expansion/icon.vue';
    import filterGame from "../../filter/game.vue";

    export default {

        components: {
            expansionIcon,
            filterGame,
            row,
            vSelect,
        },

        props: {
            model: {
                required: true,
                type: Object,
            },
            games: {
                type: Object,
                required: true,
            },
            expansions: {
                type: Array,
                required: true,
            },
        },

        data () {
            return {
                uri: this.model.path + '/content',
                isLoading: false,
                items: [],
                filter: {
                    game_id: 1,
                },
                form: {
                    expansion_id: 0,
                },
                errors: {},
            };
        },

        mounted() {

            this.fetch();

        },

        computed: {
            availableExpansions() {
                const expansion_ids = this.items.reduce( function (total, content) {
                    total.push(content.storagable.id);
                    return total;
                }, []);

                function compare(a, b) {
                    if (a.name < b.name) {
                        return -1;
                    }

                    if (a.name > b.name) {
                        return 1;
                    }

                    return 0;
                }

                var component = this;

                return this.expansions.filter(function (expansion) {
                    return ((expansion.game_id == component.filter.game_id) && (expansion_ids.indexOf(expansion.id) == -1));
                }).sort(compare);
            },
        },

        methods: {
            create() {
                var component = this;
                axios.post(component.uri, component.form)
                    .then(function (response) {
                        component.items.unshift(response.data);
                        component.form.expansion_id = 0;
                        Vue.success(component.$t('app.successes.created'));
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
                        component.items = response.data;
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('storages.content.errors.loaded'));
                        console.log(error);
                    });
            },
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            remove(index) {
                this.items.splice(index, 1);
            },
        },
    };
</script>