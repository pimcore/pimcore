/**
 * The {@link Ext.direct.RemotingProvider RemotingProvider} exposes access to
 * server side methods on the client (a remote procedure call (RPC) type of
 * connection where the client can initiate a procedure on the server).
 * 
 * This allows for code to be organized in a fashion that is maintainable,
 * while providing a clear path between client and server, something that is
 * not always apparent when using URLs.
 * 
 * To accomplish this the server-side needs to describe what classes and methods
 * are available on the client-side. This configuration will typically be
 * outputted by the server-side Ext Direct stack when the API description is built.
 */
Ext.define('Ext.direct.RemotingProvider', {
    extend: 'Ext.direct.JsonProvider',
    alias: 'direct.remotingprovider',

    requires: [
        'Ext.util.MixedCollection',
        'Ext.util.DelayedTask',
        'Ext.direct.Transaction',
        'Ext.direct.RemotingMethod',
        'Ext.direct.Manager'
    ],

    type: 'remoting',

    /**
     * @cfg {Object} actions
     *
     * Object literal defining the server side actions and methods. For example, if
     * the Provider is configured with:
     *
     *      // each property within the 'actions' object represents a server side Class
     *      actions: {
     *          // array of methods in each server side Class to be stubbed out on client
     *          TestAction: [{
     *              name: 'doEcho',   // stub method will be TestAction.doEcho
     *              len:  1,
     *              batched: false    // always send requests immediately for this method
     *          }, {
     *              name: 'multiply', // name of method
     *              len:  2           // The number of parameters that will be used to create an
     *                                // array of data to send to the server side function.
     *          }, {
     *              name: 'doForm',
     *              formHandler: true // tells the client that this method handles form calls
     *          }],
     *          
     *          // These methods will be created in nested namespace TestAction.Foo
     *          'TestAction.Foo': [{
     *              name: 'ordered',  // stub method will be TestAction.Foo.ordered
     *              len:  1
     *          }, {
     *              name: 'noParams', // this method does not accept any parameters
     *              len:  0
     *          }, {
     *              name: 'named',    // stub method will be TestAction.Foo.named
     *              params: ['foo', 'bar']    // parameters are passed by name
     *          }, {
     *              name: 'namedNoStrict',
     *              params: [],       // this method accepts parameters by name
     *              strict: false     // but does not check if they are required
     *                                // and will pass any to the server side
     *          }]
     *      }
     *
     * Note that starting with 4.2, dotted Action names will generate nested objects.
     * If you wish to reverse to previous behavior, set {@link #cfg-disableNestedActions}
     * to `true`.
     *
     * In the following example a *client side* handler is used to call the
     * server side method "multiply" in the server-side "TestAction" Class:
     *
     *      TestAction.multiply(
     *          // pass two arguments to server, so specify len=2
     *          2, 4,
     *          
     *          // callback function after the server is called
     *          //  result: the result returned by the server
     *          //       e: Ext.direct.RemotingEvent object
     *          // success: true or false
     *          // options: options to be applied to method call and passed to callback
     *          function (result, e, success, options) {
     *              var t, action, method;
     *              
     *              t = e.getTransaction();
     *              action = t.action; // server side Class called
     *              method = t.method; // server side method called
     *              
     *              if (e.status) {
     *                  var answer = Ext.encode(result); // 8
     *              }
     *              else {
     *                  var msg = e.message; // failure message
     *              }
     *          },
     *          
     *          // Scope to call the callback in (optional)
     *          window,
     *          
     *          // Options to apply to this method call. This can include
     *          // Ajax.request() options; only `timeout` is supported at this time.
     *          // When timeout is set for a method call, it will be executed immediately
     *          // without buffering.
     *          // The same options object is passed to the callback so it's possible
     *          // to "forward" some data when needed.
     *          {
     *              timeout: 60000, // milliseconds
     *              foo: 'bar'
     *          }
     *      );
     *
     * In the example above, the server side "multiply" function will be passed two
     * arguments (2 and 4). The "multiply" method should return the value 8 which will be
     * available as the `result` in the callback example above. 
     */

    /**
     * @cfg {Boolean} [disableNestedActions=false]
     * In versions prior to 4.2, using dotted Action names was not really meaningful,
     * because it generated flat {@link #cfg-namespace} object with dotted property
     * names. For example, take this API declaration:
     *
     *      {
     *          actions: {
     *              TestAction: [{
     *                  name: 'foo',
     *                  len:  1
     *              }],
     *              'TestAction.Foo' [{
     *                  name: 'bar',
     *                  len: 1
     *              }]
     *          },
     *          namespace: 'MyApp'
     *      }
     *
     * Before 4.2, that would generate the following API object:
     *
     *      window.MyApp = {
     *          TestAction: {
     *              foo: function() { ... }
     *          },
     *          'TestAction.Foo': {
     *              bar: function() { ... }
     *          }
     *      }
     *
     * In Ext JS 4.2, we introduced new namespace handling behavior. Now the same API
     * object will be like this:
     *
     *      window.MyApp = {
     *          TestAction: {
     *              foo: function() { ... },
     *
     *              Foo: {
     *                  bar: function() { ... }
     *              }
     *          }
     *      }
     *
     * Instead of addressing Action methods array-style `MyApp['TestAction.Foo'].bar()`,
     * now it is possible to use object addressing: `MyApp.TestAction.Foo.bar()`.
     *
     * If you find this behavior undesirable, set this config option to `true`.
     */

    /**
     * @cfg {String/Object} namespace
     *
     * Namespace for the Remoting Provider (defaults to `Ext.global`).
     * Explicitly specify the namespace Object, or specify a String to have a
     * {@link Ext#namespace namespace} created implicitly.
     */

    /**
     * @cfg {String} url
     *
     * **Required**. The url to connect to the {@link Ext.direct.Manager} server-side
     * router. 
     */

    /**
     * @cfg {String} [enableUrlEncode=data]
     *
     * Specify which param will hold the arguments for the method.
     */

    /**
     * @cfg {Number/Boolean} enableBuffer
     *
     * `true` or `false` to enable or disable combining of method
     * calls. If a number is specified this is the amount of time in milliseconds
     * to wait before sending a batched request.
     *
     * Calls which are received within the specified timeframe will be
     * concatenated together and sent in a single request, optimizing the
     * application by reducing the amount of round trips that have to be made
     * to the server. To cancel buffering for some particular invocations, pass
     * `timeout` parameter in `options` object for that method call.
     */
    enableBuffer: 10,

    /**
     * @cfg {Number} bufferLimit
     * The maximum number of requests to batch together. By default, an unlimited number
     * of requests will be batched. This option will allow to wait only for a certain
     * number of Direct method calls before dispatching a request to the server, even if
     * {@link #enableBuffer} timeout has not yet expired.
     * 
     * Note that this option does nothing if {@link #enableBuffer} is set to `false`.
     */
    bufferLimit: Number.MAX_VALUE,

    /**
     * @cfg {Number} maxRetries
     *
     * Number of times to re-attempt delivery on failure of a call.
     */
    maxRetries: 1,

    /**
     * @cfg {Number} timeout
     *
     * The timeout to use for each request.
     */

    /**
     * @event beforecall
     * @preventable
     *
     * Fires immediately before the client-side sends off the RPC call. By returning
     * `false` from an event handler you can prevent the call from being made.
     *
     * @param {Ext.direct.RemotingProvider} provider
     * @param {Ext.direct.Transaction} transaction
     * @param {Object} meta The meta data
     */            

    /**
     * @event call
     *
     * Fires immediately after the request to the server-side is sent. This does
     * NOT fire after the response has come back from the call.
     *
     * @param {Ext.direct.RemotingProvider} provider
     * @param {Ext.direct.Transaction} transaction
     * @param {Object} meta The meta data
     */            

    /**
     * @event beforecallback
     * @preventable
     *
     * Fires before callback function is executed. By returning `false` from an event handler
     * you can prevent the callback from executing.
     *
     * @param {Ext.direct.RemotingProvider} provider The provider instance
     * @param {Ext.direct.Event} event Event associated with the callback invocation
     * @param {Ext.direct.Transaction} transaction Transaction for which the callback
     * is about to be fired
     */

    constructor: function(config) {
        var me = this;

        me.callParent([config]);

        me.namespace = (Ext.isString(me.namespace) ? Ext.ns(me.namespace) : me.namespace) ||
                       Ext.global;

        me.callBuffer = [];
    },

    destroy: function() {
        if (this.callTask) {
            this.callTask.cancel();
        }

        this.callParent();
    },

    /**
     * @method connect
     * @inheritdoc
     */
    connect: function() {
        var me = this;

        //<debug>
        if (!me.url) {
            Ext.raise('Error initializing RemotingProvider "' + me.id +
                            '", no url configured.');
        }
        //</debug>

        me.callParent();
    },

    doConnect: function() {
        if (!this.apiCreated) {
            this.initAPI();
            this.apiCreated = true;
        }
    },

    /**
     * Get nested namespace by property.
     *
     * @private
     */
    getNamespace: function(root, action) {
        var parts, ns, i, len;

        root = root || Ext.global;
        parts = action.toString().split('.');

        for (i = 0, len = parts.length; i < len; i++) {
            ns = parts[i];
            root = root[ns];

            if (typeof root === 'undefined') {
                return root;
            }
        }

        return root;
    },

    /**
     * Create nested namespaces. Unlike {@link Ext#ns} this method supports
     * nested objects as root of the namespace, not only Ext.global (window).
     *
     * @private
     */
    createNamespaces: function(root, action) {
        var parts, ns, i, len;

        root = root || Ext.global;
        parts = action.toString().split('.');

        for (i = 0, len = parts.length; i < len; i++) {
            ns = parts[i];

            root[ns] = root[ns] || {};
            root = root[ns];
        }

        return root;
    },

    /**
     * Initialize the API
     *
     * @private
     */
    initAPI: function() {
        var me = this,
            actions = me.actions,
            namespace = me.namespace,
            Manager = Ext.direct.Manager,
            action, cls, methods, i, len, method, handler;

        for (action in actions) {
            if (actions.hasOwnProperty(action)) {
                if (me.disableNestedActions) {
                    cls = namespace[action];

                    if (!cls) {
                        cls = namespace[action] = {};
                    }
                }
                else {
                    cls = me.getNamespace(namespace, action);

                    if (!cls) {
                        cls = me.createNamespaces(namespace, action);
                    }
                }

                methods = actions[action];

                for (i = 0, len = methods.length; i < len; ++i) {
                    method = new Ext.direct.RemotingMethod(methods[i]);
                    cls[method.name] = handler = me.createHandler(action, method);

                    Manager.registerMethod(handler.$name, handler);
                }
            }
        }
    },

    /**
     * Create a handler function for a direct call.
     *
     * @param {String} action The action the call is for
     * @param {Object} method The details of the method
     *
     * @return {Function} A JS function that will kick off the call
     *
     * @private
     */
    createHandler: function(action, method) {
        var me = this,
            handler;

        handler = function() {
            me.invokeFunction(action, method, Array.prototype.slice.call(arguments, 0));
        };

        handler.name = handler.$name = action + '.' + method.name;
        handler.$directFn = true;

        handler.directCfg = handler.$directCfg = {
            action: action,
            method: method
        };

        return handler;
    },

    /**
     * Invoke a Direct function call
     *
     * @param {String} action The action being executed
     * @param {Object} method The method being executed
     * @param {Object} args Transaction arguments
     *
     * @private
     */
    invokeFunction: function(action, method, args) {
        var me = this,
            transaction, form, isUpload, postParams;

        transaction = me.configureTransaction(action, method, args);

        if (me.fireEvent('beforecall', me, transaction, method) !== false) {
            Ext.direct.Manager.addTransaction(transaction);

            if (transaction.isForm) {
                form = transaction.form;

                /* eslint-disable-next-line max-len */
                isUpload = String(form.getAttribute("enctype")).toLowerCase() === 'multipart/form-data';

                postParams = {
                    extTID: transaction.id,
                    extAction: action,
                    extMethod: method.name,
                    extType: 'rpc',
                    extUpload: String(isUpload)
                };

                if (transaction.metadata) {
                    postParams.extMetadata = Ext.JSON.encode(transaction.metadata);
                }

                Ext.apply(transaction, {
                    form: form,
                    isUpload: isUpload,
                    params: postParams
                });
            }

            me.queueTransaction(transaction);
            me.fireEvent('call', me, transaction, method);
        }
    },

    /**
     * Configure a transaction for a Direct request
     *
     * @param {String} action The action being executed
     * @param {Object} method The method being executed
     * @param {Array} args Method invocation arguments
     * @param {Boolean} isForm True for a form submit
     *
     * @return {Object} Transaction object
     *
     * @private
     */
    configureTransaction: function(action, method, args, isForm) {
        var data, cb, scope, options, params;

        data = method.getCallData(args);

        cb = data.callback;
        scope = data.scope;
        options = data.options;

        //<debug>
        if (cb && !Ext.isFunction(cb)) {
            Ext.raise("Callback argument is not a function " +
                            "for Ext Direct method " +
                            action + "." + method.name);
        }
        //</debug>

        // Callback might be unspecified for a notification
        // that does not expect any return value
        cb = cb && scope ? cb.bind(scope) : cb;

        params = Ext.apply({}, {
            provider: this,
            args: args,
            action: action,
            method: method.name,
            form: data.form,
            data: data.data,
            metadata: data.metadata,
            callbackOptions: options,
            callback: cb,
            isForm: !!method.formHandler,
            disableBatching: method.disableBatching
        });

        if (options && options.timeout != null) {
            params.timeout = options.timeout;
        }

        return new Ext.direct.Transaction(params);
    },

    /**
     * Add a new transaction to the queue
     *
     * @param {Ext.direct.Transaction} transaction The transaction
     *
     * @private
     */
    queueTransaction: function(transaction) {
        var me = this,
            callBuffer = me.callBuffer,
            enableBuffer = me.enableBuffer;

        if (transaction.isForm || enableBuffer === false || transaction.disableBatching ||
            transaction.timeout != null) {
            me.sendTransaction(transaction);

            return;
        }

        callBuffer.push(transaction);

        if (enableBuffer && callBuffer.length < me.bufferLimit) {
            if (!me.callTask) {
                me.callTask = new Ext.util.DelayedTask(me.combineAndSend, me);
            }

            me.callTask.delay(Ext.isNumber(enableBuffer) ? enableBuffer : 10);
        }
        else {
            me.combineAndSend();
        }
    },

    /**
     * Combine any buffered requests and send them off
     *
     * @private
     */
    combineAndSend: function() {
        var me = this,
            buffer = me.callBuffer,
            len = buffer.length;

        if (len > 0) {
            me.sendTransaction(len === 1 ? buffer[0] : buffer);
            me.callBuffer = [];
        }
    },

    /**
     * Create an Ajax request out of transaction and send it to the server
     *
     * @param {Object/Array} transaction The transaction(s) to send
     *
     * @private
     */
    sendTransaction: function(transaction) {
        var me = this,
            request, callData, params,
            enableUrlEncode = me.enableUrlEncode,
            payload, i, len;

        request = {
            url: me.url,
            callback: me.onData,
            scope: me,
            transaction: transaction,
            headers: me.getHeaders()
        };

        // Explicitly specified timeout for Ext Direct call overrides defaults
        if (transaction.timeout != null) {
            request.timeout = transaction.timeout;
        }
        else if (me.timeout != null) {
            request.timeout = me.timeout;
        }

        if (transaction.isForm) {
            Ext.apply(request, {
                params: transaction.params,
                form: transaction.form,
                isUpload: transaction.isUpload
            });
        }
        else {
            if (Ext.isArray(transaction)) {
                callData = [];

                for (i = 0, len = transaction.length; i < len; ++i) {
                    payload = me.getPayload(transaction[i]);
                    callData.push(payload);
                }
            }
            else {
                callData = me.getPayload(transaction);
            }

            if (enableUrlEncode) {
                params = {};
                /* eslint-disable-next-line max-len */
                params[Ext.isString(enableUrlEncode) ? enableUrlEncode : 'data'] = Ext.encode(callData);
                request.params = params;
            }
            else {
                request.jsonData = callData;
            }
        }

        return me.sendAjaxRequest(request);
    },

    /**
     * Gets the Ajax call info for a transaction
     *
     * @param {Ext.direct.Transaction} transaction The transaction
     *
     * @return {Object} The call params
     *
     * @private
     */
    getPayload: function(transaction) {
        var result = {
            action: transaction.action,
            method: transaction.method,
            data: transaction.data,
            type: 'rpc',
            tid: transaction.id
        };

        if (transaction.metadata) {
            result.metadata = transaction.metadata;
        }

        return result;
    },

    /**
     * React to the ajax request being completed
     *
     * @private
     */
    onData: function(options, success, response) {
        var me = this,
            i, len, events, event, transaction, transactions;

        if (me.destroying || me.destroyed) {
            return;
        }

        // Success in this context means lack of communication failure,
        // i.e. that we have successfully connected to the server and
        // received a valid HTTP response. This does not imply that
        // the server returned valid JSON data, or that individual
        // function invocations were also successful.
        events = success && me.createEvents(response);

        // Redefine success: if parsing failed, createEvents() will return
        // only one event object, and it will be a parsing error exception.
        success = events && events.length && !events[0].parsingError;

        if (success) {
            for (i = 0, len = events.length; i < len; ++i) {
                event = events[i];

                me.fireEvent('data', me, event);
                transaction = me.getTransaction(event);

                if (transaction) {
                    if (me.fireEvent('beforecallback', me, event, transaction) !== false) {
                        me.runCallback(transaction, event, true);
                    }

                    Ext.direct.Manager.removeTransaction(transaction);
                }
            }
        }
        else {
            transactions = [].concat(options.transaction);

            event = events[0] ||
                new Ext.direct.ExceptionEvent({
                    data: null,
                    transaction: transaction,
                    code: Ext.direct.Manager.exceptions.TRANSPORT,
                    message: 'Unable to connect to the server.',
                    xhr: response
                });

            for (i = 0, len = transactions.length; i < len; ++i) {
                transaction = me.getTransaction(transactions[i]);

                if (transaction && transaction.retryCount < me.maxRetries) {
                    transaction.retry();
                }
                else {
                    me.fireEvent('data', me, event);
                    me.fireEvent('exception', me, event);

                    /* eslint-disable-next-line max-len */
                    if (transaction && me.fireEvent('beforecallback', me, event, transaction) !== false) {
                        me.runCallback(transaction, event, false);
                    }

                    Ext.direct.Manager.removeTransaction(transaction);
                }
            }
        }

        me.callParent([options, success, response]);
    },

    /**
     * Get transaction from XHR options
     *
     * @param {Object} options The options sent to the Ajax request
     *
     * @return {Ext.direct.Transaction} The transaction, null if not found
     *
     * @private
     */
    getTransaction: function(options) {
        return options && options.tid ? Ext.direct.Manager.getTransaction(options.tid) : null;
    },

    /**
     * Run any callbacks related to the transaction.
     *
     * @param {Ext.direct.Transaction} transaction The transaction
     * @param {Ext.direct.Event} event The event
     *
     * @private
     */
    runCallback: function(transaction, event) {
        var success = !!event.status,
            funcName = success ? 'success' : 'failure',
            callback, options, result;

        if (transaction && transaction.callback) {
            callback = transaction.callback;
            options = transaction.callbackOptions;
            result = typeof event.result !== 'undefined' ? event.result : event.data;

            if (Ext.isFunction(callback)) {
                callback(result, event, success, options);
            }
            else {
                Ext.callback(callback[funcName], callback.scope, [result, event, success, options]);
                Ext.callback(callback.callback, callback.scope, [result, event, success, options]);
            }
        }
    },

    inheritableStatics: {
        /**
         * @private
         * @static
         * @inheritable
         */
        checkConfig: function(config) {
            // RemotingProvider needs service URI,
            // type and array of Actions
            return config && config.type === 'remoting' &&
                   config.url && Ext.isArray(config.actions);
        }
    }
});
