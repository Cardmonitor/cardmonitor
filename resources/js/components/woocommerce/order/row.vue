<template>
    <tr>
        <td class="align-middle">{{ (new Date(item.date_created)).toLocaleDateString({ day: '2-digit', month: '2-digit' }) }}</td>
        <td class="align-middle">{{ item.id }}</td>
        <td class="align-middle">{{ item.billing.last_name }}, {{ item.billing.first_name }}</td>
        <td class="align-middle">{{ item.status }}</td>
        <td class="align-middle text-right">{{ item.line_items.length }}</td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" title="Importieren" @click="store()">
                    <i class="fas fa-file-import" v-show="!is_storing"></i>
                    <i class="fas fa-spinner fa-spin" v-show="is_storing"></i>
                </button>
            </div>
        </td>
    </tr>
</template>

<script>
    export default {

        components: {

        },

        props: [ 'item' ],

        data () {
            return {
                is_storing: false,
                id: this.item.id,
            };
        },

        methods: {
            store(item) {
                var component = this;

                if (component.is_storing) {
                    return;
                }

                component.is_storing = true;
                axios.post('/woocommerce/order', {
                    id: component.id,
                })
                    .then(function (response) {
                        component.$emit('updated', response.data);
                        Vue.success(component.$t('order.successes.synced'));
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {
                        component.is_storing = false;
                });
            },
        },
    };
</script>