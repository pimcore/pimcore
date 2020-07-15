/**
 * @class Ext.chart.series.sprite.CandleStick
 * @extends Ext.chart.series.sprite.Aggregative
 *
 * CandleStick series sprite.
 */
Ext.define('Ext.chart.series.sprite.CandleStick', {
    alias: 'sprite.candlestickSeries',
    extend: 'Ext.chart.series.sprite.Aggregative',
    inheritableStatics: {
        def: {
            processors: {
                raiseStyle: function(n, o) {
                    return Ext.merge({}, o || {}, n);
                },
                dropStyle: function(n, o) {
                    return Ext.merge({}, o || {}, n);
                },

                /**
                 * @cfg {Number} [barWidth=15] The bar width of the candles.
                 */
                barWidth: 'number',

                /**
                 * @cfg {Number} [padding=3] The amount of padding between candles.
                 */
                padding: 'number',

                /**
                 * @cfg {String} [ohlcType='candlestick'] Determines whether candlestick
                 * or ohlc is used.
                 */
                ohlcType: 'enums(candlestick,ohlc)'
            },
            defaults: {
                raiseStyle: {
                    strokeStyle: 'green',
                    fillStyle: 'green'
                },
                dropStyle: {
                    strokeStyle: 'red',
                    fillStyle: 'red'
                },
                barWidth: 15,
                padding: 3,
                lineJoin: 'miter',
                miterLimit: 5,
                ohlcType: 'candlestick'
            },

            triggers: {
                raiseStyle: 'raiseStyle',
                dropStyle: 'dropStyle'
            },

            updaters: {
                raiseStyle: function() {
                    var me = this,
                        tpl = me.raiseTemplate;

                    if (tpl) {
                        tpl.setAttributes(me.attr.raiseStyle);
                    }
                },
                dropStyle: function() {
                    var me = this,
                        tpl = me.dropTemplate;

                    if (tpl) {
                        tpl.setAttributes(me.attr.dropStyle);
                    }
                }
            }
        }
    },

    candlestick: function(ctx, open, high, low, close, mid, halfWidth) {
        var minOC = Math.min(open, close),
            maxOC = Math.max(open, close);

        // lower stick
        ctx.moveTo(mid, low);
        ctx.lineTo(mid, minOC);

        // body rect
        ctx.moveTo(mid + halfWidth, maxOC);
        ctx.lineTo(mid + halfWidth, minOC);
        ctx.lineTo(mid - halfWidth, minOC);
        ctx.lineTo(mid - halfWidth, maxOC);
        ctx.closePath();

        // upper stick
        ctx.moveTo(mid, high);
        ctx.lineTo(mid, maxOC);
    },

    ohlc: function(ctx, open, high, low, close, mid, halfWidth) {
        ctx.moveTo(mid, high);
        ctx.lineTo(mid, low);
        ctx.moveTo(mid, open);
        ctx.lineTo(mid - halfWidth, open);
        ctx.moveTo(mid, close);
        ctx.lineTo(mid + halfWidth, close);
    },

    constructor: function() {
        var me = this,
            Rect = Ext.draw.sprite.Rect;

        me.callParent(arguments);
        me.raiseTemplate = new Rect({ parent: me });
        me.dropTemplate = new Rect({ parent: me });
    },

    getGapWidth: function() {
        var attr = this.attr,
            barWidth = attr.barWidth,
            padding = attr.padding;

        return barWidth + padding;
    },

    renderAggregates: function(aggregates, start, end, surface, ctx, clip) {
        var me = this,
            attr = me.attr,
            ohlcType = attr.ohlcType,
            series = me.getSeries(),

            matrix = attr.matrix,
            xx = matrix.getXX(),
            yy = matrix.getYY(),
            dx = matrix.getDX(),
            dy = matrix.getDY(),

            halfWidth = Math.round(attr.barWidth * 0.5),

            dataX = attr.dataX,
            opens = aggregates.open,
            closes = aggregates.close,
            maxYs = aggregates.maxY,
            minYs = aggregates.minY,
            startIdxs = aggregates.startIdx,

            pixelAdjust = attr.lineWidth * surface.devicePixelRatio / 2,

            renderer = attr.renderer,
            rendererConfig = renderer && {},
            rendererParams, rendererChanges,
            open, high, low, close, mid,
            i, template;

        me.rendererData = me.rendererData || { store: me.getStore() };
        pixelAdjust -= Math.floor(pixelAdjust);

        // Render raises.
        ctx.save();
        template = me.raiseTemplate;
        template.useAttributes(ctx, clip);

        if (!renderer) {
            ctx.beginPath();
        }

        for (i = start; i < end; i++) {
            if (opens[i] <= closes[i]) {

                open = Math.round(opens[i] * yy + dy) + pixelAdjust;
                high = Math.round(maxYs[i] * yy + dy) + pixelAdjust;
                low = Math.round(minYs[i] * yy + dy) + pixelAdjust;
                close = Math.round(closes[i] * yy + dy) + pixelAdjust;
                mid = Math.round(dataX[startIdxs[i]] * xx + dx) + pixelAdjust;

                if (renderer) {
                    ctx.save();
                    ctx.beginPath();

                    rendererConfig.open = open;
                    rendererConfig.high = high;
                    rendererConfig.low = low;
                    rendererConfig.close = close;
                    rendererConfig.mid = mid;
                    rendererConfig.halfWidth = halfWidth;

                    rendererParams = [me, rendererConfig, me.rendererData, i];
                    rendererChanges = Ext.callback(renderer, null, rendererParams, 0, series);

                    Ext.apply(ctx, rendererChanges);
                }

                me[ohlcType](ctx, open, high, low, close, mid, halfWidth);

                if (renderer) {
                    ctx.fillStroke(template.attr);
                    ctx.restore();
                }
            }
        }

        if (!renderer) {
            ctx.fillStroke(template.attr);
        }

        ctx.restore();

        // Render drops.
        ctx.save();
        template = me.dropTemplate;
        template.useAttributes(ctx, clip);

        if (!renderer) {
            ctx.beginPath();
        }

        for (i = start; i < end; i++) {
            if (opens[i] > closes[i]) {

                open = Math.round(opens[i] * yy + dy) + pixelAdjust;
                high = Math.round(maxYs[i] * yy + dy) + pixelAdjust;
                low = Math.round(minYs[i] * yy + dy) + pixelAdjust;
                close = Math.round(closes[i] * yy + dy) + pixelAdjust;
                mid = Math.round(dataX[startIdxs[i]] * xx + dx) + pixelAdjust;

                if (renderer) {
                    ctx.save();
                    ctx.beginPath();

                    rendererConfig.open = open;
                    rendererConfig.high = high;
                    rendererConfig.low = low;
                    rendererConfig.close = close;
                    rendererConfig.mid = mid;
                    rendererConfig.halfWidth = halfWidth;

                    rendererParams = [me, rendererConfig, me.rendererData, i];
                    rendererChanges =
                        Ext.callback(renderer, null, rendererParams, 0, me.getSeries());
                    Ext.apply(ctx, rendererChanges);
                }

                me[ohlcType](ctx, open, high, low, close, mid, halfWidth);

                if (renderer) {
                    ctx.fillStroke(template.attr);
                    ctx.restore();
                }
            }
        }

        if (!renderer) {
            ctx.fillStroke(template.attr);
        }

        ctx.restore();
    }
});
