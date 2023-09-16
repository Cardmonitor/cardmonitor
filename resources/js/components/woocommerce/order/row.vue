<template>
    <tr>
        <td class="align-middle pointer" @click="show">{{ (new Date(item.date_created)).toLocaleDateString({ day: '2-digit', month: '2-digit' }) }}</td>
        <td class="align-middle pointer" @click="show">{{ item.id }}</td>
        <td class="align-middle pointer" @click="show">{{ item.billing.last_name }}, {{ item.billing.first_name }}</td>
        <td class="align-middle pointer" @click="show">{{ item.status }}</td>
        <td class="align-middle pointer text-right" @click="show">{{ item.line_items.length }}</td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" :disabled="item.status !== 'on-hold'" title="Importieren" @click="store()">
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
            import() {

            },
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
                        location.href = response.data.path;
                    })
                    .catch(function (error) {
                        Vue.error('Die Bestellung konnte nicht importiert werden.');
                        console.log(error);
                    })
                    .finally ( function () {
                        component.is_storing = false;
                });
            },
            show() {
                location.href = '/woocommerce/order/' + this.item.id;
            }
        },
    };
</script>