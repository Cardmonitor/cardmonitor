<template>
    <div>

        <div class="d-flex mb-1">
            <h2 class="col mb-0 pl-0"><a class="text-body" href="/article">{{ $t('app.nav.article') }}</a><span class="d-none d-md-inline"> > {{ model.localName }}</span></h2>
            <div class="d-flex align-items-center">
                <a :href="model.path" class="btn btn-sm btn-secondary ml-1">{{ $t('app.overview') }}</a>
            </div>
        </div>

        <div class="row align-items-stretch">

            <div class="col-md-6 mb-3">

                <div class="card">
                    <div class="card-header">Cardmonitor</div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="number">Nummer</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control form-control-sm" id="number" v-model="form.number">
                                <div class="invalid-feedback" v-text="'number' in errors ? errors.number[0] : ''"></div>
                            </div>
                            <div class="col-sm-2">
                                <button class="btn btn-sm btn-link" @click="getNextNumber" v-show="!form.number">Nächste</button>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="storage_id">Lagerplatz</label>
                            <div class="col-sm-8">
                                <select id="storage_id" class="form-control form-control-sm" v-model="form.storage_id">
                                    <option :value="null">{{ $t('storages.no_storage') }}</option>
                                    <option :value="storage.id" v-for="(storage, key) in storages" v-html="storage.indentedName"></option>
                                </select>
                                <div class="invalid-feedback" v-text="'storage_id' in errors ? errors.storage_id[0] : ''"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="unit_cost_formatted">Einkaufspreis</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="unit_cost_formatted" v-model="form.unit_cost_formatted">
                                <div class="invalid-feedback" v-text="'unit_cost_formatted' in errors ? errors.unit_cost_formatted[0] : ''"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">Cardmarket</div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="cardmarket_article_id">ID</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="cardmarket_article_id" v-model="form.cardmarket_article_id">
                                <div class="invalid-feedback" v-text="'cardmarket_article_id' in errors ? errors.cardmarket_article_id[0] : ''"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="language_id">Sprache</label>
                            <div class="col-sm-8">
                                <select id="language_id" class="form-control form-control-sm" v-model="form.language_id">
                                    <option :value="language_id" v-for="(name, language_id) in languages">{{ name }}</option>
                                </select>
                                <div class="invalid-feedback" v-text="'language_id' in errors ? errors.language_id[0] : ''"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="language_id">Zustand</label>
                            <div class="col-sm-8">
                                <select class="form-control form-control-sm" v-model="form.condition">
                                    <option :value="id" v-for="(name, id) in conditions">{{ name }}</option>
                                </select>
                                <div class="invalid-feedback" v-text="'condition' in errors ? errors.condition[0] : ''"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="is_foil">Foil</label>
                            <div class="col-sm-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_foil" v-model="form.is_foil">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="is_signed">Signed</label>
                            <div class="col-sm-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_signed" v-model="form.is_signed">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="is_playset">Playset</label>
                            <div class="col-sm-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_playset" v-model="form.is_playset">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="unit_price_formatted">Verkaufspreis</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="unit_price_formatted" v-model="form.unit_price_formatted">
                                <div class="invalid-feedback" v-text="'unit_price_formatted' in errors ? errors.unit_price_formatted[0] : ''"></div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label col-form-label-sm" for="cardmarket_comments">Kommentar</label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="cardmarket_comments" v-model="form.cardmarket_comments">
                                <div class="invalid-feedback" v-text="'cardmarket_comments' in errors ? errors.cardmarket_comments[0] : ''"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row my-5"><div class="col"></div></div>

        <div class="fixed-bottom bg-white p-3 text-right">
            <button class="btn btn-primary btn-sm" @click="update(false)">Speichern</button>
            <button class="btn btn-secondary btn-sm" @click="update(true)" v-show="form.number">Speichern & Hochladen</button>
        </div>
    </div>
</template>

<script>

    export default {

        components: {

        },

        props: {
            conditions: {
                type: Object,
                required: true,
            },
            model: {
                type: Object,
                required: true,
            },
            languages: {
                type: Object,
                required: true,
            },
            storages: {
                type: Array,
                required: true,
            },
        },

        computed: {

        },

        data() {
            return {
                form: {
                    cardmarket_article_id: this.model.cardmarket_article_id,
                    cardmarket_comments: this.model.cardmarket_comments,
                    condition: this.model.condition,
                    is_foil: this.model.is_foil,
                    is_playset: this.model.is_playset,
                    is_signed: this.model.is_signed,
                    language_id: this.model.language_id,
                    number: this.model.number,
                    provision_formatted: this.model.provision_formatted,
                    slot: this.model.slot,
                    storage_id: this.model.storage_id,
                    sync: false,
                    unit_cost_formatted: this.model.unit_cost_formatted,
                    unit_price_formatted: this.model.unit_price_formatted,
                },
                errors: {},
            };
        },

        methods: {
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
            update(sync) {
                var component = this;
                component.form.sync = sync;
                axios.put(component.model.path, component.form)
                    .then( function (response) {
                        component.errors = {};

                        component.form.cardmarket_article_id = response.data.cardmarket_article_id,
                        component.form.cardmarket_comments = response.data.cardmarket_comments,
                        component.form.condition = response.data.condition,
                        component.form.is_foil = response.data.is_foil,
                        component.form.is_playset = response.data.is_playset,
                        component.form.is_signed = response.data.is_signed,
                        component.form.language_id = response.data.language_id,
                        component.form.number = response.data.number,
                        component.form.provision_formatted = response.data.provision_formatted,
                        component.form.slot = response.data.slot,
                        component.form.storage_id = response.data.storage_id,
                        component.form.unit_cost_formatted = response.data.unit_cost_formatted,
                        component.form.unit_price_formatted = response.data.unit_price_formatted,

                        Vue.success((sync ? component.$t('app.successes.created_uploaded') : component.$t('app.successes.updated')));
                    })
                    .catch(function (error) {
                        if (error.response.status === 422) {
                            component.errors = {};
                            component.$emit('updated', error.response.data);
                            Vue.error(error.response.data.sync_error);
                            return;
                        }

                        component.errors = error.response.data.errors;
                        Vue.error(component.$t('app.errors.updated'));
                });
            },
        },
    };
</script>