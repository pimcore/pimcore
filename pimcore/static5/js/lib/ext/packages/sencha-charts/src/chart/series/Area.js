/**
 * @class Ext.chart.series.Area
 * @extends Ext.chart.series.StackedCartesian
 *
 * Creates an Area Chart.
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
 *                   {name: 'metric one',   data1: 10, data2: 12, data3: 14, data4: 8,  data5: 13},
 *                   {name: 'metric two',   data1: 7,  data2: 8,  data3: 16, data4: 10, data5: 3 },
 *                   {name: 'metric three', data1: 5,  data2: 2,  data3: 14, data4: 12, data5: 7 },
 *                   {name: 'metric four',  data1: 2,  data2: 14, data3: 6,  data4: 1,  data5: 23},
 *                   {name: 'metric five',  data1: 27, data2: 38, data3: 36, data4: 13, data5: 33}
 *               ]
 *             },
 *             axes: [{
 *                 type: 'numeric',
 *                 position: 'left',
 *                 fields: ['data1'],
 *                 grid: true,
 *                 minimum: 0
 *             }, {
 *                 type: 'category',
 *                 position: 'bottom',
 *                 fields: ['name']
 *             }],
 *             series: {
 *                 type: 'area',
 *                 xField: 'name',
 *                 yField: ['data1', 'data2', 'data3']
 *             }
 *         }
 *     });
 */
Ext.define('Ext.chart.series.Area', {

    extend: 'Ext.chart.series.StackedCartesian',

    alias: 'series.area',
    type: 'area',
    seriesType: 'areaSeries',

    requires: ['Ext.chart.series.sprite.Area'],

    config: {
        /**
         * @inheritdoc
         * @cfg {Boolean} [splitStacks=false]
         */
        splitStacks: false
    }
});
