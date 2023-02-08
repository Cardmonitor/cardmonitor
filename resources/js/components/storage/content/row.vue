<template>
    <tr>
        <td class="align-middle text-left"><expansion-icon :expansion="item.storagable"></expansion-icon></td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.delete')" @click="destroy"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import expansionIcon from '../../expansion/icon.vue';

    export default {

        components: {
            expansionIcon,
        },

        props: ['item', 'uri', 'index'],

        data () {
            return {
                id: this.item.id,
                errors: {},
            };
        },

        methods: {
            destroy() {
                var component = this;
                axios.delete('/content/' + component.id)
                    .then(function (response) {
                        if (response.data.deleted) {
                            component.$emit("deleted", component.id);
                            Vue.success(component.$t('app.successes.deleted'));
                        }
                        else {
                            Vue.error(component.$t('app.errors.deleted'));
                        }
                });
            },
        },
    };
</script>