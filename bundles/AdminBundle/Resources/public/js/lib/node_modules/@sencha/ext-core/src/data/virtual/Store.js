/**
 * This type of store is a replacement for BufferedStore at least for Modern. The primary
 * use of this store is to create and manage "active ranges" of records.
 *
 * For example:
 *
 *      var range = store.createActiveRange({
 *          begin: 100,
 *          end: 200,
 *          prefetch: true,  // allow prefetching beyond range
 *          callback: 'onRangeUpdate',
 *          scope: this
 *      });
 *
 *      // Navigate to a different range. This will causes pages to load and
 *      // the onRangeUpdate method will be called as the load(s) progress.
 *      // This can change the length or number of records spanned on each
 *      // call.
 *      //
 *      range.goto(300, 400);
 *
 *      onRangeUpdate: function (range, begin, end) {
 *          // Called when records appear in the range...
 *          // We can check if all things are loaded:
 *
 *          // Or we can use range.records (sparsely populated)
 *      }
 *
 * @since 6.5.0
 */
Ext.define('Ext.data.virtual.Store', {
    extend: 'Ext.data.ProxyStore',
    alias: 'store.virtual',

    requires: [
        'Ext.util.SorterCollection',
        'Ext.util.FilterCollection',

        'Ext.data.virtual.PageMap',
        'Ext.data.virtual.Range'
    ],

    uses: [
        'Ext.data.virtual.Group'
    ],

    isVirtualStore: true,

    config: {
        data: null,
        totalCount: null,
        /**
         * @cfg {Number} leadingBufferZone
         * The number of records to fetch beyond the active range in the direction of
         * movement. If the range is advancing forward, the additional records are beyond
         * `end`. If advancing backwards, they are before `begin`.
         */
        leadingBufferZone: 200,

        /**
         * @cfg {Number} trailingBufferZone
         * The number of records to fetch beyond the active trailing the direction of
         * movement. If the range is advancing forward, the additional records are before
         * `begin`. If advancing backwards, they are beyond `end`.
         */
        trailingBufferZone: 50
    },

    /**
     * @cfg remoteSort
     * @inheritdoc
     */
    remoteSort: true,

    /**
     * @cfg remoteFilter
     * @inheritdoc
     */
    remoteFilter: true,

    /**
     * @cfg sortOnLoad
     * @inheritdoc
     */
    sortOnLoad: false,

    /**
     * @cfg trackRemoved
     * @inheritdoc
     */
    trackRemoved: false,

    constructor: function(config) {
        var me = this;

        me.sortByPage = me.sortByPage.bind(me);

        me.activeRanges = [];

        me.pageMap = new Ext.data.virtual.PageMap({
            store: me
        });

        me.callParent([ config ]);
    },

    doDestroy: function() {
        this.pageMap.destroy();
        this.callParent();
    },

    applyGrouper: function(grouper) {
        this.group(grouper);

        return this.grouper;
    },

    //-----------------------------------------------------------------------

    /**
     * @method contains
     * @inheritdoc
     */
    contains: function(record) {
        return this.indexOf(record) > -1;
    },

    /**
     * Create a `Range` instance to access records by their index.
     *
     * @param {Object/Ext.data.virtual.Range} [config]
     * @return {Ext.data.virtual.Range}
     * @since 6.5.0
     */
    createActiveRange: function(config) {
        var range = Ext.apply({
            leadingBufferZone: this.getLeadingBufferZone(),
            trailingBufferZone: this.getTrailingBufferZone(),
            store: this
        }, config);

        return new Ext.data.virtual.Range(range);
    },

    /**
     * @method getAt
     * @inheritdoc
     */
    getAt: function(index) {
        var page = this.pageMap.getPageOf(index, /* autoCreate= */false),
            ret;

        if (page && page.records) { // if (page is loaded)
            ret = page.records[index - page.begin];
        }

        return ret || null;
    },

    /**
     * Get the Record with the specified id.
     *
     * This method is not affected by filtering, lookup will be performed from all records
     * inside the store, filtered or not.
     *
     * @param {Mixed} id The id of the Record to find.
     * @return {Ext.data.Model} The Record with the passed id. Returns null if not found.
     */
    getById: function(id) {
        return this.pageMap.byId[id] || null;
    },

    getCount: function() {
        return this.totalCount || 0;
    },

    getGrouper: function() {
        return this.grouper;
    },

    getGroups: function() {
        var me = this,
            groups = me.groupCollection;

        if (!groups) {
            me.groupCollection = groups = new Ext.util.Collection();
        }

        return groups;
    },

    getSummaryRecord: function() {
        return this.summaryRecord || null;
    },

    isGrouped: function() {
        return !!this.grouper;
    },

    group: function(grouper, direction) {
        var me = this;

        grouper = grouper || null;

        if (grouper) {
            if (typeof grouper === 'string') {
                grouper = {
                    property: grouper,
                    direction: direction || 'ASC'
                };
            }

            if (!grouper.isGrouper) {
                grouper = new Ext.util.Grouper(grouper);
            }

            grouper.setRoot('data');

            me.getGroups().getSorters().splice(0, 1, {
                property: 'id',
                direction: grouper.getDirection()
            });
        }

        me.grouper = grouper;

        if (!me.isConfiguring) {
            me.reload();
            me.fireGroupChange(grouper);
        }
    },

    getByInternalId: function(internalId) {
        return this.pageMap.getByInternalId(internalId);
    },

    /**
     * Get the index of the record within the virtual store. Because virtual stores only
     * load a partial set of records, not all records in the logically matching set will
     * have been loaded and will therefore return -1.
     *
     * @param {Ext.data.Model} record The record to find.
     * @return {Number} The index of the `record` or -1 if not found.
     */
    indexOf: function(record) {
        return this.pageMap.indexOf(record);
    },

    /**
     * Get the index within the store of the record with the passed id. Because virtual
     * stores only load a partial set of records, not all records in the logically
     * matching set will have been loaded and will therefore return -1.
     *
     * @param {String} id The id of the record to find.
     * @return {Number} The index of the record or -1 if not found.
     */
    indexOfId: function(id) {
        var rec = this.getById(id);

        return rec ? this.indexOf(rec) : -1;
    },

    /**
     * Returns `true` if the store has been loaded.
     * @return {Boolean} `true` if the store has been loaded.
     */
    isLoaded: function() {
        return Ext.isNumber(this.totalCount);
    },

    load: function(options) {
        if (typeof options === 'function') {
            options = {
                callback: options
            };
        }

        /* eslint-disable-next-line vars-on-top */
        var me = this,
            page = (options && options.page) || 1,
            pageSize = me.getPageSize(),
            operation = me.createOperation('read', Ext.apply({
                start: (page - 1) * pageSize,
                limit: pageSize,
                page: page,

                filters: me.getFilters().items,
                sorters: me.getSorters().items,
                grouper: me.getGrouper()
            }, options));

        if (me.fireEvent('beforeload', me, operation) !== false) {
            me.onBeforeLoad(operation);
            operation.execute();
        }
        else {
            operation.setCompleted();
        }

        return operation;
    },

    reload: function(options) {
        var me = this;

        if (typeof options === 'function') {
            options = {
                callback: options
            };
        }

        if (me.fireEvent('beforereload') === false) {
            return null;
        }

        options = Ext.apply({
            internalScope: me,
            internalCallback: me.handleReload,
            page: 1
        }, options);

        me.pageMap.clear();
        me.getGroups().clear();

        return me.load(options);
    },

    // TODO load?
    // TODO reload?

    removeAll: function() {
        var me = this,
            activeRanges = me.activeRanges,
            i;

        me.pageMap.clear();

        for (i = activeRanges.length; i-- > 0;) {
            activeRanges[i].reset();
        }

        me.fireEvent('clear', me);
    },

    //---------------------------------------------------------------------

    applyProxy: function(proxy) {
        proxy = this.callParent([proxy]);

        // This store asks for pages.
        // If used with a MemoryProxy, it must work
        if (proxy && proxy.setEnablePaging) {
            proxy.setEnablePaging(true);
        }

        return proxy;
    },

    // createDataCollection: function () {
    //     var result = new Ext.data.virtual.Data({
    //             store: this
    //         });
    //
    //     return result;
    // },

    createFiltersCollection: function() {
        return new Ext.util.FilterCollection();
    },

    createSortersCollection: function() {
        return new Ext.util.SorterCollection();
    },

    onFilterEndUpdate: function() {
        var me = this,
            filters = me.getFilters(false);

        // This is not affected by suppressEvent.
        if (!me.isConfiguring) {
            me.reload();
            me.fireEvent('filterchange', me, filters.getRange());
        }
    },

    onSorterEndUpdate: function() {
        var me = this,
            sorters = me.getSorters().getRange(),
            fire = !me.isConfiguring;

        if (fire) {
            me.fireEvent('beforesort', me, sorters);
        }

        if (fire) {
            me.reload();
            me.fireEvent('sort', me, sorters);
        }
    },

    updatePageSize: function(pageSize) {
        var totalCount = this.totalCount;

        if (totalCount !== null) {
            this.pageMap.setPageCount(Math.ceil(totalCount / pageSize));
        }
    },

    updateTotalCount: function(totalCount, oldTotalCount) {
        var me = this,
            pageMap = me.pageMap;

        me.totalCount = totalCount;

        pageMap.setPageCount(Math.ceil(totalCount / me.getPageSize()));

        me.fireEvent('totalcountchange', me, totalCount, oldTotalCount);
    },

    //--------------------------------------------------------
    // Unsupported API's

    //<debug>
    add: function() {
        Ext.raise('Virtual stores do not support the add() method');
    },

    insert: function() {
        Ext.raise('Virtual stores do not support the insert() method');
    },

    filter: function() {
        if (!this.getRemoteFilter()) {
            Ext.raise('Virtual stores do not support local filtering');
        }

        // Remote filtering forces a load. load clears the store's contents.
        this.callParent(arguments);
    },

    filterBy: function() {
        Ext.raise('Virtual stores do not support local filtering');
    },

    loadData: function() {
        Ext.raise('Virtual stores do not support the loadData() method');
    },

    applyData: function() {
        Ext.raise('Virtual stores do not support direct data loading');
    },

    updateRemoteFilter: function(remoteFilter, oldRemoteFilter) {
        if (remoteFilter === false) {
            Ext.raise('Virtual stores are always remotely filtered.');
        }

        this.callParent([remoteFilter, oldRemoteFilter]);
    },

    updateRemoteSort: function(remoteSort, oldRemoteSort) {
        if (remoteSort === false) {
            Ext.raise('Virtual stores are always remotely sorted.');
        }

        this.callParent([remoteSort, oldRemoteSort]);
    },

    updateTrackRemoved: function(value) {
        if (value !== false) {
            Ext.raise('Virtual stores do not support trackRemoved.');
        }

        this.callParent(arguments);
    },
    //</debug>

    afterEdit: function(record, modifiedFieldNames) {
        var me = this;

        me.fireEvent('update', me, record, Ext.data.Model.EDIT, modifiedFieldNames);
        me.fireEvent('datachanged', me);
    },

    privates: {
        attachSummaryData: function(resultSet) {
            var me = this,
                summary = resultSet.getSummaryData(),
                grouper, len, i, data, rec;

            if (summary) {
                me.summaryRecord = summary;
            }

            summary = resultSet.getGroupData();

            if (summary) {
                grouper = me.getGrouper();

                if (grouper) {
                    me.groupSummaryData = data = {};

                    for (i = 0, len = summary.length; i < len; ++i) {
                        rec = summary[i];
                        data[grouper.getGroupString(rec)] = rec;
                    }
                }
            }
        },

        handleReload: function(op) {
            var me = this,
                activeRanges = me.activeRanges,
                len = activeRanges.length,
                pageMap = me.pageMap,
                resultSet = op.getResultSet(),
                wasSuccessful = op.wasSuccessful(),
                rsRecords = [],
                i, range;

            if (wasSuccessful) {
                me.readTotalCount(resultSet);
                me.fireEvent('reload', me, op);

                for (i = 0; i < len; ++i) {
                    range = activeRanges[i];

                    if (pageMap.canSatisfy(range)) {
                        range.reload();
                    }
                }
            }

            if (resultSet) {
                rsRecords = resultSet.records;
            }

            me.fireEvent('load', me, rsRecords, wasSuccessful, op);
        },

        loadVirtualPage: function(page, callback, scope) {
            var me = this,
                pageMapGeneration = me.pageMap.generation;

            return me.load({
                page: page.number + 1, // store loads are 1 based
                internalCallback: function(op) {
                    var resultSet = op.getResultSet(),
                        rsRecords = [];

                    if (pageMapGeneration === me.pageMap.generation) {
                        if (op.wasSuccessful()) {
                            me.readTotalCount(resultSet);

                            me.attachSummaryData(resultSet);
                        }

                        callback.call(scope || page, op);
                        me.groupSummaryData = null;

                        if (resultSet) {
                            rsRecords = resultSet.records;
                        }

                        me.fireEvent('load', me, rsRecords, op.wasSuccessful(), op);
                    }
                }
            });
        },

        lockGroups: function(grouper, page) {
            var groups = this.getGroups(),
                groupInfo = page.groupInfo = {},
                records = page.records,
                len = records.length,
                groupSummaryData = this.groupSummaryData,
                pageMap = this.pageMap,
                n = page.number,
                group, i, groupKey, summaryRec,
                rec, firstRecords, first;

            for (i = 0; i < len; ++i) {
                rec = records[i];
                groupKey = grouper.getGroupString(rec);

                if (!groupInfo[groupKey]) {
                    groupInfo[groupKey] = rec;

                    group = groups.get(groupKey);

                    if (!group) {
                        group = new Ext.data.virtual.Group(groupKey);
                        groups.add(group);
                    }

                    // We want to track the first known record in the group.
                    // If we have a record that is before the first one we know
                    // about, add it to the front. Otherwise, we don't care about
                    // the order at this point, so just shift it on to the end.
                    firstRecords = group.firstRecords;
                    first = firstRecords[0];

                    if (first && n < pageMap.getPageIndex(first)) {
                        firstRecords.unshift(rec);
                    }
                    else {
                        firstRecords.push(rec);
                    }

                    summaryRec = groupSummaryData && groupSummaryData[groupKey];

                    if (summaryRec) {
                        group.summaryRecord = summaryRec;
                    }
                }
            }
        },

        onPageDataAcquired: function(page) {
            var grouper = this.getGrouper();

            if (grouper) {
                this.lockGroups(grouper, page);
            }
        },

        onPageDestroy: function(page) {
            var ranges = this.activeRanges,
                len = ranges.length,
                i;

            for (i = 0; i < len; ++i) {
                ranges[i].onPageDestroy(page);
            }
        },

        onPageEvicted: function(page) {
            var grouper = this.getGrouper();

            if (grouper) {
                this.releaseGroups(grouper, page);
            }
        },

        readTotalCount: function(resultSet) {
            var total = resultSet.getRemoteTotal();

            if (!isNaN(total)) {
                this.setTotalCount(total);
            }
        },

        releaseGroups: function(grouper, page) {
            var groups = this.getGroups(),
                groupInfo = page.groupInfo,
                first, firstRecords, key, group;

            for (key in groupInfo) {
                first = groupInfo[key];
                group = groups.get(key);
                firstRecords = group.firstRecords;

                // If there is only 1 first record left, this must be it, which
                // means the group no longer has records
                if (firstRecords.length === 1) {
                    groups.remove(group);
                }
                else if (firstRecords[0] === first) {
                    firstRecords.shift();
                    firstRecords.sort(this.sortByPage);
                }
                else {
                    Ext.Array.remove(firstRecords, first);
                }
            }
        },

        sortByPage: function(rec1, rec2) {
            // Bound to this instance in the constructor
            var map = this.pageMap;

            return map.getPageIndex(rec1) - map.getPageIndex(rec2);
        }
    }
});
