<template>
    <div>
        <div class="card">
            <div class="card-header">Artikel</div>
            <div class="card-body">
                <woocommerce-order-article-index :model="model" :cards="cards" :conditions="conditions" v-model="model.line_items"></woocommerce-order-article-index>
            </div>
        </div>
    </div>
</template>
<script>
    import woocommerceOrderArticleIndex from './article/index.vue';

    export default {

        components: {
            woocommerceOrderArticleIndex,
        },

        props: {
            cards: {
                type: Object,
                required: true,
            },
            conditions: {
                type: Object,
                required: true,
            },
            order: {
                type: Object,
                required: true,
            },
        },

        data() {
            return {
                model: this.order,
            };
        },
        methods: {

        },
    };
</script>