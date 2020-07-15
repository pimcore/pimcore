/**
 * This class manages a page of records in a virtual store's `PageMap`. It is created
 * with the page `number` (0-based) and uses the store's `pageSize` to calculate the
 * record span covered by it and stores these as `begin` and `end` properties. These
 * aspects of the `Page` as well as the owning `PageMap` are expected to be immutable
 * throughout the instance's life-cycle.
 *
 * The normal use for a `Page` is by a `Range`. Ranges acquire and `lock` the pages they
 * span. As they move on, they `release` these locks. The act of locking pages schedules
 * them for loading. Unlocking pages allows them to be evicted from the `PageMap` to
 * reclaim memory for other pages.
 *
 * @private
 * @since 6.5.0
 */
Ext.define('Ext.data.virtual.Page', {
    isVirtualPage: true,

    /**
     * @property {Number} begin
     * The start index of the records that this page represents.
     * Inclusive.
     * @readonly
     */
    begin: 0,

    /**
     * @property {Number} end
     * The end index of the records that this page represents.
     * Exclusive.
     * @readonly
     */
    end: 0,

    /**
     * @property {Error} error
     * The error instance if the page load `operation` failed. If this property is set,
     * the `state` will be "error".
     * @readonly
     */
    error: null,

    /**
     * @property {"active"/"prefetch"} locked
     * This property is managed by the `lock` and `release` methods. It is set to `null`
     * if the page is not locked or it will be set to the string describing the type of
     * the current lock.
     *
     * When pages are `locked` for the first time, they are scheduled for loading by the
     * owning `pageMap`.
     *
     * Locked pages are not eligible for removal from the `PageMap`.
     * @readonly
     */
    locked: null,

    /**
     * @cfg {Number} number
     * The 0-based page number of this page.
     * @readonly
     */
    number: 0,

    /**
     * @property {Ext.data.operation.Read} operation
     * The pending read of the records for this page. This property is only set when the
     * page is in the "loading" `state`.
     * @readonly
     */
    operation: null,

    /**
     * @property {Ext.data.virtual.PageMap} pageMap
     * The owning `PageMap` instance.
     * @readonly
     */
    pageMap: null,

    /**
     * @property {Ext.data.Model[]} records
     * The array of records loaded for this page. The `records[0]` item corresponds to
     * the record at index `begin` in the overall result set.
     * @readonly
     */
    records: null,

    /**
     * @property {"loading"/"loaded"/"error"} state
     * This property describes the current life-cycle state for this page. At creation,
     * this property will be `null` for the "initial" state.
     * @readonly
     */
    state: null,

    constructor: function(config) {
        var me = this,
            pageSize;

        Ext.apply(me, config);

        pageSize = me.pageMap.store.getPageSize();

        me.begin = me.number * pageSize;
        me.end = me.begin + pageSize;

        me.locks = {
            active: 0,
            prefetch: 0
        };
    },

    destroy: function() {
        var me = this,
            operation = me.operation;

        me.state = 'destroyed';

        if (operation) {
            operation.abort();
        }

        me.callParent();
    },

    /**
     * Acquires or releases the specified type of lock to this page. If this causes the
     * `locked` property to transition to a new value, the `pageMap` is informed to
     * enqueue or dequeue this page from the loading queues.
     * @param {"active"/"prefetch"} kind The type of lock.
     * @param {Number} delta A value of `1` to lock or `-1` to release.
     */
    adjustLock: function(kind, delta) {
        var me = this,
            locks = me.locks,
            pageMap = me.pageMap,
            locked = null,
            lockedWas = me.locked;

        //<debug>
        if (!(kind in locks)) {
            Ext.raise('Bad lock type (expected "active" or "prefetch"): "' + kind + '"');
        }

        if (delta !== 1 && delta !== -1) {
            Ext.raise('Invalid lock count delta (should be 1 or -1): ' + delta);
        }
        //</debug>

        locks[kind] += delta;

        if (locks.active) {
            locked = 'active';
        }
        else if (locks.prefetch) {
            locked = 'prefetch';
        }

        if (locked !== lockedWas) {
            me.locked = locked;

            if (pageMap) {
                pageMap.onPageLockChange(me, locked, lockedWas);
            }
        }
    },

    clearRecords: function(out, by) {
        var me = this,
            begin = me.begin,
            records = me.records,
            i, n;

        // If we don't have records then fillRecords() could not have filled anything
        if (records) {
            n = records.length;

            if (by) {
                for (i = 0; i < n; ++i) {
                    delete out[records[i][by]];
                }
            }
            else {
                for (i = 0; i < n; ++i) {
                    delete out[begin + i];
                }
            }
        }
    },

    fillRecords: function(out, by, withIndex) {
        var me = this,
            records = me.records,
            begin = me.begin,
            store = me.pageMap.store,
            i, n, record;

        if (records) {
            // The last page will not likely have a full page worth, so always
            // limit our loops by the actually available records...
            n = records.length;

            if (by) {
                for (i = 0; i < n; ++i) {
                    record = records[i];
                    record.join(store);
                    out[record[by]] = withIndex ? begin + i : record;
                }
            }
            else {
                for (i = 0; i < n; ++i) {
                    records[i].join(store);
                    out[begin + i] = records[i];
                }
            }
        }
    },

    isInitial: function() {
        return this.state === null;
    },

    isLoaded: function() {
        return this.state === 'loaded';
    },

    isLoading: function() {
        return this.state === 'loading';
    },

    load: function() {
        var me = this,
            operation;

        me.state = 'loading';
        operation = me.pageMap.store.loadVirtualPage(me, me.onLoad, me);

        // Memory proxy will synchronously load pages...
        if (me.state === 'loading') {
            me.operation = operation;
        }
    },

    privates: {
        onLoad: function(operation) {
            var me = this;

            me.operation = null;

            if (!me.destroyed) {
                if (!(me.error = operation.getError())) {
                    me.records = operation.getRecords();
                    me.state = 'loaded';
                }
                else {
                    me.state = 'error';
                    // TODO fireEvent or something from the store?
                }

                me.pageMap.onPageLoad(me);
            }
        }
    }
});
