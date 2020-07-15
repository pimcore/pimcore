/**
 * ProxyStore is a superclass of {@link Ext.data.Store} and {@link Ext.data.BufferedStore}.
 * It's never used directly, but offers a set of methods used by both of those subclasses.
 *
 * We've left it here in the docs for reference purposes, but unless you need to make a whole new
 * type of Store, what you're probably looking for is {@link Ext.data.Store}. If you're still
 * interested, here's a brief description of what ProxyStore is and is not.
 *
 * ProxyStore provides the basic configuration for anything that can be considered a Store.
 * It expects to be given a {@link Ext.data.Model Model} that represents the type of data
 * in the Store. It also expects to be given a {@link Ext.data.proxy.Proxy Proxy} that handles
 * the loading of data into the Store.
 *
 * ProxyStore provides a few helpful methods such as {@link #method-load} and {@link #sync},
 * which load and save data respectively, passing the requests through the configured
 * {@link #proxy}.
 *
 * Built-in Store subclasses add extra behavior to each of these functions. Note also that each
 * ProxyStore subclass has its own way of storing data - in {@link Ext.data.Store} the data
 * is saved as a flat {@link Ext.util.Collection Collection}, whereas in
 * {@link Ext.data.BufferedStore BufferedStore} we use a {@link Ext.data.PageMap} to maintain
 * a client side cache of pages of records.
 *
 * The store provides filtering and sorting support. This sorting/filtering can happen on the
 * client side or can be completed on the server. This is controlled by the
 * {@link Ext.data.Store#remoteSort remoteSort} and {@link Ext.data.Store#remoteFilter remoteFilter}
 * config options. For more information see the {@link #method-sort} and
 * {@link Ext.data.Store#filter filter} methods.
 */
