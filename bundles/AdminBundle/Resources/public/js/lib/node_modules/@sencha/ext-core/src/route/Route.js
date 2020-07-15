/**
 * Enables reactive actions to handle changes in the hash by using the
 * {@link Ext.route.Mixin#routes routes} configuration in a controller.
 * An example configuration would be:
 *
 *     Ext.define('MyApp.view.main.MainController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.app-main',
 *
 *         routes: {
 *             'user/:{id}': 'onUser'
 *         },
 *
 *         onUser: function (values) {
 *             var id = values.id;
 *             // ...
 *         }
 *     });
 *
 * The `routes` object can also receive an object to further configure
 * the route, for example you can configure a `before` action that will
 * be executed before the `action` or can cancel the route execution:
 *
 *     Ext.define('MyApp.view.main.MainController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.app-main',
 *
 *         routes: {
 *             'user/:{id}': {
 *                 action: 'onUser',
 *                 before: 'onBeforeUser',
 *                 name: 'user'
 *             }
 *         },
 *
 *         onBeforeUser: function (values) {
 *             return Ext.Ajax
 *                 .request({
 *                     url: '/check/permission',
 *                     params: {
 *                         route: 'user',
 *                         meta: {
 *                             id: values.id
 *                         }
 *                     }
 *                 });
 *         },
 *
 *         onUser: function (values) {
 *             var id = values.id;
 *             // ...
 *         }
 *     });
 *
 * URL Parameters in a route can also define a type that will be used
 * when matching hashes when finding routes that recognize a hash and
 * also parses the value into numbers:
 *
 *     Ext.define('MyApp.view.main.MainController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.app-main',
 *
 *         routes: {
 *             'user/:{id:num}': {
 *                 action: 'onUser',
 *                 before: 'onBeforeUser',
 *                 name: 'user'
 *             }
 *         },
 *
 *         onBeforeUser: function (values) {
 *             return Ext.Ajax
 *                 .request({
 *                     url: '/check/permission',
 *                     params: {
 *                         route: 'user',
 *                         meta: {
 *                             id: values.id
 *                         }
 *                     }
 *                 });
 *         },
 *
 *         onUser: function (values) {
 *             var id = values.id;
 *             // ...
 *         }
 *     });
 *
 * In this example, the id parameter added `:num` to the parameter which
 * will now mean the route will only recognize a value for the id parameter
 * that is a number such as `#user/123` and will not recognize `#user/abc`.
 * The id passed to the action and before handlers will also get cast into
 * a number instead of a string. If a type is not provided, it will use
 * the {@link #defaultMatcher default matcher}.
 *
 * For more on types, see the {@link #cfg!types} config.
 *
 * For backwards compatibility, there is `positional` mode which is like
 * `named` mode but how you define the url parameters and how they are passed
 * to the action and before handlers is slightly different:
 *
 *     Ext.define('MyApp.view.main.MainController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.app-main',
 *
 *         routes: {
 *             'user/:id:action': {
 *                 action: 'onUser',
 *                 before: 'onBeforeUser',
 *                 name: 'user',
 *                 conditions: {
 *                     ':action': '(edit|delete)?'
 *                 }
 *             }
 *         },
 *
 *         onBeforeUser: function (id, action) {
 *             return Ext.Ajax
 *                 .request({
 *                     url: '/check/permission',
 *                     params: {
 *                         route: 'user',
 *                         meta: {
 *                             action: action,
 *                             id: id
 *                         }
 *                     }
 *                 });
 *         },
 *
 *         onUser: function (id) {
 *             // ...
 *         }
 *     });
 *
 * The parameters are defined without curly braces (`:id`, `:action`) and
 * they are passed as individual arguments to the action and before handlers.
 *
 * It's important to note you cannot mix positional and named parameter formats
 * in the same route since how they are passed to the handlers is different.
 *
 * Routes can define sections of a route pattern that are optional by surrounding
 * the section that is to be optional with parenthesis. For example, if a route
 * should match both `#user` and `#user/1234` to either show a grid of all users
 * or details or a single user, you can define the route such as:
 *
 *     Ext.define('MyApp.view.main.MainController', {
 *         extend: 'Ext.app.ViewController',
 *         alias: 'controller.app-main',
 *
 *         routes: {
 *             'user(/:{id:num})': {
 *                 action: 'onUser',
 *                 name: 'user'
 *             }
 *         },
 *
 *         onUser: function (params) {
 *             if (params.id) {
 *                 // load user details
 *             } else {
 *                 // load grid of users
 *             }
 *         }
 *     });
 */
