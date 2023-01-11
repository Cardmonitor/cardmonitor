<template>
    <tr>
        <td class="align-middle pointer" @click="link">
            {{ item.name }}<br />
            <span class="text-muted">{{ item.original_name }}</span>
        </td>
        <td class="text-right align-middle">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.delete')" @click="destroy"><i class="fas fa-fw fa-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    export default {

        props: [
            'item',
            'uri',
        ],

        data () {
            return {
                id: this.item.id,
                errors: {},
            };
        },

        methods: {
            destroy() {
                axios.delete('/image/' + this.id);
                this.$emit("deleted", this.id);
            },
            link() {
                location.href = this.item.url;
            },
        },
    };
</script>