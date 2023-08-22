<template>
    <tr>
        <td class="align-middle text-left"><a :href="item.path">{{ item.id }}</a></td>
        <td class="align-middle text-left"><expansion-icon :expansion="item"></expansion-icon></td>
        <td class="align-middle text-left">{{ item.abbreviation }}</td>
        <td class="align-middle text-left">{{ item.released_at_formatted }}</td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-secondary" title="Synchronisieren" @click="sync" :disabled="is_importing_expansion"><i class="fas fa-fw fa-sync" :class="{'fa-spin': is_importing_expansion}"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import expansionIcon from '../expansion/icon.vue';

    export default {

        components: {
            expansionIcon,
        },

        props: {
            item: {
                type: Object,
                required: true,
            },
            uri: {
                type: String,
                required: true,
            },
            index: {
                type: Number,
                required: true,
            },
            is_importing_expansion: {
                type: Boolean,
                required: true,
            },
        },

        data () {
            return {
                id: this.item.id,
                errors: {},
            };
        },

        methods: {
            sync() {
                var component = this;
                axios.put('/expansions/' + component.id)
                .then(function (response) {
                        Vue.success('Erweiterung wird im Hintergrund importiert.');
                        component.$emit('update-background-tasks', {
                            background_tasks: response.data.background_tasks,
                        });
                })
                .catch( function (error) {
                    component.errors = error.response.data.errors;
                    Vue.error(component.$t('app.errors.created'));
                });
            },
        },
    };
</script>