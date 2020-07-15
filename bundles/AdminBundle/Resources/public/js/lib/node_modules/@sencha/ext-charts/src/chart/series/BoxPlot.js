/**
 * A box plot chart is a useful tool for visializing data distribution within datasets.
 * For example, salary ranges for a set of occupations, or life expectancy for a set
 * of countries. A single box with whiskers displays the following values for a dataset:
 *
 * * minimum
 * * lower quartile (Q1)
 * * median (Q2)
 * * higher quartile (Q3)
 * * maximum
 *
 * For example:
 *
 *     @example
 *     Ext.create({
 *        xtype: 'cartesian',
 *        width: 400,
 *        height: 400,
 *        renderTo: Ext.getBody(),
 *        insetPadding: '20 20 10 10',
 *        store: {
 *            data: [{
 *                category: 'Engineer IV',
 *                low: 110, q1: 130, median: 175, q3: 200, high: 225
 *            }, {
 *                category: 'Market',
 *                low: 75, q1: 125, median: 210, q3: 230, high: 255
 *            }]
 *        },
 *        axes: [
 *            {
 *                type: 'numeric',
 *                position: 'left',
 *                renderer: function (axis, text) {
 *                    return '$' + text + ' K'
 *                }
 *            },
 *            {
 *                type: 'category',
 *                position: 'bottom'
 *            }
 *        ],
 *        series: {
 *            type: 'boxplot',
 *            xField: 'category',
 *            style: {
 *                maxBoxWidth: 50,
 *                lineWidth: 2
 *            }
 *        }
 *     });
 *
 */
Ext.define('Ext.chart.series.BoxPlot', {

    extend: 'Ext.chart.series.Cartesian',

    alias: 'series.boxplot',
    type: 'boxplot',
    seriesType: 'boxplotSeries',
    isBoxPlot: true,

    requires: [
        'Ext.chart.series.sprite.BoxPlot',
        'Ext.chart.sprite.BoxPlot'
    ],

    config: {

        itemInstancing: {
            type: 'boxplot',
            animation: {
                // Setting the duration of these attributes to zero because
                // the 'data' attributes of the series sprite (MarkerHolder)
                // will be animated instead, and then changes applied to
                // the attributes of 'boxplot' instances instantly.
                customDurations: {
                    x: 0,
                    low: 0,
                    q1: 0,
                    median: 0,
                    q3: 0,
                    high: 0
                }
            }
        },

        /**
         * @cfg {String} [lowField='low']
         * The name of the store record field that represents the smallest value of a dataset.
         */
        lowField: 'low',

        /**
         * @cfg {String} [q1Field='q1']
         * The name of the store record field that represents the lower (1-st) quartile
         * value of a dataset.
         */
        q1Field: 'q1',

        /**
         * @cfg {String} [medianField='median']
         * The name of the store record field that represents the median of a dataset.
         */
        medianField: 'median',

        /**
         * @cfg {String} [q3Field='q3']
         * The name of the store record field that represents the upper (3-rd) quartile
         * value of a dataset.
         */
        q3Field: 'q3',

        /**
         * @cfg {String} [highField='high']
         * The name of the store record field that represents the largest value of a dataset.
         */
        highField: 'high'
    },

    fieldCategoryY: ['Low', 'Q1', 'Median', 'Q3', 'High'],

    updateXAxis: function(xAxis) {
        xAxis.setExpandRangeBy(0.5);
        this.callParent(arguments);
    }
});
