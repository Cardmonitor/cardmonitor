<template>
    <div class="row">
        <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3" :key="index" v-for="(card, index) in cards">
            <img :src="card.imagePath" :alt="card.name" :title="'#' + card.number + ' ' + card.name" class="img-fluid">
        </div>
    </div>
</template>

<script>
    export default {

        props: {
            cards: {
                type: Object,
                required: true,
            },
        },

        data () {
            return {
                //
            };
        },
    };
</script>