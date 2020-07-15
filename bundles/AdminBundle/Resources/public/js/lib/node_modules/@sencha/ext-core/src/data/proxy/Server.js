/**
 * ServerProxy is a superclass of {@link Ext.data.proxy.JsonP JsonPProxy} and
 * {@link Ext.data.proxy.Ajax AjaxProxy}, and would not usually be used directly.
 * @protected
 */
Ext.define('Ext.data.proxy.Server', {
    extend: 'Ext.data.proxy.Proxy',
    alias: 'proxy.server',
    alternateClassName: 'Ext.data.ServerProxy',
    uses: ['Ext.data.Request'],

    isRemote: true,

    config: {
        /**
         * @cfg {String} url
         * The URL from which to request the data object.
         */
        url: '',

        /**
         * @cfg {String} [pageParam="page"]
         * The name of the 'page' parameter to send in a request. Defaults to 'page'. Set this to
         * `''` if you don't want to send a page parameter.
         */
        pageParam: 'page',

        /**
         * @cfg {String} [startParam="start"]
         * The name of the 'start' parameter to send in a request. Defaults to 'start'. Set this to
         * `''` if you don't want to send a start parameter.
         */
        startParam: 'start',

        /**
         * @cfg {String} [limitParam="limit"]
         * The name of the 'limit' parameter to send in a request. Defaults to 'limit'. Set this to
         * `''` if you don't want to send a limit parameter.
         */
        limitParam: 'limit',

        /**
         * @cfg {String} [groupParam="group"]
         * The name of the 'group' parameter to send in a request. Defaults to 'group'. Set this to
         * `''` if you don't want to send a group parameter.
         */
        groupParam: 'group',

        /**
         * @cfg {String} [groupDirectionParam="groupDir"]
         * The name of the direction parameter to send in a request. **This is only used when
         * simpleGroupMode is set to true.**
         * If this is set to the same value as the {@link #groupParam}, then the group property
         * name *and* direction of each grouper is passed as a single, space separated parameter,
         * looking like a database `group by` specification.
         *
         * So if there are multiple groupers, the single group parameter will look like this:
         *
         *     ?group=name%20ASC&group=age%20DESC
         */
        groupDirectionParam: 'groupDir',

        /**
         * @cfg {String} [sortParam="sort"]
         * The name of the 'sort' parameter to send in a request. Defaults to 'sort'. Set this to
         * `''` if you don't want to send a sort parameter.
         */
        sortParam: 'sort',

        /**
         * @cfg {String} [filterParam="filter"]
         * The name of the 'filter' parameter to send in a request. Defaults to 'filter'. Set this
         * to `''` if you don't want to send a filter parameter.
         */
        filterParam: 'filter',

        /**
         * @cfg {String} [directionParam="dir"]
         * The name of the direction parameter to send in a request. **This is only used when
         * simpleSortMode is set to true.**
         * 
         * If this is set to the same value as the {@link #sortParam}, then the sort property name
         * *and* direction of each sorter is passed as a single, space separated parameter, looking
         * like a database `order by` specification.
         *
         * So if there are multiple sorters, the single sort parameter will look like this:
         *
         *     ?sort=name%20ASC&sort=age%20DESC
         */
        directionParam: 'dir',

        /**
         * @cfg {String} [idParam="id"]
         * The name of the parameter which carries the id of the entity being operated upon.
         */
        idParam: 'id',

        /**
         * @cfg {Boolean} [simpleSortMode=false]
         * Enabling simpleSortMode in conjunction with remoteSort will send the sorted field names
         * in the parameter named by {@link #sortParam}, and the directions for each sorted field
         * in a parameter named by {@link #directionParam}.
         *
         * In the simplest case, with one Sorter, this will result in HTTP parameters like this:
         *
         *     ?sort=name&dir=ASC
         *
         * If there are multiple sorters, the parameters will be encoded like this:
         *
         *     ?sort=name&sort=age&dir=ASC&dir=DESC
         */
        simpleSortMode: false,

        /**
         * @cfg {Boolean} [simpleGroupMode=false]
         * Enabling simpleGroupMode in conjunction with remoteGroup will only send one group
         * property and a direction when a remote group is requested. The
         * {@link #groupDirectionParam} and {@link #groupParam} will be sent with the property name
         * and either 'ASC' or 'DESC'.
         */
        simpleGroupMode: false,

        /**
         * @cfg {Boolean} [noCache=true]
         * Disable caching by adding a unique parameter name to the request. Set to false to allow
         * caching. Defaults to true.
         */
        noCache: true,

        /**
         * @cfg {String} [cacheString="_dc"]
         * The name of the cache param added to the url when using noCache. Defaults to "_dc".
         */
        cacheString: "_dc",

        /**
         * @cfg {Number} timeout
         * The number of milliseconds to wait for a response. Defaults to 30000 milliseconds
         * (30 seconds).
         */
        timeout: 30000,

        /**
         * @cfg {Object} api
         * Specific urls to call on CRUD action methods "create", "read", "update" and "destroy".
         * Defaults to:
         *
         *     api: {
         *         create  : undefined,
         *         read    : undefined,
         *         update  : undefined,
         *         destroy : undefined
         *     }
         *
         * The url is built based upon the action being executed [create|read|update|destroy] using
         * the commensurate {@link #api} property, or if undefined default to the configured
         * {@link Ext.data.Store}.{@link Ext.data.proxy.Server#url url}.
         *
         * For example:
         *
         *     api: {
         *         create  : '/controller/new',
         *         read    : '/controller/load',
         *         update  : '/controller/update',
         *         destroy : '/controller/destroy_action'
         *     }
         *
         * If the specific URL for a given CRUD action is undefined, the CRUD action request will
         * be directed to the configured {@link Ext.data.proxy.Server#url url}.
         */
        api: {
            create: undefined,
            read: undefined,
            update: undefined,
            destroy: undefined
        },

        /**
         * @cfg {Object} extraParams
         * Extra parameters that will be included on every request. Individual requests with params
         * of the same name will override these params when they are in conflict.
         */
        extraParams: {}
    },

    /**
     * @event exception
     * Fires when the server returns an exception. This event may also be listened
     * to in the event that a request has timed out or has been aborted.
     * @param {Ext.data.proxy.Proxy} this
     * @param {Ext.data.Response} response The response that was received
     * @param {Ext.data.operation.Operation} operation The operation that triggered the request
     */

    // in a ServerProxy all four CRUD operations are executed in the same manner, so we delegate to
    // doRequest in each case
    create: function() {
        return this.doRequest.apply(this, arguments);
    },

    read: function() {
        return this.doRequest.apply(this, arguments);
    },

    update: function() {
        return this.doRequest.apply(this, arguments);
    },

    erase: function() {
        return this.doRequest.apply(this, arguments);
    },

    /**
     * Sets a value in the underlying {@link #extraParams}.
     * @param {String} name The key for the new value
     * @param {Object} value The value
     */
    setExtraParam: function(name, value) {
        var extraParams = this.getExtraParams();

        extraParams[name] = value;

        this.fireEvent('extraparamschanged', extraParams);
    },

    updateExtraParams: function(newExtraParams, oldExtraParams) {
        this.fireEvent('extraparamschanged', newExtraParams);
    },

    /**
     * Creates an {@link Ext.data.Request Request} object from
     * {@link Ext.data.operation.Operation Operation}.
     *
     * This gets called from doRequest methods in subclasses of Server proxy.
     * 
     * @param {Ext.data.operation.Operation} operation The operation to execute
     * @return {Ext.data.Request} The request object
     */
    buildRequest: function(operation) {
        var me = this,
            initialParams = Ext.apply({}, operation.getParams()),
            // Clone params right now so that they can be mutated at any point further down the
            // call stack
            params = Ext.applyIf(initialParams, me.getExtraParams() || {}),
            request,
            operationId,
            idParam;

        // copy any sorters, filters etc into the params so they can be sent over the wire
        Ext.applyIf(params, me.getParams(operation));

        // Set up the entity id parameter according to the configured name.
        // This defaults to "id". But TreeStore has a "nodeParam" configuration which
        // specifies the id parameter name of the node being loaded.
        operationId = operation.getId();
        idParam = me.getIdParam();

        if (operationId !== undefined && params[idParam] === undefined) {
            params[idParam] = operationId;
        }

        request = new Ext.data.Request({
            params: params,
            action: operation.getAction(),
            records: operation.getRecords(),
            url: operation.getUrl(),
            operation: operation,

            // this is needed by JsonSimlet in order to properly construct responses for
            // requests from this proxy
            proxy: me
        });

        request.setUrl(me.buildUrl(request));

        /*
         * Save the request on the Operation. Operations don't usually care about Request and
         * Response data, but in the ServerProxy and any of its subclasses we add both request
         * and response as they may be useful for further processing
         */
        operation.setRequest(request);

        return request;
    },

    /**
     * Processes response, which may involve updating or committing records, each of which
     * will inform the owning stores and their interested views. Finally, we may perform
     * an additional layout if the data shape has changed. 
     *
     * @protected
     */
    processResponse: function(success, operation, request, response) {
        var me = this,
            exception, reader, resultSet, meta, destroyOp;

        // Async callback could have landed at any time, including during and after
        // destruction. We don't want to unravel the whole response chain in such case.
        if (me.destroying || me.destroyed) {
            return;
        }

        // Processing a response may involve updating or committing many records
        // each of which will inform the owning stores, which will ultimately
        // inform interested views which will most likely have to do a layout
        // assuming that the data shape has changed.
        // Bracketing the processing with this event gives owning stores the ability
        // to fire their own beginupdate/endupdate events which can be used by interested
        // views to suspend layouts.
        me.fireEvent('beginprocessresponse', me, response, operation);

        if (success === true) {
            reader = me.getReader();

            if (response.status === 204) {
                resultSet = reader.getNullResultSet();
            }
            else {
                resultSet = reader.read(me.extractResponseData(response), {
                    // If we're doing an update, we want to construct the models ourselves.
                    recordCreator: operation.getRecordCreator() ||
                    reader.defaultRecordCreatorFromServer
                });
            }

            if (!operation.$destroyOwner) {
                operation.$destroyOwner = me;
                destroyOp = true;
            }

            operation.process(resultSet, request, response);
            exception = !operation.wasSuccessful();
        }
        else {
            me.setException(operation, response);
            exception = true;
        }

        // It is possible that exception callback destroyed the store and owning proxy,
        // in which case we can't do nothing except punt.
        if (me.destroyed) {
            if (!operation.destroyed && destroyOp && operation.$destroyOwner === me) {
                operation.destroy();
            }

            return;
        }

        if (exception) {
            me.fireEvent('exception', me, response, operation);
        }
        // If a JsonReader detected metadata, process it now.
        // This will fire the 'metachange' event which the Store processes to fire its own
        // 'metachange'
        else {
            meta = resultSet.getMetadata();

            if (meta) {
                me.onMetaChange(meta);
            }
        }

        // Ditto
        if (me.destroyed) {
            if (!operation.destroyed && destroyOp && operation.$destroyOwner === me) {
                operation.destroy();
            }

            return;
        }

        me.afterRequest(request, success);

        // Tell owning store processing has finished.
        // It will fire its endupdate event which will cause interested views to 
        // resume layouts.
        me.fireEvent('endprocessresponse', me, response, operation);

        if (!operation.destroyed && destroyOp && operation.$destroyOwner === me) {
            operation.destroy();
        }
    },

    /**
     * Sets up an exception on the operation
     * @private
     * @param {Ext.data.operation.Operation} operation The operation
     * @param {Object} response The response
     */
    setException: function(operation, response) {
        operation.setException({
            status: response.status,
            statusText: response.statusText,
            response: response
        });
    },

    /**
     * @method
     * Template method to allow subclasses to specify how to get the response for the reader.
     * @template
     * @private
     * @param {Object} response The server response
     * @return {Object} The response data to be used by the reader
     */
    extractResponseData: Ext.identityFn,

    /**
     * Encode any values being sent to the server. Can be overridden in subclasses.
     * @protected
     * @param {Array} value An array of sorters/filters.
     * @return {Object} The encoded value
     */
    applyEncoding: function(value) {
        return Ext.encode(value);
    },

    /**
     * Encodes the array of {@link Ext.util.Sorter} objects into a string to be sent in the request
     * url. By default, this simply JSON-encodes the sorter data
     * @param {Ext.util.Sorter[]} sorters The array of {@link Ext.util.Sorter Sorter} objects
     * @param {Boolean} [preventArray=false] Prevents the items from being output as an array.
     * @return {String} The encoded sorters
     */
    encodeSorters: function(sorters, preventArray) {
        var out = [],
            length = sorters.length,
            i;

        for (i = 0; i < length; i++) {
            out[i] = sorters[i].serialize();
        }

        return this.applyEncoding(preventArray ? out[0] : out);
    },

    /**
     * Encodes the array of {@link Ext.util.Filter} objects into a string to be sent in the request
     * url. By default, this simply JSON-encodes the filter data
     * @param {Ext.util.Filter[]} filters The array of {@link Ext.util.Filter Filter} objects
     * @return {String} The encoded filters
     */
    encodeFilters: function(filters) {
        var out = [],
            length = filters.length,
            encode, i;

        for (i = 0; i < length; i++) {
            encode |= filters[i].serializeTo(out);
        }

        // If any Filters return Objects encapsulating their full state, then the parameters
        // needs JSON encoding.
        return encode ? this.applyEncoding(out) : out;
    },

    /**
     * @private
     * Copy any sorters, filters etc into the params so they can be sent over the wire
     */
    getParams: function(operation) {
        if (!operation.isReadOperation) {
            return {};
        }

        /* eslint-disable-next-line vars-on-top */
        var me = this,
            params = {},
            grouper = operation.getGrouper(),
            sorters = operation.getSorters(),
            filters = operation.getFilters(),
            page = operation.getPage(),
            start = operation.getStart(),
            limit = operation.getLimit(),
            simpleSortMode = me.getSimpleSortMode(),
            simpleGroupMode = me.getSimpleGroupMode(),
            pageParam = me.getPageParam(),
            startParam = me.getStartParam(),
            limitParam = me.getLimitParam(),
            groupParam = me.getGroupParam(),
            groupDirectionParam = me.getGroupDirectionParam(),
            sortParam = me.getSortParam(),
            filterParam = me.getFilterParam(),
            directionParam = me.getDirectionParam(),
            hasGroups, index;

        if (pageParam && page) {
            params[pageParam] = page;
        }

        if (startParam && (start || start === 0)) {
            params[startParam] = start;
        }

        if (limitParam && limit) {
            params[limitParam] = limit;
        }

        hasGroups = groupParam && grouper;

        if (hasGroups) {
            // Grouper is a subclass of sorter, so we can just use the sorter method
            if (simpleGroupMode) {
                params[groupParam] = grouper.getProperty();

                // Allow for direction to be encoded into the same parameter
                if (groupDirectionParam === groupParam) {
                    params[groupParam] += ' ' + grouper.getDirection();
                }
                else {
                    params[groupDirectionParam] = grouper.getDirection();
                }
            }
            else {
                params[groupParam] = me.encodeSorters([grouper], true);
            }
        }

        /* eslint-disable max-len */
        if (sortParam && sorters && sorters.length > 0) {
            if (simpleSortMode) {
                // Group will be included in sorters, so skip sorter 0 if groups
                for (index = (sorters.length > 1 && hasGroups) ? 1 : 0; index < sorters.length; index++) {
                    // Allow for direction to be encoded into the same parameter
                    if (directionParam === sortParam) {
                        params[sortParam] = Ext.Array.push(params[sortParam] || [], sorters[index].getProperty() + ' ' + sorters[index].getDirection());
                    }
                    else {
                        params[sortParam] = Ext.Array.push(params[sortParam] || [], sorters[index].getProperty());
                        params[directionParam] = Ext.Array.push(params[directionParam] || [], sorters[index].getDirection());
                    }
                }
            }
            else {
                params[sortParam] = me.encodeSorters(sorters);
            }
        }
        /* eslint-enable max-len */

        if (filterParam && filters && filters.length > 0) {
            params[filterParam] = me.encodeFilters(filters);
        }

        return params;
    },

    /**
     * Generates a url based on a given Ext.data.Request object. By default, ServerProxy's buildUrl
     * will add the cache-buster param to the end of the url. Subclasses may need to perform
     * additional modifications to the url.
     * @param {Ext.data.Request} request The request object
     * @return {String} The url
     */
    buildUrl: function(request) {
        var me = this,
            url = me.getUrl(request);

        //<debug>
        if (!url) {
            Ext.raise("You are using a ServerProxy but have not supplied it with a url.");
        }
        //</debug>

        if (me.getNoCache()) {
            url = Ext.urlAppend(
                url,
                Ext.String.format("{0}={1}", me.getCacheString(), Ext.Date.now())
            );
        }

        return url;
    },

    /**
     * Get the url for the request taking into account the order of priority,
     * - The request
     * - The api
     * - The url
     * @private
     * @param {Ext.data.Request} request The request
     * @return {String} The url
     */
    getUrl: function(request) {
        var url;

        if (request) {
            url = request.getUrl() || this.getApi()[request.getAction()];
        }

        return url ? url : this.callParent();
    },

    /**
     * In ServerProxy subclasses, the {@link #method-create}, {@link #method-read},
     * {@link #method-update}, and {@link #method-erase} methods all pass through to doRequest.
     * Each ServerProxy subclass must implement the doRequest method - see
     * {@link Ext.data.proxy.JsonP} and {@link Ext.data.proxy.Ajax} for examples. This method
     * carries the same signature as each of the methods that delegate to it.
     *
     * @param {Ext.data.operation.Operation} operation The Ext.data.operation.Operation object
     * @param {Function} callback The callback function to call when the Operation has completed
     * @param {Object} scope The scope in which to execute the callback
     */
    doRequest: function(operation, callback, scope) {
        //<debug>
        Ext.raise("The doRequest function has not been implemented on your " +
                  "Ext.data.proxy.Server subclass. See src/data/ServerProxy.js for details");
        //</debug>
    },

    /**
     * Optional callback function which can be used to clean up after a request has been completed.
     * @param {Ext.data.Request} request The Request object
     * @param {Boolean} success True if the request was successful
     * @protected
     * @template
     * @method
     */
    afterRequest: Ext.emptyFn,

    destroy: function() {
        var me = this;

        me.destroying = true;

        // Don't force Reader and Writer creation if they weren't yet instantiated
        me.reader = me.writer = Ext.destroy(me.reader, me.writer);

        me.callParent();

        // This just makes it hard to ask "was destroy() called?":
        // me.destroying = false; // removed in 7.0
        me.destroyed = true;
    }
});
