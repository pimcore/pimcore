/**
 * Class that can manage the execution of route handlers. All {@link #befores} handlers
 * will be executed prior to the {@link #actions} handlers. If at any point this `Action`
 * class is stopped, no other handler (before or action) will be executed.
 */
Ext.define('Ext.route.Action', {
    config: {
        /**
         * @cfg {Function[]} actions
         * The action handlers to execute in response to the route executing.
         * The individual functions will be executed with the scope of the class
         * that connected the route and the arguments will be the configured URL
         * parameters in order they appear in the token.
         *
         * See {@link #befores} also.
         */
        actions: null,

        /**
         * @cfg {Function[]} befores
         * The before handlers to execute prior to the {@link #actions} handlers.
         * The individual functions will be executed with the scope of the class
         * that connected the route and the arguments will be the configured URL
         * parameters in the order they appear in the token plus this `Action` instance
         * as the last argument.
         *
         * **IMPORTANT** A before function must have a resolution. You can do this
         * by executing the {@link #resume} or {@link #stop} function or you can
         * return a promise and resolve/reject it.
         *
         *     var action = new Ext.route.Action({
         *         before: {
         *             fn: function (action) {
         *                 action.resume(); //or action.stop();
         *             }
         *         }
         *     });
         *     action.run();
         *
         *     var action = new Ext.route.Action({
         *         before: {
         *             fn: function () {
         *                 return new Ext.Promise(function (resolve, reject) {
         *                     resolve(); //or reject();
         *                 });
         *             }
         *         }
         *     });
         *     action.run();
         *
         * See {@link #actions} also.
         */
        befores: null,

        /**
         * @cfg {Array} urlParams
         * The URL parameters that were matched by the {@link Ext.route.Route}.
         */
        urlParams: []
    },

    /**
     * @property {Ext.Deferred} deferred
     * The deferral object that will resolve after all functions have executed
     * ({@link #befores} and {@link #actions}) or reject if any {@link #befores}
     * function stops this action.
     * @private
     */

    /**
     * @property {Boolean} [started=false]
     * Whether or not this class has started executing any {@link #befores} or {@link #actions}.
     * @readonly
     * @protected
     */
    started: false,

    /**
     * @property {Boolean} [stopped=false]
     * Whether or not this class was stopped by a {@link #befores} function.
     * @readonly
     * @protected
     */
    stopped: false,

    constructor: function(config) {
        var me = this;

        me.deferred = new Ext.Deferred();

        me.resume = me.resume.bind(me);
        me.stop = me.stop.bind(me);

        me.initConfig(config);
        me.callParent([config]);
    },

    applyActions: function(actions) {
        if (actions) {
            actions = Ext.Array.from(actions);
        }

        return actions;
    },

    applyBefores: function(befores) {
        if (befores) {
            befores = Ext.Array.from(befores);
        }

        return befores;
    },

    destroy: function() {
        this.deferred = null;

        this
            .setBefores(null)
            .setActions(null)
            .setUrlParams(null);

        this.callParent();
    },

    /**
     * Allow further function execution of other functions if any.
     *
     * @return {Ext.route.Action} this
     */
    resume: function() {
        return this.next();
    },

    /**
     * Prevent other functions from executing and resolve the {@link #deferred}.
     *
     * @return {Ext.route.Action} this
     */
    stop: function() {
        this.stopped = true;

        return this.done();
    },

    /**
     * Executes the next {@link #befores} or {@link #actions} function. If {@link #stopped}
     * is `true` or no functions are left to execute, the {@link #done} function will be called.
     *
     * @private
     * @return {Ext.route.Action} this
     */
    next: function() {
        var me = this,
            actions = me.getActions(),
            befores = me.getBefores(),
            urlParams = me.getUrlParams(),
            config, ret, args;

        if (Ext.isArray(urlParams)) {
            args = urlParams.slice();
        }
        else {
            args = [urlParams];
        }

        if (
            me.stopped ||
            (befores ? !befores.length : true) &&
            (actions ? !actions.length : true)
        ) {
            me.done();
        }
        else {
            if (befores && befores.length) {
                config = befores.shift();

                args.push(me);

                ret = Ext.callback(config.fn, config.scope, args);

                if (ret && ret.then) {
                    ret.then(function(arg) {
                        me.resume(arg);
                    }, function(arg) {
                        me.stop(arg);
                    });
                }
            }
            else if (actions && actions.length) {
                config = actions.shift();

                Ext.callback(config.fn, config.scope, args);

                me.next();
            }
            else {
                // needed?
                me.next();
            }
        }

        return me;
    },

    /**
     * Starts the execution of {@link #befores} and/or {@link #actions} functions.
     *
     * @return {Ext.promise.Promise}
     */
    run: function() {
        var deferred = this.deferred;

        if (!this.started) {
            this.next();

            this.started = true;
        }

        return deferred.promise;
    },

    /**
     * When no {@link #befores} or {@link #actions} functions are left to execute
     * or {@link #stopped} is `true`, this function will be executed to resolve
     * or reject the {@link #deferred} object.
     *
     * @private
     * @return {Ext.route.Action} this
     */
    done: function() {
        var deferred = this.deferred;

        if (this.stopped) {
            deferred.reject();
        }
        else {
            deferred.resolve();
        }

        this.destroy();

        return this;
    },

    /**
     * Add a function to the {@link #befores} stack.
     *
     *     action.before(function() {}, this);
     *
     * By default, the function will be added to the end of the {@link #befores} stack. If
     * instead the function should be placed at the beginning of the stack, you can pass
     * `true` as the first argument:
     *
     *     action.before(true, function() {}, this);
     *
     * @param {Boolean} [first=false] Pass `true` to add the function to the beginning of the
     * {@link #befores} stack instead of the end.
     * @param {Function/String} fn The function to add to the {@link #befores}.
     * @param {Object} [scope] The scope of the function to execute with. This is normally
     * the class that is adding the function to the before stack.
     * @return {Ext.route.Action} this
     */
    before: function(first, fn, scope) {
        if (!Ext.isBoolean(first)) {
            scope = fn;
            fn = first;
            first = false;
        }

        // eslint-disable-next-line vars-on-top
        var befores = this.getBefores(),
            config = {
                fn: fn,
                scope: scope
            };

        //<debug>
        if (this.destroyed) {
            Ext.raise('This action has has already resolved and therefore will never ' +
                      'execute this function.');

            return;
        }
        //</debug>

        if (befores) {
            if (first) {
                befores.unshift(config);
            }
            else {
                befores.push(config);
            }
        }
        else {
            this.setBefores(config);
        }

        return this;
    },

    /**
     * Add a function to the {@link #actions} stack.
     *
     *     action.action(function() {}, this);
     *
     * By default, the function will be added to the end of the {@link #actions} stack. If
     * instead the function should be placed at the beginning of the stack, you can pass
     * `true` as the first argument:
     *
     *     action.action(true, function() {}, this);
     *
     * @param {Boolean} [first=false] Pass `true` to add the function to the beginning of the
     * {@link #befores} stack.
     * @param {Function/String} fn The function to add to the {@link #actions}.
     * @param {Object} [scope] The scope of the function to execute with. This is normally
     * the class that is adding the function to the action stack.
     * @return {Ext.route.Action} this
     */
    action: function(first, fn, scope) {
        if (!Ext.isBoolean(first)) {
            scope = fn;
            fn = first;
            first = false;
        }

        // eslint-disable-next-line vars-on-top
        var actions = this.getActions(),
            config = {
                fn: fn,
                scope: scope
            };

        //<debug>
        if (this.destroyed) {
            Ext.raise('This action has has already resolved and therefore will never ' +
                      'execute this function.');

            return;
        }
        //</debug>

        if (actions) {
            if (first) {
                actions.unshift(config);
            }
            else {
                actions.push(config);
            }
        }
        else {
            this.setActions(config);
        }

        return this;
    },

    /**
     * Execute functions when this action has been resolved or rejected.
     *
     * @param {Function} resolve The function to execute when this action has been resolved.
     * @param {Function} reject The function to execute when a before function stopped this action.
     * @return {Ext.Promise}
     */
    then: function(resolve, reject) {
        //<debug>
        if (this.destroyed) {
            Ext.raise('This action has has already resolved and therefore will never ' +
                      'execute either function.');

            return;
        }
        //</debug>

        return this.deferred.then(resolve, reject);
    }
});
