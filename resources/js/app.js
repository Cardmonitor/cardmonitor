/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue').default;
window.Bus = new Vue();
window.Highcharts = require('highcharts');

/**
 * Number.prototype.format(n, x, s, c)
 *
 * @param integer n: length of decimal
 * @param integer x: length of whole part
 * @param mixed   s: sections delimiter
 * @param mixed   c: decimal delimiter
 */
Number.prototype.format = function(decimals, dec_point, thousands_sep) {
    var number = (this + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function (n, prec) {
          var k = Math.pow(10, prec);
          return '' + (Math.round(n * k) / k).toFixed(prec);
    };
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
};

Highcharts.setOptions({
    lang: {
        decimalPoint: ',',
        thousandsSep: '.'
    }
});

import Flash from './plugins/flash.js';
Vue.use(Flash);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

Vue.component('flash-message', require('./components/partials/flashmessage.vue').default);

Vue.component('article-create', require('./components/article/create.vue').default);
Vue.component('article-edit', require('./components/article/edit.vue').default);
Vue.component('article-table', require('./components/article/table.vue').default);
Vue.component('article-storing-history-table', require('./components/article/storing_history/table.vue').default);
Vue.component('article-storing-history-show-table', require('./components/article/storing_history/show/table.vue').default);
Vue.component('evaluation-icon', require('./components/partials/emoji/evaluation.vue').default);
Vue.component('expansion-table', require('./components/expansion/table.vue').default);
Vue.component('expansion-icon', require('./components/expansion/icon.vue').default);
Vue.component('expansion-cards-index', require('./components/expansion/cards/index.vue').default);
Vue.component('home-order-index', require('./components/home/order/index.vue').default);
Vue.component('home-order-paid', require('./components/home/order/paid.vue').default);
Vue.component('imageable-gallery', require('./components/image/imageable/gallery.vue').default);
Vue.component('imageable-table', require('./components/image/imageable/table.vue').default);
Vue.component('item-quantity-table', require('./components/item/quantity/table.vue').default);
Vue.component('item-table', require('./components/item/table.vue').default);
Vue.component('order-article-index', require('./components/order/article/index.vue').default);
Vue.component('order-item-table', require('./components/order/item/table.vue').default);
Vue.component('order-table', require('./components/order/table.vue').default);
Vue.component('purchase-article-index', require('./components/purchase/article/index.vue').default);
Vue.component('purchase-table', require('./components/purchase/table.vue').default);
Vue.component('rule-edit', require('./components/rule/edit.vue').default);
Vue.component('rule-table', require('./components/rule/table.vue').default);
Vue.component('storage-content-table', require('./components/storage/content/table.vue').default);
Vue.component('storage-table', require('./components/storage/table.vue').default);
Vue.component('user-balance-table', require('./components/user/balance/table.vue').default);
Vue.component('user-backgroundtask-show', require('./components/user/backgroundtask/show.vue').default);
Vue.component('card-export-index', require('./components/card/export/index.vue').default);
Vue.component('woocommerce-order-table', require('./components/woocommerce/order/table.vue').default);
Vue.component('woocommerce-order-show', require('./components/woocommerce/order/show.vue').default);

var common = require('./common').default;

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

common.loadLanguage(window.Laravel.locale, true).then((i18n) => {
    const app = new Vue({
        i18n,
        el: '#app',
    });

    (function(document,navigator,standalone) {
        // prevents links from apps from oppening in mobile safari
        // this javascript must be the first script in your <head>
        if ((standalone in navigator) && navigator[standalone]) {
            $('#history-back').removeClass('hidden');
            var curnode, location=document.location, stop=/^(a|html)$/i;
            document.addEventListener('click', function(e) {
                curnode=e.target;
                while (!(stop).test(curnode.nodeName)) {
                    curnode=curnode.parentNode;
                }
                // Condidions to do this only on links to your own app
                // if you want all links, use if('href' in curnode) instead.
                if(
                    'href' in curnode && // is a link
                    (chref=curnode.href).replace(location.href,'').indexOf('#') && // is not an anchor
                    (   !(/^[a-z\+\.\-]+:/i).test(chref) ||                       // either does not have a proper scheme (relative links)
                        chref.indexOf(location.protocol+'//'+location.host)===0 ) // or is in the same protocol and domain
                ) {
                    e.preventDefault();
                    //$('#loading-wrapper').fadeIn(400);
                    location.href = curnode.href;
                }
            }, false);
        }
    })(document,window.navigator,'standalone');

    $('#menu-toggle').click(function() {
        $('#nav, #content-container').toggleClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });

    $('.collapse', 'nav#nav').on('show.bs.collapse', function(){
        $('a[data-target="#' + $(this).attr('id') +'"] i.fas', 'nav#nav').toggleClass("fa-caret-right fa-caret-down");
    }).on('hide.bs.collapse', function(){
        $('a[data-target="#' + $(this).attr('id') +'"] i.fas', 'nav#nav').toggleClass("fa-caret-down fa-caret-right");
    });

    $('#message-create').on('show.bs.modal', function (e) {
        axios.get('/order/' + $(e.relatedTarget).attr('data-model-id') + '/message/create')
            .then(function (response) {
                $('#message-text', '#message-create').val(response.data.body);
        });
    });

    return app;
});

