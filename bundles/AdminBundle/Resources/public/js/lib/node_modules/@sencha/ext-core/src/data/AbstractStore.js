/**
 * AbstractStore is a superclass of {@link Ext.data.ProxyStore} and {@link Ext.data.ChainedStore}.
 * It's never used directly, but offers a set of methods used by both of those subclasses.
 *
 * Unless you need to make a whole new type of Store, see {@link Ext.data.Store} instead.
 */
Ext.define('Ext.data.AbstractStore', {
    mixins: [
        'Ext.mixin.Observable',
        'Ext.mixin.Factoryable'
    ],

    requires: [
        'Ext.util.Collection',
        'Ext.data.Range',
        'Ext.data.schema.Schema',
        'Ext.util.Filter'
    ],

    factoryConfig: {
        defaultType: 'store',
        type: 'store'
    },

    $configPrefixed: false,
    $configStrict: false,

    config: {
        /**
         * @cfg {Object[]/Function[]} filters
         * Array of {@link Ext.util.Filter Filters} for this store. Can also be passed array of
         * functions which will be used as the {@link Ext.util.Filter#filterFn filterFn} config
         * for filters:
         *
         *     filters: [
         *         function(item) {
         *             return item.weight > 0;
         *         }
         *     ]
         *
         * Individual filters can be specified as an `Ext.util.Filter` instance, a config
         * object for `Ext.util.Filter` or simply a function that will be wrapped in a
         * instance with its {@link Ext.util.Filter#filterFn filterFn} set.
         *
         * For fine grain control of the filters collection, call `getFilters` to return
         * the `Ext.util.Collection` instance that holds this store's filters.
         *
         *      var filters = store.getFilters(); // an Ext.util.FilterCollection
         *
         *      function legalAge (item) {
         *          return item.age >= 21;
         *      }
         *
         *      filters.add(legalAge);
         *
         *      //...
         *
         *      filters.remove(legalAge);
         *
         * Any changes to the `filters` collection will cause this store to adjust
         * its items accordingly.
         */
        filters: null,

        /**
         * @cfg {Boolean} [autoDestroy]
         * When a Store is used by only one {@link Ext.view.View DataView}, and should only exist
         * for the lifetime of that view, then configure the autoDestroy flag as `true`. This
         * causes the destruction of the view to trigger the destruction of its Store.
         */
        autoDestroy: undefined,

        /**
         * @cfg {String} storeId
         * Unique identifier for this store. If present, this Store will be registered with the
         * {@link Ext.data.StoreManager}, making it easy to reuse elsewhere.
         *
         * Note that when a store is instantiated by a Controller, the storeId will default
         * to the name of the store if not specified in the class.
         */
        storeId: null,

        /**
         * @cfg {Boolean} statefulFilters
         * Configure as `true` to have the filters saved when a client {@link Ext.grid.Panel grid}
         * saves its state.
         */
        statefulFilters: false,

        /**
         * @cfg {Ext.util.Sorter[]/Object[]} sorters
         * The initial set of {@link Ext.util.Sorter Sorters}
         *
         * Individual sorters can be specified as an `Ext.util.Sorter` instance, a config
         * object for `Ext.util.Sorter` or simply the name of a property by which to sort.
         *
         * An alternative way to extend the sorters is to call the `sort` method and pass
         * a property or sorter config to add to the sorters.
         *
         * For fine grain control of the sorters collection, call `getSorters` to return
         * the `Ext.util.Collection` instance that holds this collection's sorters.
         *
         *      var sorters = store.getSorters(); // an Ext.util.SorterCollection
         *
         *      sorters.add('name');
         *
         *      //...
         *
         *      sorters.remove('name');
         *
         * Any changes to the `sorters` collection will cause this store to adjust
         * its items accordingly.
         */
        sorters: null,

        /**
        * @cfg {Boolean} [remoteSort=false]
        * `true` if the sorting should be performed on the server side, false if it is local only.
        */
        remoteSort: {
            lazy: true,
            $value: false
        },

        /**
        * @cfg {Boolean} [remoteFilter=false]
        * `true` to defer any filtering operation to the server. If `false`, filtering is done
        * locally on the client.
        */
        remoteFilter: {
            lazy: true,
            $value: false
        },

        /**
        * @cfg {String} groupField
        * The field by which to group data in the store. Internally, grouping is very similar to
        * sorting - the groupField and {@link #groupDir} are injected as the first sorter
        * (see {@link #method-sort}). Stores support a single level of grouping, and groups can be
        * fetched via the {@link #getGroups} method.
        */
        groupField: undefined,

        /**
        * @cfg {String} groupDir
        * The direction in which sorting should be applied when grouping. Supported values are
        * "ASC" and "DESC".
        */
        groupDir: 'ASC',

        /**
         * @cfg {Object/Ext.util.Grouper} grouper
         * The grouper by which to group the data store. May also be specified by the
         * {@link #groupField} config, however
         * they should not be used together.
         */
        grouper: null,

        /**
        * @cfg {Number} pageSize
        * The number of records considered to form a 'page'. This is used to power the built-in
        * paging using the nextPage and previousPage functions when the grid is paged using a
        * {@link Ext.toolbar.Paging PagingToolbar} Defaults to 25.
        *
        * To disable paging, set the pageSize to `0`.
        */
        pageSize: 25,

        /**
         * @cfg {Boolean} [autoSort=true] `true` to maintain sorted order when records
         * are added regardless of requested insertion point, or when an item mutation
         * results in a new sort position.
         *
         * This does not affect a ChainedStore's reaction to mutations of the source
         * Store. If sorters are present when the source Store is mutated, this ChainedStore's
         * sort order will always be maintained.
         * @private
         */
        autoSort: null,

        /**
         * @cfg {Boolean} reloadOnClearSorters
         * Set this to `true` to trigger a reload when the last sorter is removed (only
         * applicable when {@link #cfg!remoteSort} is `true`).
         *
         * By default, the store reloads itself when a sorter is added or removed.
         *
         * When the last sorter is removed, however, the assumption is that the data
         * does not need to become "unsorted", and so no reload is triggered.
         *
         * If the server has a default order to which it reverts in the absence of any
         * sorters, then it is useful to set this config to `true`.
         * @since 6.5.1
         */
        reloadOnClearSorters: false
    },

    /**
     * @property {Number} currentPage
     * The page that the Store has most recently loaded
     * (see {@link Ext.data.Store#loadPage loadPage})
     */
    currentPage: 1,

    /**
     * @property {Boolean} loading
     * `true` if the Store is currently loading via its Proxy.
     * @private
     */
    loading: false,

    /**
     * @property {Boolean} isStore
     * `true` in this class to identify an object as an instantiated Store, or subclass thereof.
     */
    isStore: true,

    /**
     * @property {Number} updating
     * A counter that is increased by `beginUpdate` and decreased by `endUpdate`. When
     * this transitions from 0 to 1 the `{@link #event-beginupdate beginupdate}` event is
     * fired. When it transitions back from 1 to 0 the `{@link #event-endupdate endupdate}`
     * event is fired.
     * @readonly
     * @since 5.0.0
     */
    updating: 0,

    constructor: function(config) {
        var me = this,
            storeId;

        //<debug>
        me.callParent([config]);
        //</debug>

        /**
         * @event add
         * Fired when a Model instance has been added to this Store.
         *
         * @param {Ext.data.Store} store The store.
         * @param {Ext.data.Model[]} records The records that were added.
         * @param {Number} index The index at which the records were inserted.
         * @since 1.1.0
         */

        /**
         * @event remove
         * Fired when one or more records have been removed from this Store.
         *
         * **The signature for this event has changed in 5.0:**
         *
         * @param {Ext.data.Store} store The Store object
         * @param {Ext.data.Model[]} records The records that were removed. In previous
         * releases this was a single record, not an array.
         * @param {Number} index The index at which the records were removed.
         * @param {Boolean} isMove `true` if the child node is being removed so it can be
         * moved to another position in this Store.
         * @since 5.0.0
         */

        /**
         * @event update
         * Fires when a Model instance has been updated.
         * @param {Ext.data.Store} this
         * @param {Ext.data.Model} record The Model instance that was updated
         * @param {String} operation The update operation being performed. Value may be one of:
         *
         *     Ext.data.Model.EDIT
         *     Ext.data.Model.REJECT
         *     Ext.data.Model.COMMIT
         * @param {String[]} modifiedFieldNames Array of field names changed during edit.
         * @param {Object} details An object describing the change. See the
         * {@link Ext.util.Collection#event-itemchange itemchange event} of the store's backing
         * collection
         * @since 1.1.0
         */

        /**
         * @event clear
         * Fired after the {@link Ext.data.Store#removeAll removeAll} method is called.
         * @param {Ext.data.Store} this
         * @since 1.1.0
         */

        /**
         * @event datachanged
         * Fires for any data change in the store. This is a catch-all event that is typically fired
         * in conjunction with other events (such as `add`, `remove`, `update`, `refresh`).
         * @param {Ext.data.Store} this The data store
         * @since 1.1.0
         */

        /**
         * @event refresh
         * Fires when the data cache has changed in a bulk manner (e.g., it has been sorted,
         * filtered, etc.) and a widget that is using this Store as a Record cache should refresh
         * its view.
         * @param {Ext.data.Store} this The data store
         */

        /**
         * @event beginupdate
         * Fires when the {@link #beginUpdate} method is called. Automatic synchronization as
         * configured by the {@link Ext.data.ProxyStore#autoSync autoSync} flag is deferred until
         * the {@link #endUpdate} method is called, so multiple mutations can be coalesced into one
         * synchronization operation.
         */

        /**
         * @event endupdate
         * Fires when the {@link #endUpdate} method is called. Automatic synchronization as
         * configured by the {@link Ext.data.ProxyStore#autoSync autoSync} flag is deferred until
         * the {@link #endUpdate} method is called, so multiple mutations can be coalesced into one
         * synchronization operation.
         */

        /**
         * @event beforesort
         * Fires before a store is sorted.
         *
         * For {@link #remoteSort remotely sorted} stores, this will be just before the load
         * operation triggered by changing the store's sorters.
         *
         * For locally sorted stores, this will be just before the data items in the store's
         * backing collection are sorted.
         * @param {Ext.data.Store} store The store being sorted
         * @param {Ext.util.Sorter[]} sorters Array of sorters applied to the store
         */

        /**
         * @event sort
         * Fires after a store is sorted.
         *
         * For {@link #remoteSort remotely sorted} stores, this will be upon the success of a load
         * operation triggered by changing the store's sorters.
         *
         * For locally sorted stores, this will be just after the data items in the store's backing
         * collection are sorted.
         * @param {Ext.data.Store} store The store being sorted
         */
        me.isInitializing = true;
        me.mixins.observable.constructor.call(me, config);
        me.isInitializing = false;

        storeId = me.getStoreId();

        if (!storeId && (config && config.id)) {
            me.setStoreId(storeId = config.id);
        }

        if (storeId) {
            Ext.data.StoreManager.register(me);
        }
    },

    /**
     * Create a `Range` instance to access records by their index.
     *
     * @param {Object/Ext.data.Range} [config]
     * @return {Ext.data.Range}
     * @since 6.5.0
     */
    createActiveRange: function(config) {
        var range = Ext.apply({ store: this }, config);

        return new Ext.data.Range(range);
    },

    /**
     * @private
     * Called from onCollectionItemsAdd. Collection add changes the items reference of the
     * collection, and that array object if directly referenced by Ranges. The ranges
     * have to refresh themselves upon add.
     */
    syncActiveRanges: function() {
        var activeRanges = this.activeRanges,
            len = activeRanges && activeRanges.length,
            i;

        for (i = 0; i < len; i++) {
            activeRanges[i].refresh();
        }
    },

    /**
     * Gets the number of records in store.
     *
     * If using paging, this may not be the total size of the dataset. If the data object used by
     * the Reader contains the dataset size, then the {@link Ext.data.ProxyStore#getTotalCount}
     * function returns the dataset size. **Note**: see the Important note in
     * {@link Ext.data.ProxyStore#method-load}.
     *
     * When store is filtered, it's the number of records matching the filter.
     *
     * @return {Number} The number of Records in the Store.
     */
    getCount: function() {
        var data = this.getData();

        // We may be destroyed, in which case "data" will be null... best to just
        // report 0 items vs throw an exception

        return data ? data.getCount() : 0;
    },

    /**
     * Determines if the passed range is available in the page cache.
     * @private
     * @param {Number} start The start index
     * @param {Number} end The end index in the range
     */
    rangeCached: function(start, end) {
        return this.getData().getCount() >= Math.max(start, end);
    },

    /**
     * Checks if a record is in the current active data set.
     * @param {Ext.data.Model} record The record
     * @return {Boolean} `true` if the record is in the current active data set.
     * @method contains
     */

    /**
     * Finds the index of the first matching Record in this store by a specific field value.
     *
     * When store is filtered, finds records only within filter.
     *
     * **IMPORTANT**
     *
     * **If this store is {@link Ext.data.BufferedStore Buffered}, this can ONLY find records
     * which happen to be cached in the page cache. This will be parts of the dataset around the
     * currently visible zone, or recently visited zones if the pages have not yet been purged from
     * the cache.**
     *
     * @param {String} property The name of the Record field to test.
     * @param {String/RegExp} value Either a string that the field value
     * should begin with, or a RegExp to test against the field.
     * @param {Number} [startIndex=0] The index to start searching at
     * @param {Boolean} [anyMatch=false] True to match any part of the string, not just the
     * beginning.
     * @param {Boolean} [caseSensitive=false] True for case sensitive comparison
     * @param {Boolean} [exactMatch=false] True to force exact match (^ and $ characters
     * added to the regex). Ignored if `anyMatch` is `true`.
     * @return {Number} The matched index or -1
     */
    find: function(property, value, startIndex, anyMatch, caseSensitive, exactMatch) {
        //             exactMatch
        //  anyMatch    F       T
        //      F       ^abc    ^abc$
        //      T       abc     abc
        //
        var startsWith = !anyMatch,
            endsWith = !!(startsWith && exactMatch);

        return this.getData().findIndex(property, value, startIndex, startsWith, endsWith,
                                        !caseSensitive);
    },

    /**
     * Finds the first matching Record in this store by a specific field value.
     *
     * When store is filtered, finds records only within filter.
     *
     * **IMPORTANT**
     *
     * **If this store is {@link Ext.data.BufferedStore Buffered}, this can ONLY find records which
     * happen to be cached in the page cache. This will be parts of the dataset around the
     * currently visible zone, or recently visited zones if the pages have not yet been purged
     * from the cache.**
     *
     * @param {String} fieldName The name of the Record field to test.
     * @param {String/RegExp} value Either a string that the field value
     * should begin with, or a RegExp to test against the field.
     * @param {Number} [startIndex=0] The index to start searching at
     * @param {Boolean} [anyMatch=false] True to match any part of the string, not just the
     * beginning.
     * @param {Boolean} [caseSensitive=false] True for case sensitive comparison
     * @param {Boolean} [exactMatch=false] True to force exact match (^ and $ characters
     * added to the regex). Ignored if `anyMatch` is `true`.
     * @return {Ext.data.Model} The matched record or null
     */
    findRecord: function() {
        var me = this,
            index = me.find.apply(me, arguments);

        return index !== -1 ? me.getAt(index) : null;
    },

    /**
     * Finds the index of the first matching Record in this store by a specific field value.
     *
     * When store is filtered, finds records only within filter.
     *
     * **IMPORTANT**
     *
     * **If this store is {@link Ext.data.BufferedStore Buffered}, this can ONLY find records which
     * happen to be cached in the page cache. This will be parts of the dataset around the
     * currently visible zone, or recently visited zones if the pages have not yet been purged
     * from the cache.**
     *
     * @param {String} fieldName The name of the Record field to test.
     * @param {Object} value The value to match the field against.
     * @param {Number} [startIndex=0] The index to start searching at
     * @return {Number} The matched index or -1
     */
    findExact: function(fieldName, value, startIndex) {
        return this.getData().findIndexBy(function(rec) {
            return rec.isEqual(rec.get(fieldName), value);
        }, this, startIndex);
    },

    /**
     * Find the index of the first matching Record in this Store by a function.
     * If the function returns `true` it is considered a match.
     *
     * When store is filtered, finds records only within filter.
     *
     * **IMPORTANT**
     *
     * **If this store is {@link Ext.data.BufferedStore Buffered}, this can ONLY find records which
     * happen to be cached in the page cache. This will be parts of the dataset around the
     * currently visible zone, or recently visited zones if the pages have not yet been purged
     * from the cache.**
     *
     * @param {Function} fn The function to be called. It will be passed the following parameters:
     *  @param {Ext.data.Model} fn.record The record to test for filtering. Access field values
     *  using {@link Ext.data.Model#get}.
     *  @param {Object} fn.id The ID of the Record passed.
     * @param {Object} [scope] The scope (this reference) in which the function is executed.
     * Defaults to this Store.
     * @param {Number} [start=0] The index at which to start searching.
     * @return {Number} The matched index or -1
     */
    findBy: function(fn, scope, start) {
        return this.getData().findIndexBy(fn, scope, start);
    },

    /**
     * Get the Record at the specified index.
     *
     * The index is effected by filtering.
     *
     * @param {Number} index The index of the Record to find.
     * @return {Ext.data.Model} The Record at the passed index. Returns null if not found.
     */
    getAt: function(index) {
        return this.getData().getAt(index) || null;
    },

    /**
     * Gathers a range of Records between specified indices.
     *
     * This method is affected by filtering.
     *
     * @param {Number} start The starting index. Defaults to zero.
     * @param {Number} end The ending index. Defaults to the last record. The end index
     * **is included**.
     * @param [options] (private) Used by BufferedRenderer when using a BufferedStore.
     * @return {Ext.data.Model[]} An array of records.
     */
    getRange: function(start, end, options) {
        // Collection's getRange is exclusive. Do NOT mutate the value: it is passed to the
        // callback.
        var result = this.getData().getRange(start, Ext.isNumber(end) ? end + 1 : end);

        // BufferedRenderer requests a range with a callback to process that range.
        // Because it may be dealing with a buffered store and the range may not be available
        // synchronously.
        if (options && options.callback) {
            options.callback.call(options.scope || this, result, start, end, options);
        }

        return result;
    },

    /**
     * Gets the filters for this store.
     * @param {Boolean} [autoCreate] (private)
     * @return {Ext.util.FilterCollection} The filters
     */
    getFilters: function(autoCreate) {
        var me = this,
            result = me.callParent();

        if (!result && autoCreate !== false) {
            me.setFilters([]);
            result = me.callParent();
        }

        return result;
    },

    applyFilters: function(filters, filtersCollection) {
        var me = this,
            created;

        if (!filtersCollection) {
            filtersCollection = me.createFiltersCollection();
            created = true;
        }

        filtersCollection.add(filters);

        if (created) {
            me.onRemoteFilterSet(filtersCollection, me.getRemoteFilter());
        }

        return filtersCollection;
    },

    /**
     * Gets the sorters for this store.
     * @param {Boolean} [autoCreate] (private)
     * @return {Ext.util.SorterCollection} The sorters
     */
    getSorters: function(autoCreate) {
        var me = this,
            result = me.callParent();

        if (!result && autoCreate !== false) {
            // If not preventing creation, force it here
            me.setSorters([]);

            result = me.callParent();
        }

        return result;
    },

    applySorters: function(sorters, sortersCollection) {
        var me = this,
            created;

        if (!sortersCollection) {
            sortersCollection = me.createSortersCollection();
            created = true;
        }

        sortersCollection.add(sorters);

        if (created) {
            me.onRemoteSortSet(sortersCollection, me.getRemoteSort());
        }

        return sortersCollection;
    },

    /**
     * Filters the data in the Store by one or more fields. Example usage:
     *
     *     //filter with a single field
     *     myStore.filter('firstName', 'Don');
     *
     *     //filtering with multiple filters
     *     myStore.filter([
     *         {
     *             property : 'firstName',
     *             value    : 'Don'
     *         },
     *         {
     *             property : 'lastName',
     *             value    : 'Griffin'
     *         }
     *     ]);
     *
     * Internally, Store converts the passed arguments into an array of
     * {@link Ext.util.Filter} instances, and delegates the actual filtering to its internal
     * {@link Ext.util.Collection} or the remote server.
     *
     * @param {String/Ext.util.Filter[]} [filters] Either a string name of one of the
     * fields in this Store's configured {@link Ext.data.Model Model}, or an array of
     * filter configurations.
     * @param {String} [value] The property value by which to filter. Only applicable if
     * `filters` is a string.
     * @param {Boolean} [suppressEvent] (private)
     */
    filter: function(filters, value, suppressEvent) {
        if (Ext.isString(filters)) {
            filters = {
                property: filters,
                value: value
            };
        }

        this.suppressNextFilter = !!suppressEvent;
        this.getFilters().add(filters);
        this.suppressNextFilter = false;
    },

    /**
     * Removes an individual Filter from the current {@link #cfg-filters filter set}
     * using the passed Filter/Filter id and by default, applies the updated filter set
     * to the Store's unfiltered dataset.
     *
     * @param {String/Ext.util.Filter} toRemove The id of a Filter to remove from the
     * filter set, or a Filter instance to remove.
     * @param {Boolean} [suppressEvent] If `true` the filter is cleared silently.
     */
    removeFilter: function(toRemove, suppressEvent) {
        var me = this,
            filters = me.getFilters();

        me.suppressNextFilter = !!suppressEvent;

        if (toRemove instanceof Ext.util.Filter) {
            filters.remove(toRemove);
        }
        else {
            filters.removeByKey(toRemove);
        }

        me.suppressNextFilter = false;
    },

    updateAutoSort: function(autoSort) {
        // Keep collection synced with our autoSort setting
        this.getData().setAutoSort(autoSort);
    },

    updateRemoteSort: function(remoteSort) {
        // Don't call the getter here, we don't want to force sorters to be created here.
        // Also, applySorters calls getRemoteSort, which may trigger the initGetter.
        this.onRemoteSortSet(this.getSorters(false), remoteSort);
    },

    updateRemoteFilter: function(remoteFilter) {
        this.onRemoteFilterSet(this.getFilters(false), remoteFilter);
    },

    /**
     * Adds a new Filter to this Store's {@link #cfg-filters filter set} and
     * by default, applies the updated filter set to the Store's unfiltered dataset.
     * @param {Object[]/Ext.util.Filter[]} filters The set of filters to add to the current
     * {@link #cfg-filters filter set}.
     * @param {Boolean} [suppressEvent] If `true` the filter is cleared silently.
     */
    addFilter: function(filters, suppressEvent) {
        this.suppressNextFilter = !!suppressEvent;
        this.getFilters().add(filters);
        this.suppressNextFilter = false;
    },

    /**
     * Filters by a function. The specified function will be called for each
     * Record in this Store. If the function returns `true` the Record is included,
     * otherwise it is filtered out.
     *
     * When store is filtered, most of the methods for accessing store data will be working only
     * within the set of filtered records. The notable exception is {@link #getById}.
     *
     * @param {Function} fn The function to be called. It will be passed the following parameters:
     *  @param {Ext.data.Model} fn.record The record to test for filtering. Access field values
     *  using {@link Ext.data.Model#get}.
     * @param {Object} [scope] The scope (this reference) in which the function is executed.
     * Defaults to this Store.
     */
    filterBy: function(fn, scope) {
        this.getFilters().add({
            filterFn: fn,
            scope: scope || this
        });
    },

    /**
     * Reverts to a view of the Record cache with no filtering applied.
     * @param {Boolean} [suppressEvent] If `true` the filter is cleared silently.
     *
     * For a locally filtered Store, this means that the filter collection is cleared without
     * firing the {@link #datachanged} event.
     *
     * For a remotely filtered Store, this means that the filter collection is cleared, but
     * the store is not reloaded from the server.
     */
    clearFilter: function(suppressEvent) {
        var me = this,
            filters = me.getFilters(false);

        if (!filters || filters.getCount() === 0) {
            return;
        }

        me.suppressNextFilter = !!suppressEvent;
        filters.removeAll();
        me.suppressNextFilter = false;
    },

    /**
     * Tests whether the store currently has any active filters.
     * @return {Boolean} `true` if the store is filtered.
     */
    isFiltered: function() {
        return this.getFilters().getCount() > 0;
    },

    /**
     * Tests whether the store currently has any active sorters.
     * @return {Boolean} `true` if the store is sorted.
     */
    isSorted: function() {
        var sorters = this.getSorters(false);

        return !!(sorters && sorters.length > 0) || this.isGrouped();
    },

    addFieldTransform: function(sorter) {
        // Transform already specified, leave it
        if (sorter.getTransform()) {
            return;
        }

        /* eslint-disable-next-line vars-on-top */
        var fieldName = sorter.getProperty(),
            Model = this.getModel(),
            field, sortType;

        if (Model) {
            field = Model.getField(fieldName);
            sortType = field ? field.getSortType() : null;
        }

        if (sortType && sortType !== Ext.identityFn) {
            sorter.setTransform(sortType);
        }
    },

    /**
     * This method may be called to indicate the start of multiple changes to the store.
     *
     * Automatic synchronization as configured by the {@link Ext.data.ProxyStore#autoSync autoSync}
     * flag is deferred until the {@link #endUpdate} method is called, so multiple mutations can be
     * coalesced into one synchronization operation.
     *
     * Internally this method increments a counter that is decremented by `endUpdate`. It
     * is important, therefore, that if you call `beginUpdate` directly you match that
     * call with a call to `endUpdate` or you will prevent the collection from updating
     * properly.
     *
     * For example:
     *
     *      var store = Ext.StoreManager.lookup({
     *          //...
     *          autoSync: true
     *      });
     *
     *      store.beginUpdate();
     *
     *      record.set('fieldName', 'newValue');
     *
     *      store.add(item);
     *      // ...
     *
     *      store.insert(index, otherItem);
     *      //...
     *
     *      // Interested parties will listen for the endupdate event
     *      store.endUpdate();
     *
     * @since 5.0.0
     */
    beginUpdate: function() {
        if (!this.updating++ && this.hasListeners.beginupdate) {
            this.fireEvent('beginupdate');
        }
    },

    /**
     * This method is called after modifications are complete on a store. For details
     * see `{@link #beginUpdate}`.
     * @since 5.0.0
     */
    endUpdate: function() {
        if (this.updating && ! --this.updating) {
            if (this.hasListeners.endupdate) {
                this.fireEvent('endupdate');
            }

            this.onEndUpdate();
        }
    },

    /**
     * @private
     * Returns the grouping, sorting and filtered state of this Store.
     */
    getState: function() {
        var me = this,
            sorters = [],
            filters = me.getFilters(),
            grouper = me.getGrouper(),
            filterState, hasState, result;

        // Create sorters config array.
        me.getSorters().each(function(s) {
            sorters[sorters.length] = s.getState();
            hasState = true;
        });

        // Because we do not provide a filter changing mechanism, only statify the filters if they
        // opt in. Otherwise filters would get "stuck".
        if (me.statefulFilters && me.saveStatefulFilters) {
            // If saveStatefulFilters is turned on then we know that the filter collection has
            // changed since page load. Initiate the filterState as an empty stack, which is
            // meaningful in itself. If there are any filter in the collection, persist them.
            hasState = true;
            filterState = [];

            filters.each(function(f) {
                filterState[filterState.length] = f.getState();
            });
        }

        if (grouper) {
            hasState = true;
        }

        // If there is any state to save, return it as an object
        if (hasState) {
            result = {};

            if (sorters.length) {
                result.sorters = sorters;
            }

            if (filterState) {
                result.filters = filterState;
            }

            if (grouper) {
                result.grouper = grouper.getState();
            }
        }

        return result;
    },

    /**
     * @private
     * Restores state to the passed state
     */
    applyState: function(state) {
        var me = this,
            stateSorters = state.sorters,
            stateFilters = state.filters,
            stateGrouper = state.grouper;

        if (stateSorters) {
            me.getSorters().replaceAll(stateSorters);
        }

        if (stateFilters) {
            // We found persisted filters so let's save stateful filters from this point forward.
            me.saveStatefulFilters = true;
            me.getFilters().replaceAll(stateFilters);
        }

        if (stateGrouper) {
            me.setGrouper(stateGrouper);
        }
    },

    /**
     * Get the Record with the specified id.
     *
     * This method is not affected by filtering, lookup will be performed from all records
     * inside the store, filtered or not.
     *
     * @param {Mixed} id The id of the Record to find.
     * @return {Ext.data.Model} The Record with the passed id. Returns null if not found.
     * @method getById
     */

    /**
     * Returns true if the store has a pending load task.
     * @return {Boolean} `true` if the store has a pending load task.
     * @private
     * @method
     */
    hasPendingLoad: Ext.emptyFn,

    /**
     * Returns `true` if the Store has been loaded.
     * @return {Boolean} `true` if the Store has been loaded.
     * @method
     */
    isLoaded: Ext.emptyFn,

    /**
     * Returns `true` if the Store is currently performing a load operation.
     * @return {Boolean} `true` if the Store is currently loading.
     * @method
     */
    isLoading: Ext.emptyFn,

    destroy: function() {
        var me = this;

        if (me.hasListeners.beforedestroy) {
            me.fireEvent('beforedestroy', me);
        }

        me.destroying = true;

        if (me.getStoreId()) {
            Ext.data.StoreManager.unregister(me);
        }

        me.doDestroy();

        if (me.hasListeners.destroy) {
            me.fireEvent('destroy', me);
        }

        // This just makes it hard to ask "was destroy() called?":
        // me.destroying = false; // removed in 7.0

        // This will finish the sequence and null object references
        me.callParent();
    },

    /**
     * Perform the Store destroying sequence. Override this method to add destruction
     * behaviors to your custom Stores.
     *
     */
    doDestroy: Ext.emptyFn,

    /**
     * Sorts the data in the Store by one or more of its properties. Example usage:
     *
     *     //sort by a single field
     *     myStore.sort('myField', 'DESC');
     *
     *     //sorting by multiple fields
     *     myStore.sort([
     *         {
     *             property : 'age',
     *             direction: 'ASC'
     *         },
     *         {
     *             property : 'name',
     *             direction: 'DESC'
     *         }
     *     ]);
     *
     * Internally, Store converts the passed arguments into an array of {@link Ext.util.Sorter}
     * instances, and either delegates the actual sorting to its internal
     * {@link Ext.util.Collection} or the remote server.
     *
     * When passing a single string argument to sort, Store maintains a ASC/DESC toggler per field,
     * so this code:
     *
     *     store.sort('myField');
     *     store.sort('myField');
     *
     * Is equivalent to this code, because Store handles the toggling automatically:
     *
     *     store.sort('myField', 'ASC');
     *     store.sort('myField', 'DESC');
     *
     * @param {String/Ext.util.Sorter[]} [field] Either a string name of one of the
     * fields in this Store's configured {@link Ext.data.Model Model}, or an array of
     * sorter configurations.
     * @param {"ASC"/"DESC"} [direction="ASC"] The overall direction to sort the data by.
     * @param {"append"/"prepend"/"replace"/"multi"} [mode="replace"]
     */
    sort: function(field, direction, mode) {
        var me = this;

        if (arguments.length === 0) {
            if (me.getRemoteSort()) {
                me.load();
            }
            else {
                me.forceLocalSort();
            }
        }
        else {
            me.getSorters().addSort(field, direction, mode);
        }
    },

    // This is attached to the data Collection's beforesort event only if not remoteSort
    // If remoteSort, the event is fired before the reload call in Ext.data.ProxyStore#load.
    onBeforeCollectionSort: function(store, sorters) {
        if (sorters) {
            this.fireEvent('beforesort', this, sorters.getRange());
        }
    },

    onSorterEndUpdate: function() {
        var me = this,
            fireSort = true,
            sorters = me.getSorters(false),
            sorterCount;

        // If we're in the middle of grouping, it will take care of loading.
        // If the collection is not instantiated yet, it's because we are constructing.
        if (me.settingGroups || !sorters) {
            return;
        }

        sorters = sorters.getRange();
        sorterCount = sorters.length;

        if (me.getRemoteSort()) {
            // Only reload if there are sorters left to influence the sort order.
            // Unless reloadOnClearSorters is set to indicate that there's a default
            // order used by the server which must be returned to when there is no
            // explicit sort order.
            if (sorters.length || me.getReloadOnClearSorters()) {
                // The sort event will fire in the load callback;
                fireSort = false;

                me.load({
                    callback: function() {
                        me.fireEvent('sort', me, sorters);
                    }
                });
            }
        }
        else if (sorterCount) {
            me.fireEvent('datachanged', me);
            me.fireEvent('refresh', me);
        }

        if (fireSort) {
            // Sort event must fire when sorters collection is updated to empty.
            me.fireEvent('sort', me, sorters);
        }
    },

    onFilterEndUpdate: function() {
        var me = this,
            suppressNext = me.suppressNextFilter,
            filters = me.getFilters(false);

        // If the collection is not instantiated yet, it's because we are constructing.
        if (!filters) {
            return;
        }

        if (me.getRemoteFilter()) {
            //<debug>
            me.getFilters().each(function(filter) {
                if (filter.getInitialConfig().filterFn) {
                    Ext.raise('Unable to use a filtering function in conjunction with ' +
                              'remote filtering.');
                }
            });
            //</debug>
            me.currentPage = 1;

            if (!suppressNext) {
                me.load();
            }
        }
        else if (!suppressNext) {
            me.fireEvent('datachanged', me);
            me.fireEvent('refresh', me);
        }

        if (me.trackStateChanges) {
            // We just mutated the filter collection so let's save stateful filters
            // from this point forward.
            me.saveStatefulFilters = true;
        }

        // This is not affected by suppressEvent.
        me.fireEvent('filterchange', me, me.getFilters().getRange());
    },

    updateGroupField: function(field) {
        if (field) {
            this.setGrouper({
                property: field,
                direction: this.getGroupDir()
            });
        }
        else {
            this.setGrouper(null);
        }
    },

    /**
     * @method setFilters
     */

    /**
     * @method setSorters
     */

    getGrouper: function() {
        return this.getData().getGrouper();
    },

    /**
     * Groups data inside the store.
     * @param {String/Object} grouper Either a string name of one of the fields in this Store's
     * configured {@link Ext.data.Model Model}, or an object, or a {@link Ext.util.Grouper grouper}
     * configuration object.
     * @param {String} [direction] The overall direction to group the data by. Defaults to the
     * value of {@link #groupDir}.
     */
    group: function(grouper, direction) {
        var me = this,
            sorters = me.getSorters(false),
            change = grouper || (sorters && sorters.length),
            data = me.getData();

        if (grouper && typeof grouper === 'string') {
            grouper = {
                property: grouper,
                direction: direction || me.getGroupDir()
            };
        }

        me.settingGroups = true;

        // The config system would reject this case as no change
        // Assume the caller has changed a configuration of the Grouper
        // and requires the sorting to be redone.
        if (grouper === data.getGrouper()) {
            data.updateGrouper(grouper);
        }
        else {
            data.setGrouper(grouper);
        }

        delete me.settingGroups;

        if (change) {
            if (me.getRemoteSort()) {
                if (!me.isInitializing) {
                    me.load({
                        scope: me,
                        callback: function() {
                            me.fireGroupChange(); // do not pass on args
                        }
                    });
                }
            }
            else {
                me.fireEvent('datachanged', me);
                me.fireEvent('refresh', me);
                me.fireGroupChange();
            }
        }
        // groupchange event must fire when group is cleared.
        // The Grouping feature forces a view refresh when changed to a null grouper
        else {
            me.fireGroupChange();
        }
    },

    fireGroupChange: function(grouper) {
        var me = this;

        if (!me.isConfiguring && !me.destroying && !me.destroyed) {
            me.fireGroupChangeEvent(grouper || me.getGrouper());
        }
    },

    fireGroupChangeEvent: function(grouper) {
        this.fireEvent('groupchange', this, grouper);
    },

    /**
     * Clear the store grouping
     */
    clearGrouping: function() {
        this.group(null);
    },

    getGroupField: function() {
        var grouper = this.getGrouper(),
            group = '';

        if (grouper) {
            group = grouper.getProperty();
        }

        return group;
    },

    /**
     * Tests whether the store currently has an active grouper.
     * @return {Boolean} `true` if the store is grouped.
     */
    isGrouped: function() {
        return !!this.getGrouper();
    },

    applyGrouper: function(grouper) {
        this.group(grouper);

        return this.getData().getGrouper();
    },

    /**
     * Returns a collection of readonly sub-collections of your store's records
     * with grouping applied. These sub-collections are maintained internally by
     * the collection.
     *
     * See {@link #groupField}, {@link #groupDir}. Example for a store
     * containing records with a color field:
     *
     *     var myStore = Ext.create('Ext.data.Store', {
     *         groupField: 'color',
     *         groupDir  : 'DESC'
     *     });
     *
     *     myStore.getGroups();
     *
     * The above should result in the following format:
     *
     *     [
     *         {
     *             name: 'yellow',
     *             children: [
     *                 // all records where the color field is 'yellow'
     *             ]
     *         },
     *         {
     *             name: 'red',
     *             children: [
     *                 // all records where the color field is 'red'
     *             ]
     *         }
     *     ]
     *
     * Group contents are affected by filtering.
     *
     * @return {Ext.util.Collection} The grouped data
     */
    getGroups: function() {
        return this.getData().getGroups();
    },

    onEndUpdate: Ext.emptyFn,

    privates: {
        _metaProperties: {
            count: 'getCount',
            first: 'first',
            last: 'last',
            loading: 'hasPendingLoad',
            totalCount: 'getTotalCount'
        },

        interpret: function(name) {
            var me = this,
                accessor = me._metaProperties[name];

            return accessor && me[accessor](); // e.g., me.getCount()
        },

        loadsSynchronously: Ext.privateFn,

        onRemoteFilterSet: function(filters, remoteFilter) {
            if (filters) {
                filters[remoteFilter ? 'on' : 'un']('endupdate', 'onFilterEndUpdate', this);
            }
        },

        // If remoteSort is set, we react to the endUpdate of the sorters Collection by reloading
        // if there are still some sorters, or we're configured to reload on sorter remove.
        // If remoteSort is set, we do not need to listen for the data Collection's beforesort
        // event.
        //
        // If local sorting, we do not need to react to the endUpdate of the sorters Collection.
        // If local sorting, we listen for the data Collection's beforesort event to fire our
        // beforesort event.
        onRemoteSortSet: function(sorters, remoteSort) {
            var me = this,
                data;

            if (sorters) {
                sorters[remoteSort ? 'on' : 'un']('endupdate', 'onSorterEndUpdate', me);

                data = me.getData();

                if (data) {
                    data[remoteSort ? 'un' : 'on']('beforesort', 'onBeforeCollectionSort', me);
                }
            }
        }
    },

    deprecated: {
        5: {
            methods: {
                destroyStore: function() {
                    this.destroy();
                }
            }
        }
    }
});
