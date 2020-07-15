/**
 * @private
 * Implements buffered rendering of a grid, allowing users to scroll
 * through thousands of records without the performance penalties of
 * rendering all the records into the DOM at once.
 *
 * The number of rows rendered outside the visible area can be controlled by configuring the plugin.
 *
 * Users should not instantiate this class. It is instantiated automatically
 * and applied to all grids.
 *
 * ## Implementation notes
 *
 * This class monitors scrolling of the {@link Ext.view.Table
 * TableView} within a {@link Ext.grid.Panel GridPanel} to render a small section of
 * the dataset.
 *
 */
Ext.define('Ext.grid.plugin.BufferedRenderer', {
    extend: 'Ext.plugin.Abstract',
    requires: [
        'Ext.grid.locking.RowSynchronizer'
    ],

    alias: 'plugin.bufferedrenderer',

    /**
     * @property {Boolean} isBufferedRenderer
     * `true` in this class to identify an object as an instantiated BufferedRenderer,
     * or subclass thereof.
     */
    isBufferedRenderer: true,

    lockableScope: 'both',

    /**
     * @cfg {Number}
     * The zone which causes new rows to be appended to the view. As soon as the edge
     * of the rendered grid is this number of rows from the edge of the viewport, the view is moved.
     */
    numFromEdge: 2,

    /**
     * @cfg {Number}
     * The number of extra rows to render on the trailing side of scrolling
     * **outside the {@link #numFromEdge}** buffer as scrolling proceeds.
     */
    trailingBufferZone: 10,

    /**
     * @cfg {Number}
     * The number of extra rows to render on the leading side of scrolling
     * **outside the {@link #numFromEdge}** buffer as scrolling proceeds.
     */
    leadingBufferZone: 20,

    /**
     * @cfg {Boolean} [synchronousRender=true]
     * By default, on detection of a scroll event which brings the end of the rendered table within
     * `{@link #numFromEdge}` rows of the grid viewport, if the required rows are available
     * in the Store, the BufferedRenderer will render rows from the Store *immediately* before
     * returning from the event handler.
     * This setting helps avoid the impression of whitespace appearing during scrolling.
     *
     * Set this to `false` to defer the render until the scroll event handler exits.
     * This allows for faster scrolling, but also allows whitespace to be more easily scrolled
     * into view.
     *
     */
    synchronousRender: true,

    /**
     * @cfg {Number}
     * This is the time in milliseconds to buffer load requests when the store is a
     * {@link Ext.data.BufferedStore buffered store} and a page required for rendering
     * is not present in the store's cache and needs loading.
     */
    scrollToLoadBuffer: 200,

    /**
     * @private
     */
    viewSize: 100,

    /**
     * @private
     */
    rowHeight: 21,

    /**
     * @property {Number} position
     * Current pixel scroll position of the associated {@link Ext.view.Table View}.
     */
    position: 0,
    scrollTop: 0,
    lastScrollDirection: 1,
    bodyTop: 0,
    scrollHeight: 0,
    loadId: 0,

    // Initialize this as a plugin
    init: function(grid) {
        var me = this,
            view = grid.view,
            viewListeners = {
                refresh: me.onViewRefresh,
                columnschanged: me.checkVariableRowHeight,
                scope: me,
                destroyable: true
            },
            scrollerListeners = {
                scroll: me.onViewScroll,
                scope: me
            },
            initialConfig = view.initialConfig;

        me.scroller = view.lockingPartner ? view.ownerGrid.scrollable : view.getScrollable();

        // If we are going to be handling a NodeStore then it's driven by node addition and removal,
        // *not* refreshing. The view overrides required above change the view's onAdd and onRemove
        // behaviour to call onDataRefresh when necessary.
        if (grid.isTree || (grid.ownerLockable && grid.ownerLockable.isTree)) {
            view.blockRefresh = false;

            // Set a load mask if undefined in the view config.
            if (initialConfig && initialConfig.loadMask === undefined) {
                view.loadMask = true;
            }
        }

        if (view.positionBody) {
            viewListeners.refresh = me.onViewRefresh;
        }

        me.grid = grid;
        me.view = view;
        me.isRTL = view.getInherited().rtl;
        view.bufferedRenderer = me;
        view.preserveScrollOnRefresh = true;
        view.animate = false;

        // It doesn't matter if it's a FeatureStore or a DataStore.
        // The important thing is to only bind the same Type of store in future operations!
        me.bindStore(view.dataSource);

        // Use a configured rowHeight in the view
        if (view.hasOwnProperty('rowHeight')) {
            me.rowHeight = view.rowHeight;
        }

        me.position = 0;

        me.viewListeners = view.on(viewListeners);

        // Listen to the correct scroller. Either the view's one, or of it is
        // in a lockable assembly, the y scroller which scrolls them both.
        // If the view is not scrollable, this will be falsy.
        if (me.scroller) {
            me.scrollListeners = me.scroller.on(scrollerListeners);
        }
    },

    // Keep the variableRowHeight property correct WRT variable row heights being possible.
    checkVariableRowHeight: function() {
        var hadVariableRowHeight = this.variableRowHeight;

        this.variableRowHeight = this.view.hasVariableRowHeight();

        // Next time we refresh size, row height will also be recalculated
        if (!!this.variableRowHeight !== !!hadVariableRowHeight) {
            delete this.rowHeight;
        }
    },

    bindStore: function(newStore) {
        var me = this,
            currentStore = me.store;

        // If the grid was configured with a feature such as Grouping that binds a FeatureStore
        // (GroupStore, in its case) as the view's dataSource, we must continue to use
        // the same Type of store.
        //
        // Note that reconfiguring the grid can call into here.
        if (currentStore && currentStore.isFeatureStore) {
            return;
        }

        if (currentStore) {
            me.unbindStore();
        }

        me.storeListeners = newStore.on({
            scope: me,
            groupchange: me.onStoreGroupChange,
            clear: me.onStoreClear,
            beforeload: me.onBeforeStoreLoad,
            load: me.onStoreLoad,
            destroyable: true
        });

        me.store = newStore;

        me.setBodyTop(me.position = me.scrollTop = 0);

        // Delete whatever our last viewSize might have been, and fall back
        // to the prototype's default.		
        delete me.viewSize;
        delete me.rowHeight;

        if (newStore.isBufferedStore) {
            newStore.setViewSize(me.viewSize);
        }
    },

    unbindStore: function() {
        this.storeListeners.destroy();
        this.storeListeners = this.store = null;
    },

    // Disable handling of scroll events until the load is finished
    onBeforeStoreLoad: function(store) {
        var me = this,
            view = me.view;

        if (view && view.refreshCounter) {
            // Unless we are loading tree nodes, or have preserveScrollOnReload,
            // set scroll position and row range back to zero.
            if (store.isTreeStore || view.preserveScrollOnReload) {
                me.nextRefreshStartIndex = view.all.startIndex;
            }
            else {
                if (me.scrollTop !== 0) {
                    // Zero position tracker so that next scroll event will not trigger any action
                    // eslint-disable-next-line max-len
                    me.setBodyTop(me.bodyTop = me.scrollTop = me.position = me.scrollHeight = me.nextRefreshStartIndex = 0);
                    me.scroller.scrollTo(null, 0);
                }
            }

            me.lastScrollDirection = me.scrollOffset = null;
        }

        me.disable();
    },

    // Re-enable scroll event handling on load.
    onStoreLoad: function() {
        this.isStoreLoading = true;
        this.enable();
    },

    onStoreClear: function() {
        var me = this,
            view = me.view;

        // Do not do anything if view is not rendered, or if the reason for cache clearing
        // is store destruction
        if (view.rendered && !me.store.destroyed) {
            if (me.scrollTop !== 0) {
                // Zero position tracker so that next scroll event will not trigger any action
                me.bodyTop = me.scrollTop = me.position = me.scrollHeight = 0;
                me.nextRefreshStartIndex = null;
                me.scroller.scrollTo(null, 0);
            }

            // TableView does not add a Store Clear listener if there's a BufferedRenderer
            // We handle that here.
            view.refresh();

            me.lastScrollDirection = me.scrollOffset = null;
        }
    },

    // If the store is not grouped, we can switch to fixed row height mode
    onStoreGroupChange: function(store) {
        this.refreshSize();
    },

    onViewRefresh: function(view, records) {
        var me = this,
            rows = view.all,
            height;

        // Recheck the variability of row height in the view.
        me.checkVariableRowHeight();

        // The first refresh on the leading edge of the initial layout will mean that the
        // View has not had the sizes of flexed columns calculated and flushed yet.
        // So measurement of DOM height for calculation of an approximation of the variableRowHeight
        // would be premature.
        // And measurement of the body width would be premature because of uncalculated flexes.
        // eslint-disable-next-line max-len
        if (!view.componentLayoutCounter && (view.headerCt.down('{flex}') || me.variableRowHeight)) {
            view.on({
                boxready: Ext.Function.pass(me.onViewRefresh, [view, records], me),
                single: true
            });

            // AbstractView will call refreshSize() immediately after firing the 'refresh'
            // event; we need to skip that run for the reasons stated above.
            me.skipNextRefreshSize = true;

            return;
        }

        me.skipNextRefreshSize = false;

        // If we are instigating the refresh, we will have already called refreshSize
        // in doRefreshView
        if (me.refreshing) {
            return;
        }

        me.refreshSize();

        if (me.scroller) {
            if (me.scrollTop !== me.scroller.getPosition().y) {
                // The view may have refreshed and scrolled to the top, for example
                // on a sort. If so, it's as if we scrolled to the top, so we'll simulate
                // it here.
                me.onViewScroll();
            }
            else {
                if (!me.hasOwnProperty('bodyTop')) {
                    me.bodyTop = rows.startIndex * me.rowHeight;
                    me.scroller.scrollTo(null, me.bodyTop);
                }

                me.setBodyTop(me.bodyTop);

                // With new data, the height may have changed, so recalculate the rowHeight
                // and viewSize. This will either add or remove some rows.
                height = view.lastBox && view.lastBox.height;

                if (height && rows.getCount()) {
                    me.onViewResize(view, null, height);

                    // If we repaired the view by adding or removing records, then keep the records
                    // array consistent with what is there for subsequent listeners.
                    // For example the WidgetColumn listener which post-processes all rows:
                    // https://sencha.jira.com/browse/EXTJS-13942
                    if (records && (rows.getCount() !== records.length)) {
                        records.length = 0;
                        records.push.apply(
                            records, me.store.getRange(rows.startIndex, rows.endIndex)
                        );
                    }
                }
            }
        }
    },

    /**
     * @private
     * @param {Ext.layout.ContextItem} ownerContext The view's layout context
     * Called before the start of a view's layout run
     */
    beforeTableLayout: function(ownerContext) {
        var dom = this.view.body.dom,
            size;

        if (dom) {
            size = this.grid.getElementSize(dom);
            ownerContext.bodyHeight = size.height;
            ownerContext.bodyWidth = size.width;
        }
    },

    /**
     * @private
     * @param {Ext.layout.ContextItem} ownerContext The view's layout context
     * Called when a view's layout run is complete.
     */
    afterTableLayout: function(ownerContext) {
        var me = this,
            view = me.view,
            renderedBlockHeight;

        // The rendered block has changed height.
        // This could happen if a cellWrap: true column has changed width.
        // We need to recalculate row height and scroll range
        if (ownerContext.bodyHeight && view.body.dom) {
            delete me.rowHeight;
            me.refreshSize();
            renderedBlockHeight = me.grid.getElementHeight(view.body.dom);

            if (renderedBlockHeight !== ownerContext.bodyHeight) {
                me.onViewResize(view, null, view.el.lastBox.height);

                // The view resize might have added or removed rows
                renderedBlockHeight = me.bodyHeight;

                // The layout has caused the rendered block to shrink in height.
                // This could happen if a cellWrap: true column has increased in width.
                // It could cause the bottom of the rendered view to zoom upwards
                // out of sight.
                if (renderedBlockHeight < ownerContext.bodyHeight) {
                    if (me.viewSize >= me.store.getCount()) {
                        me.setBodyTop(0);
                    }

                    // Column got wider causing scroll range to shrink, leaving the view
                    // stranded above the fold. Scroll up to bring it into view.
                    // eslint-disable-next-line max-len
                    else if (me.bodyTop > me.scrollTop || me.bodyTop + renderedBlockHeight < me.scrollTop + me.viewClientHeight) {
                        me.setBodyTop(me.scrollTop - me.trailingBufferZone * me.rowHeight);
                    }
                }

                // If the rendered block is the last lines in the dataset,
                // ensure the scroll range exactly encapsuates it.
                if (view.all.endIndex === (view.dataSource.getCount()) - 1) {
                    me.stretchView(view, me.scrollHeight = me.bodyTop + renderedBlockHeight - 1);
                }
            }
        }
    },

    refreshSize: function() {
        var me = this,
            view = me.view,
            // If we have been told to skip the next refresh, or there is going to be
            // an upcoming layout, skip this op.
            skipNextRefreshSize =
                me.skipNextRefreshSize ||
                (Ext.Component.pendingLayouts && Ext.Component.layoutSuspendCount) ||
                !view.body.dom;

        // We only want to skip ONE time.
        me.skipNextRefreshSize = false;

        if (skipNextRefreshSize) {
            return;
        }

        // Cache the rendered block height.
        me.bodyHeight = me.grid.getElementHeight(view.body.dom);

        // Calculates scroll range.
        // Also calculates rowHeight if we do not have an own rowHeight property.
        me.scrollHeight = me.getScrollHeight();

        me.stretchView(view, me.scrollHeight);
    },

    /**
     * Called directly from {@link Ext.view.Table#onResize}. Reacts to View changing height by
     * recalculating the size of the rendered block, and either trimming it or adding to it.
     * @param {Ext.view.Table} view The Table view.
     * @param {Number} width The new Width.
     * @param {Number} height The new height.
     * @param {Number} oldWidth The old width.
     * @param {Number} oldHeight The old height.
     * @private
     */
    onViewResize: function(view, width, height, oldWidth, oldHeight) {
        var me = this,
            newViewSize;

        me.refreshSize();

        // Only process first layout (the boxready event) or height resizes.
        if (!oldHeight || height !== oldHeight) {
            // Changing the content height may trigger multiple layouts for locked grids.
            // Ensure they are coalesced.
            Ext.suspendLayouts();

            // Recalculate the view size in rows now that the grid view has changed height
            me.viewClientHeight = height || view.el.dom.clientHeight;

            // Use the theme's default rowHeight unless the measured row height is smaller
            // when calculating the view size. If the rows are *larger*, that doesn't really matter.
            // We just need to cover the visible range with some scrolling range extra.
            newViewSize = Math.ceil(height / Math.min(me.getThemeRowHeight(), me.rowHeight)) +
                          me.trailingBufferZone + me.leadingBufferZone;

            me.viewSize = me.setViewSize(newViewSize);

            Ext.resumeLayouts(true);
        }
    },

    stretchView: function(view, scrollRange) {
        var me = this,
            newY;

        // Ensure that both the scroll range AND the positioned view body are in the viewable area.
        if (me.scrollTop > scrollRange) {
            newY = me.nextRefreshStartIndex == null ? me.bodyHeight : scrollRange - me.bodyHeight;
            me.position = me.scrollTop = Math.max(newY, 0);
            me.scroller.scrollTo(null, me.scrollTop);
        }

        if (me.bodyTop > scrollRange) {
            view.body.translate(null, me.bodyTop = me.position);
        }

        // Tell the scroller what the scroll size is.
        if (view.getScrollable()) {
            me.refreshScroller(view, scrollRange);
        }
    },

    refreshScroller: function(view, scrollRange) {
        var scroller = view.getScrollable();

        if (scroller) {
            // Ensure the scroller viewport element size is up to date if it needs to be told
            // (touch scroller)
            if (scroller.setElementSize) {
                scroller.setElementSize();
            }

            // Ensure the scroller knows about content size
            scroller.setSize({
                x: view.headerCt.getTableWidth(),

                // No Y range in the view's scroller if we're in a locking assembly.
                // The LockingScroller stretches the views.
                y: view.lockingPartner ? null : scrollRange
            });

            // In a locking assembly, stretch the yScroller
            if (view.lockingPartner) {
                this.scroller.setSize({
                    x: 0,
                    y: scrollRange
                });
            }
        }
    },

    setViewSize: function(viewSize, fromLockingPartner) {
        var me = this,
            store = me.store,
            view = me.view,
            ownerGrid,
            rows = view.all,
            elCount = rows.getCount(),
            storeCount = store.getCount(),
            start, end,
            lockingPartner = me.view.lockingPartner && me.view.lockingPartner.bufferedRenderer,
            diff = elCount - viewSize,
            oldTop = 0,
            maxIndex = Math.max(0, storeCount - 1),
            // This is which end is closer to being visible therefore must be the first
            // to have rows added or the opposite end from which rows get removed
            // if shrinking the view.
            pointyEnd = Ext.Number.sign(
                (me.getFirstVisibleRowIndex() - rows.startIndex) -
                (rows.endIndex - me.getLastVisibleRowIndex())
            );

        // Synchronize view sizes
        if (lockingPartner && !fromLockingPartner) {
            lockingPartner.setViewSize(viewSize, true);
        }

        diff = elCount - viewSize;

        if (diff) {

            // Must be set for getFirstVisibleRowIndex to work
            me.scrollTop = me.scroller ? me.scroller.getPosition().y : 0;

            me.viewSize = viewSize;

            if (store.isBufferedStore) {
                store.setViewSize(viewSize);
            }

            // If a store loads before we have calculated a viewSize, it loads me.defaultViewSize
            // records. This may be larger or smaller than the final viewSize so the store needs
            // adjusting when the view size is calculated.
            if (elCount) {
                // New start index should be current start index unless that's now too close
                // to the end of the store to yield a full view, in which case work back
                // from the end of the store. Ensure we don't go negative.
                start = Math.max(0, Math.min(rows.startIndex, storeCount - viewSize));

                // New end index works forward from the new start index ensuring
                // we don't walk off the end
                end = Math.min(start + viewSize - 1, maxIndex);

                // Only do expensive adding or removal if range is not already correct
                if (start === rows.startIndex && end === rows.endIndex) {
                    // Needs rows adding to or bottom depending on which end is closest
                    // to being visible (The pointy end)
                    if (diff < 0) {
                        me.handleViewScroll(pointyEnd);
                    }
                }
                else {
                    // While changing our visible range, the locking partner must not sync
                    if (lockingPartner) {
                        lockingPartner.disable();
                    }

                    // View must expand
                    if (diff < 0) {

                        // If it's *possible* to add rows...
                        if (storeCount > viewSize && storeCount > elCount) {

                            // Grab the render range with a view to appending and prepending
                            // nodes to the top and bottom as necessary.
                            // Store's getRange API always has been inclusive of endIndex.
                            store.getRange(start, end, {
                                callback: function(newRecords, start, end) {
                                    ownerGrid = view.ownerGrid;

                                    // Append if necessary
                                    if (end > rows.endIndex) {
                                        // eslint-disable-next-line max-len
                                        rows.scroll(Ext.Array.slice(newRecords, rows.endIndex + 1, Infinity), 1, 0);
                                    }

                                    // Prepend if necessary
                                    if (start < rows.startIndex) {
                                        oldTop = rows.first(true);
                                        // eslint-disable-next-line max-len
                                        rows.scroll(Ext.Array.slice(newRecords, 0, rows.startIndex - start), -1, 0);

                                        // We just added some rows to the top of the rendered block
                                        // We have to bump it up to keep the view stable.
                                        me.bodyTop -= oldTop.offsetTop;
                                    }

                                    me.setBodyTop(me.bodyTop);

                                    // The newly added rows must sync the row heights
                                    // eslint-disable-next-line max-len
                                    if (lockingPartner && !fromLockingPartner && (ownerGrid.syncRowHeight || ownerGrid.syncRowHeightOnNextLayout)) {
                                        lockingPartner.setViewSize(viewSize, true);
                                        ownerGrid.syncRowHeights();
                                    }
                                }
                            });
                        }
                        // If not possible just refresh
                        else {
                            me.refreshView(0);
                        }
                    }
                    // View size is contracting
                    else {
                        // If removing from top, we have to bump the rendered block downwards
                        // by the height of the removed rows.
                        if (pointyEnd === 1) {
                            oldTop = rows.item(rows.startIndex + diff, true).offsetTop;
                        }

                        // Clip the rows off the required end
                        rows.clip(pointyEnd, diff);
                        me.setBodyTop(me.bodyTop + oldTop);
                    }

                    if (lockingPartner) {
                        lockingPartner.enable();
                    }
                }
            }

            // Update scroll range
            me.refreshSize();
        }

        return viewSize;
    },

    /**
     * @private
     * TableView's getViewRange delegates the operation to this method
     * if buffered rendering is present.
     */
    getViewRange: function() {
        var me = this,
            view = me.view,
            rows = view.all,
            rowCount = rows.getCount(),
            lockingPartnerRows = view.lockingPartner && view.lockingPartner.all,
            store = me.store,
            startIndex = 0,
            endIndex;

        // Get a best guess at the number of rows to fill the view
        if (!me.hasOwnProperty('viewSize') && me.ownerCt && me.ownerCt.height) {
            me.viewSize = Math.ceil(me.ownerCt.height / me.rowHeight);
        }

        if (!store.data.getCount()) {
            return [];
        }

        // We're starting from nothing, but there's a locking partner with the range info,
        // so match that
        if (!rowCount && lockingPartnerRows && lockingPartnerRows.getCount()) {
            startIndex = lockingPartnerRows.startIndex;
            endIndex = Math.min(
                lockingPartnerRows.endIndex, startIndex + me.viewSize - 1, store.getCount() - 1
            );
        }
        else {
            // If there already is a view range, then the startIndex from that
            if (rowCount) {
                startIndex = rows.startIndex;
            }
            // Otherwise use start index of current page.
            // https://sencha.jira.com/browse/EXTJSIV-10724
            // Buffered store may be primed with loadPage(n) call rather than autoLoad
            // which starts at index 0.
            else if (store.isBufferedStore) {
                if (!store.currentPage) {
                    store.currentPage = 1;
                }

                startIndex = rows.startIndex = (store.currentPage - 1) * (store.pageSize || 1);

                // The RowNumberer uses the current page to offset the record index,
                // so when buffered, it must always be on page 1
                store.currentPage = 1;
            }

            endIndex = startIndex + (me.viewSize || store.defaultViewSize) - 1;
        }

        return store.getRange(startIndex, endIndex);
    },

    /**
     * @private
     * Handles the Store replace event, producing a correct buffered view
     * after the replace operation.
     */
    onReplace: function(store, startIndex, oldRecords, newRecords) {
        var me = this,
            scroller = me.scroller,
            view = me.view,
            rows = view.all,
            oldStartIndex,
            renderedSize = rows.getCount(),
            lastAffectedIndex = startIndex + oldRecords.length - 1,
            recordIncrement = newRecords.length - oldRecords.length,
            scrollIncrement = recordIncrement * me.rowHeight,
            preserveScrollOnRefresh;

        // All replacement activity is past the end of a full-sized rendered block;
        // do nothing except update scroll range
        if (startIndex >= rows.startIndex + me.viewSize) {
            me.refreshSize();

            return;
        }

        // If the change is all above the rendered block and the rendered block is its maximum size,
        // update the scroll range and ensure the buffer zone above is filled if possible.
        if (renderedSize && lastAffectedIndex < rows.startIndex && rows.getCount() >= me.viewSize) {
            // Move the index-based NodeCache up or down depending on whether it's a net
            // adding or removal above.
            rows.moveBlock(recordIncrement);
            me.refreshSize();

            // If the change above us was an addition, pretend that we just scrolled upwards
            // which will ensure that there is at least this.numFromEdge rows above the fold.
            oldStartIndex = rows.startIndex;

            if (recordIncrement > 0) {

                // Do not allow this operation to mirror to the partner side.
                me.doNotMirror = true;
                me.handleViewScroll(-1);
                me.doNotMirror = false;
            }

            // If the handleViewScroll did nothing, we just have to ensure the rendered block
            // is the correct amount down the scroll range, and then readjust the top
            // of the rendered block to keep the visuals the same.
            if (rows.startIndex === oldStartIndex) {
                // If inserting or removing invisible records above the start of the rendered block,
                // the visible block must then be moved up or down the scroll range.
                if (rows.startIndex) {
                    me.setBodyTop(me.bodyTop += scrollIncrement);
                    view.suspendEvent('scroll');
                    view.scrollBy(0, scrollIncrement);
                    view.resumeEvent('scroll');
                    me.position = me.scrollTop = me.scroller.getPosition().y;
                }
            }
            // The handleViewScroll added rows, so we must scroll to keep the visuals the same;
            else {
                view.suspendEvent('scroll');
                view.scrollBy(0, (oldStartIndex - rows.startIndex) * me.rowHeight);
                view.resumeEvent('scroll');
            }

            view.refreshSize(rows.getCount() !== renderedSize);

            return;
        }

        // If the change is all below the rendered block, update the scroll range
        // and ensure the buffer zone below us is filled if possible.
        if (renderedSize && startIndex > rows.endIndex) {
            me.refreshSize();

            // If the change below us was an addition, ask for <viewSize>
            // rows to be rendered starting from the current startIndex.
            // If more rows need to be scrolled onto the bottom of the rendered
            // block to achieve this, that will do it.
            if (recordIncrement > 0) {
                me.onRangeFetched(
                    null, rows.startIndex,
                    Math.min(store.getCount(), rows.startIndex + me.viewSize) - 1
                );
            }

            view.refreshSize(rows.getCount() !== renderedSize);

            return;
        }

        // Cut into rendered block from above
        if (startIndex < rows.startIndex && lastAffectedIndex <= rows.endIndex) {
            preserveScrollOnRefresh = view.preserveScrollOnRefresh;
            view.preserveScrollOnRefresh = false;
            me.refreshView(rows.startIndex - oldRecords.length + newRecords.length);
            view.preserveScrollOnRefresh = preserveScrollOnRefresh;

            return;
        }

        if (startIndex < rows.startIndex && lastAffectedIndex <= rows.endIndex && scrollIncrement) {
            me.doVerticalScroll(scroller, me.scrollTop += scrollIncrement, true);
        }

        // Only need to change display if the view is currently empty, or
        // change intersects the rendered view.
        me.refreshView(rows.startIndex, scrollIncrement);
    },

    doVerticalScroll: function(scroller, pos, supressEvents) {
        var me = this;

        if (!scroller) {
            return;
        }

        if (supressEvents) {
            scroller.suspendEvent('scroll');
        }

        scroller.scrollTo(null, me.position = pos);

        if (supressEvents) {
            scroller.resumeEvent('scroll');
        }
    },

    /**
     * @private
     * Scrolls to and optionally selects the specified row index **in the total dataset**.
     *
     * This is a private method for internal usage by the framework.
     *
     * Use the grid's {@link Ext.panel.Table#ensureVisible ensureVisible} method to scroll
     * a particular record or record index into view.
     *
     * @param {Number/Ext.data.Model} recordIdx The record, or the zero-based position
     * in the dataset to scroll to.
     * @param {Object} [options] An object containing options to modify the operation.
     * @param {Boolean} [options.animate] Pass `true` to animate the row into view.
     * @param {Boolean} [options.highlight] Pass `true` to highlight the row with a glow animation
     * when it is in view.
     * @param {Boolean} [options.select] Pass as `true` to select the specified row.
     * @param {Boolean} [options.focus] Pass as `true` to focus the specified row.
     * @param {Function} [options.callback] A function to call when the row has been scrolled to.
     * @param {Number} options.callback.recordIdx The resulting record index (may have changed
     * if the passed index was outside the valid range).
     * @param {Ext.data.Model} options.callback.record The resulting record from the store.
     * @param {HTMLElement} options.callback.node The resulting view row element.
     * @param {Object} [options.scope] The scope (`this` reference) in which to execute
     * the callback. Defaults to this BufferedRenderer.
     * @param {Ext.grid.column.Column/Number} [options.column] The column, or column index
     * to scroll into view.
     *
     */
    scrollTo: function(recordIdx, options) {
        var args = arguments,
            me = this,
            view = me.view,
            lockingPartner = view.lockingPartner && view.lockingPartner.grid.isVisible() &&
                             view.lockingPartner.bufferedRenderer,
            store = me.store,
            total = store.getCount(),
            startIdx, endIdx, targetRow, tableTop, groupingFeature, metaGroup, record, direction;

        // New option object API
        if (options !== undefined && !(options instanceof Object)) {
            options = {
                select: args[1],
                callback: args[2],
                scope: args[3]
            };
        }

        // If we have a grouping summary feature rendering the view in groups,
        // first, ensure that the record's group is expanded,
        // then work out which record in the groupStore the record is at.
        if ((groupingFeature = view.dataSource.groupingFeature) && (groupingFeature.collapsible)) {
            if (recordIdx.isEntity) {
                record = recordIdx;
            }
            else {
                record = view.store.getAt(
                    Math.min(Math.max(recordIdx, 0), view.store.getCount() - 1)
                );
            }

            metaGroup = groupingFeature.getMetaGroup(record);

            if (metaGroup && metaGroup.isCollapsed) {
                if (!groupingFeature.isExpandingOrCollapsing && record !== metaGroup.placeholder) {
                    groupingFeature.expand(groupingFeature.getGroup(record).getGroupKey());
                    total = store.getCount();
                    recordIdx = groupingFeature.indexOf(record);
                }
                else {
                    // If we've just been collapsed, then the only record we have is
                    // the wrapped placeholder
                    record = metaGroup.placeholder;
                    recordIdx = groupingFeature.indexOfPlaceholder(record);
                }
            }
            else {
                recordIdx = groupingFeature.indexOf(record);
            }

        }
        else {

            if (recordIdx.isEntity) {
                record = recordIdx;
                recordIdx = store.indexOf(record);

                // Currently loaded pages do not contain the passed record, we cannot proceed.
                if (recordIdx === -1) {
                    //<debug>
                    Ext.raise('Unknown record passed to BufferedRenderer#scrollTo');

                    //</debug>
                    return;
                }
            }
            else {
                // Sanitize the requested record index
                recordIdx = Math.min(Math.max(recordIdx, 0), total - 1);
                record = store.getAt(recordIdx);
            }
        }

        // See if the required row for that record happens to be within the rendered range.
        if (record && (targetRow = view.getNode(record))) {
            view.grid.ensureVisible(record, options);

            // Keep the view immediately replenished when we scroll an existing element into view.
            // DOM scroll events fire asynchronously, and we must not leave subsequent code
            // without a valid buffered row block.
            me.onViewScroll();

            return;
        }

        // Calculate view start index.
        // If the required record is above the fold...
        if (recordIdx < view.all.startIndex) {
            // The startIndex of the new rendered range is a little less
            // than the target record index.
            direction = -1;

            // eslint-disable-next-line max-len
            startIdx = Math.max(Math.min(recordIdx - (Math.floor((me.leadingBufferZone + me.trailingBufferZone) / 2)), total - me.viewSize + 1), 0);
            endIdx = Math.min(startIdx + me.viewSize - 1, total - 1);
        }
        // If the required record is below the fold...
        else {
            // The endIndex of the new rendered range is a little greater
            // than the target record index.
            direction = 1;

            // eslint-disable-next-line max-len
            endIdx = Math.min(recordIdx + (Math.floor((me.leadingBufferZone + me.trailingBufferZone) / 2)), total - 1);
            startIdx = Math.max(endIdx - (me.viewSize - 1), 0);
        }

        tableTop = Math.max(startIdx * me.rowHeight, 0);

        store.getRange(startIdx, endIdx, {
            callback: function(range, start, end) {
                // Render the range.
                // Pass synchronous flag so that it does it inline, not on a timer.
                // Pass fromLockingPartner flag so that it does not inform the lockingPartner.
                me.renderRange(start, end, true);
                record = store.data.getRange(recordIdx, recordIdx + 1)[0];
                targetRow = view.getNode(record);

                // bodyTop property must track the translated position of the body
                view.body.translate(null, me.bodyTop = tableTop);

                // Ensure the scroller knows about the range if we're going down
                if (direction === 1 && view.hasVariableRowHeight()) {
                    me.refreshSize();
                }

                // Locking partner must render the same range
                if (lockingPartner) {
                    lockingPartner.renderRange(start, end, true);

                    // Sync all row heights
                    me.syncRowHeights();

                    // bodyTop property must track the translated position of the body
                    lockingPartner.view.body.translate(null, lockingPartner.bodyTop = tableTop);

                    // Ensure the scroller knows about the range if we're going down
                    if (direction === 1) {
                        lockingPartner.refreshSize();
                    }
                }

                // The target does not map to a view node.
                // Cannot scroll to it.
                if (!targetRow) {
                    return;
                }

                view.grid.ensureVisible(record, options);

                me.scrollTop = me.position = me.scroller.getPosition().y;

                if (lockingPartner) {
                    lockingPartner.position = lockingPartner.scrollTop = me.scrollTop;
                }
            }
        });
    },

    onViewScroll: function(scroller, x, scrollTop) {
        var me = this,
            bodyDom = me.view.body.dom,
            store = me.store,
            totalCount = (store.getCount()),
            vscrollDistance,
            scrollDirection;

        // May be directly called with no args, as well as from the Scroller's scroll event
        me.scrollTop = scrollTop == null ? (scrollTop = me.scroller.getPosition().y) : scrollTop;

        // Because lockable assemblies now only have one Y scroller,
        // initially hidden grids (one side may begin with all the columns)
        // still get the scroll notification, but may not have any DOM
        // to scroll.
        if (bodyDom) {
            // Only check for nearing the edge if we are enabled, and if there is overflow
            // beyond our view bounds. If there is no paging to be done
            // (Store's dataset is all in memory) we will be disabled.
            if (!(me.disabled || totalCount < me.viewSize)) {

                vscrollDistance = scrollTop - me.position;
                scrollDirection = vscrollDistance > 0 ? 1 : -1;

                // Moved at least 20 pixels, or changed direction, so test whether the numFromEdge
                // is triggered
                if (Math.abs(vscrollDistance) >= 20 ||
                    (scrollDirection !== me.lastScrollDirection)) {
                    me.lastScrollDirection = scrollDirection;
                    me.handleViewScroll(me.lastScrollDirection, vscrollDistance);
                }
            }
        }
    },

    handleViewScroll: function(direction, vscrollDistance) {
        var me = this,
            rows = me.view.all,
            store = me.store,
            storeCount = store.getCount(),
            viewSize = me.viewSize,
            lastItemIndex = storeCount - 1,
            maxRequestStart = Math.max(0, storeCount - viewSize),
            requestStart,
            requestEnd;

        // We're scrolling up
        if (direction === -1) {
            // If table starts at record zero, we have nothing to do
            if (rows.startIndex) {
                if (me.topOfViewCloseToEdge()) {
                    requestStart = Math.max(0, me.getLastVisibleRowIndex() + me.trailingBufferZone -
                                            viewSize);

                    // If, having scrolled up, a variableRowHeight calculation based
                    // upon scrolTop/rowHeight yields an obviously wrong value,
                    // then constrain it to a calculated value.
                    // We CANNOT just Math.min it with maxRequestStart, because we may already
                    // be at maxRequestStart, and asking to render the same block
                    // will have no effect.
                    // We calculate a start value a few rows above the current startIndex.
                    if (requestStart > rows.startIndex) {
                        requestStart = Math.max(
                            0, rows.startIndex + Math.floor(vscrollDistance / me.rowHeight)
                        );
                    }
                }
            }
        }
        // We're scrolling down
        else {

            // If table ends at last record, we have nothing to do
            if (rows.endIndex < lastItemIndex) {
                if (me.bottomOfViewCloseToEdge()) {
                    // eslint-disable-next-line max-len
                    requestStart = Math.max(0, Math.min(me.getFirstVisibleRowIndex() - me.trailingBufferZone, maxRequestStart));
                }
            }
        }

        // View is OK at this scroll. Advance loadId so that any load requests in flight do not
        // result in rendering upon their return.
        if (requestStart == null) {
            // View is still valid at this scroll position.
            // Do not trigger a handleViewScroll call until *ANOTHER* 20 pixels have scrolled by.
            me.position = me.scrollTop;
            me.loadId++;
        }
        // We scrolled close to the edge and the Store needs reloading
        else {
            requestEnd = Math.min(requestStart + viewSize - 1, lastItemIndex);

            // viewSize was calculated too small due to small sample row count with some skewed
            // item height in there such as a tall group header item. Bump range
            // down by one in this case.
            if (me.variableRowHeight && requestEnd === rows.endIndex &&
                requestEnd < lastItemIndex) {
                requestEnd++;
                requestStart++;
            }

            // If calculated view range has moved, then render it and return the fact
            // that the scroll was handled.
            if (requestStart !== rows.startIndex || requestEnd !== rows.endIndex) {
                me.scroller.trackingScrollTop = me.scrollTop;
                me.renderRange(requestStart, requestEnd);

                return true;
            }
        }
    },

    bottomOfViewCloseToEdge: function() {
        var me = this;

        if (me.variableRowHeight) {
            return me.bodyTop + me.bodyHeight < me.scrollTop + me.view.lastBox.height +
                   (me.numFromEdge * me.rowHeight);
        }
        else {
            return (me.view.all.endIndex - me.getLastVisibleRowIndex()) < me.numFromEdge;
        }
    },

    topOfViewCloseToEdge: function() {
        var me = this;

        if (me.variableRowHeight) {
            // The body top position is within the numFromEdge zone
            return me.bodyTop > me.scrollTop - (me.numFromEdge * me.rowHeight);
        }
        else {
            return (me.getFirstVisibleRowIndex() - me.view.all.startIndex) < me.numFromEdge;
        }
    },

    /**
     * @private
     * Refreshes the current rendered range if possible.
     * Optionally refreshes starting at the specified index.
     */
    refreshView: function(startIndex, scrollIncrement) {
        var me = this,
            viewSize = me.viewSize,
            view = me.view,
            rows = view.all,
            store = me.store,
            storeCount = store.getCount(),
            maxIndex = Math.max(0, storeCount - 1),
            lockingPartnerRows = view.lockingPartner && view.lockingPartner.all,
            preserveScroll = me.bodyTop && view.preserveScrollOnRefresh || scrollIncrement,
            endIndex;

        // Empty Store is simple, don't even ask the store
        if (!storeCount) {
            return me.doRefreshView([], 0, 0);
        }
        // Store doesn't fill the required view size. Simple start/end calcs.
        else if (storeCount < viewSize) {
            startIndex = 0;
            endIndex = maxIndex;
            me.nextRefreshStartIndex = preserveScroll ? null : 0;
        }
        // We're starting from nothing, but there's a locking partner with the range info,
        // so match that
        else if (startIndex == null && !rows.getCount() && lockingPartnerRows &&
                 lockingPartnerRows.getCount()) {
            startIndex = lockingPartnerRows.startIndex;
            endIndex = Math.min(lockingPartnerRows.endIndex, startIndex + viewSize - 1, maxIndex);
        }
        // Work out range to refresh
        else {
            if (startIndex == null) {
                // Use a nextRefreshStartIndex as set by a load operation
                // in which we are maintaining scroll position
                if (me.nextRefreshStartIndex != null && !preserveScroll) {
                    startIndex = me.nextRefreshStartIndex;
                }
                else {
                    startIndex = rows.startIndex;
                }

                me.nextRefreshStartIndex = null;
            }

            // New start index should be current start index unless that's now too close
            // to the end of the store to yield a full view, in which case work back
            // from the end of the store. Ensure we don't go negative.
            startIndex = Math.max(0, Math.min(startIndex, maxIndex - viewSize + 1));

            // New end index works forward from the new start index ensuring
            // we don't walk off the end    
            endIndex = Math.min(startIndex + viewSize - 1, maxIndex);

            if (endIndex - startIndex + 1 > viewSize) {
                startIndex = endIndex - viewSize + 1;
            }
        }

        if (startIndex === 0 && endIndex === -1) {
            me.doRefreshView([], 0, 0);
        }
        else {
            store.getRange(startIndex, endIndex, {
                callback: me.doRefreshView,
                scope: me
            });
        }
    },

    doRefreshView: function(range, startIndex, endIndex) {
        var me = this,
            view = me.view,
            scroller = me.scroller,
            rows = view.all,
            previousStartIndex = rows.startIndex,
            previousEndIndex = rows.endIndex,
            prevRowCount = rows.getCount(),
            viewMoved = startIndex !== rows.startIndex && !me.isStoreLoading,
            calculatedTop = -1,
            previousFirstItem, previousLastItem, scrollIncrement, restoreFocus;

        me.isStoreLoading = false;

        // So that listeners to the itemremove events know that its because of a refresh.
        // And so that this class's refresh listener knows to ignore it.
        view.refreshing = me.refreshing = true;

        if (view.refreshCounter) {

            // Give CellEditors or other transient in-cell items a chance to get out of the way.
            if (view.hasListeners.beforerefresh &&
                view.fireEvent('beforerefresh', view) === false) {
                return view.refreshNeeded = view.refreshing = me.refreshing = false;
            }

            // If focus was in any way in the view, whether actionable or navigable,
            // this will return a function which will restore that state.
            restoreFocus = view.saveFocusState();

            view.clearViewEl(true);
            view.refreshCounter++;

            if (range.length) {
                view.doAdd(range, startIndex);

                if (viewMoved) {
                    // Try to find overlap between newly rendered block and old block
                    previousFirstItem = rows.item(previousStartIndex, true);
                    previousLastItem = rows.item(previousEndIndex, true);

                    // Work out where to move the view top if there is overlap
                    if (previousFirstItem) {
                        scrollIncrement = -previousFirstItem.offsetTop;
                    }
                    else if (previousLastItem) {
                        scrollIncrement = rows.last(true).offsetTop - previousLastItem.offsetTop;
                    }

                    // If there was an overlap, we know exactly where to move the view
                    if (scrollIncrement) {
                        calculatedTop = Math.max(me.bodyTop + scrollIncrement, 0);
                        me.scrollTop = calculatedTop ? me.scrollTop + scrollIncrement : 0;
                    }
                    // No overlap: calculate the a new body top and scrollTop.
                    else {
                        calculatedTop = startIndex * me.rowHeight;

                        // eslint-disable-next-line max-len
                        me.scrollTop = Math.max(calculatedTop + me.rowHeight * (calculatedTop < me.bodyTop ? me.leadingBufferZone : me.trailingBufferZone), 0);
                    }
                }
            }

            // Clearing the view.
            // Ensure we jump to top.
            // Apply empty text.
            else {
                me.scrollTop = calculatedTop = me.position = 0;
                view.addEmptyText();
            }

            // Keep scroll and rendered block positions synched if there is scrolling.
            if (calculatedTop !== -1) {
                me.setBodyTop(calculatedTop);
                me.doVerticalScroll(scroller, me.scrollTop, true);
            }

            // Correct scroll range
            me.refreshSize();
            view.refreshSize(rows.getCount() !== prevRowCount);
            view.fireItemMutationEvent('refresh', view, range);

            // If focus was in any way in this view, this will restore it
            restoreFocus();

            if (view.preserveScrollOnRefresh && restoreFocus !== Ext.emptyFn) {
                me.doVerticalScroll(scroller, me.scrollTop, true);
            }

            view.headerCt.setSortState();
        }
        else {
            view.refresh();
        }

        //<debug>
        // If there are columns to trigger rendering, and the rendered block is not
        // either the view size or, if store count less than view size, the store count,
        // then there's a bug.
        if (view.getVisibleColumnManager().getColumns().length &&
            rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
            Ext.raise('rendered block refreshed at ' + rows.getCount() +
                      ' rows while BufferedRenderer view size is ' + me.viewSize);
        }
        //</debug>

        view.refreshNeeded = view.refreshing = me.refreshing = false;
    },

    renderRange: function(start, end, forceSynchronous) {
        var me = this,
            rows = me.view.all,
            store = me.store;

        // We're being told to render what we already have rendered.
        if (rows.startIndex === start && rows.endIndex === end) {
            return;
        }

        // Skip if we are being asked to render exactly the rows that we already have.
        // This can happen if the viewSize has to be recalculated
        // (due to either a data refresh or a view resize event) but the calculated size
        // ends up the same.
        if (!(start === rows.startIndex && end === rows.endIndex)) {

            // If range is available synchronously, process it now.
            if (store.rangeCached(start, end)) {
                me.cancelLoad();

                if (me.synchronousRender || forceSynchronous) {
                    me.onRangeFetched(null, start, end);
                }
                else {
                    if (!me.renderTask) {
                        me.renderTask = new Ext.util.DelayedTask(me.onRangeFetched, me);
                    }

                    // Render the new range very soon after this scroll event handler exits.
                    // If scrolling very quickly, a few more scroll events may fire before
                    // the render takes place. Each one will just *update* the arguments with which
                    // the pending invocation is called.
                    me.renderTask.delay(-1, null, null, [null, start, end]);
                }
            }

            // Required range is not in the prefetch buffer. Ask the store to prefetch it.
            else {
                me.attemptLoad(start, end, me.scrollTop);
            }
        }
    },

    onRangeFetched: function(range, start, end) {
        var me = this,
            view = me.view,
            scroller = me.scroller,
            viewEl = view.el,
            rows = view.all,
            increment = 0,
            calculatedTop,
            partnerView = !me.doNotMirror && view.lockingPartner,
            partnerColManger = partnerView && partnerView.getVisibleColumnManager(),
            partnerViewConfigured = partnerColManger && partnerColManger.getColumns().length,
            lockingPartner = partnerViewConfigured && partnerView.bufferedRenderer,
            partnerRows = partnerViewConfigured && partnerView.all,
            variableRowHeight = me.variableRowHeight,

            doSyncRowHeight = partnerViewConfigured && partnerView.ownerCt.isVisible() && (
                view.ownerGrid.syncRowHeight ||
                view.ownerGrid.syncRowHeightOnNextLayout ||
                (lockingPartner.variableRowHeight !== variableRowHeight)
            ),

            activeEl, focusedView, i, newRows, newTop, noOverlap,
            oldStart, partnerNewRows, pos, removeCount, topAdditionSize, topBufferZone, records;

        // View may have been destroyed since the DelayedTask was kicked off.
        if (view.destroyed) {
            return;
        }

        // If called as a callback from the Store, the range will be passed,
        // if called from renderRange, it won't
        if (range) {
            // Re-cache the scrollTop if there has been an asynchronous call to the server.
            me.scrollTop = scroller.getPosition().y;
        }
        else {
            range = me.store.getRange(start, end);

            // Store may have been cleared since the DelayedTask was kicked off.
            if (!range) {
                return;
            }
        }

        // If we contain focus now, but do not when we have rendered the new rows,
        // we must focus the view el.
        activeEl = Ext.fly(Ext.Element.getActiveElement());

        if (viewEl.contains(activeEl)) {
            focusedView = view;
        }
        else if (partnerView && partnerView.el.contains(activeEl)) {
            focusedView = partnerView;
        }

        // In case the browser does fire synchronous focus events when a focused element
        // is derendered...
        if (focusedView) {
            activeEl.suspendFocusEvents();
        }

        // Best guess rendered block position is start row index * row height.
        // We can use this as bodyTop if the row heights are all standard.
        // We MUST use this as bodyTop if the scroll is a teleporting scroll.
        // If we are incrementally scrolling, we add the rows to the bottom, and
        // remove a block of rows from the top.
        // The bodyTop is then incremented by the height of the removed block to keep
        // the visuals the same.
        //
        // We cannot always use the calculated top, and compensate by adjusting the scroll position
        // because that would break momentum scrolling on DOM scrolling platforms, and would be
        // immediately undone in the next frame update of a momentum scroll on touch scroll
        // platforms.
        calculatedTop = start * me.rowHeight;

        // The new range encompasses the current range. Refresh and keep the scroll position stable
        if (start < rows.startIndex && end > rows.endIndex) {
            // How many rows will be added at top. So that we can reposition the table
            // to maintain scroll position
            topAdditionSize = rows.startIndex - start;

            // MUST use View method so that itemremove events are fired so widgets can be recycled.
            view.clearViewEl(true);
            newRows = view.doAdd(range, start);
            view.fireItemMutationEvent('itemadd', range, start, newRows, view);

            // Keep other side's rendered block the same
            if (lockingPartner) {
                partnerView.clearViewEl(true);
                partnerNewRows = partnerView.doAdd(range, start);
                partnerView.fireItemMutationEvent('itemadd', range, start, partnerNewRows,
                                                  partnerView);

                // We're going to be doing measurement of newRows
                // Ensure heights are synced first
                if (doSyncRowHeight) {
                    me.syncRowHeights(newRows, partnerNewRows);
                    doSyncRowHeight = false;
                }
            }

            for (i = 0; i < topAdditionSize; i++) {
                increment -= me.grid.getElementHeight(newRows[i]);
            }

            // We've just added a bunch of rows to the top of our range,
            // so move upwards to keep the row appearance stable
            newTop = me.bodyTop + increment;
        }
        else {
            // No overlapping nodes; we'll need to render the whole range.
            // teleported flag is set in getFirstVisibleRowIndex/getLastVisibleRowIndex if
            // the table body has moved outside the viewport bounds
            noOverlap = me.teleported || start > rows.endIndex || end < rows.startIndex;

            if (noOverlap) {
                view.clearViewEl(true);
                me.teleported = false;
            }

            if (!rows.getCount()) {
                newRows = view.doAdd(range, start);
                view.fireItemMutationEvent('itemadd', range, start, newRows, view);

                // Keep other side's rendered block the same
                if (lockingPartner) {
                    partnerView.clearViewEl(true);
                    partnerNewRows = lockingPartner.view.doAdd(range, start);
                    partnerView.fireItemMutationEvent('itemadd', range, start, partnerNewRows,
                                                      partnerView);
                }

                newTop = calculatedTop;

                // Adjust the bodyTop to place the data correctly around the scroll vieport
                if (noOverlap && variableRowHeight) {
                    topBufferZone = me.scrollTop < me.position
                        ? me.leadingBufferZone
                        : me.trailingBufferZone;

                    // Can't calculate a new top if there are fewer than topBufferZone rows above us
                    if (start > topBufferZone) {
                        // eslint-disable-next-line max-len
                        newTop = Math.max(me.scrollTop - rows.item(rows.startIndex + topBufferZone - 1, true).offsetTop, 0);
                    }
                }
            }
            // Moved down the dataset (content moved up): remove rows from top, add to end
            else if (end > rows.endIndex) {
                removeCount = Math.max(start - rows.startIndex, 0);

                // We only have to bump the table down by the height of removed rows
                // if rows are not a standard size
                if (variableRowHeight) {
                    increment = rows.item(rows.startIndex + removeCount, true).offsetTop;
                }

                records = Ext.Array.slice(range, rows.endIndex + 1 - start);
                newRows = rows.scroll(records, 1, removeCount);

                if (lockingPartner) {
                    partnerNewRows = partnerRows.scroll(records, 1, removeCount);
                }

                // We only have to bump the table down by the height of removed rows
                // if rows are not a standard size
                if (variableRowHeight) {
                    // Bump the table downwards by the height scraped off the top
                    newTop = me.bodyTop + increment;
                }
                // If the rows are standard size, then the calculated top will be correct
                else {
                    newTop = calculatedTop;
                }
            }
            // Moved up the dataset: remove rows from end, add to top
            else {
                removeCount = Math.max(rows.endIndex - end, 0);
                oldStart = rows.startIndex;
                records = Ext.Array.slice(range, 0, rows.startIndex - start);
                newRows = rows.scroll(records, -1, removeCount);

                if (lockingPartner) {
                    partnerNewRows = partnerRows.scroll(records, -1, removeCount);
                }

                // We only have to bump the table up by the height of top-added rows if
                // rows are not a standard size. If they are standard, calculatedTop is correct.
                // Sync the row heights *before* calculating the newTop and increment
                if (doSyncRowHeight) {
                    me.syncRowHeights(newRows, partnerNewRows);
                    doSyncRowHeight = false;

                    // Bump the table upwards by the height added to the top
                    newTop = me.bodyTop - rows.item(oldStart, true).offsetTop;

                    // We've arrived at row zero...
                    if (!rows.startIndex) {
                        // But the calculated top position is out. It must be zero at this point
                        // We adjust the scroll position to keep visual position of table the same.
                        if (newTop) {
                            me.doVerticalScroll(scroller, me.scrollTop -= newTop);
                            newTop = 0;
                        }
                    }
                    // Not at zero yet, but the position has moved into negative range
                    else if (newTop < 0) {
                        increment = rows.startIndex * me.rowHeight;
                        me.doVerticalScroll(scroller, me.scrollTop += increment);
                        newTop = me.bodyTop + increment;
                    }
                }
                // If the rows are standard size, then the calculated top will be correct
                else {
                    newTop = calculatedTop;
                }
            }

            // The position property is the scrollTop value *at which the table was last correct*
            // MUST be set at table render/adjustment time
            me.position = me.scrollTop;
        }

        // A view contained focus at the start, check whether activeEl has been derendered.
        // Focus the cell's column header if so.
        if (focusedView) {
            // Restore active element's focus processing.
            activeEl.resumeFocusEvents();

            if (!focusedView.el.contains(activeEl)) {
                pos = focusedView.actionableMode
                    ? focusedView.actionPosition
                    : focusedView.lastFocused;

                if (pos && pos.column) {
                    // we set the rendering rows to true here so the actionables know
                    // that view is forcing the onFocusLeave method here
                    focusedView.renderingRows = true;
                    focusedView.onFocusLeave({});
                    focusedView.renderingRows = false;

                    me.getNewFocusTarget(pos).focus();
                }
            }
        }

        // Calculate position of item container.
        newTop = Math.max(Math.floor(newTop), 0);

        if (view.positionBody) {
            me.setBodyTop(newTop, true);
        }

        // Sync the other side to exactly the same range from the dataset.
        // Then ensure that we are still at exactly the same scroll position.
        if (lockingPartner) {
            // Locking partner BufferedRenderer must not react to the scroll.
            lockingPartner.scrollTop = me.scrollTop;

            if (lockingPartner.bodyTop !== newTop) {
                lockingPartner.setBodyTop(newTop, true);
            }

            if (doSyncRowHeight) {
                me.syncRowHeights(newRows, partnerNewRows);
            }
        }
        else if (variableRowHeight) {
            delete me.rowHeight;
            me.refreshSize();
        }

        //<debug>
        // If there are columns to trigger rendering, and the rendered block
        // is not either the view size or, if store count less than view size,
        // the store count, then there's a bug.
        if (view.getVisibleColumnManager().getColumns().length &&
            rows.getCount() !== Math.min(me.store.getCount(), me.viewSize)) {
            Ext.raise('rendered block refreshed at ' + rows.getCount() +
                      ' rows while BufferedRenderer view size is ' + me.viewSize);
        }
        //</debug>

        return newRows;
    },

    /**
     * Gets the next focus target based on the position
     * @param {Ext.grid.CellContext} pos
     * @returns {Ext.Component}
     * @since 6.2.2
     */
    getNewFocusTarget: function(pos) {
        var view = pos.view,
            grid = view.grid,
            column = pos.column,
            hiddenHeaders = column.isHidden() || grid.hideHeaders,
            tabbableItems;

        // Focus MUST NOT silently die due to DOM removal. Focus will be moved
        // in the following order as available:
        // Try focusing the contextual column header
        if (column.focusable && !hiddenHeaders) {
            return column;
        }

        tabbableItems = column.el.findTabbableElements();

        // Failing that, look inside it for a tabbable element
        if (tabbableItems && tabbableItems.length) {
            return tabbableItems[0];
        }

        // Failing that, find the available focus target of the grid or focus the view
        return grid.findFocusTarget() || view.el;
    },

    syncRowHeights: function(itemEls, partnerItemEls) {
        var me = this,
            ln = 0,
            otherLn = 1, // Different initial values so that all items are synched
            mySynchronizer = [],
            otherSynchronizer = [],
            RowSynchronizer = Ext.grid.locking.RowSynchronizer,
            i, rowSync;

        if (itemEls && partnerItemEls) {
            ln = itemEls.length;
            otherLn = partnerItemEls.length;
        }

        // The other side might not quite by in scroll sync with us, in which case
        // it may have gone a different path way and rolled some rows into
        // the rendered block where we may have re-rendered the whole thing.
        // If this has happened, fall back to syncing all rows.
        if (ln !== otherLn) {
            itemEls = me.view.all.slice();
            partnerItemEls = me.view.lockingPartner.all.slice();
            ln = otherLn = itemEls.length;
        }

        for (i = 0; i < ln; i++) {
            mySynchronizer[i] = rowSync = new RowSynchronizer(me.view, itemEls[i]);
            rowSync.measure();
        }

        for (i = 0; i < otherLn; i++) {
            otherSynchronizer[i] = rowSync =
                new RowSynchronizer(me.view.lockingPartner, partnerItemEls[i]);
            rowSync.measure();
        }

        for (i = 0; i < ln; i++) {
            mySynchronizer[i].finish(otherSynchronizer[i]);
            otherSynchronizer[i].finish(mySynchronizer[i]);
        }

        // Ensure that both BufferedRenderers have the same idea about scroll range and row height
        me.syncRowHeightsFinish();
    },

    syncRowHeightsFinish: function() {
        var me = this,
            view = me.view,
            lockingPartner = view.lockingPartner.bufferedRenderer,
            ownerGrid = view.ownerGrid,
            scrollable = view.getScrollable();

        ownerGrid.syncRowHeightOnNextLayout = false;

        // Now that row heights have potentially changed, both BufferedRenderers
        // have to re-evaluate what they think the average rowHeight is
        // based on the synchronized-height rows.
        //
        // If the view has not been layed out, then the upcoming first resize event
        // will trigger the needed refreshSize call; See onViewRefresh -
        // If control arrives there and the componentLayoutCounter is zero and
        // there is variableRowHeight, it schedules itself to be run on boxready
        // so refreshSize will be called there for the first time.
        if (view.componentLayoutCounter) {
            delete me.rowHeight;
            me.refreshSize();
            delete lockingPartner.rowHeight;
            lockingPartner.refreshSize();
        }

        // Component layout only restores the scroller's state for managed layouts
        // here we need to make sure the scroller is restores after the rows sync
        if (scrollable) {
            scrollable.restoreState();
        }
    },

    setBodyTop: function(bodyTop, skipStretchView) {
        var me = this,
            view = me.view,
            rows = view.all,
            store = me.store,
            body = view.body;

        if (!body.dom) {
            // The view may be rendered, but the body element not attached.
            return;
        }

        me.translateBody(body, bodyTop);

        // If this is the last page, correct the scroll range to be just enough to fit.
        if (me.variableRowHeight) {
            me.bodyHeight = me.grid.getElementHeight(body.dom);

            // We are displaying the last row, so ensure the scroll range
            // finishes exactly at the bottom of the view body
            if (rows.endIndex === store.getCount() - 1) {
                me.scrollHeight = bodyTop + me.bodyHeight - 1;
            }
            // Not last row - recalculate scroll range
            else {
                me.scrollHeight = me.getScrollHeight();
            }

            if (!skipStretchView) {
                me.stretchView(view, me.scrollHeight);
            }
        }
        else {
            // If we have fixed row heights, calculate rendered block height
            // without forcing a layout
            me.bodyHeight = rows.getCount() * me.rowHeight;
        }
    },

    translateBody: function(body, bodyTop) {
        body.translate(null, this.bodyTop = bodyTop);
    },

    getFirstVisibleRowIndex: function(startRow, endRow, viewportTop, viewportBottom) {
        var me = this,
            view = me.view,
            rows = view.all,
            elements = rows.elements,
            clientHeight = me.viewClientHeight,
            target,
            targetTop,
            bodyTop = me.bodyTop;

        // If variableRowHeight, we have to search for the first row who's bottom edge
        // is within the viewport
        if (rows.getCount() && me.variableRowHeight) {
            if (!arguments.length) {
                startRow = rows.startIndex;
                endRow = rows.endIndex;
                viewportTop = me.scrollTop;
                viewportBottom = viewportTop + clientHeight;

                // Teleported so that body is outside viewport: Use rowHeight calculation
                if (bodyTop > viewportBottom || bodyTop + me.bodyHeight < viewportTop) {
                    me.teleported = true;

                    return Math.floor(me.scrollTop / me.rowHeight);
                }

                // In first, non-recursive call, begin targeting the most likely first row
                target = startRow + Math.min(me.numFromEdge + ((me.lastScrollDirection === -1)
                    ? me.leadingBufferZone
                    : me.trailingBufferZone), Math.floor((endRow - startRow) / 2));
            }
            else {
                if (startRow === endRow) {
                    return endRow;
                }

                target = startRow + Math.floor((endRow - startRow) / 2);
            }

            targetTop = bodyTop + elements[target].offsetTop;

            // If target is entirely above the viewport, chop downwards
            if (targetTop + me.grid.getElementHeight(elements[target]) <= viewportTop) {
                return me.getFirstVisibleRowIndex(target + 1, endRow, viewportTop, viewportBottom);
            }

            // Target is first
            if (targetTop <= viewportTop) {
                return target;
            }
            // Not narrowed down to 1 yet; chop upwards
            else if (target !== startRow) {
                return me.getFirstVisibleRowIndex(startRow, target - 1, viewportTop,
                                                  viewportBottom);
            }
        }

        return Math.floor(me.scrollTop / me.rowHeight);
    },

    /**
     * Returns the index of the last row in your table view deemed to be visible.
     * @return {Number}
     * @private
     */
    getLastVisibleRowIndex: function(startRow, endRow, viewportTop, viewportBottom) {
        var me = this,
            view = me.view,
            rows = view.all,
            elements = rows.elements,
            clientHeight = me.viewClientHeight,
            target,
            targetTop, targetBottom,
            bodyTop = me.bodyTop;

        // If variableRowHeight, we have to search for the first row who's bottom edge
        // is below the bottom of the viewport
        if (rows.getCount() && me.variableRowHeight) {
            if (!arguments.length) {
                startRow = rows.startIndex;
                endRow = rows.endIndex;
                viewportTop = me.scrollTop;
                viewportBottom = viewportTop + clientHeight;

                // Teleported so that body is outside viewport: Use rowHeight calculation
                if (bodyTop > viewportBottom || bodyTop + me.bodyHeight < viewportTop) {
                    me.teleported = true;

                    return Math.floor(me.scrollTop / me.rowHeight) +
                           Math.ceil(clientHeight / me.rowHeight);
                }

                // In first, non-recursive call, begin targeting the most likely last row
                target = endRow - Math.min(me.numFromEdge + ((me.lastScrollDirection === 1)
                    ? me.leadingBufferZone
                    : me.trailingBufferZone), Math.floor((endRow - startRow) / 2));
            }
            else {
                if (startRow === endRow) {
                    return endRow;
                }

                target = startRow + Math.floor((endRow - startRow) / 2);
            }

            targetTop = bodyTop + elements[target].offsetTop;

            // If target is entirely below the viewport, chop upwards
            if (targetTop > viewportBottom) {
                return me.getLastVisibleRowIndex(startRow, target - 1, viewportTop, viewportBottom);
            }

            targetBottom = targetTop + me.grid.getElementHeight(elements[target]);

            // Target is last
            if (targetBottom >= viewportBottom) {
                return target;
            }
            // Not narrowed down to 1 yet; chop downwards
            else if (target !== endRow) {
                return me.getLastVisibleRowIndex(target + 1, endRow, viewportTop, viewportBottom);
            }
        }

        return Math.min(me.getFirstVisibleRowIndex() + Math.ceil(clientHeight / me.rowHeight),
                        rows.endIndex);
    },

    getScrollHeight: function() {
        var me = this,
            view = me.view,
            rows = view.all,
            store = me.store,
            recCount = store.getCount(),
            rowCount = rows.getCount(),
            row, rowHeight, borderWidth, scrollHeight;

        if (!recCount) {
            return 0;
        }

        if (!me.hasOwnProperty('rowHeight')) {
            if (rowCount) {
                if (me.variableRowHeight) {
                    me.rowHeight = Math.floor(me.bodyHeight / rowCount);
                }
                else {
                    row = rows.first();
                    rowHeight = row.getHeight();

                    // In IE8 we're adding bottom border on all the rows to work around
                    // the lack of :last-child selector, and we compensate that by setting
                    // a negative top margin that equals the border width, so that top and
                    // bottom borders overlap on adjacent rows. Negative margin does not
                    // affect the row's reported height though so we have to compensate
                    // for that effectively invisible additional border width here.
                    if (Ext.isIE8) {
                        borderWidth = row.getBorderWidth('b');

                        if (borderWidth > 0) {
                            rowHeight -= borderWidth;
                        }
                    }

                    me.rowHeight = rowHeight;
                }
            }
            else {
                delete me.rowHeight;
            }
        }

        if (me.variableRowHeight) {
            // If this is the last page, ensure the scroll range is exactly enough
            // to scroll to the end of the rendered block.
            if (rows.endIndex === recCount - 1) {
                scrollHeight = me.bodyTop + me.bodyHeight - 1;
            }
            // Calculate the scroll range based upon measured row height and our scrollPosition.
            else {
                scrollHeight = Math.floor((recCount - rowCount) * me.rowHeight) + me.bodyHeight;

                // If there's a discrepancy between the boy position we have scrolled to,
                // and the calculated position, account for that in the scroll range
                // so that we have enough range to scroll all the data into view.
                scrollHeight += me.bodyTop - rows.startIndex * me.rowHeight;
            }
        }
        else {
            scrollHeight = Math.floor(recCount * me.rowHeight);
        }

        return (me.scrollHeight = scrollHeight);
    },

    getThemeRowHeight: function() {
        var me = this,
            testEl;

        if (!me.themeRowHeight) {
            testEl = Ext.getBody().createChild({
                cls: Ext.baseCSSPrefix + 'theme-row-height-el'
            });
            me.self.prototype.themeRowHeight = testEl.dom.offsetHeight;
            testEl.destroy();
        }

        return me.themeRowHeight;
    },

    attemptLoad: function(start, end, loadScrollPosition) {
        var me = this;

        if (me.scrollToLoadBuffer) {
            if (!me.loadTask) {
                me.loadTask = new Ext.util.DelayedTask();
            }

            me.loadTask.delay(
                me.scrollToLoadBuffer, me.doAttemptLoad, me, [start, end, loadScrollPosition]
            );
        }
        else {
            me.doAttemptLoad(start, end, loadScrollPosition);
        }
    },

    cancelLoad: function() {
        if (this.loadTask) {
            this.loadTask.cancel();
        }
    },

    doAttemptLoad: function(start, end, loadScrollPosition) {
        var me = this;

        // If we were called on a delay, check for destruction
        if (!me.destroyed) {
            me.store.getRange(start, end, {
                loadId: ++me.loadId,
                callback: function(range, start, end, options) {
                    // If our loadId position has not changed since the getRange request started,
                    // we can continue to render.
                    // If the scroll position is different to the scroll position which triggered
                    // the load, ignore it - we don't need the data any more.
                    if (options.loadId === me.loadId && me.scrollTop === loadScrollPosition) {
                        me.onRangeFetched(range, start, end);
                    }
                },
                fireEvent: false
            });
        }
    },

    destroy: function() {
        var me = this;

        me.cancelLoad();

        if (me.store) {
            me.unbindStore();
        }

        // Remove listeners from old grid, view and store
        Ext.destroy(me.viewListeners, me.stretcher, me.gridListeners, me.scrollListeners);

        me.callParent();
    }
});
