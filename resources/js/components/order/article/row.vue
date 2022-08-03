<template>
    <tr>
        <td class="align-middle d-none d-sm-table-cell pointer" @click="toShow">{{ (index + 1) }}</td>
        <td class="align-middle text-center pointer" @click="toShow"><i class="fas fa-fw" :class="item.state_icon" :title="item.state_comments"></i></td>
        <td class="align-middle pointer" @click="toShow">
            <div><span class="fi" :class="'fi-' + item.language.code" :title="item.language.name"></span> {{ item.localName }}<span v-if="item.card.number"> ({{ item.card.number }})</span></div>
            <div v-if="item.cardmarket_comments">{{ item.cardmarket_comments }}</div>
        </td>
        <td class="align-middle pointer" @click="toShow"><expansion-icon :expansion="item.card.expansion" :show-name="false"></expansion-icon></td>
        <td class="align-middle d-none d-lg-table-cell text-center pointer" @click="toShow"><rarity :value="item.card.rarity"></rarity></td>
        <td class="align-middle d-none d-xl-table-cell text-center pointer" @click="toShow"><condition :value="item.condition"></condition></td>
        <td class="align-middle d-none d-lg-table-cell pointer" @click="toShow">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control text-right" :class="'unit_cost_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.unit_cost_formatted" @keydown.enter="update">
            <div class="invalid-feedback" v-text="'unit_cost_formatted' in errors ? errors.unit_cost_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control text-right" :class="'provision_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.provision_formatted" @keydown.enter="update">
            <div class="invalid-feedback" v-text="'provision_formatted' in errors ? errors.provision_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price - item.unit_cost - item.provision).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.show')" @click="toShow"><i class="fas fa-fw fa-eye"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save')" @click="update"><i class="fas fa-fw fa-save"></i></button>
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

        props: ['item', 'uri', 'index'],

        data () {
            return {
                id: this.item.id,
                isEditing: false,
                form: {
                    provision_formatted: this.item.provision_formatted,
                    unit_cost_formatted: this.item.unit_cost_formatted,
                },
                errors: {},
            };
        },

        methods: {
            show(event) {
                this.$emit('show', {
                    src: this.item.card.imagePath,
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