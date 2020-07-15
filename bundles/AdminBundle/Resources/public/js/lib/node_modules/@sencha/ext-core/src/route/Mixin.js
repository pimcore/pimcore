/**
 * A mixin to allow any class to configure and listen to routes and also change the hash.
 */
Ext.define('Ext.route.Mixin', {
    extend: 'Ext.Mixin',

    requires: [
        'Ext.route.Handler',
        'Ext.route.Router'
    ],

    mixinConfig: {
        id: 'routerable',
        before: {
            destroy: 'destroyRouterable'
        }
    },

    config: {
        /**
         * @cfg {Object} routes
         * @accessor
         *
         * An object of routes to handle hash changes. A route can be defined in a simple way:
         *
         *     routes: {
         *         'foo/bar': 'handleFoo',
         *         'user/:id': 'showUser'
         *     }
         *
         * Where the property is the hash (which can accept a parameter defined by a colon)
         * and the value is the method on the controller to execute. The parameters will get sent
         * in the action method.
         *
         * If no routes match a given hash, an {@link Ext.GlobalEvents#unmatchedroute} event
         * will be fired. This can be listened to in four ways:
         *
         *     Ext.on('unmatchedroute', function(token) {});
         *
         *     Ext.define('MyApp.controller.Foo', {
         *         extend: 'Ext.app.Controller',
         *
         *         listen: {
         *             global: {
         *                 unmatchedroute: 'onUnmatchedRoute'
         *             }
         *         },
         *
         *         onUnmatchedRoute: function(token) {}
         *     });
         *
         *     Ext.application({
         *         name: 'MyApp',
         *
         *         listen: {
         *             global: {
         *                 unmatchedroute: 'onUnmatchedRoute'
         *             }
         *         },
         *
         *         onUnmatchedRoute: function(token) {}
         *     });
         *
         *     Ext.application({
         *         name: 'MyApp',
         *
         *         listeners: {
         *             unmatchedroute: 'onUnmatchedRoute'
         *         },
         *
         *         onUnmatchedRoute: function(token) {}
         *     });
         *
         * There is also a complex means of defining a route where you can use a before action
         * and even specify your own RegEx for the parameter:
         *
         *     routes: {
         *         'foo/bar': {
         *             action: 'handleFoo',
         *             before: 'beforeHandleFoo'
         *         },
         *         'user/:id': {
         *             action: 'showUser',
         *             before: 'beforeShowUser',
         *             conditions: {
         *                 ':id': '([0-9]+)'
         *             }
         *         }
         *     }
         *
         * This will only match if the `id` parameter is a number.
         *
         * The before action allows you to cancel an action. Every before action will get passed
         * an `action` argument with a `resume` and `stop` methods as the last argument of the
         * method and you *MUST* execute either method:
         *
         *     beforeHandleFoo: function (action) {
         *         // some logic here
         *
         *         // this will allow the handleFoo action to be executed
         *         action.resume();
         *     },
         *     handleFoo: function () {
         *         // will get executed due to true being passed in callback in beforeHandleFoo
         *     },
         *     beforeShowUser: function (id, action) {
         *         // allows for async process like an Ajax
         *         Ext.Ajax.request({
         *             url: 'foo.php',
         *             success: function () {
         *                 // will not allow the showUser method to be executed
         *                 // but will continue other queued actions.
         *                 action.stop();
         *             },
         *             failure: function () {
         *                 // will not allow the showUser method to be executed
         *                 // and will not allow other queued actions to be executed.
         *                 action.stop(true);
         *             }
         *         });
         *     },
         *     showUser: function (id) {
         *         // will not get executed due to false being passed in callback in beforeShowUser
         *     }
         *
         * You **MUST** execute the `{@link Ext.route.Action#resume resume}` or
         * `{@link Ext.route.Action#stop stop}` method on the `action` argument. Executing
         * `action.resume();` will continue the action, `action.stop();` will prevent
         * further execution.
         *
         * The default RegEx that will be used is `([%a-zA-Z0-9\\-\\_\\s,]+)` but you can specify
         * any that may suit what you need to accomplish. An example of an advanced condition
         * may be to make a parameter optional and case-insensitive:
         *
         *     routes: {
         *         'user:id': {
         *             action: 'showUser',
         *             before: 'beforeShowUser',
         *             conditions: {
         *                 ':id': '(?:(?:\/){1}([%a-z0-9_,\s\-]+))?'
         *             }
         *         }
         *     }
         *
         * Each route can be named; this allows for the route to be looked up by name instead of
         * url. By default, the route's name will be the url you configure but you can provide
         * the `{@link Ext.route.Route#name name}` config to override the default:
         *
         *     routes: {
         *         'user:id': {
         *             action: 'showUser',
         *             before: 'beforeShowUser',
         *             name: 'user',
         *             conditions: {
         *                 ':id': '(?:(?:\/){1}([%a-z0-9_,\s\-]+))?'
         *             }
         *         }
         *     }
         *
         * The `user:id` route can not be looked up via the `user` name which is useful when using
         * `{@link #redirectTo}`.
         *
         * A wildcard route can also be defined which works exactly like any other route but will
         * always execute before any other route.  To specify a wildcard route, use the `*`
         * as the url:
         *
         *     routes: {
         *         '*': 'onToken'
         *     }
         *
         * Since a wildcard route will execute before any other route, it can delay the execution
         * of other routes allowing for such things like a user session to be retrieved:
         *
         *     routes: {
         *         '*': {
         *             before: 'onBeforeToken'
         *         }
         *     },
         *
         *     onBeforeToken: function () {
         *         return Ext.Ajax.request({
         *             url: '/user/session'
         *         });
         *     }
         *
         * In the above example, no other route will execute unless that
         * {@link Ext.Ajax#request request} returns successfully.
         *
         * You can also use a wildcard route if you need to defer routes until a store has been
         * loaded when an application first starts up:
         *
         *     routes: {
         *         '*': {
         *             before: 'onBeforeToken'
         *         }
         *     },
         *
         *     onBeforeToken: function (action) {
         *         var store = Ext.getStore('Settings');
         *
         *         if (store.loaded) {
         *             action.resume();
         *         } else {
         *             store.on('load', action.resume, action, { single: true });
         *         }
         *     }
         *
         * The valid options are configurations from {@link Ext.route.Handler} and
         * {@link Ext.route.Route}.
         */
        routes: null
    },

    destroyRouterable: function() {
        Ext.route.Router.disconnect(this);
    },

    applyRoutes: function(routes, oldRoutes) {
        var Router = Ext.route.Router,
            url;

        if (routes) {
            for (url in routes) {
                routes[url] = Router.connect(url, routes[url], this);
            }
        }

        if (oldRoutes) {
            for (url in oldRoutes) {
                Router.disconnect(this, oldRoutes[url]);
            }
        }

        return routes;
    },

    /**
     * Update the hash. By default, it will not execute the routes if the current token and the
     * token passed are the same.
     *
     * @param {String/Number/Object/Ext.data.Model} hash The hash to redirect to. The hash can be
     * of several values:
     *  - **String** The hash to exactly be set to.
     *  - **Number** If `1` is passed, {@link Ext.util.History#forward forward} function will be
     * executed. If `-1` is passed, {@link Ext.util.History#bck back} function will be executed.
     *  - **Ext.data.Model** If a model instance is passed, the Model's
     * {@link Ext.data.Model#toUrl toUrl} function will be executed to convert it into a String
     * value to set the hash to.
     *  - **Object** An Object can be passed to control individual tokens in the full hash.
     * The key should be an associated {@link Ext.route.Route Route}'s
     * {@link Ext.route.Route#name name} and the value should be the value of that token
     * in the complete hash. For example, if you have two routes configured, each token in the
     * hash that can be matched for each route can be individually controlled:
     *
     *     routes: {
     *         'foo/bar': 'onFooBar',
     *         'baz/:id': {
     *             action: 'onBaz',
     *             name: 'baz'
     *         }
     *     }
     *
     * If you pass in a hash of `#foo/bar|baz/1`, each route will execute in response. If you want
     * to change only the `baz` route but leave the `foo/bar` route in the hash, you can pass only
     * the `baz` key in an object:
     *
     *     this.redirectTo({
     *         baz : 'baz/5'
     *     });
     *
     * and the resulting hash will be `#foo/bar/|baz/5` and only the `baz` route will execute
     * in reaction but the `foo/bar` will not react since it's associated token in the hash
     * remained the same. If you wanted to update the `baz` route and remove `foo/bar`
     * from the hash, you can set the value to `null`:
     *
     *     this.redirectTo({
     *         'foo/bar': null,
     *         baz: 'baz/3'
     *     });
     *
     * and the resulting hash will be `#baz/3`. Like before, the `baz` route will execute
     * in reaction.
     *
     * @param {Object} opt An optional `Object` describing how to enact the hash being passed in.
     * Valid options are:
     *
     *  - `force` Even if the hash will not change, setting this to `true` will force the
     * {@link Ext.route.Router Router} to react.
     *  - `replace` When set to `true`, this will replace the current resource in the history stack
     * with the hash being set.
     *
     * For backwards compatibility, if `true` is passed instead of an `Object`, this will set
     * the `force` option to `true`.
     *
     * @return {Boolean} Will return `true` if the token was updated.
     */
    redirectTo: function(hash, opt) {
        var currentHash = Ext.util.History.getToken(),
            Router = Ext.route.Router,
            delimiter = Router.getMultipleToken(),
            tokens = currentHash ? currentHash.split(delimiter) : [],
            length = tokens.length,
            force, i, name, obj, route, token, match;

        if (hash === -1) {
            return Ext.util.History.back();
        }
        else if (hash === 1) {
            return Ext.util.History.forward();
        }
        else if (hash.isModel) {
            hash = hash.toUrl();
        }
        else if (Ext.isObject(hash)) {
            // Passing an object attempts to replace a token in the hash.
            for (name in hash) {
                obj = hash[name];

                if (!Ext.isObject(obj)) {
                    obj = {
                        token: obj
                    };
                }

                if (length) {
                    route = Router.getByName(name);

                    if (route) {
                        match = false;

                        for (i = 0; i < length; i++) {
                            token = tokens[i];

                            if (route.matcherRegex.test(token)) {
                                match = true;

                                if (obj.token) {
                                    // a token was found in the hash, replace it
                                    if (obj.fn && obj.fn.call(this, token, tokens, obj) === false) {
                                        // if the fn returned false, skip update
                                        continue;
                                    }

                                    tokens[i] = obj.token;

                                    if (obj.force) {
                                        // clear lastToken to force recognition
                                        route.lastToken = null;
                                    }
                                }
                                else {
                                    // remove token
                                    tokens.splice(i, 1);

                                    i--;
                                    length--;

                                    // reset lastToken
                                    route.lastToken = null;
                                }
                            }
                        }

                        if (obj && obj.token && !match) {
                            // a token was not found in the hash, push to the end
                            tokens.push(obj.token);
                        }
                    }
                }
                else if (obj && obj.token) {
                    // there is no current hash, push to the end
                    tokens.push(obj.token);
                }
            }

            hash = tokens.join(delimiter);
        }

        if (opt === true) {
            // for backwards compatibility
            force = opt;
            opt = null;
        }
        else if (opt) {
            force = opt.force;
        }

        length = tokens.length;

        if (force && length) {
            for (i = 0; i < length; i++) {
                token = tokens[i];

                Router.clearLastTokens(token);
            }
        }

        if (currentHash === hash) {
            if (force) {
                // hash won't change, trigger handling anyway
                Router.onStateChange(hash);
            }

            // hash isn't going to change, return false
            return false;
        }

        if (opt && opt.replace) {
            Ext.util.History.replace(hash);
        }
        else {
            Ext.util.History.add(hash);
        }

        return true;
    },

    privates: {
        afterClassMixedIn: function(targetClass) {
            var proto = targetClass.prototype,
                routes = proto.routes;

            if (routes) {
                delete proto.routes;

                targetClass.getConfigurator().add({
                    routes: routes
                });
            }
        }
    }
});
