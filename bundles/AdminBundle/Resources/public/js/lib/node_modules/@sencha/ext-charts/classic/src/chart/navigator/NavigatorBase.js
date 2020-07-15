/**
 *
 */
Ext.define('Ext.chart.navigator.NavigatorBase', {
    extend: 'Ext.chart.CartesianChart',

    onRender: function() {
        this.callParent();
        this.setupEvents();
    },

    // Note: 'applyDock' and 'updateDock' won't ever be called in Classic.
    // See the Classic Component's 'setDock' method, which is overridden here.
    setDocked: function(docked) {
        var me = this,
            ownerCt = me.getNavigatorContainer();

        if (!(docked === 'top' || docked === 'bottom')) {
            Ext.raise("Can only dock to 'top' or 'bottom'.");
        }

        if (docked !== me.dock) {
            if (ownerCt && ownerCt.moveDocked) {
                ownerCt.moveDocked(me, docked);
            }
            else {
                me.dock = docked;
            }
        }

        return me;
    },

    getDocked: function() {
        return this.dock;
    }

});
