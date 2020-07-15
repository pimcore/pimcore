/**
 * The store type used for legend items.
 */
Ext.define('Ext.chart.legend.store.Store', {
    extend: 'Ext.data.Store',
    requires: ['Ext.chart.legend.store.Item'],
    model: 'Ext.chart.legend.store.Item',
    isLegendStore: true,

    config: {
        autoDestroy: true
    }
});
