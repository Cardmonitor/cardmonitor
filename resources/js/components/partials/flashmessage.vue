<template>
    <div id="status">
        <Transition name="slide-fade">
            <div class="alert d-flex align-items-center" :class="'alert-' + message.type" v-if="message">
                <div>
                    <i class="fas fa-fw fa-3x" :class="icons[message.type]"></i>
                </div>
                <div class="col text-center" v-html="message.text"></div>
            </div>
        </Transition>
    </div>
</template>

<script>
    export default {

        props: {
            initialMessage: {
                required: false,
                type: Object,
                default: null,
            },
        },

        data() {
            return {
                message: null,
                icons: {
                    'success': 'fa-check-circle',
                    'danger': 'fa-exclamation-circle',
                },
                timeoutId: null,
            };
        },

        mounted() {
            var component = this;
            if (component.initialMessage) {
                component.flash(component.initialMessage);
            }
            Bus.$on('flash-message', function (message) {
                component.flash(message);
            });

            if (window.user.id) {
                Echo.private('App.User.' + window.user.id)
                    .notification((notification) => {
                        component.flash(notification.message);
                        if ('background_tasks' in notification.data) {
                            Bus.$emit('update-background-tasks', notification.data.background_tasks);
                        }
                    });
            }

        },

        methods: {
            flash(message) {
                var component = this;
                clearTimeout(component.timeoutId);
                message.type = typeof message.type === 'undefined' ? 'success' : message.type;
                component.message = message;

                component.timeoutId = setTimeout( function () {
                    component.message = null;
                }, 5000);
            }
        },

    };
</script>

<style scoped>
    #status {
        position: fixed;
        bottom: 40px;
        right: 20px
    }

    .slide-fade-enter-active,
    .slide-fade-leave-active {
      transition: all 0.4s;
    }
    .slide-fade-enter,
    .slide-fade-leave-to {
      transform: translateX(400px);
      opacity: 0;
    }
</style>