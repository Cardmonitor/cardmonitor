<template>
    <div>
        <div class="form-group">
            <select class="form-control" v-model="filter.language_id" @change="fetch()">
                <option :value="language.id" v-for="(language) in languages">{{ language.name }}</option>
            </select>
        </div>
        <filter-expansion :initial-value="filter.expansion_id" :options="expansions" :show-label="false" :game-id="filter.game_id" v-model="filter.expansion_id" @input="fetch()"></filter-expansion>
        <div class="form-group" style="margin-bottom: 0;">
            <filter-search :should-focus="filter.shouldFocus" v-model="filter.searchtext" @input="fetch()" @focused="filter.shouldFocus = false"></filter-search>
        </div>
        <div class="col mt-3 p-0" style="height: 450px; overflow: auto;">
            <div v-if="is_loading" class="mt-3 p-5">
                <center>
                    <span style="font-size: 48px;">
                        <i class="fas fa-spinner fa-spin"></i><br />
                    </span>
                    {{ $t('app.loading') }}
                </center>
            </div>
            <div class="alert alert-dark mt-3" v-else-if="showSearchAlert"><center>{{ $t('article.create.alert_no_filter') }}</center></div>
            <table class="table table-hover table-striped" v-else-if="cards.length">
                <tbody>
                    <tr v-for="(card, index) in cards" @click="form.card_id = card.id" :class="card.id == form.card_id ? 'table-primary' : ''">
                        <td class="align-middle d-none d-lg-table-cell text-center pointer w-icon"><i class="fas fa-image" @mouseover="showImgbox({src: card.imagePath})" @mouseout="hideImgbox"></i></td>
                        <td class="align-middle pointer w-icon"><expansion-icon :expansion="card.expansion" :show-name="false"></expansion-icon></td>
                        <td class="align-middle text-center w-icon"><rarity :value="card.rarity"></rarity></td>
                        <td class="align-middle pointer">
                            <div>{{ card.local_name }}</div>
                            <div class="text-muted" v-if="filter.language_id != 1">{{ card.name }}</div>
                        </td>
                        <td class="align-middle text-right pointer">#{{ card.number }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="alert alert-dark mt-3" v-else><center>Keine Karten vorhanden.</center></div>
        </div>
        <button class="btn btn-block btn-sm btn-primary mt-3" @click="update();">Speichern</button>
        <div id="imgbox">
            <img :src="imgbox.src" v-show="imgbox.show">
        </div>
    </div>
</template>
<script>
import filterExpansion from '../../../filter/expansion.vue';
import filterSearch from '../../../filter/search.vue';
import rarity from '../../../partials/emoji/rarity.vue';

export default {

    components: {
        filterExpansion,
        filterSearch,
        rarity,
    },

    props: {
        initalItem: {
            type: Object,
            required: true,
        },
        initialStateComments: {
            type: String,
            required: true,
        },
        expansions: {
            type: Array,
            required: true,
        },
        languages: {
            type: Array,
            required: true,
        },
    },

    mounted() {
        this.fetch();
    },

    computed: {
        shouldFetch() {
                return (this.filter.searchtext.length >= 3 || this.filter.expansion_id != 0);
            },
            showSearchAlert() {
                return (this.shouldFetch == false && this.items.length == 0);
            },
            sortedExpansions: function() {
                function compare(a, b) {
                    if (a.name < b.name) {
                        return -1;
                    }

                    if (a.name > b.name) {
                        return 1;
                    }

                    return 0;
                }

                return this.expansions.sort(compare);
            },
    },

    data() {
        return {
            uri: '/card',
            cards: [],
            imgbox: {
                src: null,
                show: true,
            },
            items: [],
            filter: {
                game_id: this.initalItem.card.game_id,
                searchtext: this.initalItem.localName,
                expansion_id: null,
                language_id: this.initalItem.language_id,
                shouldFocus: true,
            },
            form: {
                card_id: this.initalItem.card_id,
                state_comments: this.initialStateComments,
            },
            is_loading: false,
            state_comments_max_length: 255,
        };
    },

    watch: {
        isVisible: function() {
            if (this.isVisible) {
                this.fetch();
            }
        },
    },

    methods: {
        fetch() {
            var component = this;
            if (component.filter.searchtext.length < 3 && component.filter.expansion_id == 0) {
                component.cards = {};
                return;
            }
            component.is_loading = true;
            axios.get(component.uri, {
                params: component.filter
            })
                .then(function (response) {
                    component.cards = response.data;
                    component.is_loading = false;
                    if (component.cards.length == 1) {
                        component.form.card_id = component.cards[0].id;
                    }
                    else {
                        component.item = null;
                    }
                })
                .catch(function (error) {
                    Vue.error(component.$t('app.errors.loading'));
                    console.log(error);
                });
        },
        update() {
            var component = this;

            component.setStateComments('Die Karte ' + component.initalItem.card.name + ' (' + component.initalItem.card.expansion.abbreviation + ')' + ' wurde ausgetauscht.');

            axios.put(component.initalItem.path, component.form)
                .then(function (response) {
                    Vue.success(component.$t('app.successes.updated'));
                    component.$emit('updated', response.data);
                })
                .catch(function (error) {
                    Vue.error(component.$t('app.errors.updated'));
                    console.log(error);
                });
        },
        showImgbox({src}) {
            this.imgbox.src = src;
            this.imgbox.show = true;
        },
        hideImgbox() {
            this.imgbox.show = false;
        },
        setStateComments(comment) {
            if (this.form.state_comments.length + comment.length > this.state_comments_max_length) {
                Vue.error('Der Kommentar darf maximal ' + this.state_comments_max_length + ' Zeichen lang sein.');
                return;
            }

            if (this.form.state_comments) {
                this.form.state_comments += "\n";
            }
            this.form.state_comments += comment;
            this.form.state_comments = this.form.state_comments.trim();
        },
    }

}
</script>
