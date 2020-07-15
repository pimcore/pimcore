/**
 * The Router is an ordered set of {@link Ext.route.Route} definitions that decode a
 * url into a controller function to execute. Each `route` defines a type of url to match,
 * along with the controller function to call if it is matched. The Router uses the
 * {@link Ext.util.History} singleton to find out when the browser's url has changed.
 *
 * Routes are almost always defined inside a {@link Ext.Controller Controller}, as
 * opposed to on the Router itself. End-developers should not usually need to interact
 * directly with the Router as the Controllers manage everything automatically. See the
 * {@link Ext.Controller Controller documentation} for more information on specifying
 * routes.
 */
Ext.define('Ext.route.Router', {
    singleton: true,

    requires: [
        'Ext.route.Action',
        'Ext.route.Route',
        'Ext.util.History'
    ],

    /**
     * @event beforeroutes
     * @member Ext.GlobalEvents
     *
     * Fires when the hash has changed and before any routes are executed. This allows
     * pre-processing to add additional {@link Ext.route.Action#before before} or
     * {@link Ext.route.Action#action action} handlers when the
     * {@link Ext.route.Action Action} is run.
     *
     * Route execution can be prevented by returning `false` in the listener
     * or executing the {@link Ext.route.Action#stop stop} method on the action.
     *
     * @param {Ext.route.Action} action An action that will be executed
     * prior to any route execution.
     * @param {String[]} tokens An array of individual tokens in the hash.
     */

    /**
     * @event routereject
     * @member Ext.GlobalEvents
     *
     * Fires when a route was rejected from either a before action,
     * {@link Ext.GlobalEvents#beforeroutes} event or {@link Ext.GlobalEvents#beforeroute} event.
     *
     * @param {Ext.route.Route} route The route which had it's execution rejected.
     */

    config: {
        /**
         * @cfg {Boolean} hashBang Sets {@link Ext.util.History#hashbang} to enable/disable
         * hashbang support.
         */
        hashbang: null,

        /**
         * @cfg {String} [multipleToken=|] The token to split the routes to support multiple routes.
         */
        multipleToken: '|',

        /**
         * @cfg {Boolean} [queueRoutes=true] `true` to queue routes to be executed one after the
         * other, false to execute routes immediately.
         */
        queueRoutes: true
    },

    /**
     * @property {Object} routes The connected {@link Ext.route.Route}
     * instances.
     */

    /**
     * @property {Boolean} isSuspended `true` if the router is currently suspended.
     */

    constructor: function() {
        var History = Ext.util.History;

        if (!History.ready) {
            History.init();
        }

        History.on('change', this.onStateChange, this);

        this.initConfig();

        this.clear();
    },

    updateHashbang: function(hashbang) {
        Ext.util.History.hashbang = hashbang;
    },

    /**
     * React to a token
     *
     * @private
     * @param {String} token The token to react to.
     */
    onStateChange: function(token) {
        var me = this,
            tokens = token.split(me.getMultipleToken()),
            queue, i, length;

        if (me.isSuspended) {
            queue = me.suspendedQueue;
            i = 0;
            length = tokens.length;

            if (queue) {
                for (; i < length; i++) {
                    token = tokens[i];

                    // shouldn't keep track of duplicates
                    if (!Ext.Array.contains(queue, token)) {
                        queue.push(token);
                    }
                }
            }
        }
        else {
            me.handleBefore(tokens);
        }
    },

    /**
     * Fires the {@link Ext.GlobalEvents#beforeroutes} event and if
     * `false` is returned can prevent any routes from executing.
     *
     * @private
     * @param {String[]} tokens The individual tokens that were split from the hash
     * using {@link #multipleToken}.
     */
    handleBefore: function(tokens) {
        var me = this,
            action = new Ext.route.Action();

        if (Ext.fireEvent('beforeroutes', action, tokens) === false) {
            action.destroy();
        }
        else {
            action
                .run()
                .then(me.handleBeforeRoute.bind(me, tokens), Ext.emptyFn);
        }
    },

    /**
     * If a wildcard route was connected, that route needs to execute prior
     * to any other route.
     *
     * @private
     * @param {String[]} tokens The individual tokens that were split from the hash
     * using {@link #multipleToken}.
     */
    handleBeforeRoute: function(tokens) {
        var me = this,
            beforeRoute = me.getByName('*');

        if (beforeRoute) {
            beforeRoute
                .execute()
                .then(me.doRun.bind(me, tokens), Ext.emptyFn);
        }
        else {
            // no befores, go ahead with route determination
            me.doRun(tokens);
        }
    },

    /**
     * Find routes that recognize one of the tokens in the document fragment
     * and then exeucte the routes.
     *
     * @private
     * @param {String[]} tokens The individual tokens that were split from the hash
     * using {@link #multipleToken}.
     */
    doRun: function(tokens) {
        var me = this,
            app = me.application,
            routes = me.routes,
            i = 0,
            length = tokens.length,
            matched = {},
            unmatched = [],
            token, found,
            name, route, recognize;

        for (; i < length; i++) {
            token = tokens[i];
            found = false;

            for (name in routes) {
                route = routes[name];
                recognize = route.recognize(token);

                if (recognize) {
                    found = true;

                    if (recognize !== true) {
                        // The document fragment may have changed but the token
                        // part that the route recognized did not change. Therefore
                        // is was matched but we should not execute the route again.
                        route
                            .execute(token, recognize)
                            .then(null, Ext.bind(me.onRouteRejection, me, [route], 0));
                    }

                    Ext.Array.remove(unmatched, route);

                    if (!matched[name]) {
                        matched[name] = 1;
                    }
                }
                else if (!matched[name]) {
                    unmatched.push(route);
                }
            }

            if (!found) {
                if (app) {
                    // backwards compat
                    app.fireEvent('unmatchedroute', token);
                }

                Ext.fireEvent('unmatchedroute', token);
            }
        }

        i = 0;
        length = unmatched.length;

        for (; i < length; i++) {
            unmatched[i].onExit();
        }
    },

    /**
     * @private
     * Called when a route was rejected.
     */
    onRouteRejection: function(route, error) {
        Ext.fireEvent('routereject', route, error);

        if (error) {
            Ext.raise(error);
        }
    },

    /**
     * Create the {@link Ext.route.Route} instance and connect to the
     * {@link Ext.route.Router} singleton.
     *
     * @param {String} url The url to recognize.
     * @param {String} config The config on the controller to execute when the url is
     * matched.
     * @param {Ext.Base} instance The class instance associated with the
     * {@link Ext.route.Route}
     * @return {Ext.route.Handler} The handler that was added.
     */
    connect: function(url, config, instance) {
        var routes = this.routes,
            delimiter = this.getMultipleToken(),
            name = config.name || url,
            handler, route;

        if (url[0] === '!') {
            //<debug>
            if (!Ext.util.History.hashbang) {
                Ext.log({
                    level: 'error',
                    msg: 'Route found with "!" ("' + url +
                         '"). Should use new hashbang functionality instead. ' +
                        'Please see the router guide for more: https://docs.sencha.com/extjs/' +
                        Ext.getVersion().version + '/guides/application_architecture/router.html'
                });
            }
            //</debug>

            url = url.substr(1);
            this.setHashbang(true);
        }

        if (Ext.isString(config)) {
            config = {
                action: config
            };
        }

        handler = Ext.route.Handler.fromRouteConfig(config, instance);
        route = routes[name];

        if (!route) {
            config.name = name;
            config.url = url;

            route = routes[name] = new Ext.route.Route(config);
        }

        route.addHandler(handler);

        if (handler.lazy) {
            // eslint-disable-next-line vars-on-top
            var currentHash = Ext.util.History.getToken(),
                tokens = currentHash.split(delimiter),
                length = tokens.length,
                matched = [],
                i, token;

            for (i = 0; i < length; i++) {
                token = tokens[i];

                if (Ext.Array.indexOf(matched, token) === -1 && route.recognize(token)) {
                    matched.push(token);
                }
            }

            this.onStateChange(matched.join(delimiter));
        }

        return handler;
    },

    /**
     * Disconnects all route handlers for a class instance.
     *
     * @param {Ext.Base} instance The class instance to disconnect route handlers from.
     * @param {Object/Ext.route.Handler} [config]
     * An optional config object to match a handler for. This will check all route
     * handlers connected to the instance for match based on the action and before
     * configurations. This can also be the actual {@link Ext.route.Handler handler}
     * instance.
     */
    disconnect: function(instance, config) {
        var routes = this.routes,
            route, name;

        if (config) {
            route = config.route || this.getByName(config.name || config.url);

            if (route) {
                route.removeHandler(instance, config);
            }
        }
        else {
            for (name in routes) {
                route = routes[name];

                route.removeHandler(instance);
            }
        }
    },

    /**
     * Recognizes a url string connected to the Router, return the controller/action pair
     * plus any additional config associated with it.
     *
     * @param {String} url The url to recognize.
     * @return {Object/Boolean} If the url was recognized, the controller and action to
     * call, else `false`.
     */
    recognize: function(url) {
        var routes = this.routes,
            matches = [],
            name, arr, i, length, route, urlParams;

        for (name in routes) {
            arr = routes[name];
            length = arr && arr.length;

            if (length) {
                i = 0;

                for (; i < length; i++) {
                    route = arr[i];
                    urlParams = route.recognize(url);

                    if (urlParams) {
                        matches.push({
                            route: route,
                            urlParams: urlParams
                        });
                    }
                }
            }
        }

        return matches.length ? matches : false;
    },

    /**
     * Convenience method which just calls the supplied function with the
     * {@link Ext.route.Router} singleton. Example usage:
     *
     *     Ext.route.Router.draw(function(map) {
     *         map.connect('activate/:token', {controller: 'users', action: 'activate'});
     *         map.connect('home',            {controller: 'index', action: 'home'});
     *     });
     *
     * @param {Function} fn The function to call
     */
    draw: function(fn) {
        fn.call(this, this);
    },

    /**
     * Clear all the recognized routes.
     */
    clear: function() {
        this.routes = {};
    },

    /**
     * Resets the connected routes' last token they were executed on.
     * @param {String} [token] If passed, only clear matching routes.
     * @private
     */
    clearLastTokens: function(token) {
        var routes = this.routes,
            name, route;

        for (name in routes) {
            route = routes[name];

            if (!token || route.recognize(token)) {
                route.clearLastTokens();
            }
        }
    },

    /**
     * Gets all routes by {@link Ext.route.Route#name}.
     *
     * @return {Ext.route.Route[]} If no routes found, `undefined` will be returned otherwise
     * the array of {@link Ext.route.Route Routes} will be returned.
     */
    getByName: function(name) {
        var routes = this.routes;

        if (routes) {
            return routes[name];
        }
    },

    /**
     * Suspends the handling of tokens (see {@link #resume}).
     *
     * @param {Boolean} [trackTokens] `false` to prevent any tokens to be
     * queued while being suspended.
     */
    suspend: function(trackTokens) {
        this.isSuspended = true;

        if (!this.suspendedQueue && trackTokens !== false) {
            this.suspendedQueue = [];
        }
    },

    /**
     * Resumes the execution of routes (see {@link #suspend}).
     *
     * @param {Boolean} [discardQueue] `true` to prevent any previously queued
     * tokens from being enacted on.
     */
    resume: function(discardQueue) {
        var me = this,
            queue = me.suspendedQueue,
            token;

        if (me.isSuspended) {
            me.isSuspended = false;
            me.suspendedQueue = null;

            if (!discardQueue && queue) {
                token = queue.join(me.getMultipleToken());

                me.onStateChange(token);
            }
        }
    }
});