Ext.define('Ext.data.ProxyStore', {
    extend: 'Ext.data.AbstractStore',

    requires: [
        'Ext.data.Model',
        'Ext.data.proxy.Proxy',
        'Ext.data.proxy.Memory',
        'Ext.data.operation.*'
    ],

    config: {
        // @cmd-auto-dependency {aliasPrefix: "model.", mvc: true, blame: "all"}
        /**
         * @cfg {String/Ext.data.Model} model
         * Name of the {@link Ext.data.Model Model} associated with this store. See
         * {@link Ext.data.Model#entityName}.
         *
         * May also be the actual Model subclass.
         *
         * This config is required for the store to be able to read data unless you have
         * defined the {@link #fields} config which will create an anonymous
         * `Ext.data.Model`.
         */
        model: undefined,

        // @cmd-auto-dependency {aliasPrefix: "data.field."}
        /**
         * @cfg fields
         * @inheritdoc Ext.data.Model#cfg-fields
         * 
         * @localdoc **Note:** In general, this configuration option should only be used 
         * for simple stores like a two-field store of 
         * {@link Ext.form.field.ComboBox ComboBox}. For anything more complicated, such 
         * as specifying a particular id property or associations, a 
         * {@link Ext.data.Model Model} should be defined and specified for the 
         * {@link #model} config.
         * 
         * @since 2.3.0
         */
        fields: null,

        // @cmd-auto-dependency {aliasPrefix : "proxy."}
        /**
         * @cfg {String/Ext.data.proxy.Proxy/Object} proxy
         * The Proxy to use for this Store. This can be either a string, a config object
         * or a Proxy instance - see {@link #setProxy} for details.
         * @since 1.1.0
         */
        proxy: undefined,

        /**
         * @cfg {Boolean/Object} autoLoad
         * If data is not specified, and if autoLoad is true or an Object, this store's
         * load method is automatically called after creation. If the value of autoLoad
         * is an Object, this Object will be passed to the store's load method.
         *
         * It's important to note that {@link Ext.data.TreeStore Tree Stores} will  
         * load regardless of autoLoad's value if expand is set to true on the 
         * {@link Ext.data.TreeStore#root root node}.
         * 
         * @since 2.3.0
         */
        autoLoad: undefined,

        /**
         * @cfg {Boolean} autoSync
         * True to automatically sync the Store with its Proxy after every edit to one of
         * its Records. Defaults to false.
         */
        autoSync: false,

        /**
         * @cfg {String} batchUpdateMode
         * Sets the updating behavior based on batch synchronization. 'operation' (the
         * default) will update the Store's internal representation of the data after
         * each operation of the batch has completed, 'complete' will wait until the
         * entire batch has been completed before updating the Store's data. 'complete'
         * is a good choice for local storage proxies, 'operation' is better for remote
         * proxies, where there is a comparatively high latency.
         */
        batchUpdateMode: 'operation',

        /**
         * @cfg {Boolean} sortOnLoad
         * If true, any sorters attached to this Store will be run after loading data,
         * before the datachanged event is fired. Defaults to true, ignored if
         * {@link Ext.data.Store#remoteSort remoteSort} is true
         */
        sortOnLoad: true,

        /**
         * @cfg {Boolean} trackRemoved
         * This config controls whether removed records are remembered by this store for
         * later saving to the server.
         */
        trackRemoved: true,

        /**
         * @cfg {Boolean} asynchronousLoad
         * This defaults to `true` when this store's {@link #cfg-proxy} is asynchronous,
         * such as an {@link Ext.data.proxy.Ajax Ajax proxy}.
         *
         * When the proxy is synchronous, such as a {@link Ext.data.proxy.Memory} memory
         * proxy, this defaults to `false`.
         *
         * *NOTE:* This does not cause synchronous Ajax requests if configured `false`
         * when an Ajax proxy is used. It causes immediate issuing of an Ajax request
         * when {@link #method-load} is called rather than issuing the request at the end
         * of the current event handler run.
         *
         * What this means is that when using an Ajax proxy, calls to 
         * {@link #method-load} do not fire the request to the remote resource 
         * immediately, but schedule a request to be made. This is so that multiple 
         * requests are not fired when mutating a store's remote filters and sorters (as 
         * happens during state restoration). The request is made only once after all 
         * relevant store state is fully set.
         *
         * @since 6.0.1
         */
        asynchronousLoad: undefined
    },

    onClassExtended: function(cls, data, hooks) {
        var model = data.model,
            onBeforeClassCreated;

        if (typeof model === 'string') {
            onBeforeClassCreated = hooks.onBeforeCreated;

            hooks.onBeforeCreated = function() {
                var me = this,
                    args = arguments;

                Ext.require(model, function() {
                    onBeforeClassCreated.apply(me, args);
                });
            };
        }
    },

    /**
     * @private
     * @property {Boolean} implicitModel
     * The class name of the model that this store uses if no explicit {@link #model} is
     * given
     */
    implicitModel: 'Ext.data.Model',

    /**
     * @property {Object} lastOptions
     * Property to hold the last options from a {@link #method-load} method call. This
     * object is used for the {@link #method-reload} to reuse the same options. Please
     * see {@link #method-reload} for a simple example on how to use the lastOptions
     * property.
     */

    /**
     * @property {Number} autoSyncSuspended
     * A counter to track suspensions.
     * @private
     */
    autoSyncSuspended: 0,

    /**
     * @property {Ext.data.Model[]} removed
     * Temporary cache in which removed model instances are kept until successfully
     * synchronised with a Proxy, at which point this is cleared.
     *
     * This cache is maintained unless you set `trackRemoved` to `false`.
     *
     * @protected
     * @readonly
     */
    removed: null,

    /**
     * @event beforeload
     * Fires before a request is made for a new data object. If the beforeload handler returns
     * `false` the load action will be canceled.
     *
     * **Note:** If you are using a buffered store, you should use
     * {@link Ext.data.Store#beforeprefetch beforeprefetch}.
     * @param {Ext.data.Store} store This Store
     * @param {Ext.data.operation.Operation} operation The Ext.data.operation.Operation object
     * that will be passed to the Proxy to load the Store
     * @since 1.1.0
     */

    /**
     * @event load
     * Fires whenever the store reads data from a remote data source.
     *
     * **Note:** If you are using a buffered store, you should use
     * {@link Ext.data.Store#prefetch prefetch}.
     * @param {Ext.data.Store} this
     * @param {Ext.data.Model[]} records An array of records
     * @param {Boolean} successful True if the operation was successful.
     * @param {Ext.data.operation.Read} operation The
     * {@link Ext.data.operation.Read Operation} object that was used in the data
     * load call
     * @since 1.1.0
     */

    /**
     * @event write
     * Fires whenever a successful write has been made via the configured {@link #proxy Proxy}
     * @param {Ext.data.Store} store This Store
     * @param {Ext.data.operation.Operation} operation The
     * {@link Ext.data.operation.Operation Operation} object that was used in the write
     * @since 3.4.0
     */

    /**
     * @event beforesync
     * Fired before a call to {@link #sync} is executed. Return false from any listener to cancel
     * the sync
     * @param {Object} options Hash of all records to be synchronized, broken down into create,
     * update and destroy
     */

    /**
     * @event metachange
     * Fires when this store's underlying reader (available via the proxy) provides new metadata.
     * Metadata usually consists of new field definitions, but can include any configuration data
     * required by an application, and can be processed as needed in the event handler.
     * This event is currently only fired for JsonReaders.
     * @param {Ext.data.Store} this
     * @param {Object} meta The JSON metadata
     * @since 1.1.0
     */

    constructor: function(config) {
        var me = this;

        //<debug>
        var configModel = me.model; // eslint-disable-line vars-on-top, one-var
        //</debug>

        me.callParent(arguments);

        if (me.getAsynchronousLoad() === false) {
            me.flushLoad();
        }

        //<debug>
        if (!me.getModel() && me.useModelWarning !== false &&
            me.getStoreId() !== 'ext-empty-store') {

            // There are a number of ways things could have gone wrong, try to give as much
            // information as possible
            var logMsg = [ // eslint-disable-line vars-on-top, one-var
                Ext.getClassName(me) || 'Store',
                ' created with no model.'
            ];

            if (typeof configModel === 'string') {
                logMsg.push(" The name '", configModel, "'",
                            ' does not correspond to a valid model.');
            }

            Ext.log.warn(logMsg.join(''));
        }
        //</debug>
    },

    /**
     * @private
     */
    doDestroy: function() {
        var me = this,
            proxy = me.getProxy();

        me.clearLoadTask();
        Ext.destroy(me.getData());
        me.data = null;
        me.setProxy(null);

        if (proxy.autoCreated) {
            proxy.destroy();
        }

        me.setModel(null);

        me.callParent();
    },

    applyAsynchronousLoad: function(asynchronousLoad) {
        // Default in an asynchronousLoad setting.
        // It defaults to false if the proxy is synchronous, and true if the proxy is asynchronous.
        if (asynchronousLoad == null) {
            asynchronousLoad = !this.loadsSynchronously();
        }

        return asynchronousLoad;
    },

    updateAutoLoad: function(autoLoad) {
        // Ensure the data collection is set up
        this.getData();

        if (autoLoad) {
            // Defer the load until idle, when the store (and probably the view)
            // is fully constructed
            this.load(Ext.isObject(autoLoad) ? autoLoad : undefined);
        }
    },

    /**
     * Returns the total number of {@link Ext.data.Model Model} instances that the
     * {@link Ext.data.proxy.Proxy Proxy} indicates exist. This will usually differ from
     * {@link #getCount} when using paging - getCount returns the number of records loaded into
     * the Store at the moment, getTotalCount returns the number of records that could be loaded
     * into the Store if the Store contained all data
     * @return {Number} The total number of Model instances available via the Proxy. 0 returned if
     * no value has been set via the reader.
     */
    getTotalCount: function() {
        return this.totalCount || 0;
    },

    applyFields: function(fields) {
        if (fields) {
            this.createImplicitModel(fields);
        }
    },

    applyModel: function(model) {
        if (model) {
            model = Ext.data.schema.Schema.lookupEntity(model);
        }
        else if (!this.destroying) {
            // If no model, ensure that the fields config is converted to a model.
            this.getFields();

            model = this.getModel() || this.createImplicitModel();
        }

        return model;
    },

    applyProxy: function(proxy) {
        var model = this.getModel();

        if (proxy !== null) {
            if (proxy) {
                if (proxy.isProxy) {
                    proxy.setModel(model);
                }
                else {
                    if (Ext.isString(proxy)) {
                        proxy = {
                            type: proxy,
                            model: model
                        };
                    }
                    else if (!proxy.model) {
                        proxy = Ext.apply({
                            model: model
                        }, proxy);
                    }

                    proxy = Ext.createByAlias('proxy.' + proxy.type, proxy);
                    proxy.autoCreated = true;
                }
            }
            else if (model) {
                proxy = model.getProxy();
                this.useModelProxy = true;
            }

            if (!proxy) {
                proxy = Ext.createByAlias('proxy.memory');
                proxy.autoCreated = true;
            }
        }

        return proxy;
    },

    applyState: function(state) {
        var me = this;

        me.callParent([state]);

        // This is called during construction. Sorters and filters might have changed
        // which require a reload.
        // If autoLoad is true, it might have loaded synchronously from a memory proxy,
        // so needs to reload.
        // If it is already loaded, we definitely need to reload to apply the state.
        if (me.getAutoLoad() || me.isLoaded()) {
            me.load();
        }
    },

    updateProxy: function(proxy, oldProxy) {
        this.proxyListeners = Ext.destroy(this.proxyListeners);
    },

    updateTrackRemoved: function(track) {
        this.cleanRemoved();
        this.removed = track ? [] : null;
    },

    /**
     * @private
     */
    onMetaChange: function(proxy, meta) {
        this.fireEvent('metachange', this, meta);
    },

    // saves any phantom records
    create: function(data, options) {
        var me = this,
            Model = me.getModel(),
            instance = new Model(data),
            operation;

        options = Ext.apply({}, options);

        if (!options.records) {
            options.records = [instance];
        }

        options.internalScope = me;
        options.internalCallback = me.onProxyWrite;

        operation = me.createOperation('create', options);

        return operation.execute();
    },

    read: function() {
        return this.load.apply(this, arguments);
    },

    update: function(options) {
        var me = this,
            operation;

        options = Ext.apply({}, options);

        if (!options.records) {
            options.records = me.getUpdatedRecords();
        }

        options.internalScope = me;
        options.internalCallback = me.onProxyWrite;

        operation = me.createOperation('update', options);

        return operation.execute();
    },

    /**
     * @private
     * Callback for any write Operation over the Proxy. Updates the Store's MixedCollection
     * to reflect the updates provided by the Proxy
     */
    onProxyWrite: function(operation) {
        var me = this,
            success = operation.wasSuccessful(),
            records = operation.getRecords();

        switch (operation.getAction()) {
            case 'create':
                me.onCreateRecords(records, operation, success);
                break;

            case 'update':
                me.onUpdateRecords(records, operation, success);
                break;

            case 'destroy':
                me.onDestroyRecords(records, operation, success);
                break;
        }

        if (success) {
            me.fireEvent('write', me, operation);
            me.fireEvent('datachanged', me);
        }
    },

    // may be implemented by store subclasses
    onCreateRecords: Ext.emptyFn,

    // may be implemented by store subclasses
    onUpdateRecords: Ext.emptyFn,

    /**
     * Removes any records when a write is returned from the server.
     * @private
     * @param {Ext.data.Model[]} records The array of removed records
     * @param {Ext.data.operation.Operation} operation The operation that just completed
     * @param {Boolean} success True if the operation was successful
     */
    onDestroyRecords: function(records, operation, success) {
        if (success) {
            this.cleanRemoved();
        }
    },

    // tells the attached proxy to destroy the given records
    // @since 3.4.0
    erase: function(options) {
        var me = this,
            operation;

        options = Ext.apply({}, options);

        if (!options.records) {
            options.records = me.getRemovedRecords();
        }

        options.internalScope = me;
        options.internalCallback = me.onProxyWrite;

        operation = me.createOperation('destroy', options);

        return operation.execute();
    },

    /**
     * @private
     * Attached as the 'operationcomplete' event listener to a proxy's Batch object. By default
     * just calls through to onProxyWrite.
     */
    onBatchOperationComplete: function(batch, operation) {
        return this.onProxyWrite(operation);
    },

    /**
     * @private
     * Attached as the 'complete' event listener to a proxy's Batch object. Iterates over the batch
     * operations and updates the Store's internal data MixedCollection.
     */
    onBatchComplete: function(batch, operation) {
        var me = this,
            operations = batch.operations,
            length = operations.length,
            i;

        if (me.batchUpdateMode !== 'operation') {
            me.suspendEvents();

            for (i = 0; i < length; i++) {
                me.onProxyWrite(operations[i]);
            }

            me.resumeEvents();
        }

        me.isSyncing = false;

        if (batch.$destroyOwner === me) {
            batch.destroy();
        }

        me.fireEvent('datachanged', me);
    },

    /**
     * @private
     */
    onBatchException: function(batch, operation) {
        // //decide what to do... could continue with the next operation
        // batch.start();
        //
        // //or retry the last operation
        // batch.retry();
    },

    /**
     * @private
     * Filter function for new records.
     */
    filterNew: function(item) {
        // only want phantom records that are valid
        return item.phantom && item.isValid();
    },

    /**
     * Returns all `{@link Ext.data.Model#property-phantom phantom}` records in this store.
     * @return {Ext.data.Model[]} A possibly empty array of `phantom` records.
     */
    getNewRecords: function() {
        return [];
    },

    /**
     * Returns all valid, non-phantom Model instances that have been updated in the Store but
     * not yet synchronized with the Proxy.
     * @return {Ext.data.Model[]} The updated Model instances
     */
    getUpdatedRecords: function() {
        return [];
    },

    /**
     * Gets all {@link Ext.data.Model records} added or updated since the last commit. Note that
     * the order of records returned is not deterministic and does not indicate the order in which
     * records were modified. Note also that removed records are not included
     * (use {@link #getRemovedRecords} for that).
     * @return {Ext.data.Model[]} The added and updated Model instances
     */
    getModifiedRecords: function() {
        return [].concat(this.getNewRecords(), this.getUpdatedRecords());
    },

    /**
     * @private
     * Filter function for updated records.
     */
    filterUpdated: function(item) {
        // only want dirty records, not phantoms that are valid
        return item.dirty && !item.phantom && item.isValid();
    },

    /**
     * Returns any records that have been removed from the store but not yet destroyed on the proxy.
     * @return {Ext.data.Model[]} The removed Model instances. Note that this is a *copy* of the
     * store's array, so may be mutated.
     */
    getRemovedRecords: function() {
        var removed = this.getRawRemovedRecords();

        return removed ? Ext.Array.clone(removed) : [];
    },

    /**
     * Synchronizes the store with its {@link #proxy}. This asks the proxy to batch together any
     * new, updated and deleted records in the store, updating the store's internal representation
     * of the records as each operation completes.
     * 
     * @param {Object} [options] Object containing one or more properties supported by the sync
     * method (these get  passed along to the underlying proxy's {@link Ext.data.Proxy#batch batch}
     * method):
     * 
     * @param {Ext.data.Batch/Object} [options.batch] A {@link Ext.data.Batch} object (or batch
     * config to apply  to the created batch). If unspecified a default batch will be auto-created
     * as needed.
     * 
     * @param {Function} [options.callback] The function to be called upon completion of the sync.
     * The callback is called regardless of success or failure and is passed the following
     * parameters:
     * @param {Ext.data.Batch} options.callback.batch The {@link Ext.data.Batch batch} that was
     * processed, containing all operations in their current state after processing
     * @param {Object} options.callback.options The options argument that was originally passed
     * into sync
     * 
     * @param {Function} [options.success] The function to be called upon successful completion
     * of the sync. The success function is called only if no exceptions were reported in any
     * operations. If one or more exceptions occurred then the failure function will be called
     * instead. The success function is called  with the following parameters:
     * @param {Ext.data.Batch} options.success.batch The {@link Ext.data.Batch batch} that was
     * processed, containing all operations in their current state after processing
     * @param {Object} options.success.options The options argument that was originally passed
     * into sync
     * 
     * @param {Function} [options.failure] The function to be called upon unsuccessful completion
     * of the sync. The failure function is called when one or more operations returns an exception
     * during processing (even if some operations were also successful). In this case you can check
     * the batch's {@link Ext.data.Batch#exceptions  exceptions} array to see exactly which
     * operations had exceptions. The failure function is called with the  following parameters:
     * @param {Ext.data.Batch} options.failure.batch The {@link Ext.data.Batch} that was processed,
     * containing all operations in their current state after processing
     * @param {Object} options.failure.options The options argument that was originally passed
     * into sync
     * 
     * @param {Object} [options.params] Additional params to send during the sync Operation(s).
     *
     * @param {Object} [options.scope] The scope in which to execute any callbacks (i.e. the `this`
     * object inside the callback, success and/or failure functions). Defaults to the store's proxy.
     * 
     * @return {Ext.data.Store} this
     */
    sync: function(options) {
        var me = this,
            operations = {},
            toCreate = me.getNewRecords(),
            toUpdate = me.getUpdatedRecords(),
            toDestroy = me.getRemovedRecords(),
            needsSync = false;

        //<debug>
        if (me.isSyncing) {
            Ext.log.warn('Sync called while a sync operation is in progress. ' +
                         'Consider configuring autoSync as false.');
        }
        //</debug>

        me.needsSync = false;

        if (toCreate.length > 0) {
            operations.create = toCreate;
            needsSync = true;
        }

        if (toUpdate.length > 0) {
            operations.update = toUpdate;
            needsSync = true;
        }

        if (toDestroy.length > 0) {
            operations.destroy = toDestroy;
            needsSync = true;
        }

        if (needsSync && me.fireEvent('beforesync', operations) !== false) {
            me.isSyncing = true;

            options = options || {};

            me.proxy.batch(Ext.apply(options, {
                operations: operations,
                listeners: me.getBatchListeners(),
                $destroyOwner: me
            }));
        }

        return me;
    },

    /**
     * @private
     * Returns an object which is passed in as the listeners argument to proxy.batch inside
     * this.sync. This is broken out into a separate function to allow for customisation
     * of the listeners
     * @return {Object} The listeners object
     */
    getBatchListeners: function() {
        var me = this,
            listeners = {
                scope: me,
                exception: me.onBatchException,
                complete: me.onBatchComplete
            };

        if (me.batchUpdateMode === 'operation') {
            listeners.operationcomplete = me.onBatchOperationComplete;
        }

        return listeners;
    },

    /**
     * Saves all pending changes via the configured {@link #proxy}. Use {@link #sync} instead.
     * @deprecated 4.0.0 Will be removed in the next major version
     */
    save: function() {
        return this.sync.apply(this, arguments);
    },

    /**
     * Marks this store as needing a load. When the current executing event handler exits,
     * this store will send a request to load using its configured {@link #proxy}.
     *
     * Upon return of the data from whatever data source the proxy connected to, the retrieved
     * {@link Ext.data.Model records} will be loaded into this store, and the optional callback
     * will be called. Example usage:
     *
     *     store.load({
     *         scope: this,
     *         callback: function(records, operation, success) {
     *             // the operation object
     *             // contains all of the details of the load operation
     *             console.log(records);
     *         }
     *     });
     *
     * If the callback scope does not need to be set, a function can simply be passed:
     *
     *     store.load(function(records, operation, success) {
     *         console.log('loaded records');
     *     });
     *
     * @param {Object} [options] This is passed into the
     * {@link Ext.data.operation.Operation Operation} object that is created and then sent to the
     * proxy's {@link Ext.data.proxy.Proxy#read} function. In addition to the options listed below,
     * this object may contain properties to configure the
     * {@link Ext.data.operation.Operation Operation}.
     * @param {Function} [options.callback] A function which is called when the response arrives.
     * @param {Ext.data.Model[]} options.callback.records Array of records.
     * @param {Ext.data.operation.Operation} options.callback.operation The Operation itself.
     * @param {Boolean} options.callback.success `true` when operation completed successfully.
     * @param {Boolean} [options.addRecords=false] Specify as `true` to *add* the incoming records
     * rather than the default which is to have the incoming records *replace* the existing store
     * contents.
     * 
     * @return {Ext.data.Store} this
     * @since 1.1.0
     */
    load: function(options) {
        var me = this;

        // Legacy option. Specifying a function was allowed.
        if (typeof options === 'function') {
            options = {
                callback: options
            };
        }
        else {
            // We may mutate the options object in setLoadOptions.
            options = options ? Ext.Object.chain(options) : {};
        }

        me.pendingLoadOptions = options;

        // If we are configured to load asynchronously (the default for async proxies)
        // then schedule a flush, unless one is already scheduled.
        if (me.getAsynchronousLoad()) {
            if (!me.loadTimer) {
                me.loadTimer = Ext.asap(me.flushLoad, me);
            }
        }
        // If we are configured to load synchronously (the default for sync proxies)
        // then flush the load now.
        else {
            me.flushLoad();
        }

        return me;
    },

    /**
     * Called when the event handler which called the {@link #method-load} method exits.
     */
    flushLoad: function() {
        var me = this,
            options = me.pendingLoadOptions,
            operation;

        if (me.destroying || me.destroyed) {
            return;
        }

        // If it gets called programatically before the timer fired, the listener will need
        // cancelling.
        me.clearLoadTask();

        if (!options) {
            return;
        }

        me.setLoadOptions(options);

        if (me.getRemoteSort() && options.sorters) {
            me.fireEvent('beforesort', me, options.sorters);
        }

        operation = Ext.apply({
            internalScope: me,
            internalCallback: me.onProxyLoad,
            scope: me
        }, options);

        me.lastOptions = operation;

        operation = me.createOperation('read', operation);

        if (me.fireEvent('beforeload', me, operation) !== false) {
            me.onBeforeLoad(operation);
            me.loading = true;

            // Internal event, fired after the flag is set, we need
            // to fire this beforeload is too early
            if (me.hasListeners.beginload) {
                me.fireEvent('beginload', me, operation);
            }

            operation.execute();
        }
        else {
            if (me.getAsynchronousLoad()) {
                operation.abort();
            }

            operation.setCompleted();
        }
    },

    /**
     * Reloads the store using the last options passed to the {@link #method-load} method.
     * You can use the reload method to reload the store using the parameters from the last load()
     * call. For example:
     *
     *     store.load({
     *         params : {
     *             userid : 22216
     *         }
     *     });
     *
     *     //...
     *
     *     store.reload();
     *
     * The initial {@link #method-load} execution will pass the `userid` parameter in the request.
     * The {@link #reload} execution will also send the same `userid` parameter in its request
     * as it will reuse the `params` object from the last {@link #method-load} call.
     *
     * You can override a param by passing in the config object with the `params` object:
     *
     *     store.load({
     *         params : {
     *             userid : 22216,
     *             foo    : 'bar'
     *         }
     *     });
     *
     *     //...
     *
     *     store.reload({
     *         params : {
     *             userid : 1234
     *         }
     *     });
     *
     * The initial {@link #method-load} execution sends the `userid` and `foo` parameters but in the
     * {@link #reload} it only sends the `userid` paramter because you are overriding the `params`
     * config not just overriding the one param. To only change a single param but keep other
     * params, you will have to get the last params from the {@link #lastOptions} property:
     *
     *     // make a copy of the last params so we don't affect future reload() calls
     *     var lastOptions = store.lastOptions,
     *         lastParams = Ext.clone(lastOptions.params);
     *
     *     lastParams.userid = 1234;
     *
     *     store.reload({
     *         params : lastParams
     *     });
     *
     * This will now send the `userid` parameter as `1234` and the `foo` param as `'bar'`.
     *
     * @param {Object} [options] A config object which contains options which may override the
     * options passed to the previous load call. See the
     * {@link #method-load} method for valid configs.
     */
    reload: function(options) {
        return this.load(Ext.apply({}, options, this.lastOptions));
    },

    onEndUpdate: function() {
        var me = this;

        if (me.needsSync && me.autoSync && !me.autoSyncSuspended) {
            me.sync();
        }
    },

    /**
     * @private
     * A model instance should call this method on the Store it has been
     * {@link Ext.data.Model#join joined} to.
     * @param {Ext.data.Model} record The model instance that was edited
     * @since 3.4.0
     */
    afterReject: function(record) {
        var me = this;

        // Must pass the 5th param (modifiedFieldNames) as null, otherwise the
        // event firing machinery appends the listeners "options" object to the arg list
        // which may get used as the modified fields array by a handler.
        // This array is used for selective grid cell updating by Grid View.
        // Null will be treated as though all cells need updating.
        if (me.contains(record)) {
            me.onUpdate(record, Ext.data.Model.REJECT, null);
            me.fireEvent('update', me, record, Ext.data.Model.REJECT, null);
            me.fireEvent('datachanged', me);
        }
    },

    /**
     * A model instance should call this method on the Store it has been
     * {@link Ext.data.Model#join joined} to.
     * @param {Ext.data.Model} record The model instance that was edited.
     * @param {String[]} [modifiedFieldNames] (private)
     * @since 3.4.0
     * @private
     */
    afterCommit: function(record, modifiedFieldNames) {
        var me = this;

        if (!modifiedFieldNames) {
            modifiedFieldNames = null;
        }

        if (me.contains(record)) {
            me.onUpdate(record, Ext.data.Model.COMMIT, modifiedFieldNames);
            me.fireEvent('update', me, record, Ext.data.Model.COMMIT, modifiedFieldNames);
            me.fireEvent('datachanged', me);
        }
    },

    afterErase: function(record) {
        this.onErase(record);
    },

    onErase: Ext.emptyFn,

    onUpdate: Ext.emptyFn,

    /**
     * Returns true if the store has a pending load task.
     * @return {Boolean} `true` if the store has a pending load task.
     * @private
     */
    hasPendingLoad: function() {
        return !!this.pendingLoadOptions || this.isLoading();
    },

    /**
     * Returns true if the Store is currently performing a load operation
     * @return {Boolean} `true` if the Store is currently loading
     */
    isLoading: function() {
        return !!this.loading;
    },

    /**
     * Returns `true` if the Store has been loaded.
     * @return {Boolean} `true` if the Store has been loaded.
     */
    isLoaded: function() {
        return this.loadCount > 0;
    },

    /**
     * Suspends automatically syncing the Store with its Proxy. Only applicable if
     * {@link #autoSync} is `true`
     */
    suspendAutoSync: function() {
        ++this.autoSyncSuspended;
    },

    /**
     * Resumes automatically syncing the Store with its Proxy. Only applicable if
     * {@link #autoSync} is `true`
     * @param {Boolean} syncNow Pass `true` to synchronize now. Only synchronizes with the Proxy
     * if the suspension count has gone to zero (We are not under a higher level of suspension)
     * 
     */
    resumeAutoSync: function(syncNow) {
        var me = this;

        //<debug>
        if (!me.autoSyncSuspended) {
            Ext.log.warn('Mismatched call to resumeAutoSync - auto synchronization ' +
                         'is currently not suspended.');
        }
        //</debug>

        if (me.autoSyncSuspended && ! --me.autoSyncSuspended) {
            if (syncNow) {
                me.sync();
            }
        }
    },

    /**
     * Removes all records from the store. This method does a "fast remove",
     * individual remove events are not called. The {@link #clear} event is
     * fired upon completion.
     * @method
     * @since 1.1.0
     */
    removeAll: Ext.emptyFn,
    // individual store subclasses should implement a "fast" remove
    // and fire a clear event afterwards

    // to be implemented by subclasses
    clearData: Ext.emptyFn,

    privates: {
        /**
         * @private
         * Returns the array of records which have been removed since the last time this store
         * was synced.
         *
         * This is used internally, when purging removed records after a successful sync.
         * This is overridden by TreeStore because TreeStore accumulates deleted records on removal
         * of child nodes from their parent, *not* on removal of records from its collection.
         * The collection has records added on expand, and removed on collapse.
         */
        getRawRemovedRecords: function() {
            return this.removed;
        },

        onExtraParamsChanged: function() {

        },

        clearLoadTask: function() {
            this.pendingLoadOptions = this.loadTimer = Ext.unasap(this.loadTimer);
        },

        cleanRemoved: function() {
            // Must use class-specific getRawRemovedRecords.
            // Regular Stores add to the "removed" property on remove.
            // TreeStores are having records removed all the time; node collapse removes.
            // TreeStores add to the "removedNodes" property onNodeRemove
            var removed = this.getRawRemovedRecords(),
                len, i;

            if (removed) {
                for (i = 0, len = removed.length; i < len; ++i) {
                    removed[i].unjoin(this);
                }

                removed.length = 0;
            }
        },

        createOperation: function(type, options) {
            var me = this,
                proxy = me.getProxy(),
                listeners;

            if (!me.proxyListeners) {
                listeners = {
                    scope: me,
                    destroyable: true,
                    beginprocessresponse: me.beginUpdate,
                    endprocessresponse: me.endUpdate
                };

                if (!me.disableMetaChangeEvent) {
                    listeners.metachange = me.onMetaChange;
                }

                me.proxyListeners = proxy.on(listeners);
            }

            return proxy.createOperation(type, options);
        },

        createImplicitModel: function(fields) {
            var me = this,
                modelCfg = {
                    extend: me.implicitModel,
                    statics: {
                        defaultProxy: 'memory'
                    }
                },
                proxy, model;

            if (fields) {
                modelCfg.fields = fields;
            }

            model = Ext.define(null, modelCfg);

            me.setModel(model);

            proxy = me.getProxy();

            if (proxy) {
                model.setProxy(proxy);
            }
            else {
                me.setProxy(model.getProxy());
            }
        },

        loadsSynchronously: function() {
            return this.getProxy().isSynchronous;
        },

        onBeforeLoad: Ext.privateFn,

        removeFromRemoved: function(record) {
            // Must use class-specific getRawRemovedRecords.
            // Regular Stores add to the "removed" property on remove.
            // TreeStores are having records removed all the time; node collapse removes.
            // TreeStores add to the "removedNodes" property onNodeRemove
            var removed = this.getRawRemovedRecords();

            if (removed) {
                Ext.Array.remove(removed, record);
                record.unjoin(this);
            }
        },

        setLoadOptions: function(options) {
            var me = this,
                filters, sorters;

            if (me.getRemoteFilter()) {
                filters = me.getFilters(false);

                if (filters && filters.getCount()) {
                    options.filters = filters.getRange();
                }
            }

            if (me.getRemoteSort()) {
                sorters = me.getSorters(false);

                if (sorters && sorters.getCount()) {
                    options.sorters = sorters.getRange();
                }
            }
        }
    }

});
