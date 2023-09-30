<template>
    <div class="container" v-if="item != null">
        <div class="alert alert-dark" role="alert" v-show="counts.open == 0">
            {{ $t('order.article.show.alerts.no_open_cards') }} <span v-show="counts.problem > 0">({{ counts.problem }} {{ counts.problem == 1 ? $t('order.article.show.problems.singular') : $t('order.article.show.problems.plural') }})</span>
        </div>
        <div class="row mb-3">
            <div class="col-12 col-sm text-center d-flex flex-column justify-content-between" v-if="is_changing_card">
                <card-edit :inital-item="item" :initial-state-comments="form.state_comments" :expansions="expansions" :languages="languages" @updated="updated($event)"></card-edit>
                <button class="btn btn-sm btn-secondary mt-1" @click="is_changing_card = false;">Abbrechen</button>
            </div>
            <div class="col-12 col-sm text-center" v-else>
                <template v-if="item.card">
                    <img class="img-fluid p-3" :src="item.card.imagePath">
                    <button class="btn btn-block btn-sm btn-secondary" @click="is_changing_card = true;" v-if="item.is_sellable === 0">Karte ändern</button>
                </template>
                <button class="btn btn-block btn-sm btn-danger text-overflow-ellipsis" title="Nächste Karte (Status Nicht vorhanden)" @click="next(true, 3)" v-if="item.is_sellable === 0">Nächste Karte (Karte nicht vorhanden)</button>
            </div>
            <div class="col d-flex flex-column">
                <div class="mb-3">
                    <div><b>{{ (index + 1) }}: {{ item.local_name }} <span v-if="item.card && item.card.number">(#{{ item.card.number }})</span> <span class="fi" :class="'fi-' + language.code" :title="language.name" v-if="item.card"></span></b></div>
                    <div><expansion-icon :expansion="item.card.expansion" v-if="item.card && item.card.expansion"></expansion-icon></div>
                    <template v-if="item.card">
                        <div><rarity :value="item.card.rarity"></rarity> ({{ item.card.rarity }})</div>
                        <div><condition :value="form.condition"></condition> ({{ form.condition }})</div>
                        <div class="d-flex mb-3">
                            <button class="btn mr-3" :class="getLanguageAktiveClass(language.id)" :key="language.id" v-for="(language) in languages" @click="setLanguageId(language)">{{ language.name }}</button>
                        </div>
                        <div class="d-flex justify-content-between my-3">
                            <button class="btn" :class="getConditionAktiveClass(condition_key)" :key="condition" v-for="(condition, condition_key) in conditions" @click="setCondition(condition_key)">{{ condition_key }}</button>
                        </div>
                        <div class="mb-3">
                            <button class="btn mr-3" :class="getIsFoilAktiveClass()" @click="setIsFoil()">Foil</button>
                        </div>
                    </template>
                    <div class="form-group" v-if="item.card == null">
                        <input class="form-control" :class="'local_name' in errors ? 'is-invalid' : ''" type="text" v-model="form.local_name" placeholder="Bezeichnung">
                        <div class="invalid-feedback" v-text="'local_name' in errors ? errors.local_name[0] : ''"></div>
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
                        <textarea class="form-control form-control-sm" id="state_comments" v-model="form.state_comments" rows="5" :maxlength="state_comments.max_length" :placeholder="$t('order.article.show.state_comments.placeholder')"></textarea>
                    </div>
                </div>
                <div>
                    <i class="fas fa-fw mb-3" :class="item.state_icon" :title="item.state_comments"></i> {{ item.state_comments }}
                </div>
                <div class="d-flex justify-content-between">
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
                this.form.local_name = newValue ? newValue.card_id == null ? newValue.local_name : null : null;
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
                    local_name: this.item ? this.item.card_id == null ? this.item.local_name : null : null,
                },
                price_changes: {
                    language: false,
                    conditions: {
                        'MT': -20,
                        'NM': -20,
                        'EX': -20,
                        'GD': -20,
                        'LP': -10,
                        'PL': -10,
                        'PO': -10,
                    }
                },
                is_changing_card: false,
                state_comments: {
                    max_length: 255,
                },
            };
        },

        mounted() {
            const component = this;
            window.addEventListener('keydown', function(event) {

                // Not in input or textarea
                const target_tag_name = event.target.tagName.toLowerCase();
                if (target_tag_name === 'input' || target_tag_name === 'textarea') {
                    return;
                }

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
                    component.setStateComments('Karte nicht vorhanden');
                    component.form.unit_cost_formatted = '0,00';
                }
                axios.put('/article/' + component.item.id, component.form)
                    .then( function (response) {
                        component.errors = {};
                        component.$emit('next', response.data);
                        Vue.success('Der Artikel <b>' + response.data.local_name + (response.data.index > 1 ? ' (' + response.data.index + ')' : '') + '</b> wurde gespeichert.');
                    })
                    .catch(function (error) {
                        component.errors = error.response.data.errors;
                        Vue.error('Der Artikel <b>' + component.item.local_name + (component.item.index > 1 ? ' (' + component.item.index + ')' : '') + '</b> konnte nicht gespeichert werden.');
                });
            },
            getConditionAktiveClass(condition) {
                return this.form.condition === condition ? 'btn-primary' : 'btn-secondary';
            },
            setCondition(condition) {
                const percentage = this.getPercentageForConditionDowngrade(this.form.condition, condition);

                this.form.condition = condition;

                if (percentage !== 0) {
                    this.changePrice(percentage);
                    Vue.success('Der Zustand wurde auf <b>' + condition + '</b> geändert.' + (percentage !== 0 ? ' Der Preis wurde um <b>' + percentage + '%</b> reduziert.' : ''));
                }

                this.setStateComments('Zustand geändert: ' + condition);
            },
            getPercentageForConditionDowngrade(current_condition, condition) {
                let percentage = 0;
                let start_adding = false;
                for (const [conditions_key, price_change_percentage] of Object.entries(this.price_changes.conditions)) {
                    // add the percentage if the condition is worse
                    if (start_adding) {
                        percentage += price_change_percentage;
                    }

                    // start adding after the condition is the current condition
                    if (conditions_key === current_condition) {
                        start_adding = true;
                    }

                    // stop adding if the condition is the new condition
                    if (conditions_key === condition) {
                        break;
                    }
                }

                return percentage;
            },
            getLanguageAktiveClass(language_id) {
                return this.form.language_id === language_id ? 'btn-primary' : 'btn-secondary';
            },
            setLanguageId(language) {
                this.language = language;
                this.form.language_id = language.id;
                if (this.price_changes.language === false && this.item.language_id === 1 && language.id !== 1) {
                    const percentage = -10;
                    this.changePrice(percentage);
                    this.price_changes.language = true;
                    Vue.success('Die Sprache wurde auf <b>' + language.name + '</b> geändert. Der Preis wurde um <b>' + percentage + '%</b> reduziert.');
                }
                this.setStateComments('Sprache geändert: ' + language.code);
            },
            getIsFoilAktiveClass() {
                return this.form.is_foil ? 'btn-primary' : 'btn-secondary';
            },
            setIsFoil() {
                this.form.is_foil = !this.form.is_foil;
                this.setStateComments('Foil geändert: ' + (this.form.is_foil ? 'Ja' : 'Nein'));
            },
            changePrice(percentage) {
                let unit_cost = Number(this.form.unit_cost_formatted.replace(',', '.'));
                unit_cost = (unit_cost * (1 + percentage / 100)).toFixed(2);

                this.form.unit_cost_formatted = unit_cost.toString().replace('.', ',');
            },
            setStateComments(comment) {
                if (this.form.state_comments.length + comment.length > this.state_comments.max_length) {
                    Vue.error('Der Kommentar darf maximal ' + this.state_comments.max_length + ' Zeichen lang sein.');
                    return;
                }

                if (this.form.state_comments) {
                    this.form.state_comments += "\n";
                }

                this.form.state_comments += comment;
                this.form.state_comments = this.form.state_comments.trim();
            },
            updated(item) {
                this.$emit('updated', item);
                this.is_changing_card = false;
            },
        },
    };
</script>