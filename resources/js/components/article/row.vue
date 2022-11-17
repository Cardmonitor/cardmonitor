<template>
    <tr v-if="isEditing">
        <td class="align-middle d-none d-lg-table-cell text-center"><i class="fas fa-fw" :class="item.sync_icon" :title="item.sync_error || 'Karte synchronisiert'"></i></td>
        <td class="align-middle d-none d-xl-table-cell text-center pointer"><i class="fas fa-image" @mouseover="show($event)" @mouseout="$emit('hide')"></i></td>
        <td class="align-middle">
            <span class="fi" :class="'fi-' + item.language.code" :title="item.language.name"></span> {{ item.localName }}<span v-if="item.card.number"> ({{ item.card.number }})</span>
            <div class="d-none d-xl-table-cell text-muted" v-if="item.language_id != 1">{{ item.card.name }}</div>
        </td>
        <td class="align-middle text-center"><expansion-icon :expansion="item.card.expansion" :show-name="false"></expansion-icon></td>
        <td class="align-middle d-none d-xl-table-cell text-center"><rarity :value="item.card.rarity"></rarity></td>
        <td class="align-middle d-none d-lg-table-cell text-center">
            <select class="form-control form-control-sm" v-model="form.language_id">
                <option :value="language_id" v-for="(name, language_id) in languages">{{ name }}</option>
            </select>
            <div class="invalid-feedback" v-text="'unit_price_formatted' in errors ? errors.unit_price_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-lg-table-cell text-center">
            <select class="form-control form-control-sm" v-model="form.condition">
                <option :value="id" v-for="(name, id) in conditions">{{ name }}</option>
            </select>
            <div class="invalid-feedback" v-text="'condition' in errors ? errors.condition[0] : ''"></div>
        </td>

        <td class="align-middle d-none d-xl-table-cell">
            <div class="form-group form-check mb-0">
                <input class="form-check-input" type="checkbox" id="is_foil" v-model="form.is_foil">
                <label class="form-check-label" for="is_foil">Foil</label>
            </div>
            <div class="form-group form-check mb-0">
                <input class="form-check-input" type="checkbox" id="is_signed" v-model="form.is_signed">
                <label class="form-check-label" for="is_signed">Signed</label>
            </div>
            <div class="form-group form-check mb-0">
                <input class="form-check-input" type="checkbox" id="is_playset" v-model="form.is_playset">
                <label class="form-check-label" for="is_playset">Playset</label>
            </div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-center">
            <select class="form-control form-control-sm" v-model="form.storage_id">
                <option :value="null">{{ $t('storages.no_storage') }}</option>
                <option :value="storage.id" v-for="(storage, key) in storages" v-html="storage.indentedName"></option>
            </select>
            <div class="invalid-feedback" v-text="'unit_price_formatted' in errors ? errors.unit_price_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="input-group">
                <input class="form-control form-control-sm text-right" :class="'unit_price_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.unit_price_formatted" @keydown.enter="update(false)">
                <div class="input-group-append" v-if="item.rule_id">
                    <span class="input-group-text text-left pointer" :title="'Regel ' + item.rule.name" @click="form.unit_price_formatted = item.price_rule_formatted">
                        {{ item.price_rule_formatted }}€
                    </span>
                </div>
            </div>
            <div class="invalid-feedback" v-text="'unit_price_formatted' in errors ? errors.unit_price_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control form-control-sm text-right" :class="'unit_cost_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.unit_cost_formatted" @keydown.enter="update(false)">
            <div class="invalid-feedback" v-text="'unit_cost_formatted' in errors ? errors.unit_cost_formatted[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right">{{ Number(item.provision).format(2, ',', '.') }} €</td>
        <td class="align-middle text-right d-none d-xl-table-cell pointer">{{ Number(item.unit_price - item.unit_cost - item.provision).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save')" @click="update(false)"><i class="fas fa-fw fa-save"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save_upload')" @click="update(true)"><i class="fas fa-fw fa-cloud-upload-alt"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.delete')" @click="destroy"><i class="fas fa-fw fa-trash"></i></button>
            </div>
        </td>
    </tr>
    <tr v-else>
        <td class="align-middle d-none d-lg-table-cell text-center">
            <i class="fas fa-fw fa-euro-sign text-success" title="Verkauft" v-if="item.orders.length"></i>
            <i class="fas fa-fw" :class="item.sync_icon" :title="item.sync_error || 'Karte synchronisiert'" v-else></i>
        </td>
        <td class="align-middle d-none d-xl-table-cell pointer"><i class="fas fa-image" @mouseover="show($event)" @mouseout="$emit('hide')"></i></td>
        <td class="align-middle">
            <span class="fi" :class="'fi-' + item.language.code" :title="item.language.name"></span> {{ item.localName }} ({{ item.card.number }})
            <div class="text-muted" v-if="item.language_id != 1">{{ item.card.name }}</div></td>
        <td class="align-middle text-center"><expansion-icon :expansion="item.card.expansion" :show-name="false" v-if="item.card.expansion"></expansion-icon></td>
        <td class="align-middle d-none d-xl-table-cell text-center"><rarity :value="item.card.rarity"></rarity></td>
        <td class="align-middle d-none d-lg-table-cell text-center"><condition :value="item.condition"></condition></td>
        <td class="align-middle d-none d-xl-table-cell text-center">
            <i class="fas fa-star text-warning" v-if="item.is_foil"></i>
            <span v-if="item.is_signed">S</span>
            <span v-if="item.is_playset">P</span>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right">{{ Number(item.unit_price).format(2, ',', '.') }} €</td>
        <td class="align-middle d-none d-xl-table-cell">
            <select class="form-control form-control-sm" v-model="form.storage_id">
                <option :value="null">{{ $t('storages.no_storage') }}</option>
                <option :value="storage.id" v-for="(storage, key) in storages" v-html="storage.indentedName"></option>
            </select>
            <div class="invalid-feedback" v-text="'storage_id' in errors ? errors.storage_id[0] : ''"></div>
        </td>
        <td class="align-middle d-none d-xl-table-cell text-right">
            <input class="form-control form-control-sm text-right" :class="'number' in errors ? 'is-invalid' : ''" type="text" v-model="form.number" @keydown.enter="update(false)">
            <div class="invalid-feedback" v-text="'number' in errors ? errors.number[0] : ''"></div>
            <button class="btn btn-sm btn-secondary" @click="getNextNumber">Nächste</button>
        </td>
        <td class="align-middle d-none d-sm-table-cell text-right">
            <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-secondary" :href="item.orders[0].path" :title="'Bestellung ' + item.orders[0].cardmarket_order_id" v-if="item.orders.length"><i class="fas fa-box"></i></a>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.save')" @click="update(false)"><i class="fas fa-fw fa-save"></i></button>
                <button type="button" class="btn btn-secondary" :title="$t('app.actions.delete')" @click="destroy"><i class="fas fa-fw fa-trash"></i></button>
            </div>
        </td>
    </tr>
</template>

<script>
    import condition from '../partials/emoji/condition.vue';
    import rarity from '../partials/emoji/rarity.vue';
    import expansionIcon from '../expansion/icon.vue';

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
                isEditing: false,
                isAdvancedEditing: false,
                form: {
                    cardmarket_comments: this.item.cardmarket_comments,
                    condition: this.item.condition,
                    is_foil: this.item.is_foil,
                    is_playset: this.item.is_playset,
                    is_signed: this.item.is_signed,
                    language_id: this.item.language_id,
                    number: this.item.number,
                    provision_formatted: this.item.provision_formatted,
                    slot: this.item.slot,
                    storage_id: this.item.storage_id,
                    sync: false,
                    unit_cost_formatted: this.item.unit_cost_formatted,
                    unit_price_formatted: this.item.unit_price_formatted,
                },
                errors: {},
            };
        },

        mounted() {
            this.$watch('item', function (newValue, oldValue) {
                this.form = {
                    cardmarket_comments: newValue.cardmarket_comments,
                    condition: newValue.condition,
                    is_foil: newValue.is_foil,
                    is_playset: newValue.is_playset,
                    is_signed: newValue.is_signed,
                    language_id: newValue.language_id,
                    number: newValue.number,
                    provision_formatted: newValue.provision_formatted,
                    slot: newValue.slot,
                    storage_id: newValue.storage_id,
                    sync: false,
                    unit_cost_formatted: newValue.unit_cost_formatted,
                    unit_price_formatted: newValue.unit_price_formatted,
                };
            }, {
                deep: true
            });
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
                axios.put(component.item.path, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('updated', response.data);
                        Vue.success((sync ? component.$t('app.successes.created_uploaded') : component.$t('app.successes.updated')));
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.updated'));
                });
            },
            getNextNumber() {
                var component = this;
                axios.get('/article/number')
                    .then( function (response) {
                        component.form.number = response.data.number;
                    })
                    .catch(function (error) {
                        Vue.error('Nummer konnte nicht ermittelt werden.');
                    });
            },
        },
    };
</script>

<style>

    .price_rule_wrapper {
        white-space: nowrap;
        max-width: 100%;
    }

    .price_rule {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>