<template>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-5">
                <div class="card-header">Erweiterungen von Cardmarket exportieren</div>
                <div class="card-body">

                    <filter-expansion :initial-value="form.expansion_id" :options="expansions" :show-label="true" :game-id="form.game_id" v-model="form.expansion_id"></filter-expansion>

                    <filter-skryfall-expansion :initial-value="form.skryfall_expansion_code" :options="skryfallExpansions" :show-label="true" v-model="form.skryfall_expansion_code"></filter-skryfall-expansion>

                    <div class="form-group">
                        <label for="language">Sprache</label>
                        <select class="form-control form-control-sm" v-model="form.language_id">
                            <option :value="id" v-for="(name, id) in languages">{{ name }}</option>
                        </select>
                        <div class="invalid-feedback" v-text="'language_id' in errors ? errors.language_id[0] : ''"></div>
                    </div>

                    <div v-show="isLoading" class="mt-3 p-3">
                        <center>
                            <span style="font-size: 48px;">
                                <i class="fas fa-spinner fa-spin"></i><br />
                            </span>
                            Lade Daten..
                        </center>
                    </div>
                    <div class="row mt-3 p-3" v-show="files.length > 0">
                        <a :href="file.url" class="col text-center text-body" v-for="(file, index) in files" v-if="file != null">
                            <i class="fas fa-file-csv fa-4x"></i>
                            <div>{{ file.basename }}</div>
                        </a>
                    </div>

                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-primary" @click="store" :disabled="isLoading">Exportieren</button>
                </div>
            </div>
        </div>
        </div>
</template>

<script>
    import filterExpansion from "../../filter/expansion.vue";
    import filterSkryfallExpansion from "../../filter/skryfall-expansion.vue";

    export default {

        components: {
            filterExpansion,
            filterSkryfallExpansion,
        },

        props: {
            expansions: {
                type: Array,
                required: true,
            },
            skryfallExpansions: {
                type: Array,
                required: true,
            },
            languages: {
                type: Object,
                required: true,
            },
        },

        data() {
            return {
                errors: {},
                files: [],
                form: {
                    language_id: 3,
                    expansion_id: 0,
                    skryfall_expansion_code: 0,
                    game_id: 1,
                },
                isLoading: false,
            };
        },

        mounted() {

        },

        methods: {
            store() {
                var component = this;

                if (component.form.expansion_id == 0) {
                    Vue.error('Erweiterung auswählen');
                    return;
                }

                component.isLoading = true;
                component.files = [];
                axios.post('/card/export', component.form)
                    .then(function (response) {
                        if (response.data.files.length > 0) {
                            Vue.success('Datei heruntergeladen');
                            // location.href = response.data.path;
                            component.files = response.data.files;
                        }
                        else {
                            Vue.error('Dateien konnten nicht erstellt werden');
                        }
                    })
                    .catch(function (error) {
                        Vue.error(component.$t('app.errors.loading'));
                        console.log(error);
                    })
                    .finally( function () {
                        component.isLoading = false;
                    });
            },
        },
    };
</script>