/**
 * This class manages a sparse collection of `Page` objects keyed by their page number.
 * Pages are lazily created on request by the `getPage` method.
 *
 * When pages are locked, they are scheduled to be loaded. The loading is prioritized by
 * the type of lock held on the page. Pages with "active" locks are loaded first while
 * those with "prefetch" locks are loaded only when no "active" locked pages are in the
 * queue.
 *
 * The value of the `concurrentLoading` config controls the maximum number of simultaneously
 * pending, page load requests.
 *
 * @private
 * @since 6.5.0
 */
Ext.define('Ext.data.virtual.PageMap', {
    requires: [
        'Ext.data.virtual.Page'
    ],

    isVirtualPageMap: true,

    config: {
        /**
         * @cfg {Number} cacheSize
         * The number of pages to retain in the `cache`.
         */
        cacheSize: 10,

        /**
         * @cfg {Number} concurrentLoading
         * The maximum number of simultaneous load requests that should be made to the
         * server for pages.
         */
        concurrentLoading: 1,

        /**
         * The number of pages in the data set.
         */
        pageCount: null
    },

    generation: 0,

    store: null,

    constructor: function(config) {
        var me = this;

        me.prefetchSortFn = me.prefetchSortFn.bind(me);

        me.initConfig(config);

        me.clear();
    },

    destroy: function() {
        this.clear(true);
        this.callParent();
    },

    canSatisfy: function(range) {
        var end = this.getPageIndex(range.end),
            pageCount = this.getPageCount();

        return pageCount === null || end < pageCount;
    },

    clear: function(destroy) {
        var me = this,
            alive = !destroy || null,
            pages = me.pages,
            pg;

        ++me.generation;

        /**
         * @property {Object} byId
         * A map of records by their `idProperty`.
         */
        me.byId = alive && {};

        /**
         * @property {Object} byInternalId
         * A map of records by their `internalId`.
         */
        me.byInternalId = alive && {};

        /**
         * @property {Ext.data.virtual.Page[]} cache
         * The array of unlocked pages with the oldest at the front and the newest (most
         * recently unlocked) page at the end.
         * @readonly
         */
        me.cache = alive && [];

        /**
         * @property {Object} indexMap
         * A map of record indices by their `internalId`.
         */
        me.indexMap = alive && {};

        /**
         * @property {Object} pages
         * The sparse collection of `Page` objects keyed by their page number.
         * @readonly
         */
        me.pages = alive && {};

        /**
         * @property {Ext.data.virtual.Page[]} loading
         * The array of currently loading pages.
         */
        me.loading = alive && [];

        /**
         * @property {Object} loadQueues
         * A collection of loading queues keyed by the lock state.
         * @property {Ext.data.virtual.Page[]} loadQueues.active The queue of pages to
         * load that have an "active" lock state.
         * @property {Ext.data.virtual.Page[]} loadQueues.prefetch The queue of pages to
         * load that have a "prefetch" lock state.
         */
        me.loadQueues = alive && {
            active: [],
            prefetch: []
        };

        if (pages) {
            for (pg in pages) {
                me.destroyPage(pages[pg]);
            }
        }
    },

    getPage: function(number, autoCreate) {
        var me = this,
            pageCount = me.getPageCount(),
            pages = me.pages,
            page;

        if (pageCount === null || number < pageCount) {
            page = pages[number];

            if (!page && autoCreate !== false) {
                pages[number] = page = new Ext.data.virtual.Page({
                    pageMap: me,
                    number: number
                });
            }
        }
        //<debug>
        else {
            Ext.raise('Invalid page number ' + number + ' when limit is ' + pageCount);
        }
        //</debug>

        return page || null;
    },

    getPageIndex: function(index) {
        if (index.isEntity) {
            index = this.indexOf(index);
        }

        return Math.floor(index / this.store.getPageSize());
    },

    getPageOf: function(index, autoCreate) {
        var pageSize = this.store.getPageSize(),
            n = Math.floor(index / pageSize);

        return this.getPage(n, autoCreate);
    },

    getPages: function(begin, end) {
        var pageSize = this.store.getPageSize(),
            // Convert record indices into page numbers:
            first = Math.floor(begin / pageSize),
            last = Math.ceil(end / pageSize),
            ret = {},
            n;

        for (n = first; n < last; ++n) {
            ret[n] = this.getPage(n);
        }

        return ret;
    },

    flushNextLoad: function() {
        var me = this,
            queueTimer = me.queueTimer;

        if (queueTimer) {
            Ext.unasap(queueTimer);
        }

        me.loadNext();
    },

    indexOf: function(record) {
        var ret;

        // return indexMap if record is not null/undefined
        if (record) {
            ret = this.indexMap[record.internalId];
        }

        return (ret || ret === 0) ? ret : -1;
    },

    getByInternalId: function(internalId) {
        var index = this.indexMap[internalId],
            page;

        if (index || index === 0) {
            page = this.pages[Math.floor(index / this.store.getPageSize())];

            if (page) {
                return page.records[index - page.begin];
            }
        }
    },

    updatePageCount: function(pageCount, oldPageCount) {
        var pages = this.pages,
            pageNumber, page;

        if (oldPageCount === null || pageCount < oldPageCount) {
            // Safe to delete during a for in
            for (pageNumber in pages) {
                page = pages[pageNumber];

                if (page.number >= pageCount) {
                    this.clearPage(page);
                    this.destroyPage(page);
                }
            }
        }
    },

    privates: {
        queueTimer: null,

        clearPage: function(page, fromCache) {
            var me = this,
                A = Ext.Array,
                loadQueues = me.loadQueues;

            delete me.pages[page.number];
            page.clearRecords(me.byId, 'id');
            page.clearRecords(me.byInternalId, 'internalId');
            page.clearRecords(me.indexMap, 'internalId');

            A.remove(loadQueues.active, page);
            A.remove(loadQueues.prefetch, page);

            if (!fromCache) {
                Ext.Array.remove(me.cache, page);
            }
        },

        destroyPage: function(page) {
            this.store.onPageDestroy(page);
            page.destroy();
        },

        loadNext: function() {
            var me = this,
                concurrency = me.getConcurrentLoading(),
                loading = me.loading,
                loadQueues = me.loadQueues,
                page;

            me.queueTimer = null;

            // Keep pulling from the queue(s) as long as we have more concurrency
            // allowed...
            while (loading.length < concurrency) {
                if (!(page = loadQueues.active.shift() || loadQueues.prefetch.shift())) {
                    break;
                }

                loading.push(page);
                page.load();
            }
        },

        onPageLoad: function(page) {
            var me = this,
                store = me.store,
                activeRanges = store.activeRanges,
                n = activeRanges.length,
                i;

            Ext.Array.remove(me.loading, page);

            if (!page.error) {
                page.fillRecords(me.byId, 'id');
                page.fillRecords(me.byInternalId, 'internalId');
                page.fillRecords(me.indexMap, 'internalId', true);

                store.onPageDataAcquired(page);

                for (i = 0; i < n; ++i) {
                    activeRanges[i].onPageLoad(page);
                }
            }

            me.flushNextLoad();
        },

        onPageLockChange: function(page, state, oldState) {
            var me = this,
                cache = me.cache,
                loadQueues = me.loadQueues,
                store = me.store,
                cacheSize, concurrency;

            // When a page that has never been loaded becomes locked, we want to put
            // it in the appropriate loadQueue. It is also possible for the lock state
            // to change while waiting in a loadQueue, so we may need to move it around
            // while it waits...
            if (page.isInitial()) {
                if (oldState) {
                    Ext.Array.remove(loadQueues[oldState], page);
                }

                if (state) {
                    loadQueues[state].push(page);
                    concurrency = me.getConcurrentLoading();

                    // Initiating loads immediately can easily cause problems, so wait
                    // for a tick before firing off the loads.
                    if (!me.queueTimer && me.loading.length < concurrency) {
                        me.queueTimer = Ext.asap(me.loadNext, me);
                    }
                }
            }

            if (state) {
                if (!oldState) {
                    // Make sure the page is not in the LRU queue for recycling. If it
                    // was previously not locked (!oldState) then the page is in line
                    // for removal...
                    Ext.Array.remove(cache, page);
                }
            }
            else {
                cache.push(page); // put MRU item at the end

                for (cacheSize = me.getCacheSize(); cache.length > cacheSize;) {
                    page = cache.shift();
                    me.clearPage(page, true); // remove LRU item
                    store.onPageEvicted(page);
                    me.destroyPage(page);
                }
            }
        },

        prefetchSortFn: function(a, b) {
            a = a.number;
            b = b.number;

            /* eslint-disable-next-line vars-on-top */
            var M = Math,
                firstPage = this.sortFirstPage,
                lastPage = this.sortLastPage,
                direction = this.sortDirection,
                aDir = a < firstPage,
                bDir = b < firstPage,
                ret;

            a = aDir ? M.abs(firstPage - a) : M.abs(lastPage - a);
            b = bDir ? M.abs(firstPage - b) : M.abs(lastPage - b);

            if (a === b) {
                ret = aDir ? direction : -direction;
            }
            else {
                ret = a - b;
            }

            return ret;
        },

        prioritizePrefetch: function(direction, firstPage, lastPage) {
            var me = this;

            me.sortDirection = direction;
            me.sortFirstPage = firstPage;
            me.sortLastPage = lastPage;

            me.loadQueues.prefetch.sort(me.prefetchSortFn);
        }
    }
});
