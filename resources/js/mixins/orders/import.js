export const ImportMixin = {

    data () {
        return {
            is_importing: {
                status: false,
                interval: null,
                timeout: null,
                cardmarket: false,
                woocommerce: false,
            },
        };
    },

    methods: {
        importFromCardmarket() {
            var component = this;
            if (component.is_importing.status == 1) {
                return;
            }
            clearTimeout(component.is_importing.timeout);
            axios.put('/order/sync', component.filter)
                .then(function (response) {
                    component.is_importing.status = true;
                    component.is_importing.cardmarket = true;
                    Vue.success('Bestellungen von Cardmarket werden im Hintergrund aktualisiert.');
                })
                .catch(function (error) {
                    Vue.error(component.$t('order.errors.synced'));
                    console.log(error);
                })
                .finally ( function () {

                });
        },
        importFromWooCommerce() {
            var component = this;
            if (component.is_importing.status == 1) {
                return;
            }
            clearTimeout(component.is_importing.timeout);
            axios.get('/order/woocommerce/import', {
                params: component.filter
            })
                .then(function (response) {
                    component.is_importing.status = true;
                    component.is_importing.woocommerce = true;
                    Vue.success('Bestellungen von WooCommerce werden im Hintergrund aktualisiert.');
                })
                .catch(function (error) {
                    Vue.error(component.$t('order.errors.synced'));
                    console.log(error);
                })
                .finally ( function () {

                });
        },
    },

};
