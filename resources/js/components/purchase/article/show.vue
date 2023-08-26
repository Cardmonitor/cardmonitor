<template>
    <div class="container" v-if="item != null">
        <div class="alert alert-dark" role="alert" v-show="counts.open == 0">
            {{ $t('order.article.show.alerts.no_open_cards') }} <span v-show="counts.problem > 0">({{ counts.problem }} {{ counts.problem == 1 ? $t('order.article.show.problems.singular') : $t('order.article.show.problems.plural') }})</span>
        </div>
        <div class="row mb-3">
            <div class="col-12 col-sm text-center d-flex flex-column justify-content-between" v-if="is_changing_card">
                <card-edit :inital-item="item" :expansions="expansions" :languages="languages" @updated="updated($event)"></card-edit>
                <button class="btn btn-sm btn-secondary mt-1" @click="is_changing_card = false;">Abbrechen</button>
            </div>
            <div class="col-12 col-sm text-center" v-else>
                <img class="img-fluid p-3" :src="item.card.imagePath">
                <button class="btn btn-block btn-sm btn-secondary" @click="is_changing_card = true;" v-if="item.is_sellable === 0">Karte ändern</button>
                <button class="btn btn-block btn-sm btn-danger text-overflow-ellipsis" title="Nächste Karte (Status Nicht vorhanden)" @click="next(true, 3)" v-if="item.is_sellable === 0">Nächste Karte (Karte nicht vorhanden)</button>
            </div>
            <div class="col d-flex flex-column">
                <div class="mb-3">
                    <div><b>{{ (index + 1) }}: {{ item.localName }} (#{{ item.card.number }}) <span class="fi" :class="'fi-' + language.code" :title="language.name"></span></b></div>
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
                            <option>falsche Karte</option>
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
                    <button class="btn btn-sm btn-warning text-overflow-ellipsis" title="Nächste Karte (Status Problem)" :disabled="item.is_sellable === 1" @click="next(true, 1)">{{ $t('order.article.show.actions.next_problem') }}</button>
                    <button class="btn btn-sm btn-primary text-overflow-ellipsis" title="Nächste Karte (Status OK)" :disabled="item.is_sellable === 1" @click="next(true, 0)">{{ $t('order.article.show.actions.next_ok') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import condition from '../../partials/emoji/condition.vue';
    import rarity from '../../partials/emoji/rarity.vue';
    import expansionIcon from '../../expansion/icon.vue';
    import cardEdit from './card/edit.vue';

    export default {

        components: {
            cardEdit,
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
            expansions: {
                type: Array,
                required: true,
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
                price_changes: {
                    language: false,
                },
                is_changing_card: false,
            };
        },

        mounted() {
            const component = this;
            window.addEventListener('keydown', function(event) {

                // Space: next card
                if (event.keyCode === 32) {
                    event.preventDefault();
                    component.next(true, 0);
                }
            });
        },

        methods: {
            next(shouldUpdate, state) {
                var component = this;
                if (shouldUpdate == false) {
                    component.$emit('next', component.item);
                    return;
                }
                component.form.state = state;
                if (state === 3) {
                    component.form.state_comments = 'Karte nicht vorhanden';
                    component.form.unit_cost_formatted = '0,00';
                }
                axios.put('/article/' + component.item.id, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('next', response.data);
                        Vue.success('Der Artikel <b>' + response.data.localName + (response.data.index > 1 ? ' (' + response.data.index + ')' : '') + '</b> wurde gespeichert.');
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error('Der Artikel <b>' + component.item.localName + (component.item.index > 1 ? ' (' + component.item.index + ')' : '') + '</b> konnte nicht gespeichert werden.');
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
                if (this.price_changes.language === false && this.item.language_id === 1 && language.id !== 1) {
                    this.changePrice(-10);
                    this.price_changes.language = true;
                }
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
            updated(item) {
                this.$emit('updated', item);
                this.is_changing_card = false;
            },
        },
    };
</script>