<template>
    <div class="form-group" style="min-width: 300px;">
        <label for="filter-storage">{{ $t('storages.storage') }}</label>
        <v-select class="d-flex align-items-center" name="filter-storage" :clearable="false" :options="sortedOptions" label="indentedName" :reduce="option => option.id" :value="value" @input="input($event)">
            <template v-slot:option="option">
                <span v-html="option.indentedName"></span>
            </template>
            <template v-slot:selected-option="option">
                <span v-html="option.full_name"></span>
            </template>
        </v-select>
    </div>
</template>

<script>
    import vSelect from 'vue-select';

    export default {

        components: {
            vSelect,
        },

        props: [
            'initialValue',
            'options',
        ],

        computed: {
            sortedOptions: function() {
                function compare(a, b) {
                    if (a.sort < b.sort) {
                        return -1;
                    }

                    if (a.sort > b.sort) {
                        return 1;
                    }

                    return 0;
                }

                let sortedOptions = this.options.sort(compare);

                sortedOptions.unshift({
                    id: 0,
                    full_name: this.$t('filter.all'),
                    indentedName: this.$t('filter.all'),
                    sort: 0,
                });

                return this.options.sort(compare);
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