Ext.define('Ext.route.Route', {
    requires: [
        'Ext.route.Action',
        'Ext.route.Handler'
    ],

    /**
     * @event beforeroute
     * @member Ext.GlobalEvents
     *
     * Fires when a route is about to be executed. This allows pre-processing to add additional
     * {@link Ext.route.Action#before before} or {@link Ext.route.Action#action action} handlers
     * when the {@link Ext.route.Action Action} is run.
     *
     * The route can be prevented from executing by returning `false` in a listener
     * or executing the {@link Ext.route.Action#stop stop} method on the action.
     *
     * @param {Ext.route.Route} route The route being executed.
     * @param {Ext.route.Action} action The action that will be run.
     */

    /**
     * @event beforerouteexit
     * @member Ext.GlobalEvents
     *
     * Fires when a route is being exited meaning when a route
     * was executed but no longer matches a token in the current hash.
     *
     * The exit handlers can be prevented from executing by returning `false` in a listener
     * or executing the {@link Ext.route.Action#stop stop} method on the action.
     *
     * @param {Ext.route.Action} action The action with defined exit actions. Each
     * action will execute with the last token this route was connected with.
     * @param {Ext.route.Route} route
     */

    config: {
        /**
         * @cfg {String} name The name of this route. The name can be used when using
         * {@link Ext.route.Mixin#redirectTo}.
         */
        name: null,

        /**
         * @cfg {String} url (required) The url regex to match against.
         */
        url: null,

        /**
         * @cfg {Boolean} [allowInactive=false] `true` to allow this route to be triggered on
         * a controller that is not active.
         */
        allowInactive: false,

        /**
         * @cfg {Object} conditions
         * Optional set of conditions for each token in the url string. Each key should
         * be one of the tokens, each value should be a regex that the token should accept.
         *
         * For `positional` mode, if you have a route with a url like `'files/:fileName'` and
         * you want it to match urls like `files/someImage.jpg` then you can set these
         * conditions to allow the :fileName token to accept strings containing a period:
         *
         *     conditions: {
         *         ':fileName': '([0-9a-zA-Z\.]+)'
         *     }
         *
         * For `named` mode, if you have a route with a url like `'files/:{fileName}'`
         * and you want it to match urls like `files/someImage.jpg` then you can set these
         * conditions to allow the :{fileName} token to accept strings containing a period:
         *
         *     conditions: {
         *         'fileName': '([0-9a-zA-Z\.]+)'
         *     }
         *
         * You can also define a condition to parse the value or even split it on a character:
         *
         *     conditions: {
         *         'fileName': {
         *             re: '([0-9a-zA-Z\.]+)',
         *             split: '.', // split the value so you get an array ['someImage', 'jpg']
         *             parse: function (values) {
         *                 return values[0]; // return a string without the extension
         *             }
         *         }
         *     }
         */
        conditions: {},

        /**
         * @cfg {Boolean} [caseInsensitive=false] `true` to allow the tokens to be matched with
         * case-insensitive.
         */
        caseInsensitive: false,

        /**
         * @cfg {Object[]} [handlers=[]]
         * The array of connected handlers to this route. Each handler must defined a
         * `scope` and can define an `action`, `before` and/or `exit` handler:
         *
         *     handlers: [{
         *         action: function() {
         *             //...
         *         },
         *         scope: {}
         *     }, {
         *         action: function() {
         *             //...
         *         },
         *         before: function() {
         *             //...
         *         },
         *         scope: {}
         *     }, {
         *         exit: function() {
         *             //...
         *         },
         *         scope: {}
         *     }]
         *
         * The `action`, `before` and `exit` handlers can be a string that will be resolved
         * from the `scope`:
         *
         *     handlers: [{
         *         action: 'onAction',
         *         before: 'onBefore',
         *         exit: 'onExit',
         *         scope: {
         *             onAction: function () {
         *                 //...
         *             },
         *             onBefore: function () {
         *                 //...
         *             },
         *             onExit: function () {
         *                 //...
         *             }
         *         }
         *     }]
         */
        handlers: [],

        /* eslint-disable max-len */
        /**
         * @since 6.6.0
         * @property {Object} types
         * An object of types that will be used to match and parse values from a matched
         * url. There are four default types:
         *
         * - `alpha` This will only match values that have only alpha characters using
         * the regex `([a-zA-Z]+)`.
         * - `alphanum` This will only match values that have alpha and numeric characters
         * using the regex `([a-zA-Z0-9]+|[0-9]*(?:\\.[0-9]*)?)`. If a value is a number,
         * which a number can have a period (`10.4`), the value will be case into a float
         * using `parseFloat`.
         * - `num` This will only match values that have numeric characters using the regex
         * `([0-9]*(?:\\.[0-9]*)?)`. The value, which can have a period (`10.4`), will be
         * case into a float using `parseFloat`.
         * - `...` This is meant to be the last argument in the url and will match all
         * characters using the regex `(.+)?`. If a value is matched, this is an optional
         * type, the value will be split by `/` and an array will be sent to the handler
         * methods. If no value was matched, the value will be `undefined`.
         *
         * When defining routes, a type is optional and will use the
         * {@link #defaultMatcher default matcher} but the url parameter must be enclosed
         * in curly braces which will send a single object to the route handlers:
         *
         *     Ext.define('MyApp.view.MainController', {
         *         extend: 'Ext.app.ViewController',
         *         alias: 'controller.myapp-main',
         *
         *         routes: {
         *             'view/:{view}/:{child:alphanum}:{args...}': {
         *                 action: 'onView',
         *                 before: 'onBeforeView',
         *                 name: 'view'
         *             }
         *         },
         *
         *         onBeforeView: function (values) {
         *             return Ext.Ajax.request({
         *                 url: 'check/permission',
         *                 params: {
         *                     view: values.view,
         *                     info: { childView: values.child }
         *                 }
         *             });
         *         },
         *
         *         onView: function (values) {}
         *     });
         *
         * In this example, there are 3 parameters defined. The `:{view}` parameter has no
         * type which will match characters using the {@link #defaultMatcher default matcher}
         * but is required to be in the matched url. The `:{child:alphanum}` will only match
         * characters that are alpha or numeric but is required to be in the matched url. The
         * `:{args...}` is the only optional parameter in this route but can match any
         * character and will be an array of values split by `/` unless there are no values
         * in which case `undefined` will be sent in the object.
         *
         * If the hash is `#view/user/edit`, the `values` argument sent to the handlers would be:
         *
         *     {
         *         view: 'user',
         *         child: 'edit',
         *         args: undefined
         *     }
         *
         * Since there were no more values for the `args` parameter, it's value is `undefined`.
         *
         * If the hash is `#view/user/1234`, the `values` argument sent to the handlers would be:
         *
         *     {
         *         view: 'user',
         *         child: 1234,
         *         args: undefined
         *     }
         *
         * Notice the `child` value is a number instead of a string.
         *
         * If the hash is `#view/user/1234/edit/settings`, the `values` argument sent to the
         * handlers would be:
         *
         *     {
         *         view: 'user',
         *         child: 1234,
         *         args: ['edit', 'settings']
         *     }
         *
         * The `args` parameter matched the `edit/settings` and split it by the `/` producing
         * the array.
         *
         * To add custom types, you can override `Ext.route.Route`:
         *
         *     Ext.define('Override.route.Route', {
         *         override: 'Ext.route.Route',
         *
         *         config: {
         *             types: {
         *                 uuid: {
         *                     re: '([0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})'
         *                 }
         *             }
         *         }
         *     });
         *
         * You can now use the `uuid` type in your routes:
         *
         *     Ext.define('MyApp.view.MainController', {
         *         extend: 'Ext.app.ViewController',
         *         alias: 'controller.myapp-main',
         *
         *         routes: {
         *             'user/:{userid:uuid}': {
         *                 action: 'onUser',
         *                 caseInsensitive: true,
         *                 name: 'user'
         *             }
         *         },
         *
         *         onUser: function (values) {}
         *     });
         *
         * This would match if the hash was like `#user/C56A4180-65AA-42EC-A945-5FD21DEC0538`
         * and the `values` object would then be:
         *
         *     {
         *         user: 'C56A4180-65AA-42EC-A945-5FD21DEC0538'
         *     }
         */
        /* eslint-enable max-len */
        types: {
            cached: true,
            $value: {
                alpha: {
                    re: '([a-zA-Z]+)'
                },
                alphanum: {
                    re: '([a-zA-Z0-9]+|[0-9]+(?:\\.[0-9]+)?|[0-9]*(?:\\.[0-9]+){1})',
                    parse: function(value) {
                        var test;

                        if (value && this.numRe.test(value)) {
                            test = parseFloat(value);

                            if (!isNaN(test)) {
                                value = test;
                            }
                        }

                        return value;
                    }
                },
                num: {
                    // allow `1`, `10`, 10.1`, `.1`
                    re: '([0-9]+(?:\\.[0-9]+)?|[0-9]*(?:\\.[0-9]+){1})',
                    parse: function(value) {
                        if (value) {
                            value = parseFloat(value);
                        }

                        return value;
                    }
                },
                '...': {
                    re: '(.+)?',
                    split: '/',
                    parse: function(values) {
                        var length, i, value;

                        if (values) {
                            length = values.length;

                            for (i = 0; i < length; i++) {
                                value = parseFloat(values[i]);

                                if (!isNaN(value)) {
                                    values[i] = value;
                                }
                            }
                        }

                        return values;
                    }
                }
            }
        }
    },

    /**
     * @property {String} [defaultMatcher='([%a-zA-Z0-9\\-\\_\\s,]+)'] The default RegExp string
     * to use to match parameters with.
     */
    defaultMatcher: '([%a-zA-Z0-9\\-\\_\\s,]+)',

    /**
     * @private
     * @property {RegExp} matcherRegex A regular expression to match the token to the
     * configured {@link #url}.
     */

    /**
     * @since 6.6.0
     * @property {RegExp} numRe A regular expression to match against float numbers for
     * `alphanum`, `num` and `...` {@link #cfg!types} in order to cast into floats.
     */
    numRe: /^[0-9]*(?:\.[0-9]*)?$/,

    /**
     * @private
     * @since 6.6.0
     * @property {RegExp} typeParamRegex
     * A regular expression to determine if the parameter may contain type information.
     * If a parameter does have type information, the url parameters sent to the
     * {@link Ext.route.Handler#before} and {@link Ext.route.Handler#after} will
     * be in an object instead of separate arguments.
     */
    typeParamRegex: /:{([0-9A-Za-z_]+)(?::?([0-9A-Za-z_]+|.{3})?)}/g,

    /**
     * @private
     * @since 6.6.0
     * @property {RegExp} optionalParamRegex
     * A regular expression to find groups intended to be optional values within the
     * hash. This means that if they are in the hash they will match and return the
     * values present. But, if they are not and the rest of the hash matches, the route
     * will still execute passing `undefined` as the values of any parameters
     * within an optional group.
     *
     *     routes: {
     *         'user(\/:{id:num})': {
     *             action: 'onUser',
     *             name: 'user'
     *         }
     *     }
     *
     * In this example, the `id` parameter and the slash will be optional since they
     * are wrapped in the parentheses. This route would execute if the hash is `#user`
     * or `#user/1234`.
     */
    optionalGroupRegex: /\((.+?)\)/g,

    /**
     * @private
     * @property {RegExp} paramMatchingRegex
     * A regular expression to check if there are parameters in the configured
     * {@link #url}.
     */
    paramMatchingRegex: /:([0-9A-Za-z_]+)/g,

    /**
     * @private
     * @property {Array/Object} paramsInMatchString
     * An array or object of parameters in the configured {@link #url}.
     */

    /**
     * @private
     * @since 6.6.0
     * @property {String} mode
     * The mode based on the {@link #cfg!url} pattern this route is configured with.
     * Valid values are:
     *
     * - `positional` The {@link #cfg!url} was configured with the parameter format
     * as `:param`. The values in the handler functions will be individual arguments.
     * Example:
     *
     *     Ext.define('MyApp.view.MainController', {
     *         extend: 'Ext.app.ViewController',
     *         alias: 'controller.myapp-main',
     *
     *         routes: {
     *             'view/:view/:child': {
     *                 action: 'onView',
     *                 before: 'onBeforeView',
     *                 name: 'view'
     *             }
     *         },
     *
     *         onBeforeView: function (view, child) {
     *             return Ext.Ajax.request({
     *                 url: 'check/permission',
     *                 params: {
     *                     view: view,
     *                     info: { childView: child }
     *                 }
     *             });
     *         },
     *
     *         onView: function (view, child) {}
     *     });
     *
     * The values from the matched url that the `view` route would execute with are
     * separate arguments in the before and action handlers.
     * - `named` The {@link #cfg!url} was configured with the parameter format as
     * `:{param:type}` where the `:type` is optional. Example:
     *
     *     Ext.define('MyApp.view.MainController', {
     *         extend: 'Ext.app.ViewController',
     *         alias: 'controller.myapp-main',
     *
     *         routes: {
     *             'view/:{view}/:{child:alphanum}': {
     *                 action: 'onView',
     *                 before: 'onBeforeView',
     *                 name: 'view'
     *             }
     *         },
     *
     *         onBeforeView: function (values) {
     *             return Ext.Ajax.request({
     *                 url: 'check/permission',
     *                 params: {
     *                     view: values.view,
     *                     info: { childView: values.child }
     *                 }
     *             });
     *         },
     *
     *         onView: function (values) {}
     *     });
     *
     * The values from the matched url the `view` route would execute with are collected
     * into an object with the parameter name as the key and the associated value as
     * the value. See {@link #cfg!types} for more about this named mode.
     */

    /**
     * @protected
     * @property {Boolean} isRoute
     */
    isRoute: true,

    constructor: function(config) {
        var me = this,
            url;

        this.initConfig(config);

        url = me.getUrl().replace(me.optionalGroupRegex, function(match, middle) {
            return '(?:' + middle + ')?';
        });

        if (url.match(me.typeParamRegex)) {
            me.handleNamedPattern(url);
        }
        else {
            me.handlePositionalPattern(url);
        }
    },

    /**
     * @private
     * @since 6.6.0
     * Handles a pattern that will enable positional {@link #property!mode}.
     *
     * @param {String} url The url pattern.
     */
    handlePositionalPattern: function(url) {
        var me = this;

        me.paramsInMatchString = url.match(me.paramMatchingRegex) || [];
        me.matcherRegex = me.createMatcherRegex(url);
        me.mode = 'positional';
    },

    /**
     * @private
     * @since 6.6.0
     * Handles a pattern that will enable named {@link #property!mode}.
     *
     * @param {String} url The url pattern.
     */
    handleNamedPattern: function(url) {
        var me = this,
            typeParamRegex = me.typeParamRegex,
            conditions = me.getConditions(),
            types = me.getTypes(),
            defaultMatcher = me.defaultMatcher,
            params = {},
            re = url.replace(typeParamRegex, function(match, param, typeMatch) {
                var type = typeMatch && types[typeMatch],
                    matcher = conditions[param] || type || defaultMatcher;

                //<debug>
                if (params[param]) {
                    Ext.raise('"' + param + '" already defined in route "' + url + '"');
                }

                if (typeMatch && !type) {
                    Ext.raise('Unknown parameter type "' + typeMatch + '" in route "' + url + '"');
                }
                //</debug>

                if (Ext.isObject(matcher)) {
                    matcher = matcher.re;
                }

                params[param] = {
                    matcher: matcher,
                    type: typeMatch
                };

                return matcher;
            });

        //<debug>
        if (re.search(me.paramMatchingRegex) !== -1) {
            Ext.raise('URL parameter mismatch. Positional url parameter found ' +
                      'while in named mode.');
        }
        //</debug>

        me.paramsInMatchString = params;
        me.matcherRegex = new RegExp('^' + re + '$', me.getCaseInsensitive() ? 'i' : '');
        me.mode = 'named';
    },

    /**
     * Attempts to recognize a given url string and return a meta data object including
     * any URL parameter matches.
     *
     * @param {String} url The url to recognize.
     * @return {Object/Boolean} The matched data, or `false` if no match.
     */
    recognize: function(url) {
        var me = this,
            recognized = me.recognizes(url),
            handlers, length, hasHandler, handler, matches, urlParams, i;

        if (recognized) {
            handlers = me.getHandlers();
            length = handlers.length;

            for (i = 0; i < length; i++) {
                handler = handlers[i];

                if (handler.lastToken !== url) {
                    // there is a handler that can execute
                    hasHandler = true;
                    break;
                }
            }

            if (!hasHandler && url === me.lastToken) {
                // url matched the lastToken
                return true;
            }

            // backwards compat
            matches = me.matchesFor(url);
            urlParams = me.getUrlParams(url);

            return Ext.applyIf(matches, {
                historyUrl: url,
                urlParams: urlParams
            });
        }

        return false;
    },

    /**
     * @private
     * @since 6.6.0
     * Returns the url parameters matched in the given url.
     *
     * @param {String} url The url this route is executing on.
     * @return {Array/Object} If {@link #property!mode} is `named`,
     * an object from {@link #method!getNamedUrlParams} will be returned.
     * If is `positional`, an array from {@link #method!getPositionalUrlParams}
     * will be returned.
     */
    getUrlParams: function(url) {
        if (this.mode === 'named') {
            return this.getNamedUrlParams(url);
        }
        else {
            return this.getPositionalUrlParams(url);
        }
    },

    /**
     * @private
     * @since 6.6.0
     * Returns an array of url parameters values in order they appear in the url.
     *
     * @param {String} url The url the route is executing on.
     * @return {Array}
     */
    getPositionalUrlParams: function(url) {
        var params = [],
            conditions = this.getConditions(),
            keys = this.paramsInMatchString,
            values = url.match(this.matcherRegex),
            length = keys.length,
            i, key, type, value;

        // remove the full match
        values.shift();

        for (i = 0; i < length; i++) {
            key = keys[i];
            value = values[i];

            if (conditions[key]) {
                type = conditions[key];
            }
            else if (key[0] === ':') {
                key = key.substr(1);

                if (conditions[key]) {
                    type = conditions[key];
                }
            }

            value = this.parseValue(value, type);

            if (Ext.isDefined(value) && value !== '') {
                if (Ext.isArray(value)) {
                    params.push.apply(params, value);
                }
                else {
                    params.push(value);
                }
            }
        }

        return params;
    },

    /**
     * @private
     * @since 6.6.0
     * Returns an object of url parameters with parameter name as the
     * object key and the value.
     *
     * @param {String} url The url the route is executing on.
     * @return {Array}
     */
    getNamedUrlParams: function(url) {
        var conditions = this.getConditions(),
            types = this.getTypes(),
            params = {},
            keys = this.paramsInMatchString,
            values = url.match(this.matcherRegex),
            name, obj, value, type, condition;

        // remove the full match
        values.shift();

        for (name in keys) {
            obj = keys[name];
            value = values.shift();
            condition = conditions[name];
            type = types[obj.type];

            if (condition || type) {
                type = Ext.merge({}, condition, types[obj.type]);
            }

            params[name] = this.parseValue(value, type);
        }

        return params;
    },

    /**
     * @private
     * @since 6.6.0
     * Parses the value from the url with a {@link #cfg!types type}
     * or a matching {@link #cfg!conditions condition}.
     *
     * @param {String} value The value from the url.
     * @param {Object} [type] The type object that will be used to parse the value.
     * @return {String/Number/Array}
     */
    parseValue: function(value, type) {
        if (type) {
            if (value && type.split) {
                value = value.split(type.split);

                // If first is empty string, remove.
                // This could be because the value prior
                // was `/foo/bar` which would lead to
                // `['', 'foo', 'bar']`.
                if (!value[0]) {
                    value.shift();
                }

                // If last is empty string, remove.
                // This could be because the value prior
                // was `foo/bar/` which would lead to
                // `['foo', 'bar', '']`.
                if (!value[value.length - 1]) {
                    value.pop();
                }
            }

            if (type.parse) {
                value = type.parse.call(this, value);
            }
        }

        if (!value && Ext.isString(value)) {
            // IE8 may have values as an empty string
            // if there was no value that was matched
            value = undefined;
        }

        return value;
    },

    /**
     * Returns `true` if this {@link Ext.route.Route} matches the given url string.
     *
     * @param {String} url The url to test.
     * @return {Boolean} `true` if this {@link Ext.route.Route} recognizes the url.
     */
    recognizes: function(url) {
        return this.matcherRegex.test(url);
    },

    /**
     * The method to execute the action using the configured before function which will
     * kick off the actual {@link #actions} on the {@link #controller}.
     *
     * @param {String} token The token this route is being executed with.
     * @param {Object} argConfig The object from the {@link Ext.route.Route}'s
     * recognize method call.
     * @return {Ext.promise.Promise}
     */
    execute: function(token, argConfig) {
        var me = this,
            allowInactive = me.getAllowInactive(),
            handlers = me.getHandlers(),
            queue = Ext.route.Router.getQueueRoutes(),
            length = handlers.length,
            urlParams = (argConfig && argConfig.urlParams) || [],
            i, handler, scope, action, promises, single, remover;

        me.lastToken = token;

        if (!queue) {
            promises = [];
        }

        return new Ext.Promise(function(resolve, reject) {
            if (argConfig === false) {
                reject();
            }
            else {
                if (queue) {
                    action = new Ext.route.Action({
                        urlParams: urlParams
                    });
                }

                for (i = 0; i < length; i++) {
                    handler = handlers[i];

                    if (token != null && handler.lastToken === token) {
                        // no change on this handler
                        continue;
                    }

                    scope = handler.scope;

                    handler.lastToken = token;

                    if (!allowInactive && scope.isActive && !scope.isActive()) {
                        continue;
                    }

                    if (!queue) {
                        action = new Ext.route.Action({
                            urlParams: urlParams
                        });
                    }

                    single = handler.single;

                    if (handler.before) {
                        action.before(handler.before, scope);
                    }

                    if (handler.action) {
                        action.action(handler.action, scope);
                    }

                    if (single) {
                        remover = Ext.bind(me.removeHandler, me, [null, handler]);

                        if (single === true) {
                            if (handler.action) {
                                action.action(remover, me);
                            }
                            else {
                                action.before(function() {
                                    remover();

                                    return Ext.Promise.resolve();
                                }, me);
                            }
                        }
                        else {
                            // all before actions have to resolve,
                            // resolve a promise to allow the action
                            // chain to continue
                            action.before(single === 'before', function() {
                                remover();

                                return Ext.Promise.resolve();
                            }, me);
                        }
                    }

                    if (!queue) {
                        if (Ext.fireEvent('beforeroute', action, me) === false) {
                            action.destroy();
                        }
                        else {
                            promises.push(action.run());
                        }
                    }
                }

                if (queue) {
                    if (Ext.fireEvent('beforeroute', action, me) === false) {
                        action.destroy();

                        reject();
                    }
                    else {
                        action.run().then(resolve, reject);
                    }
                }
                else {
                    Ext.Promise.all(promises).then(resolve, reject);
                }
            }
        });
    },

    /**
     * Returns a hash of matching url segments for the given url.
     *
     * @param {String} url The url to extract matches for
     * @return {Object} matching url segments
     */
    matchesFor: function(url) {
        var params = {},
            keys = this.mode === 'named'
                ? Ext.Object.getKeys(this.paramsInMatchString)
                : this.paramsInMatchString,
            values = url.match(this.matcherRegex),
            length = keys.length,
            i;

        // first value is the entire match so reject
        values.shift();

        for (i = 0; i < length; i++) {
            params[keys[i].replace(':', '')] = values[i];
        }

        return params;
    },

    /**
     * Takes the configured url string including wildcards and returns a regex that can be
     * used to match against a url.
     *
     * This is only used in `positional` {@link #property!mode}.
     *
     * @param {String} url The url string.
     * @return {RegExp} The matcher regex.
     */
    createMatcherRegex: function(url) {
        // Converts a route string into an array of symbols starting with a colon. e.g.
        // ":controller/:action/:id" => [':controller', ':action', ':id']
        var me = this,
            paramsInMatchString = me.paramsInMatchString,
            conditions = me.getConditions(),
            defaultMatcher = me.defaultMatcher,
            length = paramsInMatchString.length,
            modifiers = me.getCaseInsensitive() ? 'i' : '',
            i, param, matcher;

        if (url === '*') {
            // handle wildcard routes, won't have conditions
            url = url.replace('*', '\\*');
        }
        else {
            for (i = 0; i < length; i++) {
                param = paramsInMatchString[i];

                // Even if the param is a named param, we need to
                // allow "local" overriding.
                if (conditions[param]) {
                    matcher = conditions[param];
                // without colon
                }
                else if (param[0] === ':' && conditions[param.substr(1)]) {
                    matcher = conditions[param.substr(1)];
                }
                else {
                    matcher = defaultMatcher;
                }

                if (Ext.isObject(matcher)) {
                    matcher = matcher.re;
                }

                url = url.replace(new RegExp(param), matcher || defaultMatcher);
            }
        }

        // we want to match the whole string, so include the anchors
        return new RegExp('^' + url + '$', modifiers);
    },

    /**
     * Adds a handler to the {@link #cfg!handlers} stack.
     *
     * @param {Object} handler
     * An object to describe the handler. A handler should define a `fn` and `scope`.
     * If the `fn` is a String, the function will be resolved from the `scope`.
     * @return {Ext.route.Route} this
     */
    addHandler: function(handler) {
        var handlers = this.getHandlers();

        if (!handler.isInstance) {
            handler = new Ext.route.Handler(handler);
        }

        handlers.push(handler);

        return handler.route = this;
    },

    /**
     * Removes a handler from the {@link #cfg!handlers} stack. This normally happens when
     * destroying a class instance.
     *
     * @param {Object/Ext.Base} scope The class instance to match handlers with.
     * @param {Ext.route.Handler} [handler] An optional {@link Ext.route.Handler Handler}
     * to only remove from the array of handlers. If no handler is passed, all handlers
     * will be removed.
     * @return {Ext.route.Route} this
     */
    removeHandler: function(scope, handler) {
        var handlers = this.getHandlers(),
            length = handlers.length,
            newHandlers = [],
            i, item;

        for (i = 0; i < length; i++) {
            item = handlers[i];

            if (handler) {
                if (item !== handler) {
                    newHandlers.push(item);
                }
            }
            else if (item.scope !== scope) {
                newHandlers.push(item);
            }
        }

        this.setHandlers(newHandlers);

        return this;
    },

    /**
     * Clears the last token properties of this route and all handlers.
     */
    clearLastTokens: function() {
        var handlers = this.getHandlers(),
            length = handlers.length,
            i;

        for (i = 0; i < length; i++) {
            handlers[i].lastToken = null;
        }

        this.lastToken = null;
    },

    /**
     * @private
     * @since 6.6.0
     *
     * When a route is exited (no longer recognizes a token in the current hash)
     * we need to clear all last tokens and execute any exit handlers.
     */
    onExit: function() {
        var me = this,
            handlers = me.getHandlers(),
            allowInactive = me.getAllowInactive(),
            length = handlers.length,
            action = new Ext.route.Action({
                urlParams: [me.lastToken]
            }),
            i, handler, scope;

        // Need to reset handlers' `lastToken` so that when a token
        // is added to the document fragment it will not be falsely
        // matched.
        me.clearLastTokens();

        for (i = 0; i < length; i++) {
            handler = handlers[i];

            if (handler.exit) {
                scope = handler.scope;

                if (!allowInactive && scope.isActive && !scope.isActive()) {
                    continue;
                }

                action.action(handler.exit, scope);
            }
        }

        if (Ext.fireEvent('beforerouteexit', action, me) === false) {
            action.destroy();
        }
        else {
            action.run();
        }
    }
});
