/**
 *
 */
Ext.define('Ext.chart.series.sprite.Aggregative', {
    extend: 'Ext.chart.series.sprite.Cartesian',
    requires: [
        'Ext.draw.LimitedCache',
        'Ext.draw.SegmentTree'
    ],
    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number[]} [dataHigh=null] Data items representing the high values
                 * of the aggregated data.
                 */
                dataHigh: 'data',

                /**
                 * @cfg {Number[]} [dataLow=null] Data items representing the low values
                 * of the aggregated data.
                 */
                dataLow: 'data',

                /**
                 * @cfg {Number[]} [dataClose=null] Data items representing the closing values
                 * of the aggregated data.
                 */
                dataClose: 'data'
            },
            aliases: {
                /**
                 * @cfg {Number[]} [dataOpen=null] Data items representing the opening values
                 * of the aggregated data.
                 */
                dataOpen: 'dataY'
            },
            defaults: {
                dataHigh: null,
                dataLow: null,
                dataClose: null
            }
        }
    },

    config: {
        aggregator: {}
    },

    applyAggregator: function(aggregator, oldAggr) {
        return Ext.factory(aggregator, Ext.draw.SegmentTree, oldAggr);
    },

    constructor: function() {
        this.callParent(arguments);
    },

    processDataY: function() {
        var me = this,
            attr = me.attr,
            high = attr.dataHigh,
            low = attr.dataLow,
            close = attr.dataClose,
            open = attr.dataY,
            aggregator;

        me.callParent(arguments);

        if (attr.dataX && open && open.length > 0) {
            aggregator = me.getAggregator();

            if (high) {
                aggregator.setData(attr.dataX, attr.dataY, high, low, close);
            }
            else {
                aggregator.setData(attr.dataX, attr.dataY);
            }
        }
    },

    getGapWidth: function() {
        return 1;
    },

    renderClipped: function(surface, ctx, dataClipRect, surfaceClipRect) {
        var me = this,
            min = Math.min(dataClipRect[0], dataClipRect[2]),
            max = Math.max(dataClipRect[0], dataClipRect[2]),
            aggregator = me.getAggregator(),
            aggregates = aggregator && aggregator.getAggregation(
                min, max, (max - min) / surfaceClipRect[2] * me.getGapWidth()
            );

        if (aggregates) {
            me.dataStart = aggregates.data.startIdx[aggregates.start];
            me.dataEnd = aggregates.data.endIdx[aggregates.end - 1];

            me.renderAggregates(aggregates.data, aggregates.start, aggregates.end,
                                surface, ctx, dataClipRect, surfaceClipRect);
        }
    }
});
