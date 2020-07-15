/**
 *
 * @since 6.5.0
 */
Ext.define('Ext.data.virtual.Range', {
    extend: 'Ext.data.Range',

    isVirtualRange: true,

    /**
     * @cfg {String/Function} callback
     * The callback to call when new records in this range become available.
     */
    callback: null,

    /**
     * @cfg {Number} leadingBufferZone
     * The number of records to fetch beyond the active range in the direction of movement.
     * If the range is advancing forward, the additional records are beyond `end`. If
     * advancing backwards, they are before `begin`.
     */

    /**
     * @cfg {Boolean} prefetch
     * Specify `true` to enable prefetching for this range.
     */
    prefetch: false,

    /**
     * @cfg {Object} scope
     * The object that implements the supplied `callback` method.
     */
    scope: null,

    /**
     * @cfg {Number} trailingBufferZone
     * The number of records to fetch beyond the active trailing the direction of movement.
     * If the range is advancing forward, the additional records are before `begin`. If
     * advancing backwards, they are beyond `end`.
     */

    /**
     * @property {Number} direction
     * This property is set to `1` if the range was last moved forward and `-1` if it
     * was last moved backwards. This value is used to determine the "leading" and
     * "trailing" buffer zones.
     * @private
     */
    direction: 1,

    constructor: function(config) {
        this.adjustingPages = [];
        this.callParent([config]);
    },

    reset: function() {
        var me = this;

        me.records = {};
        me.activePages = me.prefetchPages = null;
    },

    privates: {
        adjustPageLocks: function(kind, adjustment) {
            var me = this,
                pages = me.adjustingPages,
                n = pages.length,
                i;

            // Consider:
            //
            //                     -->
            //    --+----------+==========+--------------------------+----
            //  ... | trailing |  active  |          leading         |   ...
            //    --+----------+==========+--------------------------+----
            //
            //      :------:   :------:   :++++++:                   :++++++:
            //
            //    ---------+----------+==========+--------------------------+----
            //  ...        | trailing |  active  |          leading         |   ...
            //    ---------+----------+==========+--------------------------+----
            //
            // The newly released pages (esp the prefetch pages) should be released
            // such that the closest ones are MRU vs the farthest ones. Releasing
            // them in forward order will do that.
            //
            // New consider:
            //
            //                                             <--
            //    ---------+--------------------------+==========+----------+----
            //  ...        |          leading         |  active  | trailing |   ...
            //    ---------+--------------------------+==========+----------+----
            //
            //      :++++++:                   :++++++:   :------:   :------:
            //
            //    --+--------------------------+==========+----------+---------
            //  ... |          leading         |  active  | trailing |   ...
            //    --+--------------------------+==========+----------+---------
            //
            // When going backwards, we want to release the pages backwards (we'll
            // just sort them that way). That way the page with least index will be
            // released last and be MRU than the others.

            if (n > 1) {
                // Since pages are in objects keyed by page number, there is no
                // order during our set operations... so we sort the array now by
                // page number (ordered by our current direction).
                pages.sort(me.direction < 0 ? me.pageSortBackFn : me.pageSortFwdFn);
            }

            for (i = 0; i < n; ++i) {
                pages[i].adjustLock(kind, adjustment);
            }

            pages.length = 0;
        },

        doGoto: function() {
            var me = this,
                begin = me.begin,
                end = me.end,
                prefetch = me.prefetch,
                records = me.records,
                store = me.store,
                pageMap = store.pageMap,
                limit = store.totalCount,
                beginWas = me.lastBegin,
                endWas = me.lastEnd,
                activePagesWas = me.activePages,
                prefetchPagesWas = me.prefetchPages,
                beginBufferZone = me.trailingBufferZone,
                endBufferZone = me.leadingBufferZone,
                adjustingPages = me.adjustingPages,
                activePages, page, pg, direction,
                prefetchBegin, prefetchEnd, prefetchPages;

            // If store.totalCount is 0: no need to goto any page, just return from here
            if (limit === 0) {
                return;
            }

            adjustingPages.length = 0;

            // Forwards
            //
            // Most likely case:
            //
            //             beginWas          endWas
            //             |=================|
            //             :---:             :+++:
            //                 |=================|
            //                 begin             end
            //
            // Big step forwards:
            //
            //             beginWas          endWas
            //             |=================|
            //             :-----------------:     :+++++++++++++++++:
            //                                     |=================|
            //                                     begin             end
            //
            // Interesting case:
            //
            //             beginWas          endWas
            //             |=================|
            //             :---:         :---:
            //                 |=========|
            //                 begin     end

            // Backwards
            //
            // Most likely case:
            //
            //             beginWas          endWas
            //             |=================|
            //          :++:              :--:
            //          |=================|
            //          begin             end
            //
            // Big step back:
            //                                 beginWas          endWas
            //                                 |=================|
            //          :+++++++++++++++++:    :-----------------:
            //          |=================|
            //          begin             end
            //
            // Interesting case:
            //
            //             beginWas          endWas
            //             |=================|
            //          :++:                 :++:
            //          |=======================|
            //          begin                   end

            // Retain the direction if narrowing or widening the range
            if ((begin > beginWas && end < endWas) || (begin < beginWas && end > endWas)) {
                direction = me.direction;
            }
            else {
                direction = (begin < beginWas) ? -1 : ((begin > beginWas) ? 1 : me.direction);
            }

            if (direction < 0) { // if (backwards)
                pg = beginBufferZone;
                beginBufferZone = endBufferZone;
                endBufferZone = pg;
            }

            me.direction = direction;
            me.activePages = activePages = pageMap.getPages(begin, end);

            if (prefetch) {
                me.prefetchBegin = prefetchBegin = Math.max(0, begin - beginBufferZone);

                // If we don't know the size of the store yet, don't try and limit the pages
                if (limit === null) {
                    limit = Number.MAX_VALUE;
                }

                me.prefetchEnd = prefetchEnd = Math.min(limit, end + endBufferZone);

                me.prefetchPages = prefetchPages = pageMap.getPages(prefetchBegin, prefetchEnd);
            }

            // In set terms we want to do this:
            //
            //      A  = activePages
            //      Aw = activePagesWas
            //      P  = prefetchPages
            //      Pw = prefetchPagesWas
            //
            //      P -= A;    (activePages start out also in prefetchPages)
            //
            //      foreach page p in (A - Aw), p.lock('active') and p.fill(records)
            //
            //      foreach page p in (P - Pw), p.lock('prefetch')
            //
            //      foreach page p in (Aw - A), p.release('active') and p.clear(records)
            //
            //      foreach page p in (Pw - P), p.release('prefetch')
            //

            for (pg in activePages) {
                page = activePages[pg];

                // Any pages that we will be actively locking, we don't want to mark as
                // prefetch:
                if (prefetchPages) {
                    delete prefetchPages[pg];
                }

                if (activePagesWas && pg in activePagesWas) {
                    // We will unlock any activePages we no longer need so remove
                    // those we will be keeping:
                    delete activePagesWas[pg];
                }
                else {
                    // For pages that weren't previously active, lock them now.
                    page.adjustLock('active', 1);
                    page.fillRecords(records);
                }
            }

            if (prefetchPages) {
                for (pg in prefetchPages) {
                    if (prefetchPagesWas && pg in prefetchPagesWas) {
                        // If page was previously locked for prefetch, we don't want to
                        // release it...
                        delete prefetchPagesWas[pg];
                    }
                    else {
                        prefetchPages[pg].adjustLock('prefetch', 1);
                    }
                }
            }

            // What's left in our "was" maps are those active or prefetch pages that we
            // previously had need of but no longer need them in that same way. Release
            // our previously prefetched pages first in case this is their final lock (we
            // want them to be retained but at a lower priority then previously active
            // pages).

            if (prefetchPagesWas) {
                for (pg in prefetchPagesWas) {
                    adjustingPages.push(prefetchPagesWas[pg]);
                }

                if (adjustingPages.length) {
                    me.adjustPageLocks('prefetch', -1);
                }
            }

            if (activePagesWas) {
                for (pg in activePagesWas) {
                    adjustingPages.push(page = activePagesWas[pg]);
                    page.clearRecords(records);
                }

                if (adjustingPages.length) {
                    me.adjustPageLocks('active', -1);
                }
            }

            if (prefetchPages) {
                pageMap.prioritizePrefetch(direction, pageMap.getPageIndex(begin),
                                           pageMap.getPageIndex(end - 1));
            }

            me.lastBegin = begin;
            me.lastEnd = end;
        },

        onPageDestroy: function(page) {
            var n = page.number,
                activePages = this.activePages,
                prefetchPages = this.prefetchPages;

            if (activePages) {
                delete activePages[n];
            }

            if (prefetchPages) {
                delete prefetchPages[n];
            }
        },

        onPageLoad: function(page) {
            var me = this,
                callback = me.callback,
                first, last;

            if (me.activePages[page.number]) {
                page.fillRecords(me.records);

                if (callback) {
                    // Clip the range to our actually active range for the sake of
                    // the user:
                    first = Math.max(me.begin, page.begin);
                    last = Math.min(me.end, page.end);

                    Ext.callback(callback, me.scope, [me, first, last]);
                }
            }
        },

        pageSortBackFn: function(page1, page2) {
            return page2.number - page1.number;
        },

        pageSortFwdFn: function(page1, page2) {
            return page1.number - page2.number;
        },

        refresh: function() {
            // ... we don't want to reset this.records
            this.records = this.records || {};
        },

        reload: function() {
            var me = this,
                begin = me.begin,
                end = me.end;

            me.begin = me.end = 0;
            me.direction = 1;
            me.prefetchPages = me.activePages = null;

            /* eslint-disable-next-line dot-notation */
            me.goto(begin, end);
        }
    }
});
