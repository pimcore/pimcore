/**
 * This class is used to send requests to the server using {@link Ext.direct.Manager Ext Direct}.
 * When a request is made, the transport mechanism is handed off to the appropriate
 * {@link Ext.direct.RemotingProvider Provider} to complete the call.
 *
 * # Specifying the functions
 *
 * This proxy expects Direct remoting method to be passed in order to be able to complete requests,
 * one Direct function per CRUD method. This is done via {@link #api} configuration:
 *
 *      api: {
 *          read: 'MyApp.readRecords',
 *          create: 'MyApp.createRecords',
 *          update: 'MyApp.updateRecords',
 *          destroy: 'MyApp.destroyRecords'
 *      }
 *
 * You can also use a `prefix` config to avoid duplicating full namespaces for Direct functions:
 *
 *      api: {
 *          prefix: 'MyApp',
 *          read: 'readRecords',
 *          create: 'createRecords',
 *          update: 'updateRecords',
 *          destroy: 'destroyRecords'
 *      }
 *
 * The preferred way is to specify function names to allow late resolution, however you can
 * pass function references instead if desired:
 *
 *      api: {
 *          read: MyApp.readRecords,
 *          create: MyApp.createRecords,
 *          update: MyApp.updateRecords,
 *          destroy: MyApp.destroyRecords
 *      }
 *
 * This method of configuring API is not recommended because this way the Direct functions
 * need to be created very early in the application lifecycle, long before
 * {@link Ext.app.Application} instance is initialized.
 *
 * You can also use the {@link #directFn} configuration instead of {@link #api}. This will use
 * the same Direct function for all types of requests.
 *
 * # Server API
 *
 * The server side methods are expected to conform to the following calling conventions:
 *
 * ## `read`
 *
 * Accept one argument which is either named arguments in an object (default), or an array
 * of values depending on the {@link #paramsAsHash} configuration. Return an array of records
 * or an object with format recognizable by the configured {@link Ext.data.reader.Reader}
 * instance.
 *
 * Example {@link Ext.direct.RemotingProvider#cfg-actions Direct API declaration}:
 *
 *      actions: {
 *          MyApp: [{
 *              name: 'readRecords',
 *              params: [],
 *              strict: false
 *          }]
 *      }
 *
 * Example function invocation:
 *
 *      MyApp.readRecords(
 *          {
 *              start: 0,
 *              limit: 10
 *          },
 *          // Results are passed to the callback function
 *          function(records) {
 *              console.log(records);
 *              // Logs:  [{ id: 'r0', text: 'foo' }, { id: 'r1', text: 'bar' }]
 *          }
 *      );
 *
 * ## `create`
 *
 * Accept one ordered argument which is either an object with data for the new record,
 * or an array of objects for multiple records. Return an array of identifiers for actually
 * created records. See {@link Ext.data.Model#clientIdProperty} for more information.
 *
 * Example {@link Ext.direct.RemotingProvider#cfg-actions Direct API declaration}:
 *
 *      actions: [
 *          MyApp: [{
 *              name: 'createRecords',
 *              len: 1
 *          }]
 *      }
 *
 * Example function invocation:
 *
 *      MyApp.createRecords(
 *          [
 *              { id: 0, text: 'foo' },
 *              { id: 1, text: 'bar' }
 *          ],
 *          // Results are passed to the callback function
 *          function(records) {
 *              console.log(records);
 *              // Logs: [{ clientId: 0, id: 'r0' }, { clientId: 1, id: 'r1' }]
 *          }
 *      );
 *
 * ## `update`
 *
 * Accept one ordered argument which is either an object with updated data and valid
 * record identifier, or an array of objects for multiple records. Return an array of
 * objects with updated record data.
 *
 * Example {@link Ext.direct.RemotingProvider#cfg-actions Direct API declaration}:
 *
 *      actions: [
 *          MyApp: [{
 *              name: 'updateRecords',
 *              len: 1
 *          }]
 *      }
 *
 * Example function invocation:
 *
 *      MyApp.updateRecords(
 *          [
 *              { id: 'r0', text: 'blerg' },
 *              { id: 'r1', text: 'throbbe' }
 *          ],
 *          // Results are passed to the callback function
 *          function(records) {
 *              console.log(records);
 *              // Logs: [{ id: 'r0', text: 'blerg' }, { id: 'r1', text: 'throbbe }]
 *          }
 *      );
 *
 * ## `destroy`
 *
 * Accept one ordered argument which is an array of record identifiers to be deleted.
 * Return an object with at least one {@link Ext.data.reader.Json#successProperty}
 * property set to `true` or `false`, with more optional properties recognizable by configured
 * {@link Ext.data.reader.Reader} instance.
 *
 * Example {@link Ext.direct.RemotingProvider#cfg-actions Direct API declaration}:
 *
 *      actions: [
 *          MyApp: [{
 *              name: 'destroyRecords',
 *              len: 1
 *          }]
 *      }
 *
 * Example function invocation:
 *
 *      MyApp.destroyRecords(
 *          [
 *              { id: 'r0' },
 *              { id: 'r1' }
 *          ],
 *          // Results are passed to the callback function
 *          function(result) {
 *              // Default successProperty is `success`
 *              if (!result.success) {
 *                  // Handle the error
 *              }
 *          }
 *      );
 *
 * ## Read method parameters
 *
 * Direct proxy provides options to help configure which parameters will be sent to the server
 * for Read operations. By setting the {@link #paramsAsHash} option to `true`, the proxy will
 * send an object literal containing each of the passed parameters. This is the default. When
 * {@link #paramsAsHash} is set to `false`, Proxy will pass the Read function an array of values
 * instead of an object, with the order determined by {@link #paramOrder} value.
 *
 * Setting {@link #paramOrder} to any value other than `undefined` will automatically reset
 * {@link #paramsAsHash} to `false`.
 *
 * # Example Usage
 *
 *      Ext.define('User', {
 *          extend: 'Ext.data.Model',
 *          fields: ['firstName', 'lastName']
 *      });
 *      
 *      Ext.define('Users', {
 *          extend: 'Ext.data.Store',
 *          model: 'User',
 *          proxy: {
 *              type: 'direct',
 *              directFn: 'MyApp.getUsers',
 *              // Tells the proxy to pass `start` and `limit` as two by-position arguments:
 *              paramOrder: 'start,limit'
 *          }
 *      });
 *      
 *      var store = new Users();
 *      store.load();
 */
