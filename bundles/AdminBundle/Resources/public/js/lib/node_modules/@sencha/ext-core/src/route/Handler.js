/**
 * This class is used to hold the handler functions for when a route is executed. It also
 * keeps track of the {@link #lastToken last token} this handler was last executed on.
 *
 * @since 6.6.0
 */
Ext.define('Ext.route.Handler', {
    /**
     * @cfg {Function/String} action
     * The handler to execute when the route recognizes a token in the hash.
     *
     * This can be prevented from executing by the {@link #before} handler.
     *
     * If defined as a String, the Function will be resolved from the {@link #scope}.
     */

    /**
     * @cfg {Function/String} before
     * The handler to execute before the {@link #action} handler. The `before` handler
     * can prevent the {@link #action} handler by executing the {@link Ext.route.Action#stop stop}
     * method on the {@link Ext.route.Action action} argument or by returning a
     * {@link Ext.Promise promise} and rejecting it:
     *
     *     routes: {
     *         'user/:id': {
     *             before: 'onBefore
     *         }
     *     },
     *
     *     onBefore: function (id, action) {
     *         action.stop();
     *     }
     *
     *     // or
     *
     *     onBefore: function (id) {
     *         return new Ext.Promise(function (resolve, reject) {
     *             reject();
     *         });
     *     }
     *
     * If using the `action` argument, the `action` argument will always be the last argument passed
     * after any configured url parameters.
     *
     * If defined as a String, the Function will be resolved from the {@link #scope}.
     */

    /**
     * @cfg {Boolean} lazy
     * If `true`, the defined routes will get executed when created.
     */
    lazy: false,

    /**
     * @cfg {Function/String} exit
     * The handler to execute when the route no longer recognizes a token in the current hash.
     *
     * If defined as a String, the Function will be resolved from the {@link #scope}.
     */

    /**
     * @property {Ext.route.Route} route
     * The route this handler is connected to.
     */

    /**
     * @cfg {Ext.Base} scope
     * The scope to call the handlers with. If the handlers are defined with a String,
     * the handlers will be resolved from this scope.
     */

    /**
      * @cfg {Boolean} single
      * Controls if this handler should be removed after first execution. There are
      * a veriety of values that control when in the execution this should be removed:
      *
      * - **true** Remove this handler after a successful and full execution. The handler
      *  will be removed after the handler's {@link #cfg!action} has been executed. If a
      *  {@link #cfg!before} has been rejected, the {@link #cfg!action} will not be
      *  executed meaning this handler will **not** be removed.
      * - **after** Remove this handler after the {@cfg!link #before} has been resolved.
      *  If {@cfg!link #before} has been rejected, this handler will **not** be removed.
      *  If no {@cfg!link #before} exists, this handler will be removed prior to the
      *  {@link #cfg!action} being executed.
      * - **before** Remove this handler before the {@link #cfg!before} has been
      *  executed. If no {@link #cfg!before} exists, this handler will be removed prior
      *  to the {@link #cfg!action} being executed.
      */

    /**
     * @private
     * @property {String} lastToken
     * The last token this handler is connected to in the current hash.
     */

    statics: {
        /**
         * @private
         *
         * Creates a {@link Ext.route.Handler Handler} instance from the config
         * defined in the {@link Ext.route.Mixin#routes} config.
         *
         * @param {Object} config The config from the routes config.
         * @param {Ext.Base} scope The scope the handlers will be called/resolved with.
         * @return {Ext.route.Handler}
         */
        fromRouteConfig: function(config, scope) {
            var handler = {
                action: config.action,
                before: config.before,
                lazy: config.lazy,
                exit: config.exit,
                scope: scope,
                single: config.single
            };

            return new this(handler);
        }
    },

    constructor: function(config) {
        Ext.apply(this, config);
    }
});
