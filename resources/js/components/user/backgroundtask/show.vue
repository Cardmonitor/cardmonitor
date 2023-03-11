<template>
    <div class="card">
        <div class="card-body">
            <div v-if="content == ''" class="mt-3 p-5">
                <center>
                    <span style="font-size: 48px;">
                        <i class="fas fa-spinner fa-spin"></i><br />
                    </span>
                    {{ $t('app.loading') }}
                </center>
            </div>
            <pre v-else>{{ content }}</pre>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            task: {
                type: String,
                required: true,
            },
        },

        data() {
            return {
                content: '',
                errors: {},
                interval: null,
                path: '',
            };
        },

        mounted() {
            this.fetch();
            if (this.task) {
                this.checkContent();
            }
        },

        watch: {
            task() {
                this.content = '';
                this.path = '';
                clearInterval(this.interval);
                if (this.task) {
                    this.fetch();
                    this.checkContent();
                }
            }
        },

        methods: {
            fetch() {
                const component = this;
                axios.get('/user/backgroundtasks/' + component.task)
                .then(function (response) {
                    component.content = response.data.content;
                    component.path = response.data.path;
                })
                .catch( function (error) {
                    component.errors = error.response.data.errors;
                    Vue.error(component.$t('app.errors.created'));
                });
            },
            checkContent() {
                const component = this;
                component.interval = setInterval( function () {
                    component.fetch()
                }, 2000);
            }
        },
    }
</script>