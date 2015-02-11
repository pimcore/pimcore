/**
 * @class Ext.chart.series.Scatter
 * @extends Ext.chart.series.Cartesian
 *
 * Creates a Scatter Chart. The scatter plot is useful when trying to display more than two variables in the same visualization.
 * These variables can be mapped into x, y coordinates and also to an element's radius/size, color, etc.
 * As with all other series, the Scatter Series must be appended in the *series* Chart array configuration. See the Chart
 * documentation for more information on creating charts. A typical configuration object for the scatter could be:
 *
 *     @example
 *     Ext.create('Ext.Container', {
 *         renderTo: Ext.getBody(),
 *         width: 600,
 *         height: 400,
 *         layout: 'fit',
 *         items: {
 *             xtype: 'cartesian',
 *             store: {
 *               fields: ['name', 'data1', 'data2', 'data3', 'data4', 'data5'],
 *               data: [
 *                   {'name':'metric one', 'data1':10, 'data2':12, 'data3':14, 'data4':8, 'data5':13},
 *                   {'name':'metric two', 'data1':7, 'data2':8, 'data3':16, 'data4':10, 'data5':3},
 *                   {'name':'metric three', 'data1':5, 'data2':2, 'data3':14, 'data4':12, 'data5':7},
 *                   {'name':'metric four', 'data1':2, 'data2':14, 'data3':6, 'data4':1, 'data5':23},
 *                   {'name':'metric five', 'data1':27, 'data2':38, 'data3':36, 'data4':13, 'data5':33}
 *               ]
 *             },
 *             axes: [{
 *                 type: 'numeric',
 *                 position: 'left',
 *                 fields: ['data1'],
 *                 title: {
 *                     text: 'Sample Values',
 *                     fontSize: 15
 *                 },
 *                 grid: true,
 *                 minimum: 0
 *             }, {
 *                 type: 'category',
 *                 position: 'bottom',
 *                 fields: ['name'],
 *                 title: {
 *                     text: 'Sample Values',
 *                     fontSize: 15
 *                 }
 *             }],
 *             series: {
 *                 type: 'scatter',
 *                 highlight: {
 *                     size: 7,
 *                     radius: 7
 *                 },
 *                 fill: true,
 *                 xField: 'name',
 *                 yField: 'data3',
 *                 marker: {
 *                     type: 'circle',
 *                     fillStyle: 'blue',
 *                     radius: 10,
 *                     lineWidth: 0
 *                 }
 *             }
 *         }
 *     });
 *
 * In this configuration we add three different categories of scatter series. Each of them is bound to a different field of the same data store,
 * `data1`, `data2` and `data3` respectively. All x-fields for the series must be the same field, in this case `name`.
 * Each scatter series has a different styling configuration for markers, specified by the `marker` object. Finally we set the left axis as
 * axis to show the current values of the elements.
 *
 */
Ext.define('Ext.chart.series.Scatter', {

    extend: 'Ext.chart.series.Cartesian',

    alias: 'series.scatter',

    type: 'scatter',
    seriesType: 'scatterSeries',

    requires: [
        'Ext.chart.series.sprite.Scatter'
    ],

    config: {
        itemInstancing: {
            fx: {
                customDurations: {
                    translationX: 0,
                    translationY: 0
                }
            }
        }
    },

    themeMarkerCount: function() {
        return 1;
    },

    applyMarker: function (marker, oldMarker) {
        this.getItemInstancing();
        this.setItemInstancing(marker);
        return this.callParent(arguments);
    },

    provideLegendInfo: function (target) {
        var me = this,
            style = me.getMarkerStyleByIndex(0),
            fill = style.fillStyle;

        target.push({
            name: me.getTitle() || me.getYField() || me.getId(),
            mark: (Ext.isObject(fill) ? fill.stops && fill.stops[0].color : fill) || style.strokeStyle || 'black',
            disabled: me.getHidden(),
            series: me.getId(),
            index: 0
        });
    }
});

