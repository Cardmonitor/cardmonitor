<template>
    <div class="container" v-if="item != null">
        <div class="alert alert-dark" role="alert" v-show="counts.open == 0">
            {{ $t('order.article.show.alerts.no_open_cards') }} <span v-show="counts.problem > 0">({{ counts.problem }} {{ counts.problem == 1 ? $t('order.article.show.problems.singular') : $t('order.article.show.problems.plural') }})</span>
        </div>
        <div class="row mb-3">
            <div class="col-12 col-sm text-center p-3">
                <img class="img-fluid" :src="item.card.imagePath">
            </div>
            <div class="col d-flex flex-column">
                <div class="mb-3">
                    <div><b>{{ (index + 1) }}: {{ item.localName }} (#{{ item.card.number }}) <span class="fi" :class="'fi-' + language.code" :title="language.name"></span> </b></div>
                    <div><expansion-icon :expansion="item.card.expansion"></expansion-icon></div>
                    <div><rarity :value="item.card.rarity"></rarity> ({{ item.card.rarity }})</div>
                    <div><condition :value="form.condition"></condition> ({{ form.condition }})</div>
                    <div class="d-flex justify-content-between my-3">
                        <button class="btn" :class="getConditionAktiveClass(condition_key)" :key="condition" v-for="(condition, condition_key) in conditions" @click="setCondition(condition_key)">{{ condition_key }}</button>
                    </div>
                    <div class="d-flex mb-3">
                        <button class="btn mr-3" :class="getLanguageAktiveClass(language.id)" :key="language.id" v-for="(language) in languages" @click="setLanguageId(language)">{{ language.name }}</button>
                    </div>
                    <div class="mb-3">
                        <button class="btn mr-3" :class="getIsFoilAktiveClass()" @click="setIsFoil()">Foil</button>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-secondary" @click="changePrice(-10)">-10%</button>
                            <button class="btn btn-secondary" @click="changePrice(-15)">-15%</button>
                            <button class="btn btn-secondary" @click="changePrice(-20)">-20%</button>
                            <button class="btn btn-secondary" @click="changePrice(-25)">-25%</button>
                        </div>
                        <div class="form-group my-3">
                            <input class="form-control" :class="'unit_cost_formatted' in errors ? 'is-invalid' : ''" type="text" v-model="form.unit_cost_formatted">
                            <div class="invalid-feedback" v-text="'unit_cost_formatted' in errors ? errors.unit_cost_formatted[0] : ''"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-secondary" @click="changePrice(10)">10%</button>
                            <button class="btn btn-secondary" @click="changePrice(15)">15%</button>
                            <button class="btn btn-secondary" @click="changePrice(20)">20%</button>
                            <button class="btn btn-secondary" @click="changePrice(25)">25%</button>
                        </div>
                    </div>
                    <div class="mt-2" v-if="item.storage_id" title="Lagerplatz"><i class="fas fa-boxes"></i> {{ item.storage.full_name }}</div>
                    <div class="mt-2" v-if="item.number" title="Lagernummer"><i class="fas fa-boxes"></i> {{ item.number }}</div>
                </div>
                <div class="col-12 col-sm px-0 mb-3">
                    <div class="form-group">
                        <label for="state_comment_boilerplate">{{ $t('order.article.show.problems.label') }}</label>
                        <select class="form-control form-control-sm" id="state_comment_boilerplate" :placeholder="$t('order.article.show.problems.placeholder')" @change="form.state_comments += $event.target.value">
                            <option>{{ $t('order.article.show.problems.label') }}</option>
                            <option>{{ $t('order.article.show.problems.not_available') }}</option>
                            <option>{{ $t('order.article.show.problems.wrong_condition') }}</option>
                            <option>{{ $t('order.article.show.problems.wrong_language') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="state_comments">{{ $t('order.article.show.state_comments.label') }}</label>
                        <input type="text" class="form-control form-control-sm" id="state_comments" v-model="form.state_comments" :placeholder="$t('order.article.show.state_comments.placeholder')">
                    </div>
                </div>
                <div>
                    <i class="fas fa-fw mb-3" :class="item.state_icon" :title="item.state_comments"></i> {{ item.state_comments }}
                </div>
                <div class="d-flex justify-content-between">
                    <button class="btn btn-sm btn-danger text-overflow-ellipsis" title="Nächste Karte (Status Problem)" @click="next(true, 1)">{{ $t('order.article.show.actions.next_problem') }}</button>
                    <button class="btn btn-sm btn-light" @click="next(false)">{{ $t('order.article.show.actions.next') }}</button>
                    <button class="btn btn-sm btn-primary text-overflow-ellipsis" title="Nächste Karte (Status OK)" @click="next(true, 0)">{{ $t('order.article.show.actions.next_ok') }}</button>
                </div>
                <div class="d-flex justify-content-around mt-3">
                    <button class="btn btn-sm btn-light" @click="next(true, 2)" v-if="item.state != 2">Für Pickliste zurückstellen</button>
                    <button class="btn btn-sm btn-light" @click="next(true, null)" v-if="item.state !== null">Status zurücksetzen</button>
                </div>
            </div>
        </div>
    </div>
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

        props: {
            item: {
                required: false,
                type: Object,
                default: null,
            },
            index: {
                required: false,
                type: Number,
                default: 0,
            },
            counts: {
                required: true,
                type: Object,
            },
            conditions: {
                required: true,
                type: Object,
            },
            languages: {
                required: true,
                type: Array,
            },
        },

        watch: {
            item(newValue) {
                this.form.state = newValue ? newValue.state : null;
                this.form.state_comments = newValue ? newValue.state_comments || '' : '';
                this.form.condition = newValue ? newValue.condition : null;
                this.form.language_id = newValue ? newValue.language_id : null;
                this.form.is_foil = newValue ? newValue.is_foil : null;
                this.form.unit_cost_formatted = newValue ? newValue.unit_cost_formatted : null;
                this.language = newValue ? newValue.language : null;
            },
        },

        computed: {
            //
        },

        data() {
            return {
                language: this.item ? this.item.language : null,
                errors: [],
                form: {
                    state: this.item ? this.item.state : null,
                    state_comments: this.item ? this.item.state_comments || '' : '',
                    condition: this.item ? this.item.condition : null,
                    language_id: this.item ? this.item.language_id : null,
                    is_foil: this.item ? this.item.is_foil : null,
                    unit_cost_formatted: this.item ? this.item.unit_cost_formatted : null,
                },
            };
        },

        methods: {
            next(shouldUpdate, state) {
                var component = this;
                if (shouldUpdate == false) {
                    component.$emit('next', component.item);
                    return;
                }
                component.form.state = state;
                axios.put('/article/' + component.item.id, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('next', response.data);
                        Vue.success('Artikel gespeichert.');
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error('Artikel konnte nicht gespeichert werden.');
                });
            },
            getConditionAktiveClass(condition) {
                return this.form.condition === condition ? 'btn-primary' : 'btn-secondary';
            },
            setCondition(condition) {
                this.form.condition = condition;
            },
            getLanguageAktiveClass(language_id) {
                return this.form.language_id === language_id ? 'btn-primary' : 'btn-secondary';
            },
            setLanguageId(language) {
                this.language = language;
                this.form.language_id = language.id;
            },
            getIsFoilAktiveClass() {
                return this.form.is_foil ? 'btn-primary' : 'btn-secondary';
            },
            setIsFoil() {
                this.form.is_foil = !this.form.is_foil;
            },
            changePrice(percentage) {
                let unit_cost = Number(this.form.unit_cost_formatted.replace(',', '.'));
                unit_cost = (unit_cost * (1 + percentage / 100)).toFixed(2);

                this.form.unit_cost_formatted = unit_cost.toString().replace('.', ',');
            },
        },
    };
</script>