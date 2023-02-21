/**
 * This class is used as a grid `plugin`. It provides a DropZone which cooperates with
 * DragZones whose dragData contains a "field" property representing a form Field.
 * Fields may be dropped onto grid data cells containing a matching data type.
 */
Ext.define('Ext.ux.dd.CellFieldDropZone', {
    /* eslint-disable vars-on-top */
    extend: 'Ext.dd.DropZone',
    alias: 'plugin.ux-cellfielddropzone',

    containerScroll: true,

    /**
     * @cfg {Function/String} onCellDrop
     * The function to call on a cell data drop, or the name of the function on the
     * corresponding `{@link Ext.app.ViewController controller}`. For details on the
     * parameters, see `{@link #method!onCellDrop onCellDrop}`.
     */

    /**
     * This method is called when a field is dropped on a cell. This method is normally
     * replaced by the `{@link #cfg!onCellDrop onCellDrop}` config property passed to the
     * constructor.
     * @param {String} fieldName The name of the field.
     * @param {Mixed} value The value of the field.
     * @method onCellDrop
     */
    onCellDrop: Ext.emptyFn,

    constructor: function(cfg) {
        if (cfg) {
            var me = this,
                ddGroup = cfg.ddGroup,
                onCellDrop = cfg.onCellDrop;

            if (onCellDrop) {
                if (typeof onCellDrop === 'string') {
                    me.onCellDropFn = onCellDrop;
                    me.onCellDrop = me.callCellDrop;
                }
                else {
                    me.onCellDrop = onCellDrop;
                }
            }

            if (ddGroup) {
                me.ddGroup = ddGroup;
            }
        }
    },

    init: function(grid) {
        var me = this;

        // Call the DropZone constructor using the View's scrolling element
        // only after the grid has been rendered.
        if (grid.rendered) {
            me.grid = grid;
            grid.getView().on({
                render: function(v) {
                    me.view = v;
                    Ext.ux.dd.CellFieldDropZone.superclass.constructor.call(me, me.view.el);
                },
                single: true
            });
        }
        else {
            grid.on('render', me.init, me, { single: true });
        }
    },

    getTargetFromEvent: function(e) {
        var me = this,
            v = me.view,

            // Ascertain whether the mousemove is within a grid cell
            cell = e.getTarget(v.getCellSelector());

        if (cell) {
            // We *are* within a grid cell, so ask the View exactly which one,
            // Extract data from the Model to create a target object for
            // processing in subsequent onNodeXXXX methods. Note that the target does
            // not have to be a DOM element. It can be whatever the noNodeXXX methods are
            // programmed to expect.
            var row = v.findItemByChild(cell),
                columnIndex = cell.cellIndex;

            if (row && Ext.isDefined(columnIndex)) {
                return {
                    node: cell,
                    record: v.getRecord(row),
                    fieldName: me.grid.getVisibleColumnManager().getColumns()[columnIndex].dataIndex
                };
            }
        }
    },

    onNodeEnter: function(target, dd, e, dragData) {
        // On Node enter, see if it is valid for us to drop the field on that type of
        // column.
        delete this.dropOK;

        if (!target) {
            return;
        }

        // Check that a field is being dragged.
        var f = dragData.field;

        if (!f) {
            return;
        }

        // Check whether the data type of the column being dropped on accepts the
        // dragged field type. If so, set dropOK flag, and highlight the target node.
        var field = target.record.fieldsMap[target.fieldName];

        if (field.isNumeric) {
            if (!f.isXType('numberfield')) {
                return;
            }
        }
        else if (field.isDateField) {
            if (!f.isXType('datefield')) {
                return;
            }
        }
        else if (field.isBooleanField) {
            if (!f.isXType('checkbox')) {
                return;
            }
        }

        this.dropOK = true;
        Ext.fly(target.node).addCls('x-drop-target-active');
    },

    onNodeOver: function(target, dd, e, dragData) {
        // Return the class name to add to the drag proxy. This provides a visual
        // indication of drop allowed or not allowed.
        return this.dropOK ? this.dropAllowed : this.dropNotAllowed;
    },

    onNodeOut: function(target, dd, e, dragData) {
        Ext.fly(target.node).removeCls('x-drop-target-active');
    },

    onNodeDrop: function(target, dd, e, dragData) {
        // Process the drop event if we have previously ascertained that a drop is OK.
        if (this.dropOK) {
            var value = dragData.field.getValue();

            target.record.set(target.fieldName, value);
            this.onCellDrop(target.fieldName, value);

            return true;
        }
    },

    callCellDrop: function(fieldName, value) {
        Ext.callback(this.onCellDropFn, null, [fieldName, value], 0, this.grid);
    }
});
