<template>
    <div class="card mb-3">
        <div class="card-header">{{ $t('app.nav.item') }}</div>
        <div class="card-body">
            <div class="row mb-1">
                <div class="col d-flex align-items-start">
                    <div class="form-group mb-0 mr-1">
                        <div>
                            <select class="form-control" :class="'name' in errors ? 'is-invalid' : ''" v-model="form.item_id" @change="create">
                                <option :value="null">{{ $t('item.table.option_create') }}</option>
                                <option :value="item.id" v-for="(item, key) in sortedCustoms">{{ item.name }}</option>
                            </select>
                            <div class="invalid-feedback" v-text="'name' in errors ? errors.name[0] : ''"></div>
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-start">

                </div>
            </div>
            <table class="table table-striped table-hover table-sm" v-if="items.length">
                <tbody>
                    <row :item="item" :key="item.id" :uri="uri" v-for="(item, index) in items" @deleted="remove(index)" @updated="updated(index, $event)"></row>
                </tbody>
            </table>
            <div class="alert alert-dark mt-3" v-else><center>{{ $t('item.alerts.no_data') }}</center></div>
        </div>
    </div>
</template>

<script>
    import row from './row.vue';

    export default {

        components: {
            row,
        },

        props : {
            model: {
                required: true,
                type: Object,
            },
            customs: {
                required: true,
                type: Array,
            },
        },

        computed: {
            sortedCustoms: function() {
                function compare(a, b) {
                    if (a.name < b.name) {
                        return -1;
                    }

                    if (a.name > b.name) {
                        return 1;
                    }

                    return 0;
                }

                return this.customs.sort(compare);
            },
        },

        data() {
            return {
                errors: {},
                form: {
                    item_id: null,
                },
                items: this.model.sales,
                uri: this.model.path + '/transactions',
            };
        },

        methods: {
            create() {
                if (this.form.item_id == null) {
                    return;
                }
                var component = this;
                axios.post(component.model.path + '/transactions', component.form)
                    .then(function (response) {
                        component.items.push(response.data);
                        component.form.item_id = null;
                        Vue.success(component.$t('app.successes.created'));
                    })
                    .catch( function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.created'));
                });
            },
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            remove(index) {
                this.items.splice(index, 1);
                Vue.success(component.$t('app.successes.deleted'));
            },
        },

    };
</script>