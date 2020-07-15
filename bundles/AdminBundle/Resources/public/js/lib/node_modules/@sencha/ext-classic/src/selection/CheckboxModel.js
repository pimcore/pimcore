/**
 * A selection model that renders a column of checkboxes that can be toggled to
 * select or deselect rows. The default mode for this selection model is MULTI.
 *
 *       @example
 *       var store = Ext.create('Ext.data.Store', {
 *           fields: ['name', 'email', 'phone'],
 *           data: [{
 *               name: 'Lisa',
 *               email: 'lisa@simpsons.com',
 *               phone: '555-111-1224'
 *           }, {
 *               name: 'Bart',
 *               email: 'bart@simpsons.com',
 *               phone: '555-222-1234'
 *           }, {
 *               name: 'Homer',
 *               email: 'homer@simpsons.com',
 *               phone: '555-222-1244'
 *           }, {
 *               name: 'Marge',
 *               email: 'marge@simpsons.com',
 *               phone: '555-222-1254'
 *           }]
 *       });
 *
 *       Ext.create('Ext.grid.Panel', {
 *           title: 'Simpsons',
 *           store: store,
 *           columns: [{
 *               text: 'Name',
 *               dataIndex: 'name'
 *           }, {
 *               text: 'Email',
 *               dataIndex: 'email',
 *               flex: 1
 *           }, {
 *               text: 'Phone',
 *               dataIndex: 'phone'
 *           }],
 *           height: 200,
 *           width: 400,
 *           renderTo: Ext.getBody(),
 *           selModel: {
 *               selType: 'checkboxmodel'
 *           }
 *       });
 *
 * The selection model will inject a header for the checkboxes in the first view
 * and according to the {@link #injectCheckbox} configuration.
 */
