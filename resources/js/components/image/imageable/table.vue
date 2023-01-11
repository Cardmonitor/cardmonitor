<template>
    <div>
        <div class="container-fluid">
            <div class="row mb-3">
                <create @created="created" :model="model"></create>
            </div>
        </div>
        <div v-if="isLoading" class="p-5">
            <center>
                <span style="font-size: 48px;">
                    <i class="fas fa-spinner fa-spin"></i><br />
                </span>
                {{ $t('app.loading') }}
            </center>
        </div>
        <table class="table table-sm table-hover table-striped bg-white" v-else-if="items.length">
            <thead>
                <tr>
                    <th width="100%">{{ $t('app.name') }}</th>
                    <th class="text-right w-action">{{ $t('app.actions.action') }}</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(item, index) in items">
                    <row :item="item" :uri="uri" :key="item.id" @deleted="remove(index)" @updated="updated(index, $event)"></row>
                </template>
            </tbody>
        </table>
        <div class="alert alert-dark" v-else><center>{{ $t('image.alerts.no_data') }}</center></div>
    </div>
</template>

<script>
    import create from "./create.vue";
    import row from "./row.vue";

    export default {

        components: {
            create,
            row,
        },

        props: [
            'model',
            'uri',
            'token',
        ],

        data () {
            return {
                items: [],
                isLoading: true,
                showFilter: false,
                searchTimeout: null,
                name: '',
                errors: {},
                action: '/order/' + this.model.id + '/images',
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

        methods: {
            created(files) {
                for (var index in files) {
                    this.items.unshift(files[index]);
                }
            },
            updated(index, item) {
                Vue.set(this.items, index, item);
            },
            fetch() {
                var component = this;
                component.isLoading = true;
                axios.get('/order/' + component.model.id + '/images')
                    .then(function (response) {
                        component.items = response.data;
                        component.isLoading = false;
                    })
                    .catch(function (error) {
                        console.log(error);
                        Vue.error(component.$t('app.errors.loaded'))
                    });
            },
            search () {
                var component = this;
                if (component.searchTimeout)
                {
                    clearTimeout(component.searchTimeout);
                    component.searchTimeout = null;
                }
                component.searchTimeout = setTimeout(function () {
                    component.fetch()
                }, 300);
            },
            remove(index) {
                this.items.splice(index, 1);
            },
        },
    };
</script>

<style>
    .filezone {
        outline: 2px dashed grey;
        outline-offset: -10px;
        background: #ccc;
        color: dimgray;
        padding: 10px 10px;
        min-height: 200px;
        position: relative;
        cursor: pointer;
    }
    .filezone:hover {
        background: #c0c0c0;
    }
</style>