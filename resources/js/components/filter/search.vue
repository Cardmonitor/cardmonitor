<template>
    <div>
        <input :value="value" class="form-control form-control-sm" ref="search" :class="error ? 'is-invalid' : ''" type="search" :placeholder="$t('app.search')" @keyup="delay">
        <div class="invalid-feedback" v-text="error ? error : ''"></div>
    </div>
</template>

<script>
    export default {

        props: [
            'error',
            'value',
            'shouldFocus',
        ],

        data() {
            return {
                timeout: null,
            };
        },

        watch: {
            shouldFocus(newValue) {
                if (newValue) {
                    this.$refs['search'].focus();
                    this.$emit('focused');
                }
            },
        },

        methods: {
            delay () {
                var component = this;
                if (component.timeout)
                {
                    clearTimeout(component.timeout);
                    component.timeout = null;
                }
                component.timeout = setTimeout(function () {
                    component.$emit('input', component.$refs.search.value);
                }, 750);
            },
        },

    };
</script>