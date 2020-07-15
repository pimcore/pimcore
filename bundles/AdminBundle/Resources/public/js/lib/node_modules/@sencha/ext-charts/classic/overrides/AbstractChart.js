/**
 * @class Ext.chart.overrides.AbstractChart
 */
Ext.define('Ext.chart.overrides.AbstractChart', {
    override: 'Ext.chart.AbstractChart',

    updateLegend: function(legend, oldLegend) {
        this.callParent([legend, oldLegend]);

        if (legend && legend.isDomLegend) {
            this.addDocked(legend);
        }
    },

    performLayout: function() {
        if (this.isVisible(true)) {
            return this.callParent();
        }

        this.cancelChartLayout();

        return false;
    },

    afterComponentLayout: function(width, height, oldWidth, oldHeight) {
        this.callParent([width, height, oldWidth, oldHeight]);

        if (!this.hasFirstLayout) {
            this.scheduleLayout();
        }
    },

    allowSchedule: function() {
        return this.rendered;
    },

    doDestroy: function() {
        this.destroyChart();
        this.callParent();
    }

});
