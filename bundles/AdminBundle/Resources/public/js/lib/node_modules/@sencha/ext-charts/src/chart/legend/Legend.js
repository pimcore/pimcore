/**
 * This class provides a dataview-based chart legend.
 */
Ext.define('Ext.chart.legend.Legend', {
    extend: 'Ext.chart.legend.LegendBase',
    alternateClassName: 'Ext.chart.Legend',
    xtype: 'legend',
    alias: 'legend.dom',
    type: 'dom',
    isLegend: true,
    isDomLegend: true,

    config: {
        /**
         * @cfg {Array}
         * The rect of the legend relative to its container.
         */
        rect: null,

        /**
         * @cfg {Boolean} toggleable
         * `true` to allow series items to have their visibility
         * toggled by interaction with the legend items.
         */
        toggleable: true

        /**
         * @cfg {Ext.chart.legend.store.Store} store
         * The {@link Ext.chart.legend.store.Store} to bind this legend to.
         * @private
         */
    },

    baseCls: Ext.baseCSSPrefix + 'legend',

    horizontalCls: Ext.baseCSSPrefix + 'legend-horizontal',
    verticalCls: Ext.baseCSSPrefix + 'legend-vertical',

    toggleItem: function(index) {
        var disabledCount = 0,
            canToggle = true,
            disabled, store, count, record, i;

        if (!this.getToggleable()) {
            return;
        }

        store = this.getStore();

        if (store) {
            count = store.getCount();

            for (i = 0; i < count; i++) {
                record = store.getAt(i);

                if (record.get('disabled')) {
                    disabledCount++;
                }
            }

            canToggle = count - disabledCount > 1;

            record = store.getAt(index);

            if (record) {
                disabled = record.get('disabled');

                if (disabled || canToggle) {
                    // This will trigger AbstractChart.onLegendStoreUpdate.
                    record.set('disabled', !disabled);
                }
            }
        }
    },

    onResize: function(width, height, oldWidth, oldHeight) {
        var me = this,
            chart = me.chart;

        if (!me.isConfiguring) {
            if (chart) {
                chart.scheduleLayout();
            }
        }
    }
});
