<template>
    <tr>
        <td class="align-middle d-none d-sm-table-cell pointer" @click="toShow">{{ (index + 1) }}</td>
        <td class="align-middle text-center pointer" @click="toShow"><i class="fas fa-fw" :class="item.state_icon" :title="item.state_comments"></i></td>
        <td class="align-middle pointer" @click="toShow">
            <div><span>{{ language }}</span> {{ item.name }}<span v-if="card.number"> ({{ card.number }})</span></div>
            <div v-if="item.cardmarket_comments">{{ item.cardmarket_comments }}</div>
        </td>
        <td class="align-middle pointer" @click="toShow"><expansion-icon :expansion="card.expansion" :show-name="false"></expansion-icon></td>
        <td class="align-middle d-none d-lg-table-cell text-center pointer" @click="toShow"><rarity :value="card.rarity"></rarity></td>
        <td class="align-middle d-none d-xl-table-cell text-center pointer" @click="toShow"><condition :value="condition"></condition></td>
        <td class="align-middle d-none d-lg-table-cell pointer" @click="toShow">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control form-control-sm text-right" :class="'unit_cost_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.unit_cost_formatted" @keydown.enter="update">
            <div class="invalid-feedback" v-text="'unit_cost_formatted' in errors ? errors.unit_cost_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control form-control-sm text-right" :class="'provision_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.provision_formatted" @keydown.enter="update">
            <div class="invalid-feedback" v-text="'provision_formatted' in errors ? errors.provision_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price - item.unit_cost - item.provision).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.show')" @click="toShow"><i class="fas fa-fw fa-eye"></i></button>
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.save')" @click="update"><i class="fas fa-fw fa-save"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import condition from '../../../partials/emoji/condition.vue';
    import rarity from '../../../partials/emoji/rarity.vue';
    import expansionIcon from '../../../expansion/icon.vue';

    export default {

        components: {
            condition,
            expansionIcon,
            rarity,
        },

        props: {
            card: {
                type: Object,
                required: true,
            },
            conditions: {
                type: Object,
                required: true,
            },
            item: {
                type: Object,
                required: true,
            },
            index: {
                type: Number,
                required: true,
            },
        },

        computed: {
            isFoil() {
                let component = this;
                let foil = this.item.meta_data.find( function (meta, index) {
                    component.meta_data.keys.foil = index;
                    return meta.key === 'foil';
                });

                return foil ? foil.value : false;
            },
            language() {
                let component = this;
                let language = this.item.meta_data.find( function (meta, index) {
                    component.meta_data.keys.language = index;
                    return meta.key.startsWith('sprache');
                });

                return language ? language.value : '';
            },
            condition() {
                let component = this;
                let condition = this.item.meta_data.find( function (meta, index) {
                    component.meta_data.keys.condition = index;
                    return meta.key === 'zustand';
                });

                // Key steht in den Klammern am Ende des Strings
                return condition.value.match(/\(([^)]+)\)/)[1];
            },
        },

        data () {
            return {
                id: this.item.id,
                isEditing: false,
                form: {
                    provision_formatted: this.item.provision_formatted,
                    unit_cost_formatted: this.item.unit_cost_formatted,
                },
                errors: {},
                meta_data: {
                    keys: {
                        foil: -1,
                        language: -1,
                        condition: -1,
                    },
                },
            };
        },

        methods: {
            show(event) {
                this.$emit('show', {
                    src: this.item.image.src,
                    top: (event.y - 425) + 'px',
                    left: (event.x - document.getElementById('nav').offsetLeft - 175) + 'px',
                });
            },
            toShow() {
                this.$emit('toshow');
            },
            update() {
                var component = this;
                axios.put('/article/' + component.id, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.isEditing = false;
                        component.$emit('updated', response.data);
                        Vue.success('Artikel gespeichert.');
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error('Artikel konnte nicht gespeichert werden.');
                });
            },
        },
    };
</script>