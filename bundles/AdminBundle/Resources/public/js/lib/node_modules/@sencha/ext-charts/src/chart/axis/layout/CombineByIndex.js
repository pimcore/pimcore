/**
 * Discrete layout that combines duplicate data points only if they have the same index.
 * For example:
 *
 *     @example
 *     Ext.create({
 *         xtype: 'cartesian',
 *         title: 'Weight vs Calories',
 *
 *         renderTo: document.body,
 *         width: 400,
 *         height: 400,
 *
 *         store: {
 *              fields: ['month', 'weight', 'calories'],
 *              data: [
 *                  {
 *                      month: 'Jan',
 *                      weight: 185,
 *                      calories: 2650
 *                  },
 *                  {
 *                      month: 'Jan',
 *                      weight: 188,
 *                      calories: 2800
 *                  },
 *                  {
 *                      month: 'Feb',
 *                      weight: 188,
 *                      calories: 2800
 *                  },
 *                  {
 *                      month: 'Mar',
 *                      weight: 191,
 *                      calories: 2800
 *                  },
 *                  {
 *                      month: 'Apr',
 *                      weight: 189,
 *                      calories: 1500
 *                  },
 *                  {
 *                      month: 'May',
 *                      weight: 187,
 *                      calories: 1350
 *                  }
 *              ]
 *         },
 *
 *         axes: [{
 *             type: 'numeric',
 *             position: 'left',
 *             fields: ['weight'],
 *             minimum: 140
 *         }, {
 *             type: 'numeric',
 *             position: 'right',
 *             fields: ['calories'],
 *             minimum: 500,
 *             maximum: 3500
 *         }, {
 *             type: 'category',
 *             grid: true,
 *             layout: 'combineByIndex',
 *             fields: 'month',
 *             position: 'bottom',
 *             label: {
 *                 rotate: {
 *                     degrees: -45
 *                 }
 *             }
 *         }],
 *
 *         series: [{
 *             type: 'line',
 *             title: 'Weight',
 *             xField: 'month',
 *             yField: 'weight',
 *             smooth: true,
 *             marker: true
 *         }, {
 *             type: 'line',
 *             title: 'Calories',
 *             xField: 'month',
 *             yField: 'calories',
 *             smooth: true,
 *             marker: true
 *         }],
 *
 *         legend: {
 *             docked: 'bottom'
 *         }
 *
 *     });
 *
 * @since 6.5.0
 */
Ext.define('Ext.chart.axis.layout.CombineByIndex', {
    extend: 'Ext.chart.axis.layout.Discrete',
    alias: 'axisLayout.combineByIndex',

    getCoordFor: function(value, field, idx, items) {
        var labels = this.labels,
            result = idx;

        if (labels[idx] !== value) {
            result = labels.push(value) - 1;
        }

        return result;
    }
});
