const Flash = {
    install: function (Vue, options) {
        Vue.flash = function (text, type) {
            type = typeof type === 'undefined' ? 'success' : type;

            Bus.$emit('flash-message', {
                text: text,
                type: type
            });
        };
        Vue.success = function (text) {
            Vue.flash(text, 'success');
        };
        Vue.warning = function (text) {
            Vue.flash(text, 'warning');
        };
        Vue.error = function (text) {
            Vue.flash(text, 'danger');
        };
    }
}

export default Flash;