Ext.define('Ext.data.proxy.Direct', {
    /* Begin Definitions */

    extend: 'Ext.data.proxy.Server',
    alternateClassName: 'Ext.data.DirectProxy',

    alias: 'proxy.direct',

    requires: ['Ext.direct.Manager'],

    /* End Definitions */

    /**
     * @cfg url
     * @hide
     */

    config: {
        /**
         * @cfg {String/String[]} paramOrder
         * A list of params to be passed to server side Read function. Specify the params
         * in the order in which they must be executed on the server-side as either (1)
         * an Array of String values, or (2) a String of params delimited by either
         * whitespace, comma, or pipe. For example, any of the following would be
         * acceptable:
         *
         *     paramOrder: ['param1','param2','param3']
         *     paramOrder: 'param1 param2 param3'
         *     paramOrder: 'param1,param2,param3'
         *     paramOrder: 'param1|param2|param'
         */
        paramOrder: undefined,

        /**
         * @cfg {Boolean} paramsAsHash
         * Send Read function parameters as a collection of named arguments. Providing a
         * {@link #paramOrder} nullifies this configuration.
         */
        paramsAsHash: true,

        /**
         * @cfg {Function/String} directFn
         * Function to call when executing a request. `directFn` is a simple alternative
         * to defining the api configuration parameter for Stores which will not
         * implement a full CRUD api. The `directFn` may also be a string reference to
         * the fully qualified name of the function, for example:
         * `'MyApp.company.GetProfile'`. This can be useful when using dynamic loading.
         * The string will be resolved before calling the function for the first time.
         */
        directFn: undefined,

        /**
         * @cfg {Object} api
         * The same as {@link Ext.data.proxy.Server#api}, however instead of providing
         * urls you should provide a Direct function name for each CRUD method.
         *
         * Instead of providing fully qualified names for each function, you can use
         * `prefix` property to provide a common prefix for all functions:
         *
         *   api: {
         *       prefix: 'MyApp',
         *       read: 'readRecords',
         *       create: 'createRecords',
         *       update: 'updateRecords',
         *       destroy: 'destroyRecords'
         *   }
         *
         * This way function names will be resolved to `'MyApp.readRecords'`, 
         * `'MyApp.createRecords'`, etc. Note that using `prefix` and fully qualified
         * function names is **not** supported, and prefix will be used for every
         * function name when configured.
         *
         * See also {@link #directFn}.
         */
        api: undefined,

        /**
         * @cfg {Object/Array} metadata
         * Optional set of fixed parameters to send with every Proxy request, similar to
         * {@link #extraParams} but available with all CRUD requests. Also unlike
         * {@link #extraParams}, metadata is not mixed with the ordinary data but sent
         * separately in the data packet.
         * You may need to update your server side Ext Direct stack to use this feature.
         */
        metadata: undefined
    },

    /**
     * @private
     */
    paramOrderRe: /[\s,|]/,

    constructor: function(config) {
        this.callParent([config]);
        this.canceledOperations = {};
    },

    applyParamOrder: function(paramOrder) {
        if (Ext.isString(paramOrder)) {
            paramOrder = paramOrder.split(this.paramOrderRe);
        }

        return paramOrder;
    },

    updateApi: function() {
        this.methodsResolved = false;
    },

    updateDirectFn: function() {
        this.methodsResolved = false;
    },

    resolveMethods: function() {
        var me = this,
            fn = me.getDirectFn(),
            api = me.getApi(),
            method;

        if (fn) {
            me.setDirectFn(method = Ext.direct.Manager.parseMethod(fn));

            if (!Ext.isFunction(method)) {
                Ext.raise('Cannot resolve directFn ' + fn);
            }
        }

        if (api) {
            api = Ext.direct.Manager.resolveApi(api, me);
            me.setApi(api);
        }

        me.methodsResolved = true;
    },

    doRequest: function(operation) {
        var me = this,
            writer, request, action, params, args, api, fn;

        if (!me.methodsResolved) {
            me.resolveMethods();
        }

        request = me.buildRequest(operation);
        action = request.getAction();
        api = me.getApi();

        if (api) {
            fn = api[action];
        }

        fn = fn || me.getDirectFn();

        //<debug>
        if (!fn || !fn.directCfg) {
            Ext.raise({
                msg: 'No Ext Direct function specified for Direct proxy "' + action + '" operation',
                proxy: me
            });
        }

        // This might lead to exceptions so bail out early
        if (!me.paramOrder && fn.directCfg.method.len > 1) {
            Ext.raise({
                msg: 'Incorrect parameters for Direct proxy "' + action + '" operation',
                proxy: me
            });
        }
        //</debug>

        writer = me.getWriter();

        if (writer && operation.allowWrite()) {
            request = writer.write(request);
        }

        // The weird construct below is due to historical way of handling extraParams;
        // they were mixed in with request data in ServerProxy.buildRequest() and were
        // inseparable after that point. This does not work well with CUD operations
        // so instead of using potentially poisoned request params we took the raw
        // JSON data as Direct function argument payload (but only for CUD!). A side
        // effect of that was that the request metadata (extraParams) was only available
        // for read operations.
        // We keep this craziness for backwards compatibility.
        if (action === 'read') {
            params = request.getParams();
        }
        else {
            params = request.getJsonData();
        }

        args = fn.directCfg.method.getArgs({
            params: params,
            allowSingle: writer.getAllowSingle(),
            paramOrder: me.getParamOrder(),
            paramsAsHash: me.getParamsAsHash(),
            paramsAsArray: true,
            metadata: me.getMetadata(),
            callback: me.createRequestCallback(request, operation),
            scope: me
        });

        request.setConfig({
            args: args,
            directFn: fn
        });

        fn.apply(window, args);

        // Store expects us to return something to indicate that the request
        // is pending; not doing so will make a buffered Store repeat the
        // requests over and over.
        return request;
    },

    /**
     * Aborts a running request by operation.
     *
     * @param {Ext.data.operation.Operation} operation The operation to abort. This parameter
     * is mandatory.
     */
    abort: function(operation) {
        var id;

        // Assume this can be called with request instead of operation, a la Ajax proxy
        if (operation && operation.isDataRequest) {
            operation = operation.getOperation();
        }

        // Check definedness again, the above could have returned null
        if (operation && operation.isOperation) {
            id = operation.id;
        }

        // We cannot abort a running request but we can ignore the data when it comes back.
        if (id != null) {
            this.canceledOperations[id] = true;
        }
    },

    /**
     * @method applyEncoding
     * @inheritdoc
     */
    applyEncoding: Ext.identityFn,

    createRequestCallback: function(request, operation) {
        var me = this;

        return function(data, event) {
            if (!me.canceledOperations[operation.id]) {
                me.processResponse(event.status, operation, request, event);
            }

            delete me.canceledOperations[operation.id];
        };
    },

    /**
     * @method extractResponseData
     * @inheritdoc
     */
    extractResponseData: function(response) {
        return Ext.isDefined(response.result) ? response.result : response.data;
    },

    /**
     * @method setException
     * @inheritdoc
     */
    setException: function(operation, response) {
        operation.setException(response.message);
    },

    /**
     * @method buildUrl
     * @inheritdoc
     */
    buildUrl: function() {
        return '';
    }
});
