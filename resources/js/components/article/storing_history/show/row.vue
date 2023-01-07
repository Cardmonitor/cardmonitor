<template>
    <tr>
        <td class="align-middle d-none d-lg-table-cell text-center">
            <i class="fas fa-fw fa-euro-sign text-success" title="Verkauft" v-if="item.orders.length"></i>
            <i class="fas fa-fw" :class="item.sync_icon" :title="item.sync_error || 'Karte synchronisiert'" v-else></i>
        </td>
        <td class="align-middle d-none d-xl-table-cell pointer"><i class="fas fa-image" @mouseover="show($event)" @mouseout="$emit('hide')"></i></td>
        <td class="align-middle pointer" @click="link()">
            <span class="fi" :class="'fi-' + item.language.code" :title="item.language.name"></span> {{ item.localName }} ({{ item.card.number }})
            <div class="text-muted" v-if="item.language_id != 1">{{ item.card.name }}</div></td>
        <td class="align-middle d-none d-xl-table-cell text-center"><expansion-icon :expansion="item.card.expansion" :show-name="false" v-if="item.card.expansion"></expansion-icon></td>
        <td class="align-middle d-none d-xl-table-cell text-center"><rarity :value="item.card.rarity" v-if="item.card.rarity"></rarity></td>
        <td class="align-middle d-none d-lg-table-cell text-center"><condition :value="item.condition"></condition></td>
        <td class="align-middle d-none d-xl-table-cell text-center">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
            <span v-if="item.is_signed">S</span>
            <span v-if="item.is_playset">P</span>
        </td>
        <td class="align-middle text-right">{{ item.number }}</td>
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
            item: {
                type: Object,
                required: true,
            },
        },

        data () {
            return {
                id: this.item.id,
            };
        },

        methods: {
            show(event) {
                this.$emit('show', {
                    src: this.item.card.imagePath,
                });
            },
            toShow() {
                this.$emit('toshow');
            },
            link() {
                location.href = this.item.path;
            },
        },
    };
</script>