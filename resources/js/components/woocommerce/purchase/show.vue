<template>
    <div>
        <div class="card">
            <div class="card-header">Artikel</div>
            <div class="card-body">
                <woocommerce-purchase-article-index :model="model" :cards="cards" :conditions="conditions" v-model="model.line_items"></woocommerce-purchase-article-index>
            </div>
        </div>
    </div>
</template>
<script>
    import woocommercePurchaseArticleIndex from './article/index.vue';

    export default {

        components: {
            woocommercePurchaseArticleIndex,
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