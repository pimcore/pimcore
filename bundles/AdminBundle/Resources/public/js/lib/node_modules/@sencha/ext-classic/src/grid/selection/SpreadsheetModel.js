/**
 * A selection model for {@link Ext.grid.Panel grids} which allows you to select data in
 * a spreadsheet-like manner.
 *
 * Supported features:
 *
 *  - Single / Range / Multiple individual row selection.
 *  - Single / Range cell selection.
 *  - Column selection by click selecting column headers.
 *  - Select / deselect all by clicking in the top-left, header.
 *  - Adds row number column to enable row selection.
 *  - Optionally you can enable row selection using checkboxes
 *
 * # Example usage
 *
 *     @example
 *     var store = Ext.create('Ext.data.Store', {
 *         fields: ['name', 'email', 'phone'],
 *         data: [
 *             { name: 'Lisa', email: 'lisa@simpsons.com', phone: '555-111-1224' },
 *             { name: 'Bart', email: 'bart@simpsons.com', phone: '555-222-1234' },
 *             { name: 'Homer', email: 'homer@simpsons.com', phone: '555-222-1244' },
 *             { name: 'Marge', email: 'marge@simpsons.com', phone: '555-222-1254' }
 *         ]
 *     });
 *
 *     Ext.create('Ext.grid.Panel', {
 *         title: 'Simpsons',
 *         store: store,
 *         width: 400,
 *         renderTo: Ext.getBody(),
 *         columns: [
 *             { text: 'Name', dataIndex: 'name' },
 *             { text: 'Email', dataIndex: 'email', flex: 1 },
 *             { text: 'Phone', dataIndex: 'phone' }
 *         ],
 *         selModel: {
 *            type: 'spreadsheet'
 *         }
 *     });
 *
 * # Using {@link Ext.data.BufferedStore}s
 * It is very important to remember that a {@link Ext.data.BufferedStore} does *not* contain the
 * full dataset. The purpose of a BufferedStore is to only hold in the client, a range of
 * pages from the dataset that corresponds with what is currently visible in the grid
 * (plus a few pages above and below the visible range to allow fast scrolling).
 *
 * When using "select all" rows and a BufferedStore, an `allSelected` flag is set, and so all
 * records which are read into the client side cache will thenceforth be selected, and will
 * be rendered as selected in the grid.
 *
 * *But records which have not been read into the cache will obviously not be available
 * when interrogating selected records. As you scroll through the dataset, and more
 * pages are read from the server, they will become available to add to the selection.*
 *
 * @since 5.1.0
 */
