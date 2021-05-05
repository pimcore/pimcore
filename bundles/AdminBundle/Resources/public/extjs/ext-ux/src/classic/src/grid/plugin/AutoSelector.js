/**
 * This plugin ensures that its associated grid or tree always has a selection record. The
 * only exception is, of course, when there are no records in the store.
 * @since 6.0.2
 */
Ext.define('Ext.ux.grid.plugin.AutoSelector', {
    extend: 'Ext.plugin.Abstract',

    alias: 'plugin.gridautoselector',

    config: {
        store: null
    },

    init: function(grid) {
        var me = this;

        //<debug>
        if (!grid.isXType('tablepanel')) {
            Ext.raise('The gridautoselector plugin is designed only for grids and trees');
        }
        //</debug>

        me.grid = grid;

        me.watchGrid();

        grid.on({
            reconfigure: me.watchGrid,
            scope: me
        });
    },

    destroy: function() {
        this.setStore(null);
        this.grid = null;

        this.callParent();
    },

    ensureSelection: function() {
        var grid = this.grid,
            store = grid.getStore(),
            selection;

        if (store.getCount()) {
            selection = grid.getSelection();

            if (!selection || !selection.length) {
                grid.getSelectionModel().select(0);
            }
        }
    },

    watchGrid: function() {
        this.setStore(this.grid.getStore());
        this.ensureSelection();
    },

    updateStore: function(store) {
        var me = this;

        Ext.destroy(me.storeListeners);

        me.storeListeners = store && store.on({
            // We could go from 0 records to 1+ records... now we can select one!
            add: me.ensureSelection,
            // We might remove the selected record...
            remove: me.ensureSelection,

            destroyable: true,
            scope: me
        });
    }
});
