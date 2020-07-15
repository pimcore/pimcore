/**
 * BoxPlot series sprite that manages {@link Ext.chart.sprite.BoxPlot} instances.
 */
Ext.define('Ext.chart.series.sprite.BoxPlot', {
    alias: 'sprite.boxplotSeries',
    extend: 'Ext.chart.series.sprite.Cartesian',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number[]} [dataLow=null] Array of coordinated minimum values.
                 */
                dataLow: 'data',

                /**
                 * @cfg {Number[]} [dataQ1=null] Array of coordinated 1-st quartile values.
                 */
                dataQ1: 'data',

                /**
                 * @cfg {Number[]} [dataQ3=null] Array of coordinated 3-rd quartile values.
                 */
                dataQ3: 'data',

                /**
                 * @cfg {Number[]} [dataHigh=null] Array of coordinated maximum values.
                 */
                dataHigh: 'data',

                /**
                 * @cfg {Number} [minBoxWidth=2] The minimum box width.
                 */
                minBoxWidth: 'number',

                /**
                 * @cfg {Number} [maxBoxWidth=20] The maximum box width.
                 */
                maxBoxWidth: 'number',

                /**
                 * @cfg {Number} [minGapWidth=5] The minimum gap between boxes.
                 */
                minGapWidth: 'number'
            },
            aliases: {
                /**
                 * The `dataMedian` attribute can be used to set the value of
                 * the `dataY` attribute. E.g.:
                 *
                 *     sprite.setAttributes({
                 *         dataMedian: [...]
                 *     });
                 *
                 * To fetch the value of the attribute one has to use
                 *
                 *     sprite.attr.dataY // array of coordinated median values
                 *
                 * and not
                 *
                 *     sprite.attr.dataMedian // WRONG!
                 *
                 * `dataY` attribute is defined by the `Ext.chart.series.sprite.Series`.
                 *
                 * @cfg {Number[]} [dataMedian=null] Array of coordinated median values.
                 */
                dataMedian: 'dataY'
            },
            defaults: {
                minBoxWidth: 2,
                maxBoxWidth: 40,
                minGapWidth: 5
            }
        }
    },

    renderClipped: function(surface, ctx, dataClipRect) {
        if (this.cleanRedraw) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            attr = me.attr,
            series = me.getSeries(),
            renderer = attr.renderer,
            rendererData = { store: me.getStore() },
            itemCfg = {},

            dataX = attr.dataX,
            dataLow = attr.dataLow,
            dataQ1 = attr.dataQ1,
            dataMedian = attr.dataY,
            dataQ3 = attr.dataQ3,
            dataHigh = attr.dataHigh,

            min = Math.min(dataClipRect[0], dataClipRect[2]),
            max = Math.max(dataClipRect[0], dataClipRect[2]),
            start = Math.max(0, Math.floor(min)),
            end = Math.min(dataX.length - 1, Math.ceil(max)),

            // surfaceMatrix = me.surfaceMatrix,

            matrix = attr.matrix,
            xx = matrix.elements[0], // horizontal scaling can be < 0, if RTL
            yy = matrix.elements[3],
            dx = matrix.elements[4],
            dy = matrix.elements[5],

            // `xx` essentially represents the distance between data points in surface coordinates.
            maxBoxWidth = Math.abs(xx) - attr.minGapWidth,
            minBoxWidth = Math.min(maxBoxWidth, attr.maxBoxWidth),
            boxWidth = Math.round(Math.max(attr.minBoxWidth, minBoxWidth)),

            x, low, q1, median, q3, high,
            rendererParams, changes,
            i;

        if (renderer) {
            rendererParams = [me, itemCfg, rendererData];
        }

        for (i = start; i <= end; i++) {

            x = dataX[i] * xx + dx;
            low = dataLow[i] * yy + dy;
            q1 = dataQ1[i] * yy + dy;
            median = dataMedian[i] * yy + dy;
            q3 = dataQ3[i] * yy + dy;
            high = dataHigh[i] * yy + dy;

            // --- Draw Box ---

            // Reuse 'itemCfg' object and 'rendererParams' arrays for better performance.

            itemCfg.x = x;
            itemCfg.low = low;
            itemCfg.q1 = q1;
            itemCfg.median = median;
            itemCfg.q3 = q3;
            itemCfg.high = high;

            itemCfg.boxWidth = boxWidth;

            if (renderer) {
                rendererParams[3] = i;
                changes = Ext.callback(renderer, null, rendererParams, 0, series);
                Ext.apply(itemCfg, changes);
            }

            me.putMarker('items', itemCfg, i, !renderer);
        }
    }
});
