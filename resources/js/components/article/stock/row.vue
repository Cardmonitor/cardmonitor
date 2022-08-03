<template>
    <tr>

        <td class="align-middle d-none d-xl-table-cell pointer"><i class="fas fa-image" @mouseover="show($event)" @mouseout="$emit('hide')"></i></td>
        <td class="align-middle">
            <span class="fi-icon" :class="'fi-icon-' + item.language.code" :title="item.language.name"></span> {{ item.localName }} ({{ item.card.number }})
            <div class="text-muted" v-if="item.language_id != 1">{{ item.card.name }}</div></td>
        <td class="align-middle text-center"><expansion-icon :expansion="item.card.expansion" :show-name="false"></expansion-icon></td>
        <td class="align-middle d-none d-xl-table-cell text-center"><rarity :value="item.card.rarity"></rarity></td>
        <td class="align-middle d-none d-lg-table-cell text-center"><span class="fi-icon" :class="'fi-icon-' + item.language.code" :title="item.language.name"></span></td>
        <td class="align-middle d-none d-lg-table-cell text-center"><condition :value="item.condition"></condition></td>
        <td class="align-middle d-none d-xl-table-cell text-center">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
            <span v-if="item.is_signed">S</span>
            <span v-if="item.is_playset">P</span>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right">{{ Number(item.unit_price).format(2, ',', '.') }} €</td>
        <td class="align-middle text-right pointer">
            <input class="form-control text-right" :class="'amount' in errors ? 'is-invalid' : ''" type="text" v-model="form.amount" @keydown.enter="update">
            <div class="invalid-feedback" v-text="'amount' in errors ? errors.amount[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save')" @click="update(false)"><i class="fas fa-fw fa-save"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save_upload')" @click="update(true)"><i class="fas fa-fw fa-cloud-upload-alt"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.delete')" @click="destroy" v-if="false"><i class="fas fa-fw fa-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import condition from '../../partials/emoji/condition.vue';
    import rarity from '../../partials/emoji/rarity.vue';
    import expansionIcon from '../../expansion/icon.vue';

    export default {

        components: {
            condition,
            expansionIcon,
            rarity,
        },

        props: ['item', 'uri', 'selected', 'conditions', 'languages', 'storages'],

        data () {
            return {
                id: this.item.id,
                isEditing: (this.item.sold_at ? false : true),
                isAdvancedEditing: false,
                form: {
                    amount: this.item.amount,
                    sync: true,
                },
                errors: {},
            };
        },

        mounted() {
            this.$watch('item', function (newValue, oldValue) {
                this.form = {
                    amount: newValue.amount,
                    sync: false,
                };
            }, {
                deep: true
            });
        },

        methods: {
            show(event) {
                var position = this.GetScreenCordinates(event.target);
                this.$emit('show', {
                    src: this.item.card.imagePath,
                    top: (position.y - 200) + 'px',
                    left: (position.x - document.getElementById('nav').offsetLeft - 150) + 'px',
                });
            },
            toShow() {
                this.$emit('toshow');
            },
            destroy() {
                var component = this;
                axios.delete(component.item.path)
                    .then( function (response) {
                        if (response.data.deleted) {
                            Vue.success(component.$t('app.successes.deleted'))
                            component.$emit("deleted", component.id);
                            return;
                        }

                        Vue.error(component.$t('app.errors.deleted'));
                    })
            },
            update(sync) {
                var component = this;
                component.form.sync = sync;
                axios.put('/article/stock/' + component.item.id, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('updated', response.data);
                        Vue.success((sync ? 'Bestand wurde angepasst und hochgeladen' : component.$t('app.successes.created')));
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.updated'));
                });
            },
            GetScreenCordinates(obj) {
                var p = {};
                p.x = obj.offsetLeft;
                p.y = obj.offsetTop;
                while (obj.offsetParent) {
                    p.x = p.x + obj.offsetParent.offsetLeft;
                    p.y = p.y + obj.offsetParent.offsetTop;
                    if (obj == document.getElementsByTagName("body")[0]) {
                        break;
                    }
                    else {
                        obj = obj.offsetParent;
                    }
                }
                return p;
            },
        },
    };
</script>