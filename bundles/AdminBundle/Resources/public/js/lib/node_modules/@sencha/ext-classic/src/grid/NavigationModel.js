// TODO: Implement http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#grid standards
/**
 * @class Ext.grid.NavigationModel
 * @private
 * This class listens for key events fired from a {@link Ext.grid.Panel GridPanel},
 * and moves the currently focused item by adding the class {@link #focusCls}.
 */
Ext.define('Ext.grid.NavigationModel', {
    extend: 'Ext.view.NavigationModel',

    alias: 'view.navigation.grid',

    /**
     * @event navigate Fired when a key has been used to navigate around the view.
     * @param {Object} event
     * @param {Ext.event.Event} event.keyEvent The key event which caused the navigation.
     * @param {Number} event.previousRecordIndex The previously focused record index.
     * @param {Ext.data.Model} event.previousRecord The previously focused record.
     * @param {HTMLElement} event.previousItem The previously focused grid cell.
     * @param {Ext.grid.Column} event.previousColumn The previously focused grid column.
     * @param {Number} event.recordIndex The newly focused record index.
     * @param {Ext.data.Model} event.record the newly focused record.
     * @param {HTMLElement} event.item the newly focused grid cell.
     * @param {Ext.grid.Column} event.column The newly focused grid column.
     */

    /**
     * @event cellactivate Fired when a cell is activated while in actionable mode
     * @param {Ext.grid.Panel} grid The grid panel that has the cell activated
     * @param {Ext.grid.CellContext} position The position in the grid that was activated
     * @param {Object} event
     */

    focusCls: Ext.baseCSSPrefix + 'grid-item-focused',

    getViewListeners: function() {
        var me = this;

        return {
            focusmove: {
                element: 'el',
                fn: me.onFocusMove
            },

            containermousedown: me.onContainerMouseDown,
            cellmousedown: me.onCellMouseDown,

            // We focus on click if the mousedown handler did not focus
            // because it was a translated "touchstart" event.
            cellclick: me.onCellClick,
            itemmousedown: me.onItemMouseDown,

            // We focus on click if the mousedown handler did not focus because
            // it was a translated "touchstart" event.
            itemclick: me.onItemClick,
            itemcontextmenu: me.onItemClick,
            scope: me
        };
    },

    initKeyNav: function(view) {
        var me = this,
            nav;

        // We will have two keyNavs if we are the navigation model for a lockable assembly
        if (!me.keyNav) {
            me.keyNav = [];
            me.position = new Ext.grid.CellContext(view);
        }

        // Drive the KeyNav off the View's itemkeydown event so that beforeitemkeydown listeners
        // may veto. By default KeyNav uses defaultEventAction: 'stopEvent', and this is required
        // for movement keys which by default affect scrolling.

        nav = new Ext.util.KeyNav({
            target: view,
            ignoreInputFields: true,

            // Must use the same event that form fields use to detect keystrokes.
            // keypress happens *after* keydown, but the framework must see key events
            // in bubble sequence order
            // So a field in actionable mode must see its key event before this nav model.
            eventName: Ext.supports.SpecialKeyDownRepeat ? 'itemkeydown' : 'itemkeypress',
            defaultEventAction: 'stopEvent',

            processEvent: me.processViewEvent,
            up: me.onKeyUp,
            down: me.onKeyDown,
            right: me.onKeyRight,
            left: me.onKeyLeft,
            pageDown: me.onKeyPageDown,
            pageUp: me.onKeyPageUp,
            home: me.onKeyHome,
            end: me.onKeyEnd,
            space: me.onKeySpace,
            enter: me.onKeyEnter,
            esc: me.onKeyEsc,
            113: {
                // Actionable mode is triggered by F2 key with no modifiers
                ctrl: false,
                shift: false,
                alt: false,
                handler: me.onKeyF2
            },
            tab: me.onKeyTab,
            A: {
                ctrl: true,
                // Need a separate function because we don't want the key
                // events passed on to selectAll (causes event suppression).
                handler: me.onSelectAllKeyPress
            },
            scope: me
        });
        me.keyNav.push(nav);
        me.onKeyNavCreate(nav);
    },

    onKeyNavCreate: Ext.emptyFn,

    addKeyBindings: function(binding) {
        var len = this.keyNav.length,
            i;

        // We will have two keyNavs if we are the navigation model for a lockable assembly
        for (i = 0; i < len; i++) {
            this.keyNav[i].addBindings(binding);
        }
    },

    enable: function() {
        var len = this.keyNav.length,
            i;

        // We will have two keyNavs if we are the navigation model for a lockable assembly
        for (i = 0; i < len; i++) {
            this.keyNav[i].enable();
        }

        this.disabled = false;
    },

    disable: function() {
        var len = this.keyNav.length,
            i;

        // We will have two keyNavs if we are the navigation model for a lockable assembly
        for (i = 0; i < len; i++) {
            this.keyNav[i].disable();
        }

        this.disabled = true;
    },

    /**
     * @private
     * Every key event is tagged with the source view, so the NavigationModel is independent.
     * Called in the scope of the KeyNav. This function is injected into the NavigationModel's
     * {@link Ext.util.KeyNav KeyNav) as its {@link Ext.util.KeyNav#processEvent processEvent}
     * config.
     */
    processViewEvent: function(view, record, row, recordIndex, event) {
        var key = event.getKey();

        // In actionable mode, we only listen for TAB, F2 and ESC to exit actionable mode
        if (view.actionableMode) {
            this.map.ignoreInputFields = false;

            if (key === event.TAB || key === event.ESC || key === event.F2) {
                return event;
            }
        }
        // In navigation mode, we process all keys
        else {
            this.map.ignoreInputFields = true;

            // Ignore TAB key in navigable mode
            return key === event.TAB ? null : event;
        }
    },

    onContainerMouseDown: function(view, mousedownEvent) {
        var me = this,
            context = new Ext.grid.CellContext(view),
            lastFocused, position;

        me.callParent([view, mousedownEvent]);

        lastFocused = view.lastFocused;
        position = (view.actionableMode && view.actionPosition) || lastFocused;

        if (!position || lastFocused === 'scrollbar') {
            return;
        }

        context.setPosition(position.record, position.column);
        mousedownEvent.position = context;
        me.attachClosestCell(mousedownEvent);

        // If we are not already on that position, set position there.
        if (!me.position.isEqual(context)) {
            me.setPosition(context, null, mousedownEvent);
        }
    },

    onCellMouseDown: function(view, cell, cellIndex, record, row, recordIndex, mousedownEvent) {
        var targetComponent = Ext.Component.from(mousedownEvent, cell),
            actionableEl = mousedownEvent.getTarget(this.isFocusableEl, cell),
            ac;

        // If actionable mode, and		
        //  (mousedown on a tabbable, or anywhere in the ownership tree of an inner active
        // component),		 we should just keep the action position synchronized.
        // The tabbable element will be part of actionability.	
        // If the mousedown was NOT on some focusable object, we need to exit actionable mode.
        if (view.actionableMode) {
            // If mousedown is on a focusable element, or in the component tree of the active
            // component (which is NOT this)
            if (!actionableEl) {
                actionableEl = (ac = Ext.ComponentManager.getActiveComponent()) &&
                               ac !== view && ac.owns(mousedownEvent);
            }

            if (actionableEl) {
                // Keep actionPosition synched
                view.setActionableMode(true, mousedownEvent.position);
            }
            // Not on anything actionable, then exit actionable mode
            else {
                view.setActionableMode(false, mousedownEvent.position);
            }

            return;
        }

        // If the event is a touchstart, leave it until the click to focus.
        // Mousedowns may have a focusing effect.
        if (mousedownEvent.pointerType !== 'touch') {
            if (mousedownEvent.position.column.cellFocusable !== false) {
                if (actionableEl) {

                    // So that the impending onFocusEnter does not
                    // process the event and delegate focus. We
                    // control that here. This means disabling tabbability.
                    if (!view.containsFocus) {
                        view.containsFocus = true;
                        view.toggleChildrenTabbability(false);
                    }

                    if (view.setActionableMode(true, mousedownEvent.position) !== false) {
                        actionableEl.focus();
                    }
                }
                else {
                    cell.focus();
                }

                if (mousedownEvent.button === 2) {
                    this.fireNavigateEvent(mousedownEvent);
                }

                // If mousedowning on a focusable Component.
                // After having set the position according to the mousedown, we then
                // enter actionable mode and focus the component just as if the user
                // Had navigated here and pressed F2.
                if (targetComponent && targetComponent.isFocusable &&
                    targetComponent.isFocusable()) {
                    view.setActionableMode(true, mousedownEvent.position);

                    // Focus the targeted Component
                    targetComponent.focus();
                }
            }
            else {
                mousedownEvent.preventDefault(true);
            }
        }
    },

    onCellClick: function(view, cell, cellIndex, record, row, recordIndex, clickEvent) {
        var me = this,
            targetComponent = Ext.Component.from(clickEvent, cell),
            clickOnFocusable = targetComponent && targetComponent.isFocusable &&
                               targetComponent.isFocusable();

        // If a prior click handler has moved focus out of the view
        // we cannot navigate because navigation has moved outside of the view.
        // Must check that we contains the focused element, not the containsFocus flag
        // because asynchronous focus events might mean that flag is not yet set
        // even though the active element is within the view.
        if (!Ext.isIE10m && !view.el.contains(Ext.Element.getActiveElement()) &&
            clickEvent.pointerType !== 'touch') {
            return;
        }

        // In actionable mode, we fire a navigate event in case the column's
        // stopSelection is false
        if (view.actionableMode) {
            // Only continue if action position is in a different place
            // Test using the guaranteed present clickEvent.position.
            // actionPosition might be null if action is right now in the other
            // side of a lockable.
            if (!clickEvent.position.isEqual(view.actionPosition)) {
                // Must still set position so that the other actionable
                // at the different action position blurs and finishes.
                if (!clickOnFocusable) {
                    view.setActionableMode(false, clickEvent.position);
                }
            }

            me.fireEvent('navigate', {
                view: view,
                navigationModel: me,
                keyEvent: clickEvent,
                previousPosition: me.previousPosition,
                previousRecordIndex: me.previousRecordIndex,
                previousRecord: me.previousRecord,
                previousItem: me.previousItem,
                previousCell: me.previousCell,
                previousColumnIndex: me.previousColumnIndex,
                previousColumn: me.previousColumn,
                position: clickEvent.position,
                recordIndex: clickEvent.position.rowIdx,
                record: clickEvent.position.record,
                selectionStart: me.selectionStart,
                item: clickEvent.item,
                cell: clickEvent.position.cellElement,
                columnIndex: clickEvent.position.colIdx,
                column: clickEvent.position.column
            });
        }
        else {
            // If the mousedown that initiated the click has navigated us to the correct spot,
            // just fire the event
            if (me.position.isEqual(clickEvent.position) || clickOnFocusable) {
                // IE10m has asynchronous focus events and the only way to detect
                // if something else was focused after onCellMouseDown was executed
                // is to verify if navigationModel has a record
                //<legacyBrowser>
                if (Ext.isIE10m && !me.record) {
                    return;
                }
                //</legacyBrowser>

                me.fireNavigateEvent(clickEvent);
            }
            // If the column is focusable, focus the cell.
            // The onFocusMove listener will react to the focus change
            else if (clickEvent.position.column.cellFocusable !== false) {
                me.setPosition(clickEvent.position, null, clickEvent);
            }
            else {
                clickEvent.preventDefault();
            }
        }
    },

    /**
     * @private
     * @param {Ext.event.Event} e The focusmove event
     * This is where we are informed of intra-view cell navigation which may be caused
     * by screen readers. We have to react to that and keep our internal state consistent.
     */
    onFocusMove: function(e) {
        var view = Ext.Component.from(e.delegatedTarget, null, 'tableview'),
            cell = e.target,
            isCell = Ext.fly(cell).is(view.cellSelector),
            record, column, newPosition;

        if (view) {
            // Focus moved to the view - process as if an onfocusEnter
            if (e.toElement === view.el.dom) {
                view.actionableMode = false;

                return view.onFocusEnter(e);
            }

            // If what was focused was a cell...
            if (!view.actionableMode && isCell) {
                record = view.getRecord(cell);
                column = view.getHeaderByCell(cell);

                if (record && column) {
                    newPosition = new Ext.grid.CellContext(view).setPosition(record, column);

                    // The focus might have been the *result* of setting the position
                    if (!newPosition.isEqual(this.position)) {
                        this.setPosition(newPosition);
                    }
                }
            }
            else if ((view.actionableMode || view.activating) && !isCell &&
                     view.el.contains(e.target) && view.el.dom !== e.target) {
                view.ownerGrid.fireEvent('cellactivate', view.ownerGrid, view.actionPosition);
            }
        }
    },

    onItemMouseDown: function(view, record, item, index, mousedownEvent) {
        var me = this,
            scroller;

        // If the event is a touchstart, leave it until the click to focus
        // A mousedown outside a cell. Must be in a Feature, or on a row border
        if (!mousedownEvent.position.cellElement && (mousedownEvent.pointerType !== 'touch')) {
            // We are going to redirect focus, so do not allow default focus to proceed
            // but allow text selection if the view is configured with enableTextSelection
            if (!view.enableTextSelection) {
                mousedownEvent.preventDefault();
            }

            // Stamp the closest cell into the event as if it were a cellmousedown
            me.attachClosestCell(mousedownEvent);

            // If we are not already on that position, set position there.
            if (!me.position.isEqual(mousedownEvent.position)) {
                me.setPosition(mousedownEvent.position, null, mousedownEvent);
            }

            // If the browser autoscrolled to bring the cell into focus
            // undo that.
            scroller = view.getScrollable();

            if (scroller) {
                scroller.restoreState();
            }
        }
    },

    onItemClick: function(view, record, item, index, clickEvent) {
        // A mousedown outside a cell. Must be in a Feature, or on a row border
        if (!clickEvent.position.cellElement) {
            this.attachClosestCell(clickEvent);

            // touchstart does not focus the closest cell, leave it until touchend
            // (translated as a click)
            if (clickEvent.pointerType === 'touch') {
                this.setPosition(clickEvent.position, null, clickEvent);
            }
            else {
                this.fireNavigateEvent(clickEvent);
            }
        }
    },

    attachClosestCell: function(event) {
        var position = event.position,
            targetCell = position.cellElement,
            x, columns, len, i, b;

        if (!targetCell) {
            x = event.getX();
            columns = position.view.getVisibleColumnManager().getColumns();
            len = columns.length;

            for (i = 0; i < len; i++) {
                b = columns[i].getBox();

                if (x >= b.left && x < b.right) {
                    position.setColumn(columns[i]);
                    position.rowElement = position.getRow(true);
                    position.cellElement = position.getCell(true);

                    return;
                }
            }
        }
    },

    // eslint-disable-next-line max-len
    deferSetPosition: function(delay, recordIndex, columnIndex, keyEvent, suppressEvent, preventNavigation) {
        var setPositionTask = this.view.getFocusTask();

        // This is essentially a focus operation. Use the singleton focus task
        // used by Focusable Components to schedule a setPosition call. This way
        // it can be superseded programmatically by regular Component focus calls.
        setPositionTask.delay(
            delay, this.setPosition, this,
            [recordIndex, columnIndex, keyEvent, suppressEvent, preventNavigation]
        );

        return setPositionTask;
    },

    setPosition: function(recordIndex, columnIndex, keyEvent, suppressEvent, preventNavigation) {
        var me = this,
            clearing = recordIndex == null && columnIndex == null,
            isClear = me.record == null && me.recordIndex == null && me.item == null,
            view, scroller, selModel, dataSource, columnManager,
            newRecordIndex, newColumnIndex, newRecord,
            newColumn, columns;

        // Work out the view we are operating on.
        // If they passed a CellContext, use the view from that.
        // Otherwise, use the view injected into the event by Ext.view.View#processEvent.
        // Otherwise, use the last focused view.
        // Failing that, use the view we were bound to.
        if (recordIndex && recordIndex.isCellContext) {
            view = recordIndex.view;
        }
        else if (keyEvent && keyEvent.view) {
            view = keyEvent.view;
        }
        else if (me.lastFocused) {
            view = me.lastFocused.view;
        }
        else {
            view = me.view;
        }

        // In case any async focus was requested before this call.
        view.cancelFocusTask();

        // Return if the view was destroyed between the deferSetPosition call and now,
        // or if the call is a no-op or if there are no items which could be focused.
        if (view.destroyed || !view.refreshCounter || !view.ownerCt || clearing && isClear ||
            !view.all.getCount()) {
            return;
        }

        selModel = view.getSelectionModel();
        dataSource = view.dataSource;
        columnManager = view.getVisibleColumnManager();
        columns = columnManager.getColumns();

        // If a CellContext is passed, use it.
        // Passing null happens on blur to remove focus class.
        if (recordIndex && recordIndex.isCellContext) {
            newRecord = recordIndex.record;
            newRecordIndex = recordIndex.rowIdx;
            newColumnIndex = Math.min(recordIndex.colIdx, columns.length - 1);
            newColumn = columns[newColumnIndex];

            // If the record being focused is not available (eg, after a removal),
            // then go to the same position
            if (dataSource.indexOf(newRecord) === -1) {
                scroller = view.getScrollable();

                // Change recordIndex so that the "No movement" test is bypassed
                // if the record is not found
                me.recordIndex = -1;

                // If the view will not jump upwards to bring the next row under the mouse
                // as expected because it's at the end, focus the previous row
                // eslint-disable-next-line max-len
                if (scroller && (scroller.getPosition().y >= scroller.getMaxPosition().y - view.all.last(true).offsetHeight)) {
                    recordIndex.rowIdx--;
                }

                newRecordIndex = Math.min(recordIndex.rowIdx, dataSource.getCount() - 1);
                newRecord = dataSource.getAt(newRecordIndex);
            }
        }
        else {
            // Both axes are null, we defocus
            if (clearing) {
                newRecord = newRecordIndex = null;
            }
            else {
                // AbstractView's default behaviour on focus is to call setPosition(0);
                // A call like this should default to the last column focused, or column 0;
                if (columnIndex == null) {
                    columnIndex = me.lastFocused ? me.lastFocused.column : 0;
                }

                if (typeof recordIndex === 'number') {
                    newRecordIndex = Math.max(Math.min(recordIndex, dataSource.getCount() - 1), 0);
                    newRecord = dataSource.getAt(recordIndex);
                }
                // row is a Record
                else if (recordIndex.isEntity) {
                    newRecord = recordIndex;
                    newRecordIndex = dataSource.indexOf(newRecord);
                }
                // row is a grid row
                else if (recordIndex.tagName) {
                    newRecord = view.getRecord(recordIndex);
                    newRecordIndex = dataSource.indexOf(newRecord);

                    if (newRecordIndex === -1) {
                        newRecord = null;
                    }
                }
                else {
                    if (isClear) {
                        return;
                    }

                    clearing = true;
                    newRecord = newRecordIndex = null;
                }
            }

            // Record position was successful
            if (newRecord) {
                // If the record being focused is not available (eg, after a sort), then go to 0,0
                if (newRecordIndex === -1) {
                    // Change recordIndex so that the "No movement" test is bypassed
                    // if the record is not found
                    me.recordIndex = -1;
                    newRecord = dataSource.getAt(0);
                    newRecordIndex = 0;
                    columnIndex = null;
                }

                // No columnIndex passed, and no previous column position - default to column 0
                if (columnIndex == null) {
                    if (!(newColumn = me.column)) {
                        newColumnIndex = 0;
                        newColumn = columns[0];
                    }
                }
                else if (typeof columnIndex === 'number') {
                    newColumn = columns[columnIndex];
                    newColumnIndex = columnIndex;
                }
                else {
                    newColumn = columnIndex;
                    newColumnIndex = columnManager.indexOf(columnIndex);
                }
            }
            else {
                clearing = true;
                newColumn = newColumnIndex = null;
            }
        }

        // The column requested may have been hidden or removed (eg reconfigure)
        // Fall back to column index.
        if (newColumn && columnManager.indexOf(newColumn) === -1) {
            if (newColumnIndex === -1) {
                newColumnIndex = 0;
            }
            else {
                newColumnIndex = Math.min(newColumnIndex, columns.length - 1);
            }

            newColumn = columns[newColumnIndex];
        }

        // If we are in actionable mode and focusing a cell, exit actionable mode
        // at the requested position
        if (view.actionableMode && !clearing) {
            return view.ownerGrid.setActionableMode(
                false, new Ext.grid.CellContext(view).setPosition(newRecord, newColumn)
            );
        }

        // No movement; just ensure the correct item is focused and return early.
        // Do not push current position into previous position, do not fire events.
        if (newRecordIndex === me.recordIndex && newColumnIndex === me.columnIndex &&
            view === me.position.view) {
            return me.focusPosition(me.position);
        }

        if (me.cell) {
            me.cell.removeCls(me.focusCls);
        }

        // Track the last position.
        // Used by SelectionModels as the navigation "from" position.
        me.previousRecordIndex = me.recordIndex;
        me.previousRecord = me.record;
        me.previousItem = me.item;
        me.previousCell = me.cell;
        me.previousColumn = me.column;
        me.previousColumnIndex = me.columnIndex;
        me.previousPosition = me.position.clone();

        // Track the last selectionStart position to correctly track ranges
        // (i.e., SHIFT + selection).
        me.selectionStart = selModel.selectionStart;

        // Set our CellContext to the new position
        me.position.setAll(
            view,
            me.recordIndex = newRecordIndex,
            me.columnIndex = newColumnIndex,
            me.record = newRecord,
            me.column = newColumn
        );

        if (clearing) {
            me.item = me.cell = null;
        }
        else {
            me.focusPosition(me.position, preventNavigation);
        }

        // Legacy API is that the SelectionModel fires focuschange events
        // and the TableView fires rowfocus and cellfocus events.
        if (!suppressEvent) {
            selModel.fireEvent('focuschange', selModel, me.previousRecord, me.record);
            view.fireEvent('rowfocus', me.record, me.item, me.recordIndex);
            view.fireEvent('cellfocus', me.record, me.cell, me.position);
        }

        // If we have moved, fire an event
        if (keyEvent && !preventNavigation && me.cell !== me.previousCell) {
            me.fireNavigateEvent(keyEvent);
        }
    },

    /**
     * @private
     * Focuses the currently active position.
     * This is used on view refresh and on replace.
     * @return {undefined}
     */
    focusPosition: function(position) {
        var me = this,
            view,
            row,
            scroller;

        me.item = me.cell = null;

        if (position && position.record && position.column) {
            view = position.view;

            // If the position is passed from a grid event, the rowElement will be stamped into it.
            // Otherwise, select it from the indicated item.
            if (position.rowElement) {
                row = me.item = position.rowElement;
            }
            else {
                // Get the dataview item for the position's record
                row = view.getRowByRecord(position.record);

                // If there is no item at that index, it's probably because
                // there's buffered rendering. This is handled below.
            }

            if (row) {
                // If the position is passed from a grid event, the cellElement
                // will be stamped into it. Otherwise, select it from the row.
                me.cell = position.cellElement ||
                          Ext.fly(row).down(position.column.getCellSelector(), true);

                // Maintain the cell as a Flyweight to avoid transient elements ending up
                // in the cache as full Ext.Elements.
                if (me.cell) {
                    me.cell = new Ext.dom.Fly(me.cell);

                    // Maintain lastFocused in the view so that on non-specific focus of the View,
                    // we can focus the view's correct descendant.
                    view.lastFocused = me.lastFocused = me.position.clone();

                    // Use explicit scrolling rather than relying on the browser's focus behaviour.
                    // Scroll on focus overscrolls. ensureVisible scrolls exatly correctly.
                    scroller = view.getScrollable();

                    if (scroller) {
                        scroller.ensureVisible(me.cell);
                    }

                    me.focusItem(me.cell);
                    view.focusEl = me.cell;
                }
                // Cell no longer in view. Clear current position.
                else {
                    me.position.setAll();
                    me.record = me.column = me.recordIndex = me.columnIndex = null;
                }
            }
            // View node no longer in view. Clear current position.
            // Attempt to scroll to the record if it is in the store, but out of rendered range.
            else {
                row = view.dataSource.indexOf(position.record);
                me.position.setAll();
                me.record = me.column = me.recordIndex = me.columnIndex = null;

                // The reason why the row could not be selected from the DOM could be because it's
                // out of rendered range, so scroll to the row, and then try focusing it.
                if (row !== -1 && view.bufferedRenderer) {
                    me.lastKeyEvent = null;
                    view.bufferedRenderer.scrollTo(row, false, me.afterBufferedScrollTo, me);
                }
            }
        }
    },

    /**
     * @template
     * @protected
     * Called to focus an item in the client {@link Ext.view.View DataView}.
     * The default implementation adds the {@link #focusCls} to the passed item focuses it.
     * Subclasses may choose to keep focus in another target.
     *
     * For example {@link Ext.view.BoundListKeyNav} maintains focus in the input field.
     * @param {Ext.dom.Element} item
     * @return {undefined}
     */
    focusItem: function(item) {
        item.addCls(this.focusCls);
        item.focus();
    },

    getCell: function() {
        return this.cell;
    },

    getPosition: function(skipChecks) {
        var me = this,
            position = me.position,
            curIndex,
            view,
            dataSource;

        if (position.record && position.column) {
            // If caller doesn't care whether the record and column is still there,
            // just needs to know about focus
            if (skipChecks) {
                return position;
            }

            view = position.view;
            dataSource = view.dataSource;

            curIndex = dataSource.indexOf(position.record);

            // If not with the same ID, at the same index if that is in range
            if (curIndex === -1) {
                curIndex = position.rowIdx;

                // If no record now at that index (even if it's less than the totalCount,
                // it may be a BufferedStore) then there is no focus position,
                // and we must return null
                if (!(position.record = dataSource.getAt(curIndex))) {
                    curIndex = -1;
                }
            }

            // If the positioned record or column has gone away, we have no position
            if (curIndex === -1 || view.getVisibleColumnManager().indexOf(position.column) === -1) {
                position.setAll();
                me.record = me.column = me.recordIndex = me.columnIndex = null;
            }
            else {
                return position;
            }
        }

        return null;
    },

    getLastFocused: function() {
        var me = this,
            view,
            lastFocused = me.lastFocused;

        if (lastFocused && lastFocused.record && lastFocused.column) {
            view = lastFocused.view;

            // If the last focused record or column has gone away, we have no lastFocused
            if (view.dataSource.indexOf(lastFocused.record) !== -1 &&
                view.getVisibleColumnManager().indexOf(lastFocused.column) !== -1) {
                return lastFocused;
            }
        }
    },

    onKeyTab: function(keyEvent) {
        var forward = !keyEvent.shiftKey,
            view = keyEvent.position.view,
            ret, focusTarget, position;

        ret = view.findFocusPosition(keyEvent.target, keyEvent.position, forward, keyEvent);

        focusTarget = ret.target;
        position = ret.position;

        // We found a focus target either in the cell or in a sibling cell
        // in the direction of navigation.
        if (focusTarget) {
            // Keep actionPosition synched
            this.actionPosition = position.view.actionPosition = position;

            Ext.fly(focusTarget).focus();
        }

        // Focus target not found, we need to exit the row
        else {
            view.onRowExit(
                keyEvent, keyEvent.item,
                keyEvent.item[forward ? 'nextSibling' : 'previousSibling'],
                forward
            );
        }

        // We control navigation when in actionable mode.
        // no TAB events must navigate.
        keyEvent.preventDefault();
    },

    onKeyUp: function(keyEvent) {
        var newRecord = keyEvent.view.walkRecs(keyEvent.record, -1),
            pos = this.getPosition();

        if (newRecord) {
            pos.setRow(newRecord);

            // If no cell at the current column, move towards row start
            if (!pos.getCell(true)) {
                pos.navigate(-1);
            }

            this.setPosition(pos, null, keyEvent);
        }
    },

    onKeyDown: function(keyEvent) {
        // If we are in the middle of an animated node expand, jump to next sibling.
        // The first child record is in a temp animation DIV and will be removed, so will blur.
        // eslint-disable-next-line max-len
        var newRecord = keyEvent.record.isExpandingOrCollapsing ? null : keyEvent.view.walkRecs(keyEvent.record, 1),
            pos = this.getPosition();

        if (newRecord) {
            pos.setRow(newRecord);

            // If no cell at the current column, move towards row start
            if (!pos.getCell(true)) {
                pos.navigate(-1);
            }

            this.setPosition(pos, null, keyEvent);
        }
    },

    onKeyRight: function(keyEvent) {
        var newPosition = this.move('right', keyEvent);

        if (newPosition) {
            this.setPosition(newPosition, null, keyEvent);
        }
    },

    onKeyLeft: function(keyEvent) {
        var newPosition = this.move('left', keyEvent);

        if (newPosition) {
            this.setPosition(newPosition, null, keyEvent);
        }
    },

    // ENTER emulates a dblclick event at the TableView level
    onKeyEnter: function(keyEvent) {
        var eventArgs = [
                'cellclick', keyEvent.view, keyEvent.position.cellElement, keyEvent.position.colIdx,
                keyEvent.record, keyEvent.position.rowElement, keyEvent.recordIndex, keyEvent
            ],
            actionCell = keyEvent.position.getCell(true);

        // May have been deleted by now by an ActionColumn handler
        if (actionCell) {
            // Stop the keydown event so that an ENTER keyup does not get delivered to
            // any element which focus is transferred to in a click handler.
            if (!actionCell.querySelector('[tabIndex="-1"]')) {
                keyEvent.stopEvent();
                keyEvent.view.fireEvent.apply(keyEvent.view, eventArgs);
                eventArgs[0] = 'celldblclick';
                keyEvent.view.fireEvent.apply(keyEvent.view, eventArgs);
            }

            // Enters actionable mode. Unless the emulated evenbts have done it
            if (!this.view.actionableMode) {
                this.view.ownerGrid.setActionableMode(true, this.getPosition());
            }
        }
    },

    onKeyF2: function(keyEvent) {
        // Toggles actionable mode
        var grid = this.view.ownerGrid,
            actionableMode = grid.actionableMode;

        grid.setActionableMode(!actionableMode, actionableMode ? null : this.getPosition());
    },

    onKeyEsc: function(keyEvent) {
        var grid = this.view.ownerGrid;

        // Exits actionable mode
        if (grid.actionableMode) {
            grid.setActionableMode(false);
        }
        // If we are NOT in actionable mode, we must return true so that the event is not stopped.
        // ESC might be consumed at a higher level - for example an encapsulating Window.
        else {
            return true;
        }
    },

    move: function(dir, keyEvent) {
        var me = this,
            position = me.getPosition(),
            result = position,
            rowVeto = keyEvent.shiftKey && (dir === 'right' || dir === 'left');

        if (position && position.record) {
            while (result) {
                // Do not allow SHIFT+(left|right) to wrap.
                // Important to use result.view, since a call to walkCells could change the
                // resulting view if we're using locking.
                result = result.view.walkCells(result, dir, rowVeto ? me.vetoRowChange : null, me);

                // If the new position is fousable, we're done.
                if (result && result.getCell(true) && result.column.cellFocusable !== false) {
                    return result;
                }
            }
        }

        //<debug>
        // Enforce code correctness in unbuilt source.
        return null;
        //</debug>
    },

    vetoRowChange: function(newPosition) {
        return this.getPosition().record === newPosition.record;
    },

    // Go one page down from the lastFocused record in the grid.
    onKeyPageDown: function(keyEvent) {
        var me = this,
            view = keyEvent.view,
            rowsVisible = me.getRowsVisible(),
            newIdx,
            newRecord;

        if (rowsVisible) {
            // If rendering is buffered, we cannot just increment the row - the row may not be there
            // We have to ask the BufferedRenderer to navigate to the target.
            // And that may involve asynchronous I/O, so must post-process in a callback.
            if (view.bufferedRenderer) {
                newIdx = Math.min(keyEvent.recordIndex + rowsVisible,
                                  view.dataSource.getCount() - 1);

                me.lastKeyEvent = keyEvent;

                view.bufferedRenderer.scrollTo(newIdx, false, me.afterBufferedScrollTo, me);
            }
            else {
                newRecord = view.walkRecs(keyEvent.record, rowsVisible);
                me.setPosition(newRecord, null, keyEvent);
            }
        }
    },

    // Go one page up from the lastFocused record in the grid.
    onKeyPageUp: function(keyEvent) {
        var me = this,
            view = keyEvent.view,
            rowsVisible = me.getRowsVisible(),
            newIdx,
            newRecord;

        if (rowsVisible) {
            // If rendering is buffered, we cannot just increment the row - the row may not be there
            // We have to ask the BufferedRenderer to navigate to the target.
            // And that may involve asynchronous I/O, so must post-process in a callback.
            if (view.bufferedRenderer) {
                newIdx = Math.max(keyEvent.recordIndex - rowsVisible, 0);
                me.lastKeyEvent = keyEvent;
                view.bufferedRenderer.scrollTo(newIdx, false, me.afterBufferedScrollTo, me);
            }
            else {
                newRecord = view.walkRecs(keyEvent.record, -rowsVisible);
                me.setPosition(newRecord, null, keyEvent);
            }
        }
    },

    // Home moves the focus to the first cell of the current row.
    onKeyHome: function(keyEvent) {
        var me = this,
            view = keyEvent.view;

        // ALT+Home - go to first visible record in grid.
        if (keyEvent.altKey) {
            if (view.bufferedRenderer) {
                // If rendering is buffered, we cannot just increment the row - the row
                // may not be there
                // We have to ask the BufferedRenderer to navigate to the target.
                // And that may involve asynchronous I/O, so must post-process in a callback.
                me.lastKeyEvent = keyEvent;
                view.bufferedRenderer.scrollTo(0, false, me.afterBufferedScrollTo, me);
            }
            else {
                // Walk forwards to the first record
                me.setPosition(
                    view.walkRecs(keyEvent.record, -view.dataSource.indexOf(keyEvent.record)),
                    null, keyEvent
                );
            }
        }
        // Home moves the focus to the First cell in the current row.
        else {
            me.setPosition(keyEvent.record, 0, keyEvent);
        }
    },

    afterBufferedScrollTo: function(newIdx, newRecord) {
        this.setPosition(newRecord, null, this.lastKeyEvent, null, !this.lastKeyEvent);
    },

    // End moves the focus to the last cell in the current row.
    onKeyEnd: function(keyEvent) {
        var me = this,
            view = keyEvent.view;

        // ALT/End - go to last visible record in grid.
        if (keyEvent.altKey) {
            if (view.bufferedRenderer) {
                // If rendering is buffered, we cannot just increment the row - the row
                // may not be there
                // We have to ask the BufferedRenderer to navigate to the target.
                // And that may involve asynchronous I/O, so must postprocess in a callback.
                me.lastKeyEvent = keyEvent;
                view.bufferedRenderer.scrollTo(
                    view.store.getCount() - 1, false, me.afterBufferedScrollTo, me
                );
            }
            else {
                // Walk forwards to the end record
                me.setPosition(
                    view.walkRecs(
                        keyEvent.record,
                        view.dataSource.getCount() - 1 - view.dataSource.indexOf(keyEvent.record)
                    ),
                    null, keyEvent
                );
            }
        }
        // End moves the focus to the last cell in the current row.
        else {
            me.setPosition(
                keyEvent.record,
                keyEvent.view.getVisibleColumnManager().getColumns().length - 1,
                keyEvent
            );
        }
    },

    // Returns the number of rows currently visible on the screen or
    // false if there were no rows. This assumes that all rows are
    // of the same height and the first view is accurate.
    getRowsVisible: function() {
        var rowsVisible = false,
            view = this.view,
            firstRow = view.all.first(),
            rowHeight, gridViewHeight;

        if (firstRow) {
            rowHeight = firstRow.getHeight();
            gridViewHeight = view.el.getHeight();
            rowsVisible = Math.floor(gridViewHeight / rowHeight);
        }

        return rowsVisible;
    },

    fireNavigateEvent: function(keyEvent) {
        var me = this;

        me.fireEvent('navigate', {
            view: me.position.view,
            navigationModel: me,
            keyEvent: keyEvent || new Ext.event.Event({}),
            previousPosition: me.previousPosition,
            previousRecordIndex: me.previousRecordIndex,
            previousRecord: me.previousRecord,
            previousItem: me.previousItem,
            previousCell: me.previousCell,
            previousColumnIndex: me.previousColumnIndex,
            previousColumn: me.previousColumn,
            position: me.position,
            recordIndex: me.recordIndex,
            record: me.record,
            selectionStart: me.selectionStart,
            item: me.item,
            cell: me.cell,
            columnIndex: me.columnIndex,
            column: me.column
        });
    },

    isFocusableEl: function(el) {
        return Ext.fly(el).isFocusable();
    }
});
