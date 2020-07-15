Ext.define('Ext.chart.overrides.AbstractChart', {
    override: 'Ext.chart.AbstractChart',

    // In Modern toolkit, if chart element style has no z-index specified,
    // some chart surfaces with higher z-indexes (e.g. overlay)
    // may end up on top of modal dialogs shown over the chart.
    zIndex: 0,

    updateLegend: function(legend, oldLegend) {
        this.callParent([legend, oldLegend]);

        if (legend && legend.isDomLegend) {
            this.add(legend);
        }
    },

    onItemRemove: function(item, index, destroy) {
        var map = this.surfaceMap,
            type = item.type,
            items = map && map[type];

        this.callParent([item, index, destroy]);

        if (items) {
            Ext.Array.remove(items, item);

            if (items.length === 0) {
                delete map[type];
            }
        }
    },

    doDestroy: function() {
        this.destroyChart();
        this.callParent();
    }
});
