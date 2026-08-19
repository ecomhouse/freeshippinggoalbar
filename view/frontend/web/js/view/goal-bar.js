define([
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (Component, customerData) {
    'use strict';

    var SECTION = 'ecomhouse-freeshipping-goal-bar';

    return Component.extend({
        defaults: {
            template: 'EcomHouse_FreeShippingGoalBar/minicart/goal-bar'
        },

        /**
         * @override
         * @returns {Object}
         */
        initialize: function () {
            this._super();
            this.state = customerData.get(SECTION);
            this.followCartUpdates();

            return this;
        },

        /**
         * Keep this section in step with the cart section.
         *
         * Extensions that update the cart over AJAX sometimes refresh a hardcoded list of
         * sections - `customerData.reload(['cart'], true)` is a common one - which marks their
         * own sections fresh and leaves every other section stale in localStorage. The bar would
         * then show the previous cart's numbers until an unrelated action refreshed it. Whenever
         * the cart section is newer than ours, pull ours too.
         *
         * @returns {void}
         */
        followCartUpdates: function () {
            var cart = customerData.get('cart'),
                self = this;

            this.pullIfStale(cart());
            cart.subscribe(function (cartData) {
                self.pullIfStale(cartData);
            });
        },

        /**
         * @param {Object} cartData
         * @returns {void}
         */
        pullIfStale: function (cartData) {
            if (Number((cartData || {}).data_id || 0) > Number(this.state().data_id || 0)) {
                customerData.reload([SECTION], false);
            }
        },

        /**
         * Whether the server says there is a goal to show. Everything the bar renders - wording,
         * percentage, formatted amounts - is computed server-side; this component only prints it.
         *
         * @returns {Boolean}
         */
        isVisible: function () {
            return !!this.state().available;
        },

        /**
         * @returns {Boolean}
         */
        isReached: function () {
            return !!this.state().eligible;
        },

        /**
         * @returns {String}
         */
        getFillWidth: function () {
            return (Number(this.state().percent) || 0) + '%';
        }
    });
});
