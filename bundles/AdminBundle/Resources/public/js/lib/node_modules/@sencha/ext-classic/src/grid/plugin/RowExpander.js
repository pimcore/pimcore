/**
 * Plugin (ptype = 'rowexpander') that adds the ability to have a Column in a grid which enables
 * a second row body which expands/contracts.  The expand/contract behavior is configurable to react
 * on clicking of the column, double click of the row, and/or hitting enter while a row is selected.
 *
 * **Note:** The {@link Ext.grid.plugin.RowExpander rowexpander} plugin and the rowbody
 * feature are exclusive and cannot both be set on the same grid / tree.
 */
Ext.define('Ext.grid.plugin.RowExpander', {
    extend: 'Ext.plugin.Abstract',
    lockableScope: 'top',

    requires: [
        'Ext.grid.feature.RowBody'
    ],

    alias: 'plugin.rowexpander',

    /**
     * @cfg {Number} [columnWidth=24]
     * The width of the row expander column which contains the [+]/[-] icons to toggle
     * row expansion.
     */
    columnWidth: 24,

    /**
     * @cfg {Ext.XTemplate} rowBodyTpl
     * An XTemplate which, when passed a record data object, produces HTML for the expanded
     * row content.
     *
     * Note that if this plugin is applied to a lockable grid, the rowBodyTpl applies to the normal
     * (unlocked) side. See {@link #lockedTpl}
     *
     */
    rowBodyTpl: null,

    /**
     * @cfg {Ext.XTemplate} [lockedTpl]
     * An XTemplate which, when passed a record data object, produces HTML for the expanded
     * row content *on the locked side of a lockable grid*.
     */
    lockedTpl: null,

    /**
     * @cfg {Boolean} expandOnEnter
     * This config is no longer supported. The Enter key initiated the grid's actinoable mode.
     */

    /**
     * @cfg {Boolean} expandOnDblClick
     * `true` to toggle a row between expanded/collapsed when double clicked
     * (defaults to `true`).
     */
    expandOnDblClick: true,

    /**
     * @cfg {Boolean} selectRowOnExpand
     * `true` to select a row when clicking on the expander icon
     * (defaults to `false`).
     */
    selectRowOnExpand: false,

    /**
     * @cfg {Boolean} scrollIntoViewOnExpand
     * @since 6.2.0
     * `true` to ensure that the full row expander body is visible when clicking on the expander
     * icon (defaults to `true`)
     */
    scrollIntoViewOnExpand: true,

    /**
     * @cfg {Number}
     * The width of the Row Expander column header
     */
    headerWidth: 24,

    /**
     * @cfg {Boolean} [bodyBefore=false]
     * Configure as `true` to put the row expander body *before* the data row.
     * 
     */
    bodyBefore: false,

    rowBodyTrSelector: '.' + Ext.baseCSSPrefix + 'grid-rowbody-tr',
    rowBodyHiddenCls: Ext.baseCSSPrefix + 'grid-row-body-hidden',
    rowCollapsedCls: Ext.baseCSSPrefix + 'grid-row-collapsed',

    addCollapsedCls: {
        fn: function(out, values, parent) {
            var me = this.rowExpander;

            if (!me.recordsExpanded[values.record.internalId]) {
                values.itemClasses.push(me.rowCollapsedCls);
            }

            this.nextTpl.applyOut(values, out, parent);
        },

        syncRowHeights: function(lockedItem, normalItem) {
            this.rowExpander.syncRowHeights(lockedItem, normalItem);
        },

        // We need a high priority to get in ahead of the outerRowTpl
        // so we can setup row data
        priority: 20000
    },

    /**
     * @event expandbody
     * **Fired through the grid's View**
     * @param {HTMLElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HTMLElement} expandRow The &lt;tr> element containing the expanded data.
     */
    /**
     * @event collapsebody
     * **Fired through the grid's View.**
     * @param {HTMLElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HTMLElement} expandRow The &lt;tr> element containing the expanded data.
     */

    setCmp: function(grid) {
        var me = this,
            features;

        me.callParent([grid]);

        // Keep track of which record internalIds are expanded.
        me.recordsExpanded = {};

        //<debug>
        if (!me.rowBodyTpl) {
            Ext.raise("The 'rowBodyTpl' config is required and is not defined.");
        }
        //</debug>

        me.rowBodyTpl = Ext.XTemplate.getTpl(me, 'rowBodyTpl');
        features = me.getFeatureConfig(grid);

        if (grid.features) {
            grid.features = Ext.Array.push(features, grid.features);
        }
        else {
            grid.features = features;
        }
        // NOTE: features have to be added before init (before Table.initComponent)
    },

    /**
     * @protected
     * @return {Array} And array of Features or Feature config objects.
     * Returns the array of Feature configurations needed to make the RowExpander work.
     * May be overridden in a subclass to modify the returned array.
     */
    getFeatureConfig: function(grid) {
        var me = this,
            features = [],
            featuresCfg = {
                ftype: 'rowbody',
                rowExpander: me,
                rowIdCls: me.rowIdCls,
                bodyBefore: me.bodyBefore,
                recordsExpanded: me.recordsExpanded,
                rowBodyHiddenCls: me.rowBodyHiddenCls,
                rowCollapsedCls: me.rowCollapsedCls,
                setupRowData: me.getRowBodyFeatureData,
                setup: me.setup
            };

        features.push(Ext.apply({
            lockableScope: 'normal',
            getRowBodyContents: me.getRowBodyContentsFn(me.rowBodyTpl)
        }, featuresCfg));

        // Locked side will need a copy to keep the two DOM structures symmetrical.
        // A lockedTpl config is available to create content in locked side.
        // The enableLocking flag is set early in Ext.panel.Table#initComponent if any columns
        // are locked.
        if (grid.enableLocking) {
            features.push(Ext.apply({
                lockableScope: 'locked',
                getRowBodyContents: me.lockedTpl
                    ? me.getRowBodyContentsFn(me.lockedTpl)
                    : function() {
                        return '';
                    }
            }, featuresCfg));
        }

        return features;
    },

    getRowBodyContentsFn: function(rowBodyTpl) {
        var me = this;

        return function(rowValues) {
            rowBodyTpl.owner = me;

            return rowBodyTpl.applyTemplate(rowValues.record.getData());
        };
    },

    init: function(grid) {
        var me = this,
            // Plugin attaches to topmost grid if lockable
            ownerLockable = grid.lockable && grid,
            view, lockedView, normalView;

        if (ownerLockable) {
            me.lockedGrid = ownerLockable.lockedGrid;
            me.normalGrid = ownerLockable.normalGrid;
            lockedView = me.lockedView = me.lockedGrid.getView();
            normalView = me.normalView = me.normalGrid.getView();
        }

        me.callParent([grid]);
        me.grid = grid;
        view = me.view = grid.getView();

        // If the owning grid is lockable, ensure the collapsed class is applied to the locked side
        // by adding a row processor to both views.
        if (ownerLockable) {
            me.bindView(lockedView);
            me.bindView(normalView);
            me.addExpander(me.lockedGrid.headerCt.items.getCount() ? me.lockedGrid : me.normalGrid);

            // Add row processor which adds collapsed class.
            // Ensure tpl and view can access this plugin via a "rowExpander" property.
            lockedView.addRowTpl(me.addCollapsedCls).rowExpander =
                normalView.addRowTpl(me.addCollapsedCls).rowExpander =
                lockedView.rowExpander =
                normalView.rowExpander = me;

            // If our client grid part of a lockable grid, we listen to its ownerLockable's
            // processcolumns
            ownerLockable.mon(ownerLockable, {
                processcolumns: me.onLockableProcessColumns,
                lockcolumn: me.onColumnLock,
                unlockcolumn: me.onColumnUnlock,
                scope: me
            });
        }
        // Add row processor which adds collapsed class
        else {
            me.bindView(view);
            // Ensure tpl and view can access this plugin
            view.addRowTpl(me.addCollapsedCls).rowExpander =
                view.rowExpander = me;
            me.addExpander(grid);
            grid.on('beforereconfigure', me.beforeReconfigure, me);
        }
    },

    onItemAdd: function(newRecords, startIndex, newItems) {
        var me = this,
            ownerLockable = me.grid.lockable,
            len = newItems.length,
            record,
            i;

        // If any added items are expanded, we will need a syncRowHeights call on next layout
        for (i = 0; i < len; i++) {
            record = newRecords[i];

            if (!record.isNonData && me.recordsExpanded[record.internalId]) {
                if (ownerLockable) {
                    me.grid.syncRowHeightOnNextLayout = true;
                }

                return;
            }
        }
    },

    beforeReconfigure: function(grid, store, columns, oldStore, oldColumns) {
        var me = this;

        if (columns) {
            me.expanderColumn = new Ext.grid.column.Column(me.getHeaderConfig());
            columns.unshift(me.expanderColumn);
        }

    },

    onLockableProcessColumns: function(lockable, lockedHeaders, normalHeaders) {
        this.addExpander(lockedHeaders.length ? lockable.lockedGrid : lockable.normalGrid);
    },

    /**
     * @private
     * Inject the expander column into the correct grid.
     *
     * If we are expanding the normal side of a lockable grid, poke the column
     * into the locked side if the locked side has columns
     */
    addExpander: function(expanderGrid) {
        var me = this,
            selModel = expanderGrid.getSelectionModel(),
            checkBoxPosition = selModel.injectCheckbox;

        me.expanderColumn = expanderGrid.headerCt.insert(0, me.getHeaderConfig());

        // If a CheckboxModel, and it's position is 0, it must now go at position one because this
        // cell always gets in at position zero, and spans 2 columns.
        if (checkBoxPosition === 0 || checkBoxPosition === 'first') {
            checkBoxPosition = 1;
        }

        selModel.injectCheckbox = checkBoxPosition;
    },

    getRowBodyFeatureData: function(record, idx, rowValues) {
        var me = this;

        me.self.prototype.setupRowData.apply(me, arguments);

        rowValues.rowBody = me.getRowBodyContents(rowValues);
        rowValues.rowBodyCls = me.recordsExpanded[record.internalId] ? '' : me.rowBodyHiddenCls;
    },

    bindView: function(view) {
        var me = this,
            listeners = {
                itemkeydown: me.onKeyDown,
                scope: me
            };

        if (me.expandOnDblClick) {
            listeners.itemdblclick = me.onDblClick;
        }

        if (me.grid.lockable) {
            listeners.itemadd = me.onItemAdd;
        }

        view.on(listeners);
    },

    onKeyDown: function(view, record, row, rowIdx, e) {
        var me = this,
            key = e.getKey(),
            pos = view.getNavigationModel().getPosition(),
            isCollapsed;

        if (pos) {
            row = Ext.fly(row);
            isCollapsed = row.hasCls(me.rowCollapsedCls);

            // + key on collapsed or - key on expanded
            if (((key === 107 || (key === 187 && e.shiftKey)) && isCollapsed) ||
                ((key === 109 || key === 189) && !isCollapsed)) {
                me.toggleRow(rowIdx, record);
            }
        }
    },

    onDblClick: function(view, record, row, rowIdx, e) {
        this.toggleRow(rowIdx, record);
    },

    toggleRow: function(rowIdx, record) {
        var me = this,
            // If we are handling a lockable assembly,
            // handle the normal view first
            view = me.normalView || me.view,
            fireView = view,
            rowNode = view.getNode(rowIdx),
            normalRow = Ext.fly(rowNode),
            lockedRow,
            nextBd = normalRow.down(me.rowBodyTrSelector, true),
            wasCollapsed = normalRow.hasCls(me.rowCollapsedCls),
            addOrRemoveCls = wasCollapsed ? 'removeCls' : 'addCls',
            ownerLockable = me.grid.lockable && me.grid;

        normalRow[addOrRemoveCls](me.rowCollapsedCls);
        Ext.fly(nextBd)[addOrRemoveCls](me.rowBodyHiddenCls);
        me.recordsExpanded[record.internalId] = wasCollapsed;

        Ext.suspendLayouts();

        // Sync the collapsed/hidden classes on the locked side
        if (ownerLockable) {
            // It's the top level grid's LockingView that does the firing
            // when there's a lockable assembly involved.
            fireView = ownerLockable.getView();

            // Only attempt to toggle lockable side if it is visible.
            if (me.lockedGrid.isVisible()) {

                view = me.lockedView;

                // The other side must be thrown into the layout matrix so that
                // row height syncing can be done. If it is collapsed but floated,
                // it will not automatically be added to the layout when the top 
                // level grid layout calculates its layout children.
                view.lockingPartner.updateLayout();

                // Process the locked side.
                lockedRow = Ext.fly(view.getNode(rowIdx));

                // Just because the grid is locked, doesn't mean we'll necessarily have
                // a locked row.
                if (lockedRow) {
                    lockedRow[addOrRemoveCls](me.rowCollapsedCls);

                    // If there is a template for expander content in the locked side,
                    // toggle that side too
                    nextBd = lockedRow.down(me.rowBodyTrSelector, true);
                    Ext.fly(nextBd)[addOrRemoveCls](me.rowBodyHiddenCls);
                }
            }

            // We're going to need a layout run to synchronize row heights
            ownerLockable.syncRowHeightOnNextLayout = true;
        }

        fireView.fireEvent(wasCollapsed ? 'expandbody' : 'collapsebody', rowNode, record, nextBd);
        view.refreshSize(true);

        Ext.resumeLayouts(true);

        if (me.scrollIntoViewOnExpand && wasCollapsed) {
            me.grid.ensureVisible(rowIdx);
        }
    },

    // Called from TableLayout.finishedLayout
    syncRowHeights: function(lockedItem, normalItem) {
        var me = this,
            lockedBd = Ext.fly(lockedItem).down(me.rowBodyTrSelector),
            normalBd = Ext.fly(normalItem).down(me.rowBodyTrSelector),
            lockedHeight,
            normalHeight;

        // If expanded, we have to ensure expander row heights are synched
        if (normalBd.isVisible()) {

            // If heights are different, expand the smallest one
            if ((lockedHeight = lockedBd.getHeight()) !== (normalHeight = normalBd.getHeight())) {
                if (lockedHeight > normalHeight) {
                    normalBd.setHeight(lockedHeight);
                }
                else {
                    lockedBd.setHeight(normalHeight);
                }
            }
        }
        // When not expanded we do not control the heights
        else {
            lockedBd.dom.style.height = normalBd.dom.style.height = '';
        }
    },

    onColumnUnlock: function(lockable, column) {
        var me = this,
            lockedColumns;

        lockable = lockable || me.grid;
        lockedColumns = lockable.lockedGrid.visibleColumnManager.getColumns();

        // User has unlocked all columns and left only the expander column in the locked side.
        if (lockedColumns.length === 1) {
            lockable.normalGrid.removeCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');
            lockable.lockedGrid.addCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');

            if (lockedColumns[0] === me.expanderColumn) {
                lockable.unlock(me.expanderColumn);
            }
            else {
                lockable.lock(me.expanderColumn, 0);
            }
        }
    },

    onColumnLock: function(lockable, column) {
        var me = this,
            lockedColumns;

        lockable = lockable || me.grid;
        lockedColumns = me.lockedGrid.visibleColumnManager.getColumns();

        // This is the first column to move into the locked side.
        // The expander column must follow it.
        if (lockedColumns.length === 1) {
            me.lockedGrid.headerCt.insert(0, me.expanderColumn);
            lockable.normalGrid.addCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');
            lockable.lockedGrid.removeCls(Ext.baseCSSPrefix + 'grid-hide-row-expander-spacer');
        }
    },

    getHeaderConfig: function() {
        var me = this,
            lockable = me.grid.lockable && me.grid;

        return {
            width: me.headerWidth,
            ignoreExport: true,
            lockable: false,
            autoLock: true,
            sortable: false,
            resizable: false,
            draggable: false,
            hideable: false,
            menuDisabled: true,
            tdCls: Ext.baseCSSPrefix + 'grid-cell-special',
            innerCls: Ext.baseCSSPrefix + 'grid-cell-inner-row-expander',
            renderer: function() {
                return '<div class="' + Ext.baseCSSPrefix +
                       'grid-row-expander" role="presentation" tabIndex="0"></div>';
            },
            processEvent: function(type, view, cell, rowIndex, cellIndex, e, record) {
                var isTouch = e.pointerType === 'touch',
                    isExpanderClick = !!e.getTarget('.' + Ext.baseCSSPrefix + 'grid-row-expander');

                if ((type === "click" && isExpanderClick) ||
                    (type === 'keydown' && e.getKey() === e.SPACE)) {

                    // Focus the cell on real touch tap.
                    // This is because the toggleRow saves and restores focus
                    // which may be elsewhere than clicked on causing a scroll jump.
                    if (isTouch) {
                        cell.focus();
                    }

                    me.toggleRow(rowIndex, record, e);
                    e.stopSelection = !me.selectRowOnExpand;
                }
                else if (e.type === 'mousedown' && !isTouch && isExpanderClick) {
                    e.preventDefault();
                }
            },

            // This column always migrates to the locked side if the locked side is visible.
            // It has to report this correctly so that editors can position things correctly
            isLocked: function() {
                return lockable && (lockable.lockedGrid.isVisible() || this.locked);
            },

            // In an editor, this shows nothing.
            editRenderer: function() {
                return '&#160;';
            }
        };
    }
});
