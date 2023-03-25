<template>
    <tr :class="{'table-warning': item.articles_on_hold_count > 0}">
        <td class="align-middle d-none d-sm-table-cell pointer" @click="link">{{ item.paid_at_formatted }}</td>
        <td class="align-middle pointer" @click="link">
            <div>{{ item.cardmarket_order_id }}</div>
            <div class="text-muted" v-if="item.buyer">{{ item.buyer.name }}</div>
        </td>
        <td class="align-middle d-none d-md-table-cell text-right pointer" @click="link">
            {{ item.articles_count }}
            <div class="text-muted" v-if="item.articles_on_hold_count"><i class="fas fa-fw fa-pause"></i> {{ item.articles_on_hold_count }}/{{ item.articles_count }}</div>
        </td>
        <td class="align-middle d-none d-md-table-cell text-right pointer" @click="link">{{ Number(item.revenue).toFixed(2) }} €</td>
        <td class="align-middle d-none d-xl-table-cell text-right pointer" @click="link">{{ Number(item.cost).toFixed(2) }} €</td>
        <td class="align-middle d-none d-xl-table-cell text-right pointer" @click="link">{{ Number(item.profit).toFixed(2) }} €</td>
        <td class="align-middle d-none d-md-table-cell pointer" @click="link">{{ item.state }}</td>
        <td class="align-middle d-none d-lg-table-cell text-center pointer" @click="link">
            <div class="d-flex align-items-center justify-content-around">
                <div :title="$t('order.evaluations.grade')"><evaluation :value="item.evaluation ? item.evaluation.grade : 0"></evaluation></div>
                <div :title="$t('order.evaluations.item_description')"><evaluation :value="item.evaluation ? item.evaluation.item_description : 0"></evaluation></div>
                <div :title="$t('order.evaluations.packaging')"><evaluation :value="item.evaluation ? item.evaluation.packaging : 0"></evaluation></div>
            </div>
            <div class="text-overflow-ellipsis" v-if="item.evaluation && item.evaluation.comment">{{ item.evaluation.comment }}</div>
        </td>
        <td class="align-middle text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button class="btn btn-primary" :title="$t('app.actions.send')" @click="send(item)" v-if="item.state == 'paid' && false">{{ $t('app.actions.send') }}</button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.edit')" @click="link"><i class="fas fa-edit"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import evaluation from '../partials/emoji/evaluation.vue';

    export default {

        components: {
            evaluation,
        },

        props: [ 'item', 'uri', 'selected' ],

        data () {
            return {
                id: this.item.id,
            };
        },

        methods: {
            link () {
                location.href = this.item.path;
            },
            send(item) {
                var component = this;
                axios.post(item.path + '/send')
                    .then(function (response) {
                        component.$emit('updated', response.data);
                        Vue.success(component.$t('order.successes.synced'));
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('order.errors.synced'));
                        console.log(error);
                    })
                    .finally ( function () {

                });
            },
        },
    };
</script>