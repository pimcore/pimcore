/**
 * @private
 *
 * Lockable is a private mixin which injects lockable behavior into any
 * TablePanel subclass such as GridPanel or TreePanel. TablePanel will
 * automatically inject the Ext.grid.locking.Lockable mixin in when one of the
 * these conditions are met:
 *
 *  - The TablePanel has the lockable configuration set to true
 *  - One of the columns in the TablePanel has locked set to true/false
 *
 * Each TablePanel subclass must register an alias. It should have an array
 * of configurations to copy to the 2 separate tablepanels that will be generated
 * to note what configurations should be copied. These are named normalCfgCopy and
 * lockedCfgCopy respectively.
 *
 * Configurations which are specified in this class will be available on any grid or
 * tree which is using the lockable functionality.
 *
 * By default the two grids, "locked" and "normal" will be arranged using an
 * {@link Ext.layout.container.HBox hbox} layout. If the lockable grid is configured with
 * `{@link #split split:true}`, a vertical splitter will be placed between the two grids
 * to resize them.
 *
 * It is possible to override the layout of the lockable grid, or example, you may wish to
 * use a border layout and have one of the grids collapsible.
 */
Ext.define('Ext.grid.locking.Lockable', {
    alternateClassName: 'Ext.grid.Lockable',

    requires: [
        'Ext.grid.locking.View',
        'Ext.grid.header.Container',
        'Ext.grid.locking.HeaderContainer',
        'Ext.view.Table',
        'Ext.scroll.LockingScroller'
    ],

    /**
     * @cfg {Boolean} syncRowHeight
     * Synchronize rowHeight between the normal and locked grid view. This is turned on
     * by default. If your grid is guaranteed to have rows of all the same height, you
     * should set this to false to optimize performance.
     */
    syncRowHeight: true,

    /**
     * @cfg {String} subGridXType
     * The xtype of the subgrid to specify. If this is not specified lockable will 
     * determine the subgrid xtype to create by the following rule. Use the superclasses
     * xtype if the superclass is NOT tablepanel, otherwise use the xtype itself.
     */

    /**
     * @cfg {Object} lockedViewConfig
     * A view configuration to be applied to the locked side of the grid. Any conflicting
     * configurations between lockedViewConfig and viewConfig will be overwritten by the
     * lockedViewConfig.
     */

    /**
     * @cfg {Object} normalViewConfig
     * A view configuration to be applied to the normal/unlocked side of the grid. Any
     * conflicting configurations between normalViewConfig and viewConfig will be
     * overwritten by the normalViewConfig.
     */

    headerCounter: 0,

    /**
     * @cfg {Object} lockedGridConfig
     * Any special configuration options for the locked part of the grid
     */

    /**
     * @cfg {Object} normalGridConfig
     * Any special configuration options for the normal part of the grid
     */

    /**
     * @cfg {Boolean/Object} [split=false]
     * Configure as `true` to place a resizing {@link Ext.resizer.Splitter splitter}
     * between the locked and unlocked columns. May also be a configuration object for the Splitter.
     */

    /**
     * @cfg {Object} layout
     * By default, a lockable grid uses an {@link Ext.layout.container.HBox HBox} layout to arrange
     * the two grids (possibly separated by a splitter).
     *
     * Using this config it is possible to specify a different layout to arrange the two grids.
     */

    /**
     * @cfg stateEvents
     * @inheritdoc Ext.state.Stateful#cfg-stateEvents
     * @localdoc Adds the following stateEvents:
     * 
     *  - {@link #event-lockcolumn}
     *  - {@link #event-unlockcolumn}
     */

    lockedGridCls: Ext.baseCSSPrefix + 'grid-inner-locked',
    normalGridCls: Ext.baseCSSPrefix + 'grid-inner-normal',
    lockingBodyCls: Ext.baseCSSPrefix + 'grid-locking-body',
    scrollContainerCls: Ext.baseCSSPrefix + 'grid-scroll-container',
    scrollBodyCls: Ext.baseCSSPrefix + 'grid-scroll-body',
    scrollbarClipperCls: Ext.baseCSSPrefix + 'grid-scrollbar-clipper',
    scrollbarCls: Ext.baseCSSPrefix + 'grid-scrollbar',
    scrollbarVisibleCls: Ext.baseCSSPrefix + 'grid-scrollbar-visible',

    /**
     * @cfg {String} lockText
     * The text to display on the column menu to lock a column.
     * @locale
     */
    lockText: 'Lock',

    /**
     * @cfg {String} unlockText
     * The text to display on the column menu to unlock a column.
     * @locale
     */
    unlockText: 'Unlock',

    // Required for the Lockable Mixin. These are the configurations which will be copied to the
    // normal and locked sub tablepanels
    bothCfgCopy: [
        'hideHeaders',
        'enableColumnHide',
        'enableColumnMove',
        'enableColumnResize',
        'sortableColumns',
        'multiColumnSort',
        'columnLines',
        'rowLines',
        'variableRowHeight',
        'numFromEdge',
        'trailingBufferZone',
        'leadingBufferZone',
        'scrollToLoadBuffer',
        'syncRowHeight'
    ],

    normalCfgCopy: [
        'scroll'
    ],

    lockedCfgCopy: [],

    /**
     * @event processcolumns
     * Fires when the configured (or **reconfigured**) column set is split into two
     * depending on the {@link Ext.grid.column.Column#locked locked} flag.
     * @param {Ext.grid.column.Column[]} lockedColumns The locked columns.
     * @param {Ext.grid.column.Column[]} normalColumns The normal columns.
     */

    /**
     * @event lockcolumn
     * Fires when a column is locked.
     * @param {Ext.grid.Panel} this The gridpanel.
     * @param {Ext.grid.column.Column} column The column being locked.
     */

    /**
     * @event unlockcolumn
     * Fires when a column is unlocked.
     * @param {Ext.grid.Panel} this The gridpanel.
     * @param {Ext.grid.column.Column} column The column being unlocked.
     */

    determineXTypeToCreate: function(lockedSide) {
        var me = this;

        if (me.subGridXType) {
            return me.subGridXType;
        }
        else if (!lockedSide) {
            // Tree columns only moves down into the locked side.
            // The normal side is always just a grid
            return 'gridpanel';
        }

        return me.isXType('treepanel') ? 'treepanel' : 'gridpanel';
    },

    // injectLockable will be invoked before initComponent's parent class implementation
    // is called, so throughout this method this. are configurations
    injectLockable: function() {
        // The child grids are focusable, not this one
        this.focusable = false;

        // ensure lockable is set to true in the TablePanel
        this.lockable = true;

        // Instruct the TablePanel it already has a view and not to create one.
        // We are going to aggregate 2 copies of whatever TablePanel we are using
        this.hasView = true;

        // eslint-disable-next-line vars-on-top
        var me = this,
            store = me.store = Ext.StoreManager.lookup(me.store),
            lockedViewConfig = me.lockedViewConfig,
            normalViewConfig = me.normalViewConfig,
            viewConfig = me.viewConfig,

            // When setting the loadMask value, the viewConfig wins if it is defined.
            loadMaskCfg = viewConfig && viewConfig.loadMask,
            loadMask = (loadMaskCfg !== undefined) ? loadMaskCfg : me.loadMask,
            bufferedRenderer = me.bufferedRenderer,
            Obj = Ext.Object,

            // Hash of {lockedFeatures:[],normalFeatures:[]}
            allFeatures,

            // Hash of {topPlugins:[],lockedPlugins:[],normalPlugins:[]}
            allPlugins,
            lockedGrid, normalGrid, columns, lockedHeaderCt, normalHeaderCt, setWidth, i;

        allFeatures = me.constructLockableFeatures();

        // Must be available early. The BufferedRenderer needs access to it to add
        // scroll listeners.
        me.scrollable = new Ext.scroll.LockingScroller({
            component: me,
            x: false,
            y: true
        });

        // This is just a "shell" Panel which acts as a Container for the two grids
        // and must not use the features
        me.features = null;

        // Distribute plugins to whichever Component needs them
        allPlugins = me.constructLockablePlugins();
        me.plugins = allPlugins.topPlugins;

        lockedGrid = {
            id: me.id + '-locked',
            $initParent: me,
            isLocked: true,
            bufferedRenderer: bufferedRenderer,
            ownerGrid: me,
            ownerLockable: me,
            xtype: me.determineXTypeToCreate(true),
            store: store,
            scrollerOwner: false,
            // Lockable does NOT support animations for Tree
            // Because the right side is just a grid, and the grid view doen't animate
            // bulk insertions/removals
            animate: false,
            border: false,
            cls: me.lockedGridCls,

            // Usually a layout in one side necessitates the laying out of the other side
            // even if each is fully managed in both dimensions, and is therefore a layout root.
            // The only situation that we do *not* want layouts to escape into the owning lockable
            // assembly is when using a border layout and any of the border regions is floated
            // from a collapsed state.
            isLayoutRoot: function() {
                return this.floatedFromCollapse || this.ownerGrid.normalGrid.floatedFromCollapse;
            },

            features: allFeatures.lockedFeatures,
            plugins: allPlugins.lockedPlugins
        };

        normalGrid = {
            id: me.id + '-normal',
            $initParent: me,
            isLocked: false,
            bufferedRenderer: bufferedRenderer,
            ownerGrid: me,
            ownerLockable: me,
            xtype: me.determineXTypeToCreate(),
            store: store,
            // Pass down our reserveScrollbar to the normal side:
            reserveScrollbar: me.reserveScrollbar,
            scrollerOwner: false,
            border: false,
            cls: me.normalGridCls,

            // As described above, isolate layouts when floated out from a collapsed border region.
            isLayoutRoot: function() {
                return this.floatedFromCollapse || this.ownerGrid.lockedGrid.floatedFromCollapse;
            },

            features: allFeatures.normalFeatures,
            plugins: allPlugins.normalPlugins
        };

        me.addCls(Ext.baseCSSPrefix + 'grid-locked');

        // Copy appropriate configurations to the respective aggregated tablepanel instances.
        // Pass 4th param true to NOT exclude those settings on our prototype.
        // Delete them from the master tablepanel.
        Ext.copy(normalGrid, me, me.bothCfgCopy, true);
        Ext.copy(lockedGrid, me, me.bothCfgCopy, true);
        Ext.copy(normalGrid, me, me.normalCfgCopy, true);
        Ext.copy(lockedGrid, me, me.lockedCfgCopy, true);

        Ext.apply(normalGrid, me.normalGridConfig);
        Ext.apply(lockedGrid, me.lockedGridConfig);

        for (i = 0; i < me.normalCfgCopy.length; i++) {
            delete me[me.normalCfgCopy[i]];
        }

        for (i = 0; i < me.lockedCfgCopy.length; i++) {
            delete me[me.lockedCfgCopy[i]];
        }

        me.addStateEvents(['lockcolumn', 'unlockcolumn']);

        columns = me.processColumns(me.columns || [], lockedGrid);

        lockedGrid.columns = columns.locked;

        // If no locked columns, hide the locked grid
        if (!lockedGrid.columns.items.length) {
            lockedGrid.hidden = true;
        }

        normalGrid.columns = columns.normal;

        if (!normalGrid.columns.items.length) {
            normalGrid.hidden = true;
        }

        // normal grid should flex the rest of the width
        normalGrid.flex = 1;

        // Chain view configs to avoid mutating user's config
        lockedGrid.viewConfig = lockedViewConfig =
            (lockedViewConfig ? Obj.chain(lockedViewConfig) : {});

        normalGrid.viewConfig = normalViewConfig =
            (normalViewConfig ? Obj.chain(normalViewConfig) : {});

        lockedViewConfig.loadingUseMsg = false;
        lockedViewConfig.loadMask = false;
        normalViewConfig.loadMask = false;

        //<debug>
        if (viewConfig && viewConfig.id) {
            Ext.log.warn('id specified on Lockable viewConfig, it will be shared ' +
                         'between both views: "' + viewConfig.id + '"');
        }
        //</debug>

        Ext.applyIf(lockedViewConfig, viewConfig);
        Ext.applyIf(normalViewConfig, viewConfig);

        // Allow developer to configure the layout.
        // Instantiate the layout so its type can be ascertained.
        if (me.layout === Ext.panel.Table.prototype.layout) {
            me.layout = {
                type: 'hbox',
                align: 'stretch'
            };
        }

        me.getLayout();

        // Sanity check the split config.
        // Only allowed to insert a splitter between the two grids if it's a box layout
        if (me.layout.type === 'border') {
            if (me.split) {
                lockedGrid.split = me.split;
            }

            if (!lockedGrid.region) {
                lockedGrid.region = 'west';
            }

            if (!normalGrid.region) {
                normalGrid.region = 'center';
            }

            me.addCls(Ext.baseCSSPrefix + 'grid-locked-split');
        }

        if (!(me.layout instanceof Ext.layout.container.Box)) {
            me.split = false;
        }

        // The LockingView is a pseudo view which owns the two grids.
        // It listens for store events and relays the calls into each view bracketed
        // by a layout suspension.
        me.view = new Ext.grid.locking.View({
            loadMask: loadMask,
            locked: lockedGrid,
            normal: normalGrid,
            ownerGrid: me
        });

        me.view.relayEvents(me.scrollable, ['scroll']);

        // after creating the locking view we now have Grid instances for both locked and
        // unlocked sides
        lockedGrid = me.lockedGrid;
        normalGrid = me.normalGrid;

        // View has to be moved back into the panel during float
        lockedGrid.on({
            beginfloat: me.onBeginLockedFloat,
            endfloat: me.onEndLockedFloat,
            scope: me
        });

        setWidth = lockedGrid.setWidth;

        // Intercept setWidth here so we can tell the difference between
        // our own calls to setWidth vs user calls
        lockedGrid.setWidth = function() {
            lockedGrid.shrinkWrapColumns = false;
            setWidth.apply(lockedGrid, arguments);
        };

        // Account for initially hidden columns, or user hide of columns in handlers
        // called during grid construction
        if (!lockedGrid.getVisibleColumnManager().getColumns().length) {
            lockedGrid.hide();
        }

        if (!normalGrid.getVisibleColumnManager().getColumns().length) {
            normalGrid.hide();
        }

        // Extract the instantiated views from the locking View.
        // The locking View injects lockingGrid and normalGrid into this lockable panel.
        // This is because during constraction, it must be possible for descendant components
        // to navigate up to the owning lockable panel and then down into either side.

        lockedHeaderCt = lockedGrid.headerCt;
        normalHeaderCt = normalGrid.headerCt;

        // The top grid, and the LockingView both need to have a headerCt which is usable.
        // It is part of their private API that framework code uses when dealing with a grid
        // or grid view
        me.headerCt = me.view.headerCt = new Ext.grid.locking.HeaderContainer(me);

        lockedHeaderCt.lockedCt = true;
        lockedHeaderCt.lockableInjected = true;
        normalHeaderCt.lockableInjected = true;

        lockedHeaderCt.on({
            add: me.delaySyncLockedWidth,
            remove: me.delaySyncLockedWidth,
            columnshow: me.delaySyncLockedWidth,
            columnhide: me.delaySyncLockedWidth,
            sortchange: me.onLockedHeaderSortChange,
            columnresize: me.delaySyncLockedWidth,
            scope: me
        });

        normalHeaderCt.on({
            add: me.delaySyncLockedWidth,
            remove: me.delaySyncLockedWidth,
            columnshow: me.delaySyncLockedWidth,
            columnhide: me.delaySyncLockedWidth,
            sortchange: me.onNormalHeaderSortChange,
            scope: me
        });

        me.modifyHeaderCt();
        me.items = [lockedGrid];

        if (me.split) {
            me.addCls(Ext.baseCSSPrefix + 'grid-locked-split');

            me.items[1] = Ext.apply({
                xtype: 'splitter'
            }, me.split);
        }

        me.items.push(normalGrid);

        me.relayHeaderCtEvents(lockedHeaderCt);
        me.relayHeaderCtEvents(normalHeaderCt);

        // The top level Lockable container does not get bound to the store, so we need
        // to programatically add the relayer so that The filterchange state event is fired.
        //
        // TreePanel also relays the beforeload and load events, so 
        me.storeRelayers = me.relayEvents(store, [
            /**
             * @event filterchange
             * @inheritdoc Ext.data.Store#filterchange
             */
            'filterchange',

            /**
             * @event groupchange
             * @inheritdoc Ext.data.Store#groupchange
             */
            'groupchange',

            /**
             * @event beforeload
             * @inheritdoc Ext.data.Store#beforeload
             */
            'beforeload',

            /**
             * @event load
             * @inheritdoc Ext.data.Store#load
             */
            'load'
        ]);

        // Only need to relay from the normalGrid. Since it's created after the lockedGrid,
        // we can be confident to only listen to it.
        me.gridRelayers = me.relayEvents(normalGrid, [
            /**
             * @event viewready
             * @inheritdoc Ext.panel.Table#viewready
             */
            'viewready'
        ]);
    },

    afterInjectLockable: function() {
        var me = this;

        // Here we should set the maskElement to scrollContainer so the loadMask cover both views
        // but not the headers and grid title bar.
        me.maskElement = 'scrollContainer';

        if (me.disableOnRender) {
            me.on('afterrender', function() {
                me.unmask();
            }, { single: true });
        }

        delete me.lockedGrid.$initParent;
        delete me.normalGrid.$initParent;
    },

    syncLockableHeaderVisibility: function() {
        var me = this,
            hideHeaders = me.hideHeaders,
            locked = me.lockedGrid,
            normal = me.normalGrid;

        if (hideHeaders === null) {
            hideHeaders = locked.shouldAutoHideHeaders() && normal.shouldAutoHideHeaders();
        }

        locked.hideHeaders = normal.hideHeaders = hideHeaders;
        locked.syncHeaderVisibility();
        normal.syncHeaderVisibility();
    },

    getLockingViewConfig: function() {
        return {
            xclass: 'Ext.grid.locking.View',
            locked: this.lockedGrid,
            normal: this.normalGrid,
            panel: this
        };
    },

    onBeginLockedFloat: function(locked) {
        var el = locked.getContentTarget().dom,
            lockedHeaderCt = this.lockedGrid.headerCt,
            normalHeaderCt = this.normalGrid.headerCt,
            headerCtHeight = Math.max(normalHeaderCt.getHeight(), lockedHeaderCt.getHeight());

        // The two layouts are seperated and no longer share stretchmax height data upon
        // layout, so for the duration of float, force them to be at least the current
        // matching height.
        lockedHeaderCt.minHeight = headerCtHeight;
        normalHeaderCt.minHeight = headerCtHeight;

        locked.el.addCls(Ext.panel.Panel.floatCls);

        // Move view into the grid unless it's already there.
        // We fire a beginfloat event when expanding or collapsing from 
        // floated out state.
        if (el.firstChild !== locked.view.el.dom) {
            el.appendChild(locked.view.el.dom);
        }

        locked.body.dom.scrollTop = this.getScrollable().getPosition().y;
    },

    onEndLockedFloat: function() {
        var locked = this.lockedGrid;

        // The two headerCts are connected now, allow them to stretchmax each other
        if (locked.collapsed) {
            locked.el.removeCls(Ext.panel.Panel.floatCls);
        }
        else {
            this.lockedGrid.headerCt.minHeight = this.normalGrid.headerCt.minHeight = null;
        }

        this.lockedScrollbarClipper.appendChild(locked.view.el.dom);
        this.doSyncLockableLayout();
    },

    beforeLayout: function() {
        var me = this,
            lockedGrid = me.lockedGrid,
            normalGrid = me.normalGrid,
            totalColumnWidth;

        if (lockedGrid && normalGrid) {

            // The locked side of a grid, if it is shrinkwrapping fixed size columns,
            // must take into account the column widths plus the border widths of the grid element
            // and the headerCt element.
            // This must happen at this late stage so that all relevant classes are added
            // which affect what borders are applied to what elements.
            if (lockedGrid.getSizeModel().width.shrinkWrap) {
                lockedGrid.gridPanelBorderWidth = lockedGrid.el.getBorderWidth('lr');
                lockedGrid.shrinkWrapColumns = true;
            }

            if (lockedGrid.shrinkWrapColumns) {
                totalColumnWidth = lockedGrid.headerCt.getTableWidth();

                //<debug>
                if (isNaN(totalColumnWidth)) {
                    Ext.raise("Locked columns in an unsized locked side do NOT support " +
                              "a flex width.");
                }
                //</debug>

                lockedGrid.setWidth(totalColumnWidth + lockedGrid.gridPanelBorderWidth);

                // setWidth will clear shrinkWrapColumns, so force it again here
                lockedGrid.shrinkWrapColumns = true;
            }

            if (!me.scrollContainer) {
                me.initScrollContainer();
            }

            me.lastScrollPos = Ext.clone(me.getScrollable().getPosition());

            // Undo margin styles set by afterLayout
            lockedGrid.view.el.setStyle('margin-bottom', '');
            normalGrid.view.el.setStyle('margin-bottom', '');
        }
    },

    syncLockableLayout: function() {
        var me = this;

        // This is called directly from child TableView#afterComponentLayout
        // So we might get two calls if both are visible, and both lay out.
        // Schedule a single sync on the tail end of the current layout.
        if (!me.afterLayoutListener) {
            me.afterLayoutListener = Ext.on({
                afterlayout: me.doSyncLockableLayout,
                scope: me,
                single: true
            });
        }
    },

    doSyncLockableLayout: function() {
        var me = this,
            collapseExpand = me.isCollapsingOrExpanding,
            lockedGrid = me.lockedGrid,
            normalGrid = me.normalGrid,
            lockedViewEl, normalViewEl, lockedViewRegion,
            normalViewRegion, scrollbarSize, scrollbarWidth, scrollbarHeight, normalViewWidth,
            normalViewX, hasVerticalScrollbar, hasHorizontalScrollbar,
            scrollContainerHeight, scrollBodyHeight, lockedScrollbar, normalScrollbar,
            scrollbarVisibleCls, scrollHeight, lockedGridVisible, normalGridVisible, scrollBodyDom,
            viewRegion, scrollerElHeight, scrollable;

        me.afterLayoutListener = null;

        if (collapseExpand) {
            // Expand
            if (collapseExpand === 2) {
                me.on('expand', 'doSyncLockableLayout', me, { single: true });
            }

            return;
        }

        /* eslint-disable max-len */
        if (lockedGrid && normalGrid) {
            lockedGridVisible = lockedGrid.isVisible(true) && !lockedGrid.collapsed;
            normalGridVisible = normalGrid.isVisible(true);
            lockedViewEl = lockedGrid.view.el;
            normalViewEl = normalGrid.view.el;
            scrollBodyDom = me.scrollBody.dom;
            lockedViewRegion = lockedGridVisible ? lockedGrid.body.getRegion(true) : new Ext.util.Region(0, 0, 0, 0);
            normalViewRegion = normalGridVisible ? normalGrid.body.getRegion(true) : new Ext.util.Region(0, 0, 0, 0);
            scrollbarSize = Ext.scrollbar.size();
            scrollbarWidth = scrollbarSize.width;
            scrollbarHeight = scrollerElHeight = scrollbarSize.height;
            normalViewWidth = normalGridVisible ? normalViewRegion.width : 0;
            normalViewX = lockedGridVisible ? (normalGridVisible ? normalViewRegion.x - lockedViewRegion.x : lockedViewRegion.width) : 0;
            hasHorizontalScrollbar = (normalGrid.headerCt.tooNarrow || lockedGrid.headerCt.tooNarrow) ? scrollbarHeight : 0;
            scrollContainerHeight = normalViewRegion.height || lockedViewRegion.height;
            scrollBodyHeight = scrollContainerHeight;
            lockedScrollbar = me.lockedScrollbar;
            normalScrollbar = me.normalScrollbar;
            scrollbarVisibleCls = me.scrollbarVisibleCls;
            scrollable = me.getScrollable();

            // EXTJS-23301 IE10/11 does not allow an overflowing element to scroll
            // if the element height is the same as the scrollbar height. This
            // affects the horizontal normal scrollbar only as the vertical
            // scrollbar container will always have a width larger due to content.
            if (Ext.supports.CannotScrollExactHeight) {
                scrollerElHeight += 1;
            }

            if (hasHorizontalScrollbar) {
                lockedViewEl.setStyle('margin-bottom', -scrollbarHeight + 'px');
                normalViewEl.setStyle('margin-bottom', -scrollbarHeight + 'px');
                scrollBodyHeight -= scrollbarHeight;

                if (lockedGridVisible && lockedGrid.view.body.dom) {
                    me.lockedScrollbarScroller.setSize({ x: lockedGrid.headerCt.getTableWidth() });
                }

                if (normalGrid.view.body.dom) {
                    me.normalScrollbarScroller.setSize({ x: normalGrid.headerCt.getTableWidth() });
                }
            }

            me.scrollBody.setHeight(scrollBodyHeight);

            lockedViewEl.dom.style.height = normalViewEl.dom.style.height = '';
            scrollHeight = (me.scrollable.getSize().y + hasHorizontalScrollbar);
            normalGrid.view.stretchHeight(scrollHeight);
            lockedGrid.view.stretchHeight(scrollHeight);

            hasVerticalScrollbar = scrollbarWidth &&
                scrollBodyDom.scrollHeight > scrollBodyDom.clientHeight;

            if (hasVerticalScrollbar && normalViewWidth) {
                normalViewWidth -= scrollbarWidth;
                normalViewEl.setStyle('width', normalViewWidth + 'px');
            }

            lockedScrollbar.toggleCls(scrollbarVisibleCls, lockedGridVisible && !!hasHorizontalScrollbar);
            normalScrollbar.toggleCls(scrollbarVisibleCls, !!hasHorizontalScrollbar);

            // Floated from collapsed views must overlay. THis raises them up.
            me.normalScrollbarClipper.toggleCls(me.scrollbarClipperCls + '-floated', !!me.normalGrid.floatedFromCollapse);
            me.normalScrollbar.toggleCls(me.scrollbarCls + '-floated', !!me.normalGrid.floatedFromCollapse);
            me.lockedScrollbarClipper.toggleCls(me.scrollbarClipperCls + '-floated', !!me.lockedGrid.floatedFromCollapse);
            me.lockedScrollbar.toggleCls(me.scrollbarCls + '-floated', !!me.lockedGrid.floatedFromCollapse);

            lockedScrollbar.setSize(me.lockedScrollbarClipper.dom.offsetWidth, scrollerElHeight);
            normalScrollbar.setSize(normalViewWidth, scrollerElHeight);

            me.setNormalScrollerX(normalViewX);

            if (lockedGridVisible && normalGridVisible) {
                viewRegion = lockedViewRegion.union(normalViewRegion);
            }
            else if (lockedGridVisible) {
                viewRegion = lockedViewRegion;
            }
            else {
                viewRegion = normalViewRegion;
            }

            me.scrollContainer.setBox(viewRegion);

            me.onSyncLockableLayout(hasVerticalScrollbar, viewRegion.width);

            // We should only scroll if necessary 
            if (!Ext.Object.equals(scrollable.getPosition(), me.lastScrollPos)) {
                scrollable.scrollTo(me.lastScrollPos);
            }
        }
        /* eslint-enable max-len */
    },

    onSyncLockableLayout: Ext.emptyFn,

    setNormalScrollerX: function(x) {
        this.normalScrollbar.setLocalX(x);
        this.normalScrollbarClipper.setLocalX(x);
    },

    getScrollExtraCls: function() {
        return '';
    },

    initScrollContainer: function() {
        var me = this,
            extraCls = me.getScrollExtraCls(),
            scrollContainer = me.scrollContainer = me.body.insertFirst({
                cls: [me.scrollContainerCls, extraCls]
            }),
            scrollBody = me.scrollBody = scrollContainer.appendChild({
                cls: me.scrollBodyCls
            }),
            lockedScrollbar = me.lockedScrollbar = scrollContainer.appendChild({
                cls: [me.scrollbarCls, me.scrollbarCls + '-locked', extraCls]
            }),
            normalScrollbar = me.normalScrollbar = scrollContainer.appendChild({
                cls: [me.scrollbarCls, extraCls]
            }),
            lockedView = me.lockedGrid.view,
            normalView = me.normalGrid.view,
            lockedScroller = lockedView.getScrollable(),
            normalScroller = normalView.getScrollable(),
            Scroller = Ext.scroll.Scroller,
            lockedScrollbarScroller, normalScrollbarScroller, lockedScrollbarClipper,
            normalScrollbarClipper;

        lockedView.stretchHeight(0);
        normalView.stretchHeight(0);

        me.scrollable.setConfig({
            element: scrollBody,
            lockedScroller: lockedScroller,
            normalScroller: normalScroller
        });

        lockedScrollbarClipper = me.lockedScrollbarClipper = scrollBody.appendChild({
            cls: [me.scrollbarClipperCls, me.scrollbarClipperCls + '-locked', extraCls]
        });

        normalScrollbarClipper = me.normalScrollbarClipper = scrollBody.appendChild({
            cls: [me.scrollbarClipperCls, extraCls]
        });

        lockedScrollbarClipper.appendChild(lockedView.el);
        normalScrollbarClipper.appendChild(normalView.el);

        // We just moved the view elements into a containing element that is not the same
        // as their container's target element (grid body).  Setting the ignoreDomPosition
        // flag instructs the layout system not to move them back.
        lockedView.ignoreDomPosition = true;
        normalView.ignoreDomPosition = true;

        lockedScrollbarScroller = me.lockedScrollbarScroller = new Scroller({
            element: lockedScrollbar,
            x: 'scroll',
            y: false,
            rtl: lockedScroller.getRtl && lockedScroller.getRtl()
        });

        normalScrollbarScroller = me.normalScrollbarScroller = new Scroller({
            element: normalScrollbar,
            x: 'scroll',
            y: false,
            rtl: normalScroller.getRtl && normalScroller.getRtl()
        });

        me.initScrollers();

        lockedScrollbarScroller.addPartner(lockedScroller, 'x');
        normalScrollbarScroller.addPartner(normalScroller, 'x');

        // Tell the lockable.View that it has been rendered.
        me.view.onPanelRender(scrollBody);
    },

    initScrollers: Ext.emptyFn,

    processColumns: function(columns, lockedGrid) {
        // split apart normal and locked
        var me = this,
            i,
            len,
            column,
            cp = new Ext.grid.header.Container({
                "$initParent": me
            }),
            lockedHeaders = [],
            normalHeaders = [],
            lockedHeaderCt = {
                itemId: 'lockedHeaderCt',
                stretchMaxPartner: '^^>>#normalHeaderCt',
                items: lockedHeaders
            },
            normalHeaderCt = {
                itemId: 'normalHeaderCt',
                stretchMaxPartner: '^^>>#lockedHeaderCt',
                items: normalHeaders
            },
            result = {
                locked: lockedHeaderCt,
                normal: normalHeaderCt
            },
            copy;

        // In case they specified a config object with items...
        if (Ext.isObject(columns)) {
            Ext.applyIf(lockedHeaderCt, columns);
            Ext.applyIf(normalHeaderCt, columns);
            copy = Ext.apply({}, columns);
            delete copy.items;
            Ext.apply(cp, copy);
            columns = columns.items;
        }

        // Treat the column header as though we're just creating an instance, since this
        // doesn't follow the normal column creation pattern
        cp.constructing = true;

        for (i = 0, len = columns.length; i < len; ++i) {
            column = columns[i];

            // Use the HeaderContainer object to correctly configure and create the column.
            // MUST instantiate now because the locked or autoLock config which we read here
            // might be in the prototype.
            // MUST use a Container instance so that defaults from an object columns config
            // get applied.
            if (!column.isComponent) {
                column = cp.applyDefaults(column);
                column.$initParent = cp;
                column = cp.lookupComponent(column);
                delete column.$initParent;
            }

            // mark the column as processed so that the locked attribute does not
            // trigger the locked subgrid to try to become a split lockable grid itself.
            column.processed = true;

            if (column.locked || column.autoLock) {
                lockedHeaders.push(column);
            }
            else {
                normalHeaders.push(column);
            }
        }

        me.fireEvent('processcolumns', me, lockedHeaders, normalHeaders);
        cp.destroy();

        return result;
    },

    ensureLockedVisible: function(record, options) {
        var column = options && options.column,
            lockedGrid = this.lockedGrid,
            // eslint-disable-next-line max-len
            grid = column ? column.getView().ownerCt : lockedGrid.isVisible() ? lockedGrid : this.normalGrid;

        // Just ask the appropriate grid to scroll. There is only one Y scroller.
        grid.ensureVisible.apply(grid, arguments);
    },

    /**
     * Synchronizes the row heights between the locked and non locked portion of the grid for each
     * row. If one row is smaller than the other, the height will be increased to match
     * the larger one.
     */
    syncRowHeights: function() {
        // This is now called on animationFrame. It may have been destroyed in the interval.
        if (!this.destroyed) {
            // eslint-disable-next-line vars-on-top
            var me = this,
                normalView = me.normalGrid.getView(),
                lockedView = me.lockedGrid.getView(),
                // These will reset any forced height styles from the last sync
                normalSync = normalView.syncRowHeightBegin(),
                lockedSync = lockedView.syncRowHeightBegin(),
                scrollTop;

            // Now bulk measure everything
            normalView.syncRowHeightMeasure(normalSync);
            lockedView.syncRowHeightMeasure(lockedSync);

            // Now write out all the explicit heights we need to sync up
            normalView.syncRowHeightFinish(normalSync, lockedSync);
            lockedView.syncRowHeightFinish(lockedSync, normalSync);

            // Synchronize the scrollTop positions of the two views
            scrollTop = normalView.getScrollY();
            lockedView.setScrollY(scrollTop);

            me.syncRowHeightOnNextLayout = false;
        }
    },

    // inject Lock and Unlock text
    // Hide/show Lock/Unlock options
    modifyHeaderCt: function() {
        var me = this;

        me.lockedGrid.headerCt.getMenuItems =
            me.getMenuItems(me.lockedGrid.headerCt.getMenuItems, true);

        me.normalGrid.headerCt.getMenuItems =
            me.getMenuItems(me.normalGrid.headerCt.getMenuItems, false);

        me.lockedGrid.headerCt.showMenuBy =
            Ext.Function.createInterceptor(me.lockedGrid.headerCt.showMenuBy, me.showMenuBy);

        me.normalGrid.headerCt.showMenuBy =
            Ext.Function.createInterceptor(me.normalGrid.headerCt.showMenuBy, me.showMenuBy);
    },

    onUnlockMenuClick: function() {
        this.unlock();
    },

    onLockMenuClick: function() {
        this.lock();
    },

    showMenuBy: function(clickEvent, t, header) {
        var menu = this.getMenu(),
            unlockItem = menu.down('#unlockItem'),
            lockItem = menu.down('#lockItem'),
            sep = unlockItem.prev();

        if (header.lockable === false) {
            sep.hide();
            unlockItem.hide();
            lockItem.hide();
        }
        else {
            sep.show();
            unlockItem.show();
            lockItem.show();

            if (!unlockItem.initialConfig.disabled) {
                unlockItem.setDisabled(header.lockable === false);
            }

            if (!lockItem.initialConfig.disabled) {
                lockItem.setDisabled(!header.isLockable());
            }
        }
    },

    getMenuItems: function(getMenuItems, locked) {
        var me = this,
            unlockText = me.unlockText,
            lockText = me.lockText,
            unlockCls = Ext.baseCSSPrefix + 'hmenu-unlock',
            lockCls = Ext.baseCSSPrefix + 'hmenu-lock',
            unlockHandler = me.onUnlockMenuClick.bind(me),
            lockHandler = me.onLockMenuClick.bind(me);

        // runs in the scope of headerCt
        return function() {
            // We cannot use the method from HeaderContainer's prototype here
            // because other plugins or features may already have injected an implementation
            var o = getMenuItems.call(this);

            o.push('-', {
                itemId: 'unlockItem',
                iconCls: unlockCls,
                text: unlockText,
                handler: unlockHandler,
                disabled: !locked
            });

            o.push({
                itemId: 'lockItem',
                iconCls: lockCls,
                text: lockText,
                handler: lockHandler,
                disabled: locked
            });

            return o;
        };
    },

    //<debug>
    syncTaskDelay: 1,
    //</debug>

    delaySyncLockedWidth: function() {
        var me = this,
            task = me.syncLockedWidthTask ||
                   (me.syncLockedWidthTask = new Ext.util.DelayedTask(me.syncLockedWidth, me));

        if (me.reconfiguring) {
            return;
        }

        // Do not delay if we are in suspension or configured to not delay
        if (!Ext.Component.layoutSuspendCount || me.syncTaskDelay === 0) {
            me.syncLockedWidth();
        }
        else {
            task.delay(1);
        }
    },

    /**
     * @private
     * Updates the overall view after columns have been resized, or moved from
     * the locked to unlocked side or vice-versa.
     *
     * If all columns are removed from either side, that side must be hidden, and the
     * sole remaining column owning grid then becomes *the* grid. It must flex to occupy the
     * whole of the locking view. And it must also allow scrolling.
     *
     * If columns are shared between the two sides, the *locked* grid shrinkwraps the
     * width of the visible locked columns while the normal grid flexes in what space remains.
     *
     * @return {Object} A pair of flags indicating which views need to be cleared then refreshed.
     * this contains two properties, `locked` and `normal` which are `true` if the view needs
     * to be cleared and refreshed.
     */
    syncLockedWidth: function() {
        var me = this,
            rendered = me.rendered,
            locked = me.lockedGrid,
            normal = me.normalGrid,
            lockedColCount = locked.getVisibleColumnManager().getColumns().length,
            normalColCount = normal.getVisibleColumnManager().getColumns().length,
            task = me.syncLockedWidthTask;

        // If we are called directly, veto any existing task.
        if (task) {
            task.cancel();
        }

        if (me.reconfiguring) {
            return;
        }

        Ext.suspendLayouts();

        // If there are still visible normal columns, then the normal grid will flex
        // while we effectively shrinkwrap the width of the locked columns
        if (normalColCount) {
            normal.show();

            if (lockedColCount) {
                // Revert locked grid to original region now it's not the only child grid.
                if (me.layout.type === 'border') {
                    locked.region = locked.initialConfig.region;
                }

                // The locked grid shrinkwraps the total column width while the normal grid
                // flexes in what remains UNLESS it has been set to forceFit
                if (rendered && locked.shrinkWrapColumns && !locked.headerCt.forceFit) {
                    delete locked.flex;
                    // Just set the property here and update the layout.
                    // Size settings assume it's NOT the layout root.
                    // If the locked has been floated, it might well be!
                    // Use gridPanelBorderWidth as measured in Ext.grid.ColumnLayout#beginLayout
                    // TODO: Use shrinkWrapDock on the locked grid when it works.
                    locked.width = locked.headerCt.getTableWidth() + locked.gridPanelBorderWidth;
                    locked.updateLayout();
                }

                locked.addCls(me.lockedGridCls);
                locked.show();

                if (locked.split) {
                    me.child('splitter').show();
                    me.addCls(Ext.baseCSSPrefix + 'grid-locked-split');
                }
            }
            else {
                // Hide before clearing to avoid DOM layout from clearing
                // the content and to avoid scroll syncing. TablePanel
                // disables scroll syncing on hide.
                locked.hide();

                // No visible locked columns: hide the locked grid
                // We also need to trigger a clearViewEl to clear out any
                // old dom nodes
                if (rendered) {
                    locked.getView().clearViewEl(true);
                }

                if (locked.split) {
                    me.child('splitter').hide();
                    me.removeCls(Ext.baseCSSPrefix + 'grid-locked-split');
                }
            }
        }
        // There are no normal grid columns. The "locked" grid has to be *the*
        // grid, and cannot have a shrinkwrapped width, but must flex the entire width.
        else {
            normal.hide();

            // The locked now becomes *the* grid and has to flex to occupy the full view width
            delete locked.width;

            if (me.layout.type === 'border') {
                locked.region = 'center';
                normal.region = 'west';
            }
            else {
                locked.flex = 1;
            }

            locked.removeCls(me.lockedGridCls);
            locked.show();
        }

        Ext.resumeLayouts(true);

        // Flag object indicating which views need to be cleared and refreshed.
        return {
            locked: !!lockedColCount,
            normal: !!normalColCount
        };
    },

    onLockedHeaderSortChange: Ext.emptyFn,

    onNormalHeaderSortChange: Ext.emptyFn,

    // going from unlocked section to locked
    /**
     * Locks the activeHeader as determined by which menu is open OR a header
     * as specified.
     * @param {Ext.grid.column.Column} [activeHd] Header to unlock from the locked section.
     * Defaults to the header which has the menu open currently.
     * @param {Number} [toIdx] The index to move the unlocked header to.
     * Defaults to appending as the last item.
     * @param toCt
     * @private
     */
    lock: function(activeHd, toIdx, toCt) {
        var me = this,
            normalGrid = me.normalGrid,
            lockedGrid = me.lockedGrid,
            normalView = normalGrid.view,
            lockedView = lockedGrid.view,
            normalScroller = normalView.getScrollable(),
            lockedScroller = lockedView.getScrollable(),
            normalHCt = normalGrid.headerCt,
            refreshFlags, ownerCt, lbr;

        activeHd = activeHd || normalHCt.getMenu().activeHeader;
        activeHd.unlockedWidth = activeHd.width;

        // If moving a flexed header back into a side where we can't know
        // whether the flex value will be invalid, revert it either to
        // its original width or actual width.
        if (activeHd.flex) {
            if (activeHd.lockedWidth) {
                activeHd.width = activeHd.lockedWidth;
                activeHd.lockedWidth = null;
            }
            else {
                activeHd.width = activeHd.lastBox.width;
            }

            activeHd.flex = null;
        }

        toCt = toCt || lockedGrid.headerCt;
        ownerCt = activeHd.ownerCt;

        // isLockable will test for making the locked side too wide.
        // The header we're locking may be to be added, and have no ownerCt.
        // For instance, a checkbox column being moved into the correct side
        if (ownerCt && !activeHd.isLockable()) {
            return;
        }

        Ext.suspendLayouts();

        if (normalScroller) {
            normalScroller.suspendPartnerSync();
            lockedScroller.suspendPartnerSync();
        }

        // If hidden, we need to show it now or the locked headerCt's VisibleColumnManager
        // may be out of sync as headers are only added to a visible manager if they are not
        // explicity hidden or hierarchically hidden.
        if (lockedGrid.hidden) {
            // The locked side's BufferedRenderer has never has a resize passed in,
            // so its viewSize will be the default viewSize, out of sync with the normal side.
            // Synchronize the viewSize before the two sides are refreshed.
            if (!lockedGrid.componentLayoutCounter) {
                lockedGrid.height = normalGrid.lastBox.height;
                lbr = lockedView.bufferedRenderer;

                if (lbr) {
                    lbr.rowHeight = normalView.bufferedRenderer.rowHeight;
                    lbr.onViewResize(lockedView, 0, normalGrid.body.lastBox.height);
                }
            }

            lockedGrid.show();
        }

        // TablePanel#onHeadersChanged does not respond if reconfiguring set.
        // We programatically refresh views which need it below.
        lockedGrid.reconfiguring = normalGrid.reconfiguring = true;

        // Keep the column in the hierarchy during the move.
        // So that grid.isAncestor(column) still returns true, and SpreadsheetModel
        // does not deselect
        activeHd.ownerCmp = activeHd.ownerCt;

        activeHd.locked = true;

        // Flag to the locked column add listener to do nothing
        if (Ext.isDefined(toIdx)) {
            toCt.insert(toIdx, activeHd);
        }
        else {
            toCt.add(activeHd);
        }

        lockedGrid.reconfiguring = normalGrid.reconfiguring = false;

        activeHd.ownerCmp = null;
        activeHd.rootHeaderCt = null;

        activeHd.view = lockedView;

        refreshFlags = me.syncLockedWidth();

        // Clear both views first so that any widgets are cached
        // before reuse. If we refresh the grid which just had a widget column added
        // first, the clear of the view which had the widget column in removes the widgets
        // from their new place.
        if (refreshFlags.locked) {
            lockedView.clearViewEl(true);
        }

        if (refreshFlags.normal) {
            normalView.clearViewEl(true);
        }

        // Refresh locked view second, so that if it's refreshing from empty (can start
        // with no locked columns), the buffered renderer can look to its partner
        // to get the correct range to refresh.
        normalGrid.getView().refreshNeeded = refreshFlags.normal;
        lockedGrid.getView().refreshNeeded = refreshFlags.locked;

        activeHd.onLock(activeHd);
        me.fireEvent('lockcolumn', me, activeHd);

        Ext.resumeLayouts(true);

        if (normalScroller) {
            normalScroller.resumePartnerSync(true);
            lockedScroller.resumePartnerSync();
        }
    },

    // going from locked section to unlocked
    /**
     * Unlocks the activeHeader as determined by which menu is open OR a header
     * as specified.
     * @param {Ext.grid.column.Column} [activeHd] Header to unlock from the locked section.
     * Defaults to the header which has the menu open currently.
     * @param {Number} [toIdx=0] The index to move the unlocked header to.
     * @param toCt
     * @private
     */
    unlock: function(activeHd, toIdx, toCt) {
        var me = this,
            normalGrid = me.normalGrid,
            lockedGrid = me.lockedGrid,
            normalView = normalGrid.view,
            lockedView = lockedGrid.view,
            startIndex = normalView.all.startIndex,
            lockedHCt = lockedGrid.headerCt,
            refreshFlags;

        // Unlocking; user expectation is that the unlocked column is inserted at the beginning.
        if (!Ext.isDefined(toIdx)) {
            toIdx = 0;
        }

        activeHd = activeHd || lockedHCt.getMenu().activeHeader;
        activeHd.lockedWidth = activeHd.width;

        // If moving a flexed header back into a side where we can't know
        // whether the flex value will be invalid, revert it either to
        // its original width or actual width.
        if (activeHd.flex) {
            if (activeHd.unlockedWidth) {
                activeHd.width = activeHd.unlockedWidth;
                activeHd.unlockedWidth = null;
            }
            else {
                activeHd.width = activeHd.lastBox.width;
            }

            activeHd.flex = null;
        }

        toCt = toCt || normalGrid.headerCt;

        Ext.suspendLayouts();

        // TablePanel#onHeadersChanged does not respond if reconfiguring set.
        // We programatically refresh views which need it below.
        lockedGrid.reconfiguring = normalGrid.reconfiguring = true;

        // Keep the column in the hierarchy during the move.
        // So that grid.isAncestor(column) still returns true, and SpreadsheetModel
        // does not deselect
        activeHd.ownerCmp = activeHd.ownerCt;

        if (activeHd.ownerCt) {
            activeHd.ownerCt.remove(activeHd, false);
        }

        activeHd.locked = false;
        toCt.insert(toIdx, activeHd);

        lockedGrid.reconfiguring = normalGrid.reconfiguring = false;

        activeHd.ownerCmp = null;
        activeHd.rootHeaderCt = null;

        activeHd.view = normalView;

        // syncLockedWidth returns visible column counts for both grids.
        // only refresh what needs refreshing
        refreshFlags = me.syncLockedWidth();

        // Clear both views first so that any widgets are cached
        // before reuse. If we refresh the grid which just had a widget column added
        // first, the clear of the view which had the widget column in removes the widgets
        // from their new place.
        if (refreshFlags.locked) {
            lockedView.clearViewEl(true);
        }

        if (refreshFlags.normal) {
            normalView.clearViewEl(true);
        }

        // Refresh locked view second, so that if it's refreshing from empty (can start
        // with no locked columns), the buffered renderer can look to its partner to get
        // the correct range to refresh.
        if (refreshFlags.normal) {
            normalGrid.getView().refreshView(startIndex);
        }

        if (refreshFlags.locked) {
            lockedGrid.getView().refreshView(startIndex);
        }

        activeHd.onUnlock(activeHd);
        me.fireEvent('unlockcolumn', me, activeHd);
        Ext.resumeLayouts(true);
    },

    /**
     * @private
     */
    reconfigureLockable: function(store, columns, allowUnbind) {
        // we want to totally override the reconfigure behaviour here,
        // since we're creating 2 sub-grids
        var me = this,
            oldStore = me.store,
            lockedGrid = me.lockedGrid,
            normalGrid = me.normalGrid,
            view, loadMask;

        if (!store && allowUnbind) {
            store = Ext.StoreManager.lookup('ext-empty-store');
        }

        // Note that we need to process the store first in case one or more passed columns
        // (if there are any) have active gridfilters with values which would filter
        // the currently-bound store.
        if (store && store !== oldStore) {
            store = Ext.data.StoreManager.lookup(store);
            me.store = store;

            lockedGrid.view.blockRefresh = normalGrid.view.blockRefresh = true;

            lockedGrid.bindStore(store);

            // Subsidiary views have their bindStore changed because they must not
            // bind listeners themselves. This view listens and relays calls to each view.
            // BUT the dataSource and store properties must be set
            view = lockedGrid.view;
            view.store = store;

            // If the dataSource being used by the View is *not* a FeatureStore
            // (a modified view of the base Store injected by a Feature)
            // Then we promote the store to be the dataSource.
            // If it was a FeatureStore, then it must not be changed. A FeatureStore is mutated
            // by the Feature to respond to changes in the underlying Store.
            if (!view.dataSource.isFeatureStore) {
                view.dataSource = store;
            }

            if (view.bufferedRenderer) {
                view.bufferedRenderer.bindStore(store);
            }

            normalGrid.bindStore(store);
            view = normalGrid.view;
            view.store = store;

            // If the dataSource being used by the View is *not* a FeatureStore
            // (a modified view of the base Store injected by a Feature)
            // Then we promote the store to be the dataSource.
            // If it was a FeatureStore, then it must not be changed. A FeatureStore is mutated
            // by the Feature to respond to changes in the underlying Store.
            if (!view.dataSource.isFeatureStore) {
                view.dataSource = store;
            }

            if (view.bufferedRenderer) {
                view.bufferedRenderer.bindStore(store);
            }

            me.view.store = store;

            // binding mask to new store
            loadMask = me.view.loadMask;

            if (loadMask && loadMask.isLoadMask) {
                loadMask.bindStore(store);
            }

            me.view.bindStore(normalGrid.view.dataSource, false, 'dataSource');
            lockedGrid.view.blockRefresh = normalGrid.view.blockRefresh = false;
        }

        if (columns) {
            // Both grids must not react to the headers being changed
            // (See panel/Table#onHeadersChanged)
            lockedGrid.reconfiguring = normalGrid.reconfiguring = true;
            lockedGrid.headerCt.removeAll();
            normalGrid.headerCt.removeAll();

            columns = me.processColumns(columns, lockedGrid);

            // Flag to the locked column add listener to do nothing
            lockedGrid.headerCt.add(columns.locked.items);
            normalGrid.headerCt.add(columns.normal.items);

            lockedGrid.reconfiguring = normalGrid.reconfiguring = false;

            // Ensure locked grid is set up correctly with correct width and bottom border,
            // and that both grids' visibility and scrollability status is correct
            me.syncLockedWidth();
        }

        me.refreshCounter = normalGrid.view.refreshCounter;
    },

    afterReconfigureLockable: function() {
        // Ensure width are set up, and visibility of sides are synced with whether
        // they have columns or not.
        this.syncLockedWidth();

        // If the counter hasn't changed since where we saved it previously, we haven't refreshed,
        // so kick it off now.
        if (this.refreshCounter === this.normalGrid.getView().refreshCounter) {
            this.view.refreshView();
        }
    },

    constructLockableFeatures: function() {
        var features = this.features,
            feature, featureClone, lockedFeatures, normalFeatures, i, len;

        if (features) {
            if (!Ext.isArray(features)) {
                features = [ features ];
            }

            lockedFeatures = [];
            normalFeatures = [];

            for (i = 0, len = features.length; i < len; i++) {
                feature = features[i];

                if (!feature.isFeature) {
                    feature = Ext.create('feature.' + feature.ftype, feature);
                }

                switch (feature.lockableScope) {
                    case 'locked':
                        lockedFeatures.push(feature);
                        break;

                    case 'normal':
                        normalFeatures.push(feature);
                        break;

                    default:
                        feature.lockableScope = 'both';
                        lockedFeatures.push(feature);
                        normalFeatures.push(featureClone = feature.clone());

                        // When cloned to either side, each gets a "lockingPartner"
                        // reference to the other
                        featureClone.lockingPartner = feature;
                        feature.lockingPartner = featureClone;
                }
            }
        }

        return {
            normalFeatures: normalFeatures,
            lockedFeatures: lockedFeatures
        };
    },

    constructLockablePlugins: function() {
        var plugins = this.plugins,
            plugin, normalPlugin, lockedPlugin, topPlugins, lockedPlugins, normalPlugins,
            lockableScope, pluginCls, i, len;

        if (plugins) {
            if (!Ext.isArray(plugins)) {
                plugins = [ plugins ];
            }

            topPlugins = [];
            lockedPlugins = [];
            normalPlugins = [];

            for (i = 0, len = plugins.length; i < len; i++) {
                plugin = plugins[i];

                // Plugin will most likely already have been instantiated by the Component
                // constructor
                if (plugin.init) {
                    lockableScope = plugin.lockableScope;
                }
                // If not, it's because of late addition through a subclass's initComponent
                // implementation, so we must ascertain the lockableScope directly from the class.
                else {
                    pluginCls = plugin.ptype
                        ? Ext.ClassManager.getByAlias(('plugin.' + plugin.ptype))
                        : Ext.ClassManager.get(plugin.xclass);

                    lockableScope = pluginCls.prototype.lockableScope;
                }

                switch (lockableScope) {
                    case 'both':
                        lockedPlugins.push(lockedPlugin = plugin.clonePlugin());
                        normalPlugins.push(normalPlugin = plugin.clonePlugin());

                        // When cloned to both sides, each gets a "lockingPartner"
                        // reference to the other
                        lockedPlugin.lockingPartner = normalPlugin;
                        normalPlugin.lockingPartner = lockedPlugin;

                        // If the plugin has to be propagated down to both, a new plugin config
                        // object must be given to that side and this plugin must be destroyed.
                        Ext.destroy(plugin);

                        break;

                    case 'locked':
                        lockedPlugins.push(plugin);
                        break;

                    case 'normal':
                        normalPlugins.push(plugin);
                        break;

                    default:
                        topPlugins.push(plugin);
                }
            }
        }

        return {
            topPlugins: topPlugins,
            normalPlugins: normalPlugins,
            lockedPlugins: lockedPlugins
        };
    },

    destroyLockable: function() {
        // The locking view isn't a "real" view, so we need to destroy it manually
        var me = this,
            task = me.syncLockedWidthTask;

        if (task) {
            task.cancel();
            me.syncLockedWidthTask = null;
        }

        // Release interceptors created in modifyHeaderCt
        if (me.lockedGrid && me.lockedGrid.headerCt) {
            me.lockedGrid.headerCt.showMenuBy = null;
        }

        if (me.normalGrid && me.normalGrid.headerCt) {
            me.normalGrid.headerCt.showMenuBy = null;
        }

        Ext.destroy(
            me.normalScrollbarClipper,
            me.lockedScrollbarClipper,
            me.normalScrollbar,
            me.lockedScrollbar,
            me.scrollBody,
            me.scrollContainer,
            me.normalScrollbarScroller,
            me.lockedScrollbarScroller,
            me.view,
            me.headerCt
        );
    }
}, function() {
    this.borrow(Ext.Component, ['constructPlugin']);
});
