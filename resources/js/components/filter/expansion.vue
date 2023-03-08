<template>
    <div class="form-group" style="min-width: 200px;">
        <label for="filter-expansion" v-if="showLabel">{{ $t('app.expansion') }}</label>
        <v-select class="d-flex align-items-center" name="expansion_id" :clearable="false" :options="sortedOptions" label="name" :reduce="option => option.id" placeholder="Alle" :value="value" @input="input($event)">
            <template v-slot:option="option">
                <expansion-icon :expansion="option" :name-ellipsis="true" v-if="option.id > 0"></expansion-icon>
                <span v-else>{{ option.name }}</span>
            </template>
        </v-select>
    </div>

</template>

<script>
    import expansionIcon from '../expansion/icon.vue';
    import vSelect from 'vue-select';

    export default {

        components: {
            expansionIcon,
            vSelect,
        },

        props: {
            initialValue: {
                required: false,
            },
            options: {
                required: true,
            },
            showLabel: {
                required: false,
                default: true,
            },
            gameId: {
                required: false,
                default: 1
            },
        },

        computed: {
            sortedOptions: function() {
                function compare(a, b) {
                    if (a.name < b.name) {
                        return -1;
                    }

                    if (a.name > b.name) {
                        return 1;
                    }

                    return 0;
                }

                var component = this;

                var sorted = this.options.filter(function (option) {
                    return (option.game_id == component.gameId);
                }).sort(compare);

                sorted.unshift({
                    id: 0,
                    name: 'Alle',
                });

                return sorted;
            },
        },

        data () {
            return {
                value: this.initialValue || 0,
            };
        },

        methods: {
            input: function(id) {
                this.value = id;
                this.$emit('input', id);
            },
        },
    };
</script>