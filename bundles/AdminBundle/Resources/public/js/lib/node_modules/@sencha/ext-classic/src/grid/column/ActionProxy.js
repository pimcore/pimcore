/**
 * @private
 * A Class which encapsulates individual action items within an ActionColumn and
 * acts as a proxy for various Component methods to allow ActionColumn items to be 
 * manipulated en masse by the {@link Ext.Action}s used to create them.
 */
Ext.define('Ext.grid.column.ActionProxy', {
    constructor: function(column, item, itemIndex) {
        this.column = column;
        this.item = item;
        this.itemIndex = itemIndex;
    },

    setHandler: function(handler) {
        this.item.handler = handler;
    },

    setDisabled: function(disabled) {
        if (disabled) {
            this.column.disableAction(this.itemIndex);
        }
        else {
            this.column.enableAction(this.itemIndex);
        }
    },

    setIconCls: function(iconCls) {
        this.item.iconCls = iconCls;
        this.column.getView().refreshView();
    },

    setIconGlyph: function(glyph) {
        this.item.glyph = glyph;
        this.column.getView().refreshView();
    },

    setHidden: function(hidden) {
        this.item.hidden = hidden;
        this.column.getView().refreshView();
    },

    setVisible: function(visible) {
        this.setHidden(!visible);
    },

    on: function() {
        // Allow the Action to attach its destroy listener.
        return this.column.on.apply(this.column, arguments);
    }
});