Ext.define('Ext.selection.CheckboxModel', {
    alias: 'selection.checkboxmodel',
    extend: 'Ext.selection.RowModel',

    requires: [
        'Ext.grid.column.Check'
    ],

    /**
     * @cfg {"SINGLE"/"SIMPLE"/"MULTI"} mode
     * Modes of selection.
     * Valid values are `"SINGLE"`, `"SIMPLE"`, and `"MULTI"`.
     */
    mode: 'MULTI',

    /**
     * @cfg {Number/String} [injectCheckbox=0]
     * The index at which to insert the checkbox column.
     * Supported values are a numeric index, and the strings 'first' and 'last'.
     */
    injectCheckbox: 0,

    /**
     * @cfg {Boolean} checkOnly
     * True if rows can only be selected by clicking on the checkbox column, not by clicking
     * on the row itself. Note that this only refers to selection via the UI, programmatic
     * selection will still occur regardless.
     */
    checkOnly: false,

    /**
     * @cfg {Function/String} renderer
     * @inheritDoc Ext.grid.column.Column#cfg-renderer
     */

    /**
     * @cfg {Function/String} editRenderer
     * @inheritDoc Ext.grid.column.Column#cfg-editRenderer
     */

    /* @cfg {Boolean} [locked=false]
     * If set to true, the checkbox column will be locked.
     * Note: For this config to work, it is necessary to configure the grid with
     * `enableLocking: true`, if no other columns are initially locked.
     */
    locked: false,

    /**
     * @cfg {Boolean} [showHeaderCheckbox=false]
     * Configure as `false` to not display the header checkbox at the top of the column.
     * When the store is a {@link Ext.data.BufferedStore BufferedStore}, this configuration will
     * not be available because the buffered data set does not always contain all data.
     */
    showHeaderCheckbox: undefined,

    /**
     * @cfg {String} headerText
     * Displays the configured text in the check column's header.
     *
     * if {@link #cfg-showHeaderCheckbox} is `true`, the text is shown *above* the checkbox.
     * @since 6.0.1
     */
    headerText: undefined,

    /**
     * @cfg {String} headerAriaLabel
     * ARIA label for screen readers to announce for the check column's header when it is focused.
     * Note that this label will not be visible on screen.
     *
     * @since 6.2.0
     * @locale
     */
    headerAriaLabel: 'Row selector',

    /**
     * @cfg {String} headerSelectText
     * ARIA description text to announce for the check column's header when it is focused,
     * {@link #showHeaderCheckbox} is shown, and not all rows are selected.
     *
     * @since 6.2.0
     * @locale
     */
    headerSelectText: 'Press Space to select all rows',

    /**
     * @cfg {String} headerDeselectText
     * ARIA description text to announce for the check column's header when it is focused,
     * {@link #showHeaderCheckbox} is shown, and all rows are selected.
     * @locale
     */
    headerDeselectText: 'Press Space to deselect all rows',

    /**
     * @cfg {String} rowSelectText
     * ARIA description text to announce when check column cell is focused and the row
     * is not selected.
     * @locale
     */
    rowSelectText: 'Press Space to select this row',

    /**
     * @cfg {String} rowDeselectText
     * ARIA description text to announce when check column cell is focused and the row
     * is selected.
     * @locale
     */
    rowDeselectText: 'Press Space to deselect this row',

    allowDeselect: true,

    headerWidth: 24,

    /**
     * @private
     */
    checkerOnCls: Ext.baseCSSPrefix + 'grid-hd-checker-on',

    tdCls: Ext.baseCSSPrefix + 'grid-cell-special ' + Ext.baseCSSPrefix + 'selmodel-column',

    constructor: function() {
        var me = this;

        me.callParent(arguments);

        // If mode is single and showHeaderCheck isn't explicity set to
        // true, hide it.
        if (me.mode === 'SINGLE') {
            //<debug>
            if (me.showHeaderCheckbox) {
                Ext.Error.raise('The header checkbox is not supported for SINGLE mode ' +
                                'selection models.');
            }
            //</debug>

            me.showHeaderCheckbox = false;
        }
    },

    beforeViewRender: function(view) {
        var me = this,
            ownerLockable = view.grid.ownerLockable,
            isLocked = me.locked || me.config && me.config.locked;

        me.callParent(arguments);

        // Preserve behaviour of false, but not clear why that would ever be done.
        if (me.injectCheckbox !== false) {
            // The check column gravitates to the locked side unless
            // the locked side is emptied, in which case it migrates to the normal side.
            if (ownerLockable && !me.lockListeners) {
                me.lockListeners = ownerLockable.mon(ownerLockable, {
                    lockcolumn: me.onColumnLock,
                    unlockcolumn: me.onColumnUnlock,
                    scope: me,
                    destroyable: true
                });
            }

            // If the controlling grid is NOT lockable, there's only one chance to add the column,
            // so add it.
            // If the view is the locked one and there are locked headers, add the column.
            // If the view is the normal one and we have not already added the column, add it.
            if (!ownerLockable || (view.isLockedView && (me.hasLockedHeader() || isLocked)) ||
                (view.isNormalView && !me.column)) {
                me.addCheckbox(view);

                // Listen for reconfigure of outermost grid panel.
                me.mon(view.ownerGrid, {
                    beforereconfigure: me.onBeforeReconfigure,
                    reconfigure: me.onReconfigure,
                    scope: me
                });
            }
        }
    },

    onColumnUnlock: function(lockable, column) {
        var me = this,
            checkbox = me.injectCheckbox,
            lockedColumns = lockable.lockedGrid.visibleColumnManager.getColumns();

        // User has unlocked all columns and left only the expander column in the locked side.
        if (lockedColumns.length === 1 && lockedColumns[0] === me.column) {
            if (checkbox === 'first') {
                checkbox = 0;
            }
            else if (checkbox === 'last') {
                checkbox = lockable.normalGrid.visibleColumnManager.getColumns().length;
            }

            lockable.unlock(me.column, checkbox);
        }
    },

    onColumnLock: function(lockable, column) {
        var me = this,
            checkbox = me.injectCheckbox,
            lockedColumns = lockable.lockedGrid.visibleColumnManager.getColumns();

        // User has begun filling the empty locked side - migrate to the locked side..
        if (lockedColumns.length === 1) {
            if (checkbox === 'first') {
                checkbox = 0;
            }
            else if (checkbox === 'last') {
                checkbox = lockable.lockedGrid.visibleColumnManager.getColumns().length;
            }

            lockable.lock(me.column, checkbox);
        }
    },

    bindComponent: function(view) {
        this.sortable = false;
        this.callParent(arguments);
    },

    hasLockedHeader: function() {
        var columns = this.view.ownerGrid.getVisibleColumnManager().getColumns(),
            len = columns.length,
            i;

        for (i = 0; i < len; i++) {
            if (columns[i].locked) {
                return true;
            }
        }

        return false;
    },

    /**
     * Add the header checkbox to the header row
     * @private
     */
    addCheckbox: function(view) {
        var me = this,
            checkboxIndex = me.injectCheckbox,
            headerCt = view.headerCt;

        // Preserve behaviour of false, but not clear why that would ever be done.
        if (checkboxIndex !== false) {
            if (checkboxIndex === 'first') {
                checkboxIndex = 0;
            }
            else if (checkboxIndex === 'last') {
                checkboxIndex = headerCt.getColumnCount();
            }

            Ext.suspendLayouts();

            // Cannot select all in a buffered store.
            // We do not have all the records
            if (view.getStore().isBufferedStore) {
                me.showHeaderCheckbox = false;
            }

            me.column = headerCt.add(checkboxIndex, me.column || me.getHeaderConfig());

            Ext.resumeLayouts();
        }
    },

    /**
     * Handles the grid's beforereconfigure event. Removes the checkbox header
     * if the columns are being reconfigured.
     * @private
     */
    onBeforeReconfigure: function(grid, store, columns, oldStore, oldColumns) {
        var column = this.column,
            headerCt = column.ownerCt;

        // Save out check column from destruction.
        // addCheckbox will reuse it instead of creation a new one.
        if (columns && headerCt) {
            headerCt.remove(column, false);
        }
    },

    /**
     * Handles the grid's reconfigure event. Adds the checkbox header if the columns
     * have been reconfigured.
     * @private
     * @param {Ext.panel.Table} grid
     * @param {Ext.data.Store} store
     * @param {Object[]} columns
     */
    onReconfigure: function(grid, store, columns) {
        var me = this;

        if (columns) {
            // If it's a lockable assembly, add the column to the correct side
            if (grid.lockable) {
                if (grid.lockedGrid.isVisible()) {
                    grid.lock(me.column, 0);
                }
                else {
                    grid.unlock(me.column, 0);
                }
            }
            else {
                me.addCheckbox(me.view);
            }

            grid.view.refreshView();
        }
    },

    /**
     * Toggle between selecting all and deselecting all when clicking on
     * a checkbox header.
     * @private
     */
    onHeaderClick: function(headerCt, header, e) {
        var me = this,
            store = me.store,
            isChecked, records, i, len,
            selections, selection;

        if (me.showHeaderCheckbox !== false && header === me.column && me.mode !== 'SINGLE') {
            e.stopEvent();
            isChecked = header.el.hasCls(Ext.baseCSSPrefix + 'grid-hd-checker-on');

            // selectAll will only select the contents of the store, whereas deselectAll
            // will remove all the current selections. In this case we only want to
            // deselect whatever is available in the view.
            if (isChecked) {
                records = [];
                selections = this.getSelection();

                for (i = 0, len = selections.length; i < len; ++i) {
                    selection = selections[i];

                    if (store.indexOf(selection) > -1) {
                        records.push(selection);
                    }
                }

                if (records.length > 0) {
                    me.deselect(records);
                }
            }
            else {
                me.selectAll();
            }
        }
    },

    /**
     * Retrieve a configuration to be used in a HeaderContainer.
     * This is called when injectCheckbox is not `false`.
     */
    getHeaderConfig: function() {
        var me = this,
            showCheck = me.showHeaderCheckbox !== false,
            htmlEncode = Ext.String.htmlEncode,
            config;

        config = {
            xtype: 'checkcolumn',
            headerCheckbox: showCheck,
            // historically used as a dicriminator property before isCheckColumn
            isCheckerHd: showCheck,
            ignoreExport: true,
            text: me.headerText,
            width: me.headerWidth,
            sortable: false,
            draggable: false,
            resizable: false,
            hideable: false,
            menuDisabled: true,
            checkOnly: me.checkOnly,
            checkboxAriaRole: 'presentation',
            // Firefox needs pointer-events: none on the checkbox span to work around
            // focusing issues
            tdCls: Ext.baseCSSPrefix + 'selmodel-checkbox ' + me.tdCls,
            cls: Ext.baseCSSPrefix + 'selmodel-column',
            editRenderer: me.editRenderer || me.renderEmpty,
            locked: me.hasLockedHeader(),
            processEvent: Ext.emptyFn,
            // if a custom renderer is provided in selModel, use that else use default renderer
            renderer: me.renderer || me.defaultRenderer,

            // It must not attempt to set anything in the records on toggle.
            // We handle that in onHeaderClick.
            toggleAll: Ext.emptyFn,

            // The selection model listens to the navigation model to select/deselect
            setRecordCheck: Ext.emptyFn,

            // It uses our isRowSelected to test whether a row is checked
            isRecordChecked: me.isRowSelected.bind(me)
        };

        if (!me.checkOnly) {
            // tabIndex and focusable properties should not be removed as
            // they must depend on actual column configuration
            config.ariaRole = 'presentation';
        }
        else {
            config.useAriaElements = true;
            config.ariaLabel = htmlEncode(me.headerAriaLabel);
            config.headerSelectText = htmlEncode(me.headerSelectText);
            config.headerDeselectText = htmlEncode(me.headerDeselectText);
            config.rowSelectText = htmlEncode(me.rowSelectText);
            config.rowDeselectText = htmlEncode(me.rowDeselectText);
        }

        return config;
    },

    toggleRecord: function(record, recordIndex, checked, cell) {
        this[checked ? 'select' : 'deselect']([record], this.mode !== 'SINGLE');
    },

    renderEmpty: function() {
        return '&#160;';
    },

    // After refresh, ensure that the header checkbox state matches
    refresh: function() {
        this.callParent(arguments);
        this.updateHeaderState();
    },

    selectByPosition: function(position, keepExisting) {
        if (!position.isCellContext) {
            position =
                new Ext.grid.CellContext(this.view).setPosition(position.row, position.column);
        }

        // Do not select if checkOnly, and the requested position is not the check column
        if (!this.checkOnly || position.column === this.column) {
            this.callParent([position, keepExisting]);
        }
    },

    /**
     * Synchronize header checker value as selection changes.
     * @private
     */
    onSelectChange: function(record, isSelected) {
        var me = this;

        me.callParent(arguments);

        if (me.column) {
            me.column.updateCellAriaDescription(record, isSelected);
        }

        if (!me.suspendChange) {
            me.updateHeaderState();
        }
    },

    /**
     * @private
     */
    onStoreLoad: function() {
        this.callParent(arguments);
        this.updateHeaderState();
    },

    onStoreAdd: function() {
        this.callParent(arguments);
        this.updateHeaderState();
    },

    onStoreRemove: function() {
        this.callParent(arguments);
        this.updateHeaderState();
    },

    onStoreRefresh: function() {
        this.callParent(arguments);
        this.updateHeaderState();
    },

    maybeFireSelectionChange: function(fireEvent) {
        if (fireEvent && !this.suspendChange) {
            this.updateHeaderState();
        }

        this.callParent(arguments);
    },

    resumeChanges: function() {
        this.callParent();

        if (!this.suspendChange) {
            this.updateHeaderState();
        }
    },

    /**
     * @private
     */
    updateHeaderState: function() {
        // check to see if all records are selected
        var me = this,
            store = me.store,
            storeCount = store.getCount(),
            views = me.views,
            hdSelectStatus = false,
            selectedCount = 0,
            selected, len, i;

        if (!store.isBufferedStore && storeCount > 0) {
            selected = me.selected;
            hdSelectStatus = true;

            for (i = 0, len = selected.getCount(); i < len; ++i) {
                if (store.indexOfId(selected.getAt(i).id) > -1) {
                    ++selectedCount;
                }
            }

            hdSelectStatus = storeCount === selectedCount;
        }

        if (views && views.length) {
            me.column.setHeaderStatus(hdSelectStatus);
        }
    },

    vetoSelection: function(e) {
        var me = this,
            column = me.column,
            veto, isClick, isSpace;

        if (me.checkOnly) {
            isClick = e.type === column.triggerEvent && e.getTarget(me.column.getCellSelector());
            isSpace = e.getKey() === e.SPACE && e.position.column === column;
            veto = !(isClick || isSpace);
        }

        return veto || me.callParent([e]);
    },

    privates: {
        onBeforeNavigate: function(metaEvent) {
            var e = metaEvent.keyEvent;

            if (this.selectionMode !== 'SINGLE') {
                metaEvent.ctrlKey = metaEvent.ctrlKey || e.ctrlKey ||
                                    (e.type === this.column.triggerEvent && !e.shiftKey) ||
                                    e.getKey() === e.SPACE;
            }
        },

        selectWithEventMulti: function(record, e, isSelected) {
            var me = this;

            if (!e.shiftKey && !e.ctrlKey && e.getTarget(me.column.getCellSelector())) {
                if (isSelected) {
                    me.doDeselect(record);
                }
                else {
                    me.doSelect(record, true);
                }
            }
            else {
                me.callParent([record, e, isSelected]);
            }
        }
    }
}, function(CheckboxModel) {
    CheckboxModel.prototype.checkSelector = '.' + Ext.grid.column.Check.prototype.checkboxCls;
});
