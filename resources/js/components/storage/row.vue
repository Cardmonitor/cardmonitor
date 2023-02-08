<template>
    <tr>
        <td class="align-middle">
            <label class="form-checkbox"></label>
            <input :checked="selected" type="checkbox" :value="id"  @change="$emit('input', id)" number>
        </td>
        <td class="align-middle pointer" @click="link" v-html="item.indentedName"></td>
        <td class="align-middle text-right pointer d-none d-md-table-cell" @click="link">{{ item.contents_count }}</td>
        <td class="align-middle text-right pointer d-none d-sm-table-cell" @click="link">{{ item.articleStats.count_formatted }}</td>
        <td class="align-middle text-right pointer d-none d-sm-table-cell" @click="link">{{ item.articleStats.price_formatted }} €</td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <a :href="item.editPath" class="btn btn-sm btn-secondary" :title="$t('app.actions.edit')"><i class="fas fa-edit"></i></a>
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.delete')" @click="destroy" v-if="item.isDeletable"><i class="fas fa-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    export default {

        props: ['item', 'uri', 'selected', 'storages'],

        data () {
            return {
                id: this.item.id,
                form: {
                    parent_id: this.item.parent_id,
                },
            };
        },

        methods: {
            destroy() {
                var component = this;
                axios.delete(component.item.path)
                    .then(function (response) {
                        if (response.data.deleted) {
                            component.$emit("deleted", component.id);
                            // Vue.success('Kosten wurden gelöscht.');
                        }
                        else {
                            // Vue.error('Kosten konnten nicht gelöscht werden.');
                        }
                    });
            },
            setParent() {
                var component = this;
                axios.put(component.item.path + '/parent', component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('updated', response.data);
                        Vue.success('Lagerplatz wurde gespeichert.');
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error('Lagerplatz konnte nicht gespeichert werden.');
                });
                },
            link () {
                location.href = this.item.path;
            }
        },
    };
</script>