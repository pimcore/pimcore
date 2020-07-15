/**
 * Private class which acts as a HeaderContainer for the Lockable which aggregates all columns
 * from both sides of the Lockable. It is never rendered, it's just used to interrogate the
 * column collection.
 * @private
 */
Ext.define('Ext.grid.locking.HeaderContainer', {
    extend: 'Ext.grid.header.Container',

    requires: [
        'Ext.grid.ColumnManager'
    ],

    headerCtRelayEvents: [
        "blur",
        "focus",
        "move",
        "resize",
        "destroy",
        "beforedestroy",
        "boxready",
        "afterrender",
        "render",
        "beforerender",
        "removed",
        "hide",
        "beforehide",
        "show",
        "beforeshow",
        "enable",
        "disable",
        "added",
        "deactivate",
        "beforedeactivate",
        "activate",
        "beforeactivate",
        "remove",
        "add",
        "beforeremove",
        "beforeadd",
        "afterlayout",
        "menucreate",
        "sortchange",
        "columnschanged",
        "columnshow",
        "columnhide",
        "columnmove",
        "headertriggerclick",
        "headercontextmenu",
        "headerclick",
        "columnresize",
        "statesave",
        "beforestatesave",
        "staterestore",
        "beforestaterestore"
    ],

    constructor: function(lockable) {
        var me = this,
            lockedGrid = lockable.lockedGrid,
            normalGrid = lockable.normalGrid;

        me.lockable = lockable;
        me.callParent();

        // Create the unified column manager for the lockable grid assembly
        lockedGrid.visibleColumnManager.rootColumns =
            normalGrid.visibleColumnManager.rootColumns =
            lockable.visibleColumnManager =
            me.visibleColumnManager =
                new Ext.grid.ColumnManager(true, lockedGrid.headerCt, normalGrid.headerCt);

        lockedGrid.columnManager.rootColumns =
            normalGrid.columnManager.rootColumns =
            lockable.columnManager =
            me.columnManager =
                new Ext.grid.ColumnManager(false, lockedGrid.headerCt, normalGrid.headerCt);

        // Relay *all* events from the two HeaderContainers
        me.lockedEventRelayers = me.relayEvents(lockedGrid.headerCt, me.headerCtRelayEvents);
        me.normalEventRelayers = me.relayEvents(normalGrid.headerCt, me.headerCtRelayEvents);
    },

    getRefItems: function() {
        return this.lockable.lockedGrid.headerCt.getRefItems().concat(
            this.lockable.normalGrid.headerCt.getRefItems()
        );
    },

    // This is the function which all other column access methods are based upon
    // Return the full column set for the whole Lockable assembly
    getGridColumns: function() {
        return this.lockable.lockedGrid.headerCt.getGridColumns().concat(
            this.lockable.normalGrid.headerCt.getGridColumns()
        );
    },

    // Lockable uses its headerCt to gather column state
    getColumnsState: function() {
        var me = this,
            locked = me.lockable.lockedGrid.headerCt.getColumnsState(),
            normal = me.lockable.normalGrid.headerCt.getColumnsState();

        return locked.concat(normal);
    },

    // Lockable uses its headerCt to apply column state
    applyColumnsState: function(columnsState, storeState) {
        var me = this,
            lockedGrid = me.lockable.lockedGrid,
            normalGrid = me.lockable.normalGrid,
            lockedHeaderCt = lockedGrid.headerCt,
            normalHeaderCt = me.lockable.normalGrid.headerCt,
            columns = lockedHeaderCt.items.items.concat(normalHeaderCt.items.items),
            length = columns.length,
            i, colState, column, lockedCount, switchSides;

        // Loop through the column set, applying state from the columnsState object.
        // Columns which have their "locked" property changed must be added to the appropriate
        // headerCt.
        for (i = 0; i < length; i++) {
            column = columns[i];
            colState = columnsState[column.getStateId()];

            if (colState) {
                // See if the state being applied needs to cause column movement
                // Coerce possibly absent locked config to boolean.
                switchSides = colState.locked != null && !!column.locked !== colState.locked;

                if (column.applyColumnState) {
                    column.applyColumnState(colState, storeState);
                }

                // If the column state means it has to change sides
                // move the column to the other side
                if (switchSides) {
                    (column.locked ? lockedHeaderCt : normalHeaderCt).add(column);
                }
            }
        }

        lockedCount = lockedHeaderCt.items.items.length;

        // We must now restore state in each side's HeaderContainer.
        // This means passing the state down into each side's applyColumnState
        // to get sortable, hidden and width states restored.
        // We must ensure that the index on the normal side is zero based.
        for (i = 0; i < length; i++) {
            column = columns[i];
            colState = columnsState[column.getStateId()];

            if (colState && !column.locked) {
                colState.index = Math.max(0, colState.index - lockedCount);
            }
        }

        // Each side must apply individual column's state
        lockedHeaderCt.applyColumnsState(columnsState, storeState);
        normalHeaderCt.applyColumnsState(columnsState, storeState);

        // Account for columns being hidden or moved by state application.
        if (!lockedGrid.getVisibleColumnManager().getColumns().length) {
            lockedGrid.hide();
        }

        if (!normalGrid.getVisibleColumnManager().getColumns().length) {
            normalGrid.hide();
        }
    },

    disable: function() {
        var topGrid = this.lockable;

        topGrid.lockedGrid.headerCt.disable();
        topGrid.normalGrid.headerCt.disable();
    },

    enable: function() {
        var topGrid = this.lockable;

        topGrid.lockedGrid.headerCt.enable();
        topGrid.normalGrid.headerCt.enable();
    }
});
