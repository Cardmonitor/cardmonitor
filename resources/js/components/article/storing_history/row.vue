<template>
    <tr>
        <td class="align-middle" @click="show()">{{ item.created_at_formatted }}</td>
        <td class="align-middle text-right" @click="show()">{{ item.articles_count }}</td>
    </tr>
</template>

<script>
    export default {

        components: {
            //
        },

        props: {
            item: {
                type: Object,
                required: true,
            },
        },

        data () {
            return {
                //
            };
        },

        methods: {
            show() {
                location.href = this.item.path;
            },
        },
    };
</script>