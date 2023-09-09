<template>
    <tr>
        <td class="align-middle d-none d-sm-table-cell pointer" @click="toShow">{{ (index + 1) }}</td>
        <td class="align-middle d-none d-lg-table-cell text-center"><i class="fas fa-fw" :class="item.sync_icon" :title="item.sync_error || 'Karte synchronisiert'"></i></td>
        <td class="align-middle pointer" @click="toShow"><i class="fas fa-fw" :class="item.state_icon" :title="item.state_comments"></i></td>
        <td class="align-middle pointer" @click="toShow">
            <div><span class="fi" :class="'fi-' + item.language.code" :title="item.language.name"></span> {{ item.local_name }}<span v-if="item.card && item.card.number"> (#{{ item.card.number }}) {{ item.id }}</span></div>
            <div v-if="item.cardmarket_comments">{{ item.cardmarket_comments }}</div>
        </td>
        <td class="align-middle d-none d-xl-table-cell pointer" @click="toShow">{{ item.state_comments }}</td>
        <td class="align-middle pointer" @click="toShow"><expansion-icon :expansion="item.card.expansion" :show-name="false" v-if="item.card && item.card.expansion"></expansion-icon></td>
        <td class="align-middle d-none d-lg-table-cell text-center pointer" @click="toShow"><rarity :value="item.card.rarity" v-if="item.card"></rarity></td>
        <td class="align-middle d-none d-xl-table-cell text-center pointer" @click="toShow"><condition :value="item.condition"></condition></td>
        <td class="align-middle d-none d-lg-table-cell pointer" @click="toShow">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-sm-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_cost).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-xl-table-cell text-right pointer" @click="toShow">{{ Number(item.unit_price - item.unit_cost - item.provision).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-sm-table-cell pointer" @click="toShow">{{ item.number }}</td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-sm btn-secondary" :title="$t('app.actions.show')" @click="toShow"><i class="fas fa-fw fa-eye"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.delete')" @click="destroy" v-if="item.card == null"><i class="fas fa-fw fa-trash"></i></button>
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
                if (this.item.card === null) {
                    return;
                }
                this.$emit('show', {
                    src: this.item.card.imagePath,
                    top: (event.y - 425) + 'px',
                    left: (event.x - document.getElementById('nav').offsetLeft - 175) + 'px',
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
        },
    };
</script>