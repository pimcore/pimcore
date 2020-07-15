/**
 *
 */
Ext.define('Ext.chart.navigator.NavigatorBase', {
    extend: 'Ext.chart.CartesianChart',

    initialize: function() {
        var me = this;

        me.callParent();
        me.setupEvents();
    }

});