Ext.define('Ext.grid.selection.SpreadsheetModel', {
    extend: 'Ext.selection.Model',
    requires: [
        'Ext.grid.selection.Selection',
        'Ext.grid.selection.Cells',
        'Ext.grid.selection.Rows',
        'Ext.grid.selection.Columns',
        'Ext.grid.selection.SelectionExtender' // TODO: cmd-auto-dependency
    ],

    alias: 'selection.spreadsheet',

    isSpreadsheetModel: true,

    config: {
        /**
         * @cfg {Boolean} [columnSelect=false]
         * Set to `true` to enable selection of columns.
         *
         * **NOTE**: This will remove sorting on header click and instead provide column
         * selection and deselection. Sorting is still available via column header menu.
         */
        columnSelect: {
            $value: false,
            lazy: true
        },

        /**
         * @cfg {Boolean} [cellSelect=true]
         * Set to `true` to enable selection of individual cells or a single rectangular
         * range of cells. This will provide cell range selection using click, and
         * potentially drag to select a rectangular range. You can also use "SHIFT + arrow"
         * key navigation to select a range of cells.
         */
        cellSelect: {
            $value: true,
            lazy: true
        },

        /**
         * @cfg {Boolean} [rowSelect=true]
         * Set to `true` to enable selection of rows by clicking on a row number column.
         *
         * *Note*: This feature will add the row number as the first column.
         */
        rowSelect: {
            $value: true,
            lazy: true
        },

        /**
        * @cfg {Boolean} [dragSelect=true]
        * Set to `true` to enables cell range selection by cell dragging.
        */
        dragSelect: {
            $value: true,
            lazy: true
        },

        /**
        * @cfg {Ext.grid.selection.Selection} [selected]
        * Pass an instance of one of the subclasses of {@link Ext.grid.selection.Selection}.
        */
        selected: null,

        /**
         * @cfg {String} extensible
         * This configures whether this selection model is to implement a mouse based dragging
         * gesture to extend a *contiguous* selection.
         *
         * Note that if there are multiple, discontiguous selected rows or columns, selection
         * extension is not available.
         *
         * If set, then the bottom right corner of the contiguous selection will display
         * a drag handle. By dragging this, an extension area may be defined into which
         * the selection is extended.
         *
         * Upon the end of the drag, the
         * {@link Ext.panel.Table#beforeselectionextend beforeselectionextend} event will be fired
         * though the encapsulating grid. Event handlers may manipulate the store data in any way.
         *
         * Possible values for this configuration are
         *
         *    - `"x"` Only allow extending the block to the left or right.
         *    - `"y"` Only allow extending the block above or below.
         *    - `"xy"` Allow extending the block in both dimensions.
         *    - `"both"` Allow extending the block in both dimensions.
         *    - `true` Allow extending the block in both dimensions.
         *    - `false` Disable the extensible feature
         *    - `null` Disable the extensible feature
         *
         * It's important to notice that setting this to `"both"`, `"xy"` or `true` will allow you
         * to extend the selection in both directions, but only one direction at a time.
         * It will NOT be possible to drag it diagonally. 
         */
        extensible: {
            $value: true,
            lazy: true
        },

        /**
         * @cfg {Boolean} reducible
         * @since 6.6.0
         * This configures if the extensible config is also allowed to reduce its selection
         *
         * Note: This is only relevant if `extensible` is not `false` or `null`
         */
        reducible: true
    },

    /**
     * @event selectionchange
     * Fired *by the grid* after the selection changes. Return `false` to veto the selection
     * extension.
     *
     * Note that the behavior of selectionchange is different in Ext 6.x vs. Ext 5.  In Ext 6.x,
     * if rows are being selected, a block of records is passed as the second parameter.
     * In Ext 5, the selection object was passed.  
     * 
     *
     * @param {Ext.grid.Panel} grid The grid whose selection has changed.
     * @param {Ext.grid.selection.Selection} selection A subclass of
     * {@link Ext.grid.selection.Selection} describing the new selection.
     */

    /**
     * @cfg {Boolean} checkboxSelect [checkboxSelect=false]
     * Enables selection of the row via clicking on checkbox. Note: this feature will add
     * new column at position specified by {@link #checkboxColumnIndex}.
     */
    checkboxSelect: false,

    /**
     * @cfg {Number/String} [checkboxColumnIndex=0]
     * The index at which to insert the checkbox column.
     * Supported values are a numeric index, and the strings 'first' and 'last'. Only valid when set
     * *before* render.
     */
    checkboxColumnIndex: 0,

    /**
     * @cfg {Boolean} [showHeaderCheckbox=true]
     * Configure as `false` to not display the header checkbox at the top of the checkbox column
     * when {@link #checkboxSelect} is set.
     */
    showHeaderCheckbox: true,

    /**
     * @cfg {String} [checkColumnHeaderText]
     * Displays the configured text in the check column's header.
     *
     * if {@link #cfg-showHeaderCheckbox} is `true`, the text is shown *above* the checkbox.
     * @since 6.0.1
     */
    checkColumnHeaderText: null,

    /**
     * @cfg {Number/String} [checkboxHeaderWidth=24]
     * Width of checkbox column.
     */
    checkboxHeaderWidth: 24,

    /**
     * @cfg {Number/String} [rowNumbererHeaderWidth=46]
     * Width of row numbering column.
     */
    rowNumbererHeaderWidth: 46,

    columnSelectCls: Ext.baseCSSPrefix + 'ssm-column-select',
    rowNumbererHeaderCls: Ext.baseCSSPrefix + 'ssm-row-numberer-hd',

    tdCls: Ext.baseCSSPrefix + 'grid-cell-special ' + Ext.baseCSSPrefix + 'selmodel-column',

    /**
     * @method getCount
     * This method is not supported by SpreadsheetModel.
     *
     * To interrogate the selection use {@link #cfg!selected}'s getter, which will return
     * an instance of one of the three selection types, or `null` if no selection.
     *
     * The three selection types are:
     *
     *    * {@link Ext.grid.selection.Rows}
     *    * {@link Ext.grid.selection.Columns}
     *    * {@link Ext.grid.selection.Cells}
     */

    /**
     * @method getSelectionMode
     * This method is not supported by SpreadsheetModel.
     */

    /**
     * @method setSelectionMode
     * This method is not supported by SpreadsheetModel.
     */

    /**
     * @method setLocked
     * This method is not currently supported by SpreadsheetModel.
     */

    /**
     * @method isLocked
     * This method is not currently supported by SpreadsheetModel.
     */

    /**
     * @method isRangeSelected
     * This method is not supported by SpreadsheetModel.
     *
     * To interrogate the selection use {@link #cfg!selected}'s getter, which will return
     * an instance of one of the three selection types, or `null` if no selection.
     *
     * The three selection types are:
     *
     *    * {@link Ext.grid.selection.Rows}
     *    * {@link Ext.grid.selection.Columns}
     *    * {@link Ext.grid.selection.Cells}
     */

    /**
     * @member Ext.panel.Table
     * @event beforeselectionextend An event fired when an extension block is extended 
     * using a drag gesture.  Only fired when the SpreadsheetSelectionModel is used and 
     * configured with the 
     * {@link Ext.grid.selection.SpreadsheetModel#extensible extensible} config.
     * @param {Ext.panel.Table} grid The owning grid.
     * @param {Ext.grid.selection.Selection} An object which encapsulates a contiguous
     * selection block.
     * @param {Object} extension An object describing the type and size of extension.
     * @param {String} extension.type `"rows"` or `"columns"`
     * @param {Ext.grid.CellContext} extension.start The start (top left) cell of the
     * extension area.
     * @param {Ext.grid.CellContext} extension.end The end (bottom right) cell of the
     * extension area.
     * @param {number} [extension.columns] The number of columns extended (-ve means
     * on the left side).
     * @param {number} [extension.rows] The number of rows extended (-ve means on the top side).
     */

    /**
     * @member Ext.panel.Table
     * @event selectionextenderdrag An event fired when an extension block is dragged to 
     * encompass a new range.  Only fired when the SpreadsheetSelectionModel is used and 
     * configured with the 
     * {@link Ext.grid.selection.SpreadsheetModel#extensible extensible} config.
     * @param {Ext.panel.Table} grid The owning grid.
     * @param {Ext.grid.selection.Selection} An object which encapsulates a contiguous
     * selection block.
     * @param {Object} extension An object describing the type and size of extension.
     * @param {String} extension.type `"rows"` or `"columns"`
     * @param {HTMLElement} extension.overCell The grid cell over which the mouse is being dragged.
     * @param {Ext.grid.CellContext} extension.start The start (top left) cell of the
     * extension area.
     * @param {Ext.grid.CellContext} extension.end The end (bottom right) cell of the
     * extension area.
     * @param {number} [extension.columns] The number of columns extended (-ve means
     * on the left side).
     * @param {number} [extension.rows] The number of rows extended (-ve means on the top side).
     */

    /**
     * @private
     */
    bindComponent: function(view) {
        var me = this,
            viewListeners,
            storeListeners,
            lockedGrid;

        if (me.view !== view) {
            if (me.view) {
                me.navigationModel = null;
                Ext.destroy(me.viewListeners, me.navigationListeners);
            }

            me.view = view;

            if (view) {
                // We need to realize our lazy configs now that we have the view...
                me.getCellSelect();

                lockedGrid = view.ownerGrid.lockedGrid;

                // If there is a locked grid, process it now
                if (lockedGrid) {
                    me.hasLockedHeader = true;
                    me.onViewCreated(lockedGrid, lockedGrid.getView());
                }
                // Otherwise, get back to us when the view is fully created
                // so that we can tweak its headerCt
                else {
                    view.grid.on({
                        viewcreated: me.onViewCreated,
                        scope: me,
                        single: true
                    });
                }

                me.gridListeners = view.ownerGrid.on({
                    columnschanged: me.onColumnsChanged,
                    columnmove: me.onColumnMove,
                    scope: me,
                    destroyable: true
                });

                storeListeners = me.getStoreListeners();
                storeListeners.scope = me;
                storeListeners.destroyable = true;
                me.storeListeners = me.store.on(storeListeners);
                viewListeners = me.getViewListeners();
                viewListeners.scope = me;
                viewListeners.destroyable = true;
                me.viewListeners = view.on(viewListeners);
                me.navigationModel = view.getNavigationModel();
                me.navigationListeners = me.navigationModel.on({
                    navigate: me.onNavigate,
                    scope: me,
                    destroyable: true
                });

                // Add class to add special cursor pointer to column headers
                if (me.getColumnSelect()) {
                    view.ownerGrid.addCls(me.columnSelectCls);
                }

                me.updateHeaderState();
            }
        }
    },

    /**
     * Retrieve a configuration to be used in a HeaderContainer.
     * This should be used when checkboxSelect is set to false.
     * @protected
     */
    getCheckboxHeaderConfig: function() {
        var me = this,
            showCheck = me.showHeaderCheckbox !== false;

        return {
            xtype: 'checkcolumn',
            // historically used as a discriminator property before isCheckColumn
            isCheckerHd: showCheck,
            headerCheckbox: showCheck,
            ignoreExport: true,
            text: me.checkColumnHeaderText,
            clickTargetName: 'el',
            width: me.checkboxHeaderWidth,
            sortable: false,
            draggable: false,
            resizable: false,
            hideable: false,
            menuDisabled: true,
            tdCls: me.tdCls,
            cls: Ext.baseCSSPrefix + 'selmodel-column',
            stopSelection: false,
            editRenderer: me.editRenderer || me.renderEmpty,
            locked: me.hasLockedHeader,
            updateHeaderState: me.updateHeaderState.bind(me),

            // It must not attempt to set anything in the records on toggle.
            // We handle that in onHeaderClick.
            toggleAll: Ext.emptyFn,

            // The selection model listens to the navigation model to select/deselect
            setRecordCheck: Ext.emptyFn,

            // It uses our isRowSelected to test whether a row is checked
            isRecordChecked: Ext.emptyFn
        };
    },

    renderEmpty: function() {
        return '\u00a0';
    },

    /**
     * @private
     */
    getStoreListeners: function() {
        var me = this,
            r = me.callParent();

        r.priority = 2000;
        r.refresh = me.onStoreChanged;
        r.clear = me.onStoreChanged;

        return r;
    },

    /**
     * @private
     */
    onHeaderClick: function(headerCt, header, e) {
    // Template method. See base class
        var me = this,
            sel = me.selected,
            isSelected = false;

        if (header === me.numbererColumn || header === me.checkColumn) {
            e.stopEvent();

            // Not all selected, select all
            if (!sel || !sel.isAllSelected()) {
                me.selectAll();
            }
            else {
                me.deselectAll();
            }

            me.updateHeaderState();
            me.lastColumnSelected = null;
        }
        else if (me.columnSelect) {
            if (e.shiftKey && sel && sel.lastColumnSelected) {
                sel.setRangeEnd(header);
                me.fireSelectionChange();
            }
            else {
                // keeping track of the column selection status before we go through the clear block
                isSelected = me.isColumnSelected(header);

                if (sel) {
                    if (!e.ctrlKey) {
                        sel.clear();
                        me.updateSelectionExtender();
                    }
                    else if (isSelected) {
                        me.deselectColumn(header);
                        me.selected.lastColumnSelected = null;
                    }
                }

                if (!isSelected || (!e.ctrlKey && e.pointerType !== 'touch')) {
                    me.selectColumn(header, e.ctrlKey);
                    sel = me.selected;
                    sel.lastColumnSelected = header;

                    if (!sel.startColumn) {
                        sel.startColumn = header;
                    }
                }
            }

            me.lastOverColumn = header;
        }
    },

    selectByPosition: function(position) {
        var me = this;

        position = new Ext.grid.CellContext(me.view).setPosition(position.row, position.column);

        if (me.getCellSelect()) {
            me.selectCells(position, position);
        }
        else if (me.getRowSelect()) {
            this.select(position.record);
        }
        else if (me.getColumnSelect()) {
            me.selectColumn(position.column);
        }
    },

    /**
     * @private
     */
    updateHeaderState: function() {
        // check to see if all records are selected
        var me = this,
            store = me.view.dataSource,
            views = me.views,
            sel = me.selected,
            isChecked = false,
            checkHd = me.checkColumn,
            storeCount;

        if (store && sel && sel.isRows) {
            storeCount = store.getCount();

            if (store.isBufferedStore) {
                isChecked = sel.allSelected;
            }
            else {
                isChecked = storeCount > 0 && (storeCount === sel.getCount());
            }
        }

        if (views && views.length) {
            if (checkHd) {
                checkHd.setHeaderStatus(isChecked);
            }
        }
    },

    onBindStore: function(store, oldStore, initial) {
        if (!initial) {
            this.onStoreRefresh();
        }
    },

    /**
     * Handles the grid's beforereconfigure event.
     * Adds the checkbox header if the columns have been reconfigured.
     * Also adds the row numberer.
     * @param {Ext.panel.Table} grid
     * @param {Ext.data.Store} store
     * @param {Object[]} columns
     * @param {Ext.data.Store} oldStore
     * @param {Object[]} oldColumns
     * @private
     */
    onBeforeReconfigure: function(grid, store, columns, oldStore, oldColumns) {
        var me = this,
            checkboxColumnIndex = me.checkboxColumnIndex;

        if (columns) {
            Ext.suspendLayouts();

            if (me.numbererColumn) {
                me.numbererColumn.ownerCt.remove(me.numbererColumn, false);
                columns.unshift(me.numbererColumn);
            }

            if (me.checkColumn) {
                if (checkboxColumnIndex === 'first') {
                    checkboxColumnIndex = 0;
                }
                else if (checkboxColumnIndex === 'last') {
                    checkboxColumnIndex = columns.length;
                }

                me.checkColumn.ownerCt.remove(me.checkColumn, false);
                Ext.Array.insert(columns, checkboxColumnIndex, [me.checkColumn]);
            }

            Ext.resumeLayouts();
        }
    },

    /**
     * This is a helper method to create a cell context which encapsulates one cell in a grid view.
     *
     * It will contain the following properties:
     *  colIdx - column index
     *  rowIdx - row index
     *  column - {@link Ext.grid.column.Column Column} under which the cell is located.
     *  record - {@link Ext.data.Model} Record from which the cell derives its data.
     *  view - The view. If this selection model is for a locking grid, this will be the 
     *  outermost view, the {@link Ext.grid.locking.View} which encapsulates the sub 
     *  grids. Column indices are relative to the outermost view's visible column set.
     *
     * @param {Number} record Record for which to select the cell, or row index.
     * @param {Number} column Grid column header, or column index.
     * @return {Ext.grid.CellContext} A context object describing the cell. Note that the
     * `rowidx` and `colIdx` properties are only valid
     * at the time the context object is created. Column movement, sorting or filtering
     * might changed where the cell is.
     * @private
     */
    getCellContext: function(record, column) {
        return new Ext.grid.CellContext(this.view.ownerGrid.getView()).setPosition(record, column);
    },

    select: function(records, keepExisting, suppressEvent) {
        // API docs are inherited
        var me = this,
            sel = me.selected,
            view = me.view,
            store = view.dataSource,
            len,
            i,
            record,
            changed = false;

        // Ensure selection object is of the correct type
        if (!sel || !sel.isRows) {
            me.resetSelection(true);
            sel = me.selected = new Ext.grid.selection.Rows(view);
        }
        else if (!keepExisting) {
            sel.clear();
        }

        if (!Ext.isArray(records)) {
            records = [records];
        }

        len = records.length;

        for (i = 0; i < len; i++) {
            record = records[i];

            if (typeof record === 'number') {
                record = store.getAt(record);
            }

            if (!sel.contains(record)) {
                sel.add(record);
                changed = true;
            }
        }

        if (changed) {
            me.updateHeaderState();

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    deselect: function(records, suppressEvent) {
        // API docs are inherited
        var me = this,
            sel = me.selected,
            store = me.view.dataSource,
            len,
            i,
            record,
            changed = false;

        if (sel && sel.isRows) {
            if (!Ext.isArray(records)) {
                records = [records];
            }

            len = records.length;

            for (i = 0; i < len; i++) {
                record = records[i];

                if (typeof record === 'number') {
                    record = store.getAt(record);
                }

                sel.remove(record);

                if (!changed) {
                    changed = true;
                }
            }
        }

        if (changed) {
            me.updateHeaderState();

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    /* eslint-disable max-len */
    /**
     * This method allows programmatic selection of the cell range.
     *
     *     @example
     *     var store = Ext.create('Ext.data.Store', {
     *         fields  : ['name', 'email', 'phone'],
     *         data    : {
     *             items : [
     *                 { name : 'Lisa',  email : 'lisa@simpsons.com',  phone : '555-111-1224' },
     *                 { name : 'Bart',  email : 'bart@simpsons.com',  phone : '555-222-1234' },
     *                 { name : 'Homer', email : 'homer@simpsons.com', phone : '555-222-1244' },
     *                 { name : 'Marge', email : 'marge@simpsons.com', phone : '555-222-1254' }
     *             ]
     *         },
     *         proxy   : {
     *             type   : 'memory',
     *             reader : {
     *                 type : 'json',
     *                 root : 'items'
     *             }
     *         }
     *     });
     *
     *     var grid = Ext.create('Ext.grid.Panel', {
     *         title    : 'Simpsons',
     *         store    : store,
     *         width    : 400,
     *         renderTo : Ext.getBody(),
     *         columns  : [
     *            columns: [
     *               { text: 'Name',  dataIndex: 'name' },
     *               { text: 'Email', dataIndex: 'email', flex: 1 },
     *               { text: 'Phone', dataIndex: 'phone', width:120 },
     *               {
     *                   text:'Combined', dataIndex: 'name', width : 300,
     *                   renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
     *                       console.log(arguments);
     *                       return value + ' has email: ' + record.get('email');
     *                   }
     *               }
     *           ],
     *         ],
     *         selType: 'spreadsheet'
     *     });
     *
     *     var model = grid.getSelectionModel();  // get selection model
     *
     *     // We will create range of 4 cells.
     *
     *     // Now set the range  and prevent rangeselect event from being fired.
     *     // We can use a simple array when we have no locked columns.
     *     model.selectCells([0, 0], [1, 1], true);
     *
     * @param rangeStart {Ext.grid.CellContext/Number[]} Range starting position. Can be either Cell
     * context or a `[rowIndex, columnIndex]` numeric array.
     *
     * Note that when a numeric array is used in a locking grid, the column indices are relative
     * to the outermost grid, encompassing locked *and* normal sides.
     * @param rangeEnd {Ext.grid.CellContext/Number[]} Range end position. Can be either
     * Cell context or a `[rowIndex, columnIndex]` numeric array.
     *
     * Note that when a numeric array is used in a locking grid, the column indices are relative
     * to the outermost grid, encompassing locked *and* normal sides.
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    selectCells: function(rangeStart, rangeEnd, suppressEvent) {
        var me = this,
            view = me.view.ownerGrid.view,
            sel;

        rangeStart = rangeStart.isCellContext
            ? rangeStart.clone()
            : new Ext.grid.CellContext(view).setPosition(rangeStart);

        rangeEnd = rangeEnd.isCellContext
            ? rangeEnd.clone()
            : new Ext.grid.CellContext(view).setPosition(rangeEnd);

        me.resetSelection(true);

        me.selected = sel = new Ext.grid.selection.Cells(rangeStart.view);
        sel.setRangeStart(rangeStart);
        sel.setRangeEnd(rangeEnd);

        if (!suppressEvent) {
            me.fireSelectionChange();
        }
    },
    /* eslint-enable max-len */

    /**
     * Select all the data if possible.
     *
     * If {@link #rowSelect} is `true`, then all *records* will be selected.
     *
     * If {@link #cellSelect} is `true`, then all *rendered cells* will be selected.
     *
     * If {@link #columnSelect} is `true`, then all *columns* will be selected.
     *
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    selectAll: function(suppressEvent) {
        var me = this,
            sel = me.selected,
            doSelect,
            view = me.view;

        if (me.rowSelect) {
            if (!sel || !sel.isRows) {
                me.resetSelection(true);
                me.selected = sel = new Ext.grid.selection.Rows(view);
            }

            doSelect = true;
        }
        else if (me.cellSelect) {
            if (!sel || !sel.isCells) {
                me.resetSelection(true);
                me.selected = sel = new Ext.grid.selection.Cells(view);
            }

            doSelect = true;
        }
        else if (me.columnSelect) {
            if (!sel || !sel.isColumns) {
                me.resetSelection(true);
                me.selected = sel = new Ext.grid.selection.Columns(view);
            }

            doSelect = true;
        }

        if (sel) {
            sel.allSelected = true;
        }

        if (doSelect) {
            me.updateHeaderState();
            sel.selectAll(); // this populates the selection with the records

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    /**
     * Clears the selection.
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    deselectAll: function(suppressEvent) {
        var me = this,
            sel = me.selected;

        if (sel && sel.getCount()) {
            sel.clear();
            sel.allSelected = false;
            me.updateHeaderState();

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    /**
     * Select one or more rows.
     * @param rows {Ext.data.Model[]} Records to select.
     * @param {Boolean} [keepSelection=false] Pass `true` to keep previous selection.
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    selectRows: function(rows, keepSelection, suppressEvent) {
        var me = this,
            sel = me.selected,
            isSelectingRows = sel && sel.isRows,
            len = rows.length,
            i;

        if (!keepSelection || !isSelectingRows) {
            me.resetSelection(true);
        }

        if (!isSelectingRows) {
            me.selected = sel = new Ext.grid.selection.Rows(me.view);
        }

        if (rows.isEntity) {
            sel.add(rows);
        }
        else {
            for (i = 0; i < len; i++) {
                sel.add(rows[i]);
            }
        }

        if (!suppressEvent) {
            me.fireSelectionChange();
        }
    },

    isSelected: function(record) {
        // API docs are inherited.
        return this.isRowSelected(record);
    },

    /**
     * Selects a column.
     * @param {Ext.grid.column.Column} column Column to select.
     * @param {Boolean} [keepSelection=false] Pass `true` to keep previous selection.
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    selectColumn: function(column, keepSelection, suppressEvent) {
        var me = this,
            selData = me.selected,
            view = column.getView();

        // Clear other selection types
        if (!selData || !selData.isColumns || selData.view !== view.ownerGrid.view) {
            me.resetSelection(true);
            me.selected = selData = new Ext.grid.selection.Columns(view);
        }

        if (!selData.contains(column)) {
            if (!keepSelection) {
                selData.clear();
            }

            selData.add(column);

            me.updateHeaderState();

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    /**
     * Deselects a column.
     * @param {Ext.grid.column.Column} column Column to deselect.
     * @param {Boolean} [suppressEvent] Pass `true` to prevent firing the
     * `{@link #selectionchange}` event.
     */
    deselectColumn: function(column, suppressEvent) {
        var me = this,
            selData = me.getSelected();

        if (selData && selData.isColumns && selData.contains(column)) {
            selData.remove(column);
            me.updateHeaderState();

            if (!suppressEvent) {
                me.fireSelectionChange();
            }
        }
    },

    getSelection: function() {
        // API docs are inherited.
        // Superclass returns array of selected records
        var selData = this.selected;

        if (selData && selData.isRows) {
            return selData.getRecords();
        }

        return [];
    },

    destroy: function() {
        var me = this,
            scrollEls = me.scrollEls;

        Ext.destroy(me.gridListeners, me.viewListeners, me.selected,
                    me.navigationListeners, me.extensible);

        if (scrollEls) {
            Ext.dd.ScrollManager.unregister(scrollEls);
        }

        if (me._onMouseUp && !me._onMouseUp.destroyed) {
            me.stopAutoScroller();
            me._onMouseUp.destroy();
        }

        me.selected = me.gridListeners = me.viewListeners = me.selectionData =
            me.navigationListeners = me.scrollEls = null;

        me.callParent();
    },

    //-------------------------------------------------------------------------

    privates: {
        /**
         * @property {Object} axesConfigs
         * Use when converting the extensible config into a SelectionExtender
         * to create its `axes` config to specify which axes it may extend.
         * @private
         */
        axesConfigs: {
            x: 1,
            y: 2,
            xy: 3,
            both: 3,
            "true": 3 // reserved word MUST be quoted when used an a property name
        },

        getNumbererColumnConfig: function() {
            var me = this;

            return {
                xtype: 'rownumberer',
                width: me.rowNumbererHeaderWidth,
                editRenderer: me.renderEmpty,
                tdCls: me.rowNumbererTdCls,
                cls: me.rowNumbererHeaderCls,
                locked: me.hasLockedHeader
            };
        },

        /**
         * @return {Object}
         * @private
         */
        getViewListeners: function() {
            return {
                refresh: this.onViewRefresh,
                keyup: {
                    element: 'el',
                    fn: this.onViewKeyUp,
                    scope: this
                }
            };
        },

        /**
         * @private
         */
        onViewKeyUp: function(e) {
            var sel = this.selected;

            // Released the shift key, terminate a keyboard based range selection
            if (e.keyCode === e.SHIFT && sel && sel.isRows && sel.getRangeSize()) {
                // Copy the drag range into the selected records collection
                sel.addRange();
            }
        },

        /**
         * @private
         */
        onStoreChanged: function() {
            var me = this,
                selData = me.selected;

            if (selData) {
                if (selData.isCells) {
                    me.resetSelection();
                }
                else if (selData.isRows) {
                    if (me.pruneRemoved === false && selData.selectedRecords.length) {
                        me.refresh();
                    }
                    else {
                        me.resetSelection();
                    }
                }
            }
        },

        /**
         * @private
         */
        onColumnsChanged: function() {
            var me = this,
                selectionChanged = me.onViewChanged(me.view, true);

            // This event is fired directly from the HeaderContainer before the view updates.
            // So we have to wait until idle to update the selection UI.
            // NB: fireSelectionChange calls updateSelectionExtender after firing its event.
            Ext.on('idle', selectionChanged ? me.fireSelectionChange : me.updateSelectionExtender,
                   me, { single: true });
        },

        // The selection may have acquired or lost contiguity, so the replicator may need
        // enabling or disabling
        onColumnMove: function() {
            this.updateSelectionExtender();
        },

        /**
         * @private
         */
        onViewRefresh: function(view) {
            var me = this,
                selectionChanged = me.onViewChanged(view);

            // The selection may have acquired or lost contiguity, so the replicator may need
            // enabling or disabling
            // NB: fireSelectionChange calls updateSelectionExtender after firing its event.
            me[selectionChanged ? 'fireSelectionChange' : 'updateSelectionExtender']();
        },

        /**
         * @private
         */
        resetSelection: function(suppressEvent) {
            var sel = this.selected;

            if (sel) {
                sel.clear();

                if (!suppressEvent) {
                    this.fireSelectionChange();
                }
            }
        },

        /**
         * When the view has changed, whether it be to a refresh or a column change, we need
         * to check the current selection and deselect anything that may no longer be valid.
         * @param {Ext.view.Table} view
         * @param {Boolean} isColumnChange `true` if this change is based on a column change
         * @returns {Boolean} `true` if a change to the selection was made
         * @private
         * @since 6.2.2
         */
        onViewChanged: function(view, isColumnChange) {
            var me = this,
                selData = me.selected,
                store = view.store,
                selectionChanged = false,
                rowRange, colCount, colIdx, rowIdx, context;

            // When columns have changed, we have to deselect *every* cell in the row range
            // because we do not know where the columns have gone to.
            if (selData) {
                view = selData.view;

                if (isColumnChange) {
                    if (selData.isCells) {
                        context = new Ext.grid.CellContext(view);
                        rowRange = selData.getRowRange();
                        colCount = view.ownerGrid.getColumnManager().getColumns().length;

                        if (colCount) {
                            for (rowIdx = rowRange[0]; rowIdx <= rowRange[1]; rowIdx++) {
                                context.setRow(rowIdx);

                                for (colIdx = 0; colIdx < colCount; colIdx++) {
                                    // CellContext only works with visible columns and this index is
                                    // potentially a hidden column. Ensure the column is available
                                    // before deselecting the cell.
                                    context.setColumn(colIdx);

                                    if (context.column) {
                                        view.onCellDeselect(context);
                                    }

                                    // Selection may still reference a hidden column and may need
                                    // to be cleared
                                    if (me.maybeClearSelection(context)) {
                                        selectionChanged = true;
                                    }
                                }
                            }
                        }
                        else {
                            me.clearSelections();
                            selectionChanged = true;
                        }
                    }

                    // We have to deselect columns which have been hidden/removed
                    else if (selData.isColumns) {
                        selectionChanged = false;
                        selData.eachColumn(function(column, columnIdx) {
                            if (!column.isVisible() || !view.ownerGrid.isAncestor(column)) {
                                me.remove(column);

                                if (me.maybeClearSelection({ column: column })) {
                                    selectionChanged = true;
                                }
                            }
                        });
                    }
                }

                // View has refreshed; deselect filtered out records
                else if (selData.isRows && store.isFiltered()) {
                    selData.eachRow(function(rec) {
                        if (!store.contains(rec)) {
                            // Maintainer: `this` is the Rows selection object, *NOT* me.
                            this.remove(rec);

                            if (me.maybeClearSelection({ rowIdx: view.indexOf(rec) })) {
                                selectionChanged = true;
                            }
                        }

                    });
                }
            }

            return selectionChanged;
        },

        onViewCreated: function(grid, view) {
            var me = this,
                ownerGrid = view.ownerGrid,
                headerCt = view.headerCt;

            // Only add columns to the locked view, or only view if there is no twin
            if (!ownerGrid.lockable || view.isLockedView) {
                // if there is no row number column and we ask for it, then it should be added here
                if (me.getRowSelect()) {
                    // Ensure we have a rownumber column
                    me.getNumbererColumn();
                }

                if (me.checkboxSelect) {
                    me.addCheckbox(view, true);
                }

                me.mon(view.ownerGrid, 'beforereconfigure', me.onBeforeReconfigure, me);
            }

            // Disable sortOnClick if we're columnSelecting
            headerCt.sortOnClick = !me.getColumnSelect();

            if (me.getDragSelect()) {
                view.on('render', me.onViewRender, me, {
                    single: true
                });
            }
        },

        /**
         * Initialize drag selection support
         * @private
         */
        onViewRender: function(view) {
            var me = this,
                el = view.getEl(),
                views = me.views,
                len = views.length,
                i;

            // If we receive the render event after the columnSelect config has been set,
            // ensure that the view's headerCts know not to sort on click
            // if we're selecting columns.
            for (i = 0; i < len; i++) {
                views[i].headerCt.sortOnClick = !me.columnSelect;
            }

            el.ddScrollConfig = {
                vthresh: 50,
                hthresh: 50,
                frequency: 300,
                increment: 100
            };
            Ext.dd.ScrollManager.register(el);

            // Possible two child views to register as scrollable on drag
            (me.scrollEls || (me.scrollEls = [])).push(el);

            view.on('cellmousedown', me.handleMouseDown, me);

            // In a locking situation, we need a mousedown listener on both sides.
            if (view.lockingPartner) {
                view.lockingPartner.on('cellmousedown', me.handleMouseDown, me);
            }
        },

        /**
         * Plumbing for drag selection of cell range
         * @private
         */
        handleMouseDown: function(view, td, cellIndex, record, tr, rowIdx, e) {
            var me = this,
                sel = me.selected,
                header = e.position.column,
                resumingSelection = false,
                isCheckClick, startDragSelect, containsSelection;

            // Ignore right click and alt modifiers.
            // Also ignore touchstart because e cannot drag select using touches and
            // ignore when actionableMode is true so we can select the text inside an editor
            if (e.button || e.altKey || e.pointerType === 'touch' || !header) {
                return;
            }

            me.mousedownPosition = e.position.clone();

            isCheckClick = header === me.checkColumn;

            if (isCheckClick) {
                me.checkCellClicked = e.position.getCell(true);
            }
            else if (view.actionableMode) {
                return;
            }

            // Differentiate between row and cell selections.
            if (header === me.numbererColumn || isCheckClick || !me.cellSelect) {
                // Enforce rowSelect setting
                if (me.rowSelect) {
                    if (sel) {
                        containsSelection = sel.contains(record);

                        if (e.shiftKey && containsSelection) {
                            resumingSelection = true;
                        }
                        else if (!e.shiftKey && !e.ctrlKey && !isCheckClick) {
                            sel.clear();
                        }
                    }

                    if (!sel || !sel.isRows) {
                        if (sel) {
                            sel.clear();
                        }

                        sel = me.selected = new Ext.grid.selection.Rows(view);
                    }
                }
                else if (me.columnSelect) {
                    if (sel) {
                        containsSelection = sel.contains(me.mousedownPosition.column);

                        if (e.shiftKey && containsSelection) {
                            resumingSelection = true;
                        }
                        else if (!e.shiftKey && !e.ctrlKey && !isCheckClick) {
                            sel.clear();
                        }
                    }

                    if (!sel || !sel.isColumns) {
                        if (sel) {
                            sel.clear();
                        }

                        sel = me.selected = new Ext.grid.selection.Columns(view);
                    }
                }
                else {
                    return false;
                }
            }
            else {
                if (sel) {
                    containsSelection = sel.contains(me.getCellContext(record, cellIndex));

                    if (e.shiftKey && containsSelection) {
                        resumingSelection = true;
                    }
                    else if (!e.shiftKey) {
                        sel.clear();
                    }
                }

                if (!sel || !sel.isCells) {
                    if (sel) {
                        sel.clear();
                    }

                    sel = me.selected = new Ext.grid.selection.Cells(view);
                }
            }

            startDragSelect = resumingSelection || !e.shiftKey;

            if (!resumingSelection) {
                if (e.shiftKey) {
                    return;
                }

                me.lastOverRecord = me.lastOverColumn = null;
            }

            // Add the listener after the view has potentially been corrected
            me._onMouseUp = Ext.getBody().on(
                'mouseup', me.onMouseUp, me, { single: true, view: sel.view, destroyable: true }
            );

            // Only begin the drag process if configured to select what they asked for
            if (startDragSelect) {
                sel.view.el.on('mousemove', me.onMouseMove, me, { view: sel.view });
            }
        },

        /**
         * Selects range based on mouse movements
         * @param e
         * @param target
         * @param opts
         * @private
         */
        onMouseMove: function(e, target, opts) {
            var me = this,
                view = opts.view,
                cell = e.getTarget(view.cellSelector),
                header = opts.view.getHeaderByCell(cell),
                selData = me.selected;

            if (view.isLockingView) {
                view = e.within(view.lockedView.el) ? view.lockedView : view.normalView;
            }

            // when the mousedown happens in a checkcolumn, we need to verify is the mouse pointer
            // has moved out of the initial clicked cell.
            // if it has, then we select the initial row and mark it as the range start,
            // otherwise passing the lastOverRecord and return as we don't want
            // to select the record while moving the pointer around the initial cell.
            if (me.checkCellClicked) {
                // We are dragging within the check cell...
                if (cell === me.checkCellClicked) {
                    if (!me.lastOverRecord) {
                        me.lastOverRecord = view.getRecord(cell.parentNode);
                    }

                    return;
                }
                else {
                    me.checkCellClicked = null;

                    if (me.lastOverRecord) {
                        me.select(me.lastOverRecord);
                        selData.setRangeStart(me.store.indexOf(me.lastOverRecord));
                    }
                }
            }

            me.isDragging = true;

            // Disable until a valid new selection is announced in fireSelectionChange
            if (me.extensible) {
                me.extensible.disable();
            }

            if (header) {
                me.changeSelectionRange(view, cell, header, e);
            }
            else if (!e.within(view.body.el)) {
                me.scrollTowardsPointer(e, view.ownerGrid.view);
            }
        },

        changeSelectionRange: function(view, cell, header, e) {
            var me = this,
                selData = me.selected,
                record, rowIdx, recChange, colChange, pos;

            me.stopAutoScroller();

            record = view.getRecord(cell.parentNode);
            rowIdx = me.store.indexOf(record);
            recChange = record !== me.lastOverRecord;
            colChange = header !== me.lastOverColumn;

            if (recChange || colChange) {
                pos = me.getCellContext(record, header);
            }

            // Initial mousedown was in rownumberer or checkbox column
            if (selData.isRows) {
                // Only react if we've changed row
                if (recChange) {
                    if (me.lastOverRecord) {
                        selData.setRangeEnd(rowIdx, e.ctrlKey);
                    }
                    else {
                        selData.setRangeStart(rowIdx);
                    }
                }
            }
            // Selecting cells
            else if (selData.isCells) {
                // Only react if we've changed row or column
                if (recChange || colChange) {
                    if (me.lastOverRecord) {
                        selData.setRangeEnd(pos);
                    }
                    else {
                        selData.setRangeStart(pos);
                    }
                }
            }
            // Selecting columns
            else if (selData.isColumns) {
                // Only react if we've changed column
                if (colChange) {
                    if (me.lastOverColumn) {
                        selData.setRangeEnd(pos.column);
                    }
                    else {
                        selData.setRangeStart(pos.column);
                    }
                }
            }

            // Focus MUST follow the mouse.
            // Otherwise the focus may scroll out of the rendered range and revert to document
            if (recChange || colChange) {
                // We MUST pass local view into NavigationModel, not the potentially outermost
                // locking view.
                // TODO: When that's fixed, use setPosition(pos).
                view.getNavigationModel().setPosition(
                    new Ext.grid.CellContext(header.getView()).setPosition(record, header)
                );
            }

            me.lastOverColumn = header;
            me.lastOverRecord = record;
        },

        scrollTowardsPointer: function(e, view) {
            var me = this,
                viewRegion = view.el.getConstrainRegion(),
                point = e.getXY(),
                scrollTask, scrollBy;

            scrollTask = me.scrollTask || (me.scrollTask = Ext.util.TaskManager.newTask({
                run: me.doAutoScroll,
                args: [e, view],
                scope: me,
                interval: 10
            }));

            scrollBy = me.scrollBy || (me.scrollBy = []);

            // Neart bottom of view
            if (point[1] > viewRegion.bottom) {
                scrollBy[0] = 0;
                scrollBy[1] = 3;
                scrollTask.start();
            }
            else if (point[1] < viewRegion.top) {
                scrollBy[0] = 0;
                scrollBy[1] = -3;
                scrollTask.start();
            }

            // Near right edge of view
            else if (point[0] > viewRegion.right) {
                scrollBy[0] = 3;
                scrollBy[1] = 0;
                scrollTask.start();
            }

            else if (point[0] < viewRegion.left) {
                scrollBy[0] = -3;
                scrollBy[1] = 0;
                scrollTask.start();
            }
        },

        doAutoScroll: function(e, view) {
            var me = this,
                viewRegion = view.el.getConstrainRegion(),
                xy = [],
                cell, record, header;

            if (me.destroyed) {
                return;
            }

            // Bump the view in whatever direction was decided in the onDrag method.
            if (view.scrollBy) {
                view.scrollBy.apply(view, me.scrollBy);
            }

            if (me.scrollBy[0]) {
                xy[0] = me.scrollBy[0] > 0 ? viewRegion.right - 5 : viewRegion.left + 5;
            }
            else {
                xy[0] = e.getX();
            }

            if (me.scrollBy[1]) {
                xy[1] = me.scrollBy[1] > 0 ? viewRegion.bottom - 5 : viewRegion.top + 5;
            }
            else {
                xy[1] = e.getY();
            }

            cell = document.elementFromPoint.apply(document, xy);

            if (cell) {
                cell = Ext.fly(cell).up(view.cellSelector);

                if (!cell) {
                    me.stopAutoScroller();

                    return;
                }

                record = view.getRecord(cell.dom.parentNode);
                header = view.getHeaderByCell(cell.dom);

                if (cell && (record !== me.lastOverRecord || header !== me.lastOverColumn)) {
                    me.changeSelectionRange(view, cell.dom, header, e);
                }
            }

        },

        stopAutoScroller: function() {
            var me = this;

            if (me.scrollTask) {
                me.scrollBy[0] = me.scrollBy[1] = 0;
                me.scrollTask.stop();
                me.scrollTask = null;
            }
        },

        /**
         * Clean up mousemove event
         * @param e
         * @param target
         * @param opts
         * @private
         */
        onMouseUp: function(e, target, opts) {
            var me = this,
                view = opts.view,
                lastPos = me.lastOverRecord && new Ext.grid.CellContext(view).setPosition(
                    me.lastOverRecord, me.lastOverColumn
                ),
                changedCell = lastPos && !lastPos.isEqual(me.mousedownPosition),
                cell, record;

            me.checkCellClicked = null;

            me.stopAutoScroller();

            if (view && !view.destroyed) {
                // If we catch the event before the View sees it and stamps a position in,
                // we need to know where they mouseupped.
                if (!e.position) {
                    cell = e.getTarget(view.cellSelector);

                    if (cell) {
                        record = view.getRecord(cell);

                        if (record) {
                            e.position = new Ext.grid.CellContext(view).setPosition(
                                record, view.getHeaderByCell(cell)
                            );
                        }
                    }
                }

                if (e.position) {
                    changedCell = !e.position.isEqual(me.mousedownPosition);
                }

                // Disable until a valid new selection is announced in fireSelectionChange
                // unless it's a click
                if (me.extensible && changedCell) {
                    me.extensible.disable();
                }

                view.el.un('mousemove', me.onMouseMove, me);

                // Copy the records encompassed by the drag range into the record collection
                // if we are not dragging, the range will be added by onNavigate
                if (me.selected.isRows && me.isDragging) {
                    me.selected.addRange();
                }

                // Fire selection change only if we have dragged - if the mouseup position
                // is different from the mousedown position.
                // If there has been no drag, the click handler will select the single row
                if (changedCell) {
                    me.fireSelectionChange();
                }
            }

            me.isDragging = false;
        },

        /**
         * Add the header checkbox to the header row
         * @param view
         * @param {Boolean} initial True if we're binding for the first time.
         * @private
         */
        addCheckbox: function(view, initial) {
            var me = this,
                checkbox = me.checkboxColumnIndex,
                headerCt = view.headerCt;

            // Preserve behaviour of false, but not clear why that would ever be done.
            if (checkbox !== false) {
                if (checkbox === 'first') {
                    checkbox = 0;
                }
                else if (checkbox === 'last') {
                    checkbox = headerCt.getColumnCount();
                }

                me.checkColumn = headerCt.add(checkbox, me.getCheckboxHeaderConfig());
            }

            if (initial !== true) {
                view.refresh();
            }
        },

        /**
         * Called when the grid's Navigation model detects navigation events (`mousedown`,
         * `click` and certain `keydown` events).
         * @param {Ext.event.Event} navigateEvent The event which caused navigation.
         * @private
         */
        onNavigate: function(navigateEvent) {
            var me = this,
                // Use outermost view. May be lockable
                view = navigateEvent.view && navigateEvent.view.ownerGrid.view,
                record = navigateEvent.record,
                sel = me.selected,

                // Create a new Context based upon the outermost View.
                // NavigationModel works on local views.
                // TODO: remove this step when NavModel is fixed to use outermost view
                // in locked grid. At that point, we can use navigateEvent.position
                pos = view &&
                      new Ext.grid.CellContext(view).setPosition(record, navigateEvent.column),
                keyEvent = navigateEvent.keyEvent,
                ctrlKey = keyEvent.ctrlKey,
                shiftKey = keyEvent.shiftKey,
                keyCode = keyEvent.getKey(),
                selectionChanged, rowRangeStart, lastRecord;

            // if there's no position then the user might have clicked outside a cell
            if (!pos) {
                return;
            }

            // A Column's processEvent method may set this flag if configured to do so.
            if (keyEvent.stopSelection) {
                return;
            }

            // CTRL/Arrow just navigates, does not select
            if (ctrlKey && (keyCode === keyEvent.UP || keyCode === keyEvent.LEFT ||
                keyCode === keyEvent.RIGHT || keyCode === keyEvent.DOWN)) {
                return;
            }

            // Click is the mouseup at the end of a multi-cell/multi-column select swipe; reject.
            if (sel && (sel.isCells || (sel.isColumns && !me.getRowSelect() && !ctrlKey)) &&
                sel.getCount() > 1) {
                if (shiftKey && keyEvent.type === 'click' &&
                    !keyEvent.position.isEqual(me.mousedownPosition)) {
                    return;
                }
            }

            // If all selection types are disabled, or it's not a selecting event, return
            if (!(me.cellSelect || me.columnSelect || me.rowSelect) || !navigateEvent.record ||
                keyEvent.type === 'mousedown') {
                return;
            }

            // Ctrl/A key - Deselect current selection, or select all if no selection
            if (ctrlKey && keyEvent.keyCode === keyEvent.A) {
                // No selection, or only one, select all
                if (!sel || sel.getCount() < 2) {
                    me.selectAll();
                }
                else {
                    me.deselectAll();
                }

                me.updateHeaderState();

                return;
            }

            if (shiftKey) {
                // If the event is in one of the row selecting cells,
                // or cell selecting is turned off
                if (pos.column === me.numbererColumn || pos.column === me.checkColumn ||
                    !(me.cellSelect || me.columnSelect) || (sel && sel.isRows)) {
                    if (me.rowSelect) {
                        // Ensure selection object is of the correct type
                        if (!sel || !sel.isRows || sel.view !== view) {
                            me.resetSelection(true);
                            sel = me.selected = new Ext.grid.selection.Rows(view);
                        }

                        // First shift
                        if (!sel.getRangeSize()) {
                            rowRangeStart = navigateEvent.previousRecordIndex;

                            if (rowRangeStart == null) {
                                // previousRecordIndex could be empty due to BufferedRenderer
                                // de-rendering the last selected row.
                                // In that case we need to select the last selected record
                                // or start from 0.
                                lastRecord = me.getLastSelected();
                                rowRangeStart = lastRecord ? me.store.indexOf(lastRecord) : 0;
                            }

                            sel.setRangeStart(rowRangeStart);
                        }

                        sel.setRangeEnd(navigateEvent.recordIndex);
                        sel.addRange();
                        selectionChanged = true;
                    }
                }
                // Navigate event in a normal cell
                else {
                    if (me.cellSelect) {
                        // Ensure selection object is of the correct type
                        if (!sel || !sel.isCells || sel.view !== view) {
                            me.resetSelection(true);
                            sel = me.selected = new Ext.grid.selection.Cells(view);
                        }

                        // First shift
                        if (!sel.getRangeSize()) {
                            sel.setRangeStart(navigateEvent.previousPosition ||
                                              me.getCellContext(0, 0));
                        }

                        sel.setRangeEnd(pos);
                        selectionChanged = true;
                    }
                    else if (me.columnSelect) {
                        // Ensure selection object is of the correct type
                        if (!sel || !sel.isColumns || sel.view !== view) {
                            me.resetSelection(true);
                            sel = me.selected = new Ext.grid.selection.Columns(view);
                        }

                        if (!sel.getCount()) {
                            sel.setRangeStart(pos.column);
                        }

                        sel.setRangeEnd(navigateEvent.position.column);
                        selectionChanged = true;
                    }
                }
            }
            else {
                // If the event is in one of the row selecting cells, or we have enabled
                // row selection but not column selection so prioritize selecting rows
                if (pos.column === me.numbererColumn || pos.column === me.checkColumn ||
                    (me.rowSelect && !me.cellSelect)) {
                    // Ensure selection object is of the correct type
                    if (!sel || !sel.isRows || sel.view !== view) {
                        me.resetSelection(true);
                        sel = me.selected = new Ext.grid.selection.Rows(view);
                    }

                    if (ctrlKey || pos.column === me.checkColumn) {
                        if (sel.contains(record)) {
                            sel.remove(record);
                        }
                        else {
                            sel.add(record);
                        }
                    }
                    else {
                        sel.clear();
                        sel.add(record);
                        sel.setRangeStart(pos.rowIdx, true);
                    }

                    selectionChanged = true;
                }
                // Navigate event in a normal cell
                else if (keyEvent.getTarget(me.view.getCellSelector())) {
                    // Prioritize cell selection over column selection, also we have to make sure
                    // we only handle events that were fired by a cellClick.
                    // If an itemclick (row selection) was fired due to dragging,
                    // it will be handled by the selection#setRangeEnd method.
                    if (me.cellSelect) {
                        // Ensure selection object is of the correct type
                        if (!sel || !sel.isCells || sel.view !== view) {
                            me.resetSelection(true);
                            me.selected = sel = new Ext.grid.selection.Cells(view);
                        }
                        else {
                            sel.clear();
                        }

                        sel.setRangeStart(pos);
                        selectionChanged = true;
                    }
                    else if (me.columnSelect) {
                        // Ensure selection object is of the correct type
                        if (!sel || !sel.isColumns || sel.view !== view) {
                            me.resetSelection(true);
                            me.selected = sel = new Ext.grid.selection.Columns(view);
                        }

                        if (ctrlKey) {
                            if (sel.contains(pos.column)) {
                                sel.remove(pos.column);
                            }
                            else {
                                sel.add(pos.column);
                            }
                        }
                        else {
                            sel.setRangeStart(pos.column);
                        }

                        selectionChanged = true;
                    }
                }
            }

            // If our configuration allowed selection changes, update check header and fire event
            if (selectionChanged) {
                if (sel.isRows) {
                    me.updateHeaderState();
                }

                // this will give continuity between keyboard selection and mouse selection
                me.lastOverRecord = record;
                me.lastOverColumn = pos.column;
                me.fireSelectionChange();
            }
        },

        /**
         * Checks the current selection (if available) against the context being removed.
         * If the context was selected, the selection is cleared since it's no longer valid.
         * @param {Object} removedContext
         * @return {Boolean} `true` if part or all of the selection was cleared
         * @since 6.2.2
         */
        maybeClearSelection: function(removedContext) {
            var me = this,
                selData = me.selected,
                startCell = selData.startCell,
                endCell = selData.endCell,
                column = removedContext.column,
                colIdx = removedContext.colIdx,
                rowIdx = removedContext.rowIdx,
                changed;

            if (startCell && (startCell.column === column || startCell.colIdx === colIdx) &&
                startCell.rowIdx === rowIdx) {
                selData.startCell = changed = null;
            }

            if (endCell && (endCell.column === column || endCell.colIdx === colIdx) &&
                endCell.rowIdx === rowIdx) {
                selData.endCell = changed = null;
            }

            return changed === null;
        },

        /**
         * Check if given record is currently selected.
         *
         * Used in {@link Ext.view.Table view} rendering to decide upon cell UI treatment.
         * @param {Ext.data.Model} record
         * @return {Boolean}
         * @private
         */
        isRowSelected: function(record) {
            var me = this,
                sel = me.selected;

            if (sel && sel.isRows) {
                record = Ext.isNumber(record) ? me.store.getAt(record) : record;

                return sel.contains(record);
            }
            else {
                return false;
            }
        },

        /**
         * Check if given column is currently selected.
         *
         * @param {Ext.grid.column.Column} column
         * @return {Boolean}
         * @private
         */
        isColumnSelected: function(column) {
            var me = this,
                sel = me.selected;

            if (sel && sel.isColumns) {
                return sel.contains(column);
            }
            else {
                return false;
            }
        },

        /**
         * Returns true if specified cell within specified view is selected
         *
         * Used in {@link Ext.view.Table view} rendering to decide upon row UI treatment.
         * @param {Ext.grid.View} view - impactful when locked columns are used
         * @param {Number} row - row index
         * @param {Number} column - column index, within the current view
         *
         * @return {Boolean}
         * @private
         */
        isCellSelected: function(view, row, column) {
            var me = this,
                testPos,
                sel = me.selected;

            // view MUST be outermost (possible locking) view
            view = view.ownerGrid.view;

            if (sel) {
                if (sel.isColumns) {
                    if (typeof column === 'number') {
                        column = view.getVisibleColumnManager().getColumns()[column];
                    }

                    return sel.contains(column);
                }

                if (sel.isCells) {
                    testPos = new Ext.grid.CellContext(view).setPosition({
                        row: row,
                        // IMPORTANT: The historic API for columns has been to include
                        // hidden columns in the index.
                        // So we must index into the "all" ColumnManager.
                        column: column
                    });

                    return sel.contains(testPos);
                }
            }

            return false;
        },

        /**
         * @private
         */
        applySelected: function(selected) {
            // Must override base class's applier which creates a Collection
            //<debug>
            if (selected && !(selected.isRows || selected.isCells || selected.isColumns)) {
                Ext.raise('SpreadsheelModel#setSelected must be passed an instance ' +
                          'of Ext.grid.selection.Selection');
            }
            //</debug>

            return selected;
        },

        /**
         * @private
         */
        updateSelected: function(selected, oldSelected) {
            var view,
                columns,
                len,
                i,
                cell;

            // Clear old selection.
            if (oldSelected) {
                oldSelected.clear();
            }

            // Update the UI to match the new selection
            if (selected && selected.getCount()) {
                view = selected.view;

                // Rows; update each selected row
                if (selected.isRows) {
                    selected.eachRow(view.onRowSelect, view);
                }
                // Columns; update the selected columns for all rows
                else if (selected.isColumns) {
                    columns = selected.getColumns();
                    len = columns.length;

                    if (len) {
                        cell = new Ext.grid.CelContext(view);
                        view.store.each(function(rec) {
                            cell.setRow(rec);

                            for (i = 0; i < len; i++) {
                                cell.setColumn(columns[i]);
                                view.onCellSelect(cell);
                            }
                        });
                    }
                }
                // Cells; update each selected cell
                else if (selected.isCells) {
                    selected.eachCell(view.onCellSelect, view);
                }
            }
        },

        getNumbererColumn: function(col) {
            var me = this,
                result = me.numbererColumn,
                view = me.view;

            if (!result) {
                // Always put row selection columns in the locked side if there is one.
                if (view.isNormalView) {
                    view = view.ownerGrid.lockedGrid;
                }

                result = me.numbererColumn = view.headerCt.down('rownumberer') ||
                                             view.headerCt.add(0, me.getNumbererColumnConfig());
            }

            return result;
        },

        /**
         * Show/hide the extra column headers depending upon rowSelection.
         * @private
         */
        updateRowSelect: function(rowSelect) {
            var me = this,
                sel = me.selected,
                view = me.view;

            if (view && view.rendered) {
                if (rowSelect) {
                    if (me.checkColumn) {
                        me.checkColumn.show();
                    }

                    me.getNumbererColumn().show();
                }
                else {
                    if (me.checkColumn) {
                        me.checkColumn.hide();
                    }

                    if (me.numbererColumn) {
                        me.numbererColumn.hide();
                    }
                }

                if (!rowSelect && sel && sel.isRows) {
                    sel.clear();
                    me.fireSelectionChange();
                }
            }
        },

        /**
         * Enable/disable the HeaderContainer's sortOnClick in line with column select on
         * column click.
         * @private
         */
        updateColumnSelect: function(columnSelect) {
            var me = this,
                sel = me.selected,
                views = me.views,
                len = views ? views.length : 0,
                i;

            for (i = 0; i < len; i++) {
                views[i].headerCt.sortOnClick = !columnSelect;
            }

            if (!columnSelect && sel && sel.isColumns) {
                sel.clear();
                me.fireSelectionChange();
            }

            if (columnSelect) {
                me.view.ownerGrid.addCls(me.columnSelectCls);
            }
            else {
                me.view.ownerGrid.removeCls(me.columnSelectCls);
            }
        },

        /**
         * @private
         */
        updateCellSelect: function(cellSelect) {
            var me = this,
                sel = me.selected;

            if (!cellSelect && sel && sel.isCells) {
                sel.clear();
                me.fireSelectionChange();
            }
        },

        /**
         * @private
         */
        fireSelectionChange: function() {
            var me = this,
                sel = me.selected,
                view = sel.view,
                grid = view.ownerGrid,
                store = view.dataSource,
                records, count;

            // Inform selection object that we're done
            me.updateSelectionExtender();

            // We must still fire a selectionchange event through the SelectionModel
            // because Ext.panel.Table listens for this event to update its bound selection.
            if (sel.isRows) {
                records = sel.getRecords();
                count = store.getTotalCount() || store.getCount();
                // When there is a BufferedStore the allSelected flag cannot be set
                // in a manual selection
                // eslint-disable-next-line max-len 
                me.selected.allSelected = !!(store.isBufferedStore ? me.selected.allSelected : count && records.length && (count === records.length));
                me.fireEvent('selectionchange', me, records);
            }
            else if (sel.isCells) {
                me.selected.allSelected = false;

                // eslint-disable-next-line max-len
                me.fireEvent('selectionchange', me, sel.getCount() ? me.store.getRange.apply(sel.view.dataSource, sel.getRowRange()) : []);
            }

            grid.fireEvent('selectionchange', grid, sel);
        },

        /**
         * @private
         * Called by {@link Ext.panel.Table#updateBindSelection} when publishing the `selection`
         * property. It should yield the last record selected.
         * @return {Ext.data.Model} The last record selected. This is only available
         * if the current selection type is cells or rows.
         * In the case of multiple selection, the *last* record added to the selection is returned.
         */
        getLastSelected: function() {
            var sel = this.selected;

            if (sel.getLastSelected) {
                return sel.getLastSelected();
            }
        },

        updateSelectionExtender: function() {
            var sel = this.selected;

            if (sel) {
                sel.onSelectionFinish();
            }
        },

        /**
         * Called when a selection has been made. The selection object's onSelectionFinish
         * calls back into this.
         * @param {Ext.grid.selection.Selection} sel The selection object specific to 
         * the selection performed.
         * @param {Ext.grid.CellContext} [firstCell] The left/top most selected cell.
         * Will be undefined if the selection is clear.
         * @param {Ext.grid.CellContext} [lastCell] The bottom/right most selected cell.
         * Will be undefined if the selection is clear.
         * @private
         */
        onSelectionFinish: function(sel, firstCell, lastCell) {
            var extensible = this.getExtensible();

            if (extensible) {
                extensible.setHandle(firstCell, lastCell);
            }
        },

        applyExtensible: function(extensible) {
            var me = this;

            // if extensible is false/null we should return undefined so the value
            // does not get set and we don't call updateExtensible
            if (!extensible) {
                return undefined;
            }

            if (extensible === true || typeof extensible === 'string') {
                extensible = {
                    axes: me.axesConfigs[extensible]
                };
            }
            else {
                extensible = Ext.Object.chain(extensible); // don't mutate the user's config
            }

            extensible.allowReduceSelection = me.getReducible();
            extensible.view = me.selected.view;

            return new Ext.grid.selection.SelectionExtender(extensible);
        },

        /*
         * @private
         */
        applyReducible: function(reducible) {
            return !!reducible;
        },

        updateReducible: function(reducible) {
            // do not call getExtensible() here to avoid creation
            var extensible = this.extensible;

            if (extensible) {
                extensible.allowReduceSelection = reducible;
            }
        },

        /**
         * Called when the SelectionExtender has the mouse released.
         * @param {Object} extension An object describing the type and size of extension.
         * @param {String} extension.type `"rows"` or `"columns"`
         * @param {Ext.grid.CellContext} extension.start The start (top left) cell of the
         * extension area.
         * @param {Ext.grid.CellContext} extension.end The end (bottom right) cell of the
         * extension area.
         * @param {number} [extension.columns] The number of columns extended (-ve means
         * on the left side).
         * @param {number} [extension.rows] The number of rows extended (-ve means on the top side).
         * @private
         */
        extendSelection: function(extension) {
            var me = this,
                sel = me.selected,
                action = extension.reduce ? 'reduce' : 'extend';

            // Announce that the selection is to be extended, and if no objections, extend it
            // eslint-disable-next-line max-len
            if (me.view.ownerGrid.fireEvent('beforeselectionextend', me.view.ownerGrid, sel, extension) !== false) {
                sel[action + 'Range'](extension);
                me.fireSelectionChange();
            }
        },

        /**
         * @private
         */
        onIdChanged: function(store, rec, oldId, newId) {
            var sel = this.selected;

            if (sel && sel.isRows && sel.selectedRecords) {
                sel.selectedRecords.updateKey(rec, oldId);
            }
        },

        /**
         * Called when a page is added to BufferedStore.
         * @private
         */
        onPageAdd: function(pageMap, pageNumber, records) {
            var sel = this.selected,
                len = records.length,
                i,
                record,
                selected = sel && sel.selectedRecords;

            // Check for return of already selected records.
            // Maintainer: To only use one conditional expression, the value of assignment of
            // (selected = sel.selectedRecords) is part of the single conditional expression.
            if (selected && sel.isRows) {
                for (i = 0; i < len; i++) {
                    record = records[i];

                    if (selected.get(record.id)) {
                        selected.replace(record);
                    }
                    else if (sel.allSelected) {
                        selected.add(record);
                    }
                }
            }
        },

        /**
         * @private
         */
        refresh: function() {
            var sel = this.getSelected();

            // Refreshing the selected record Collection based upon a possible
            // store mutation is only valid if we are selecting records.
            if (sel && sel.isRows) {
                this.callParent();
            }
        },

        /**
         * @private
         */
        onStoreAdd: function() {
            var sel = this.getSelected();

            // Updating on store mutation is only valid if we are selecting records.
            if (sel && sel.isRows) {
                this.callParent(arguments);
                this.updateHeaderState();
            }
        },

        /**
         * @private
         */
        onStoreClear: function() {
            this.resetSelection();
        },

        /**
         * @private
         */
        onStoreLoad: function() {
            var sel = this.getSelected();

            // Updating on store mutation is only valid if we are selecting records.
            if (sel && sel.isRows) {
                this.callParent(arguments);
                this.updateHeaderState();
            }
        },

        /**
         * @private
         */
        onStoreRefresh: function() {
            var sel = this.selected;

            // Ensure that records which are no longer in the new store are pruned
            // if configured to do so.
            // Ensure that selected records in the collection are the correct instance.
            if (sel && sel.isRows && sel.selectedRecords) {
                this.updateSelectedInstances(sel.selectedRecords);
            }

            if (this.view) {
                this.updateHeaderState();
            }
        },

        /**
         * @private
         */
        onPageRemove: function(pageMap, pageNumber, records) {
            var sel = this.selected;

            // On page purge from a buffered store, do not react if
            // we have selected all. All are still selected!
            if (!(sel && sel.allSelected)) {
                this.onStoreRemove(this.store, records);
            }
        },

        /**
         * @private
         */
        onStoreRemove: function() {
            var sel = this.getSelected();

            // Updating on store mutation is only valid if we are selecting records.
            if (sel && sel.isRows) {
                this.callParent(arguments);
            }
        }
    }
}, function(SpreadsheetModel) {
    var RowNumberer = Ext.ClassManager.get('Ext.grid.column.RowNumberer');

    if (RowNumberer) {
        SpreadsheetModel.prototype.rowNumbererTdCls =
            Ext.grid.column.RowNumberer.prototype.tdCls + ' ' + Ext.baseCSSPrefix +
            'ssm-row-numberer-cell';
    }
});
