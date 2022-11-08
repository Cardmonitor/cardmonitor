<template>
    <div class="form-group">
        <label for="filter-rule">{{ $t('storages.storage') }}</label>
        <select class="form-control form-control-sm" id="filter-rule" v-model="value" @change="$emit('input', value)">
            <option :value="0">{{ $t('filter.all') }}</option>
            <option :value="-1">{{ $t('storages.no_storage') }}</option>
            <option v-for="(option, key) in sortedOptions" :value="option.id">{{ option.name }}</option>
        </select>
    </div>
</template>

<script>
    export default {
        props: [
            'initialValue',
            'options',
        ],

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

                return this.options.sort(compare);
            },
        },

        data () {
            return {
                value: this.initialValue || 0,
            };
        },
    };
</script>