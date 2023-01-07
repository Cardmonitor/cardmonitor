<template>
    <tr>
        <td class="align-middle pointer" @click="show()">{{ item.created_at_formatted }}</td>
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