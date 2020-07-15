/**
 * A sprite that represents an individual box with whiskers.
 * This sprite is meant to be managed by the {@link Ext.chart.series.sprite.BoxPlot}
 * {@link Ext.chart.MarkerHolder MarkerHolder}, but can also be used independently:
 *
 *     @example
 *     new Ext.draw.Container({
 *         width: 100,
 *         height: 100,
 *         renderTo: Ext.getBody(),
 *         sprites: [{
 *             type: 'boxplot',
 *             translationX: 50,
 *             translationY: 50
 *         }]
 *     });
 *
 * IMPORTANT: the attributes that represent y-coordinates are in screen coordinates,
 * just like with any other sprite. For this particular sprite this means that, if 'low'
 * and 'high' attributes are 10 and 90, then the minimium whisker is rendered at the top
 * of a draw container {@link Ext.draw.Surface surface} at y = 10, and the maximum whisker
 * is rendered at the bottom at y = 90. But because the series surface is flipped vertically
 * in cartesian charts, this means that there minimum is rendered at the bottom and maximum
 * at the top, just as one would expect.
 */
Ext.define('Ext.chart.sprite.BoxPlot', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'sprite.boxplot',
    type: 'boxplot',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [x=0] The coordinate of the horizontal center of a boxplot.
                 */
                x: 'number',

                /**
                 * @cfg {Number} [low=-20] The y-coordinate of the whisker that represents
                 * the minimum.
                 */
                low: 'number',

                /**
                 * @cfg {Number} [q1=-10] The y-coordinate of the box edge that represents
                 * the 1-st quartile.
                 */
                q1: 'number',

                /**
                 * @cfg {Number} [median=0] The y-coordinate of the line that represents the median.
                 */
                median: 'number',

                /**
                 * @cfg {Number} [q3=10] The y-coordinate of the box edge that represents
                 * the 3-rd quartile.
                 */
                q3: 'number',

                /**
                 * @cfg {Number} [high=20] The y-coordinate of the whisker that represents
                 * the maximum.
                 */
                high: 'number',

                /**
                 * @cfg {Number} [boxWidth=12] The width of the box in pixels.
                 */
                boxWidth: 'number',

                /**
                 * @cfg {Number} [whiskerWidth=0.5] The length of the lines at the ends
                 * of the whiskers, as a ratio of `boxWidth`.
                 */
                whiskerWidth: 'number',

                /**
                 * @cfg {Boolean} [crisp=true] Whether to snap the rendered lines to the pixel grid
                 * of not. Generally, it's best to have this set to `true` (which is the default)
                 * for pixel perfect results (especially on non-HiDPI displays), but for boxplots
                 * with small `boxWidth` visible artifacts caused by pixel grid snapping may become
                 * noticeable, and setting this to `false` can be a remedy at the expense
                 * of clarity.
                 */
                crisp: 'bool'
            },

            triggers: {
                x: 'bbox',
                low: 'bbox',
                high: 'bbox',
                boxWidth: 'bbox',
                whiskerWidth: 'bbox',
                crisp: 'bbox'
            },

            defaults: {
                x: 0,

                low: -20,
                q1: -10,
                median: 0,
                q3: 10,
                high: 20,

                boxWidth: 12,
                whiskerWidth: 0.5,

                crisp: true,

                fillStyle: '#ccc',
                strokeStyle: '#000'
            }
        }
    },

    updatePlainBBox: function(plain) {
        var me = this,
            attr = me.attr,
            halfLineWidth = attr.lineWidth / 2,
            x = attr.x - attr.boxWidth / 2 - halfLineWidth,
            y = attr.high - halfLineWidth,
            width = attr.boxWidth + attr.lineWidth,
            height = attr.low - attr.high + attr.lineWidth;

        plain.x = x;
        plain.y = y;
        plain.width = width;
        plain.height = height;
    },

    render: function(surface, ctx) {
        var me = this,
            attr = me.attr;

        attr.matrix.toContext(ctx); // enable sprite transformations

        if (attr.crisp) {
            me.crispRender(surface, ctx);
        }
        else {
            me.softRender(surface, ctx);
        }

        //<debug>
        // eslint-disable-next-line vars-on-top, one-var
        var debug = attr.debug || this.statics().debug || Ext.draw.sprite.Sprite.debug;

        if (debug) {
            // This assumes no part of the sprite is rendered after this call.
            // If it is, we need to re-apply transformations.
            // But the bounding box should always be rendered as is, untransformed.
            this.attr.inverseMatrix.toContext(ctx);

            if (debug.bbox) {
                this.renderBBox(surface, ctx);
            }
        }
        //</debug>
    },

    /**
     * @private
     * Renders a single box with whiskers.
     * Changes to this method have to be reflected in the {@link #crispRender} as well.
     * @param surface
     * @param ctx
     */
    softRender: function(surface, ctx) {
        var me = this,
            attr = me.attr,

            x = attr.x,
            low = attr.low,
            q1 = attr.q1,
            median = attr.median,
            q3 = attr.q3,
            high = attr.high,

            halfBoxWidth = attr.boxWidth / 2,
            halfWhiskerWidth = attr.boxWidth * attr.whiskerWidth / 2,

            dash = ctx.getLineDash();

        ctx.setLineDash([]); // Only stem can be dashed.

        // Box.
        ctx.beginPath();
        ctx.moveTo(x - halfBoxWidth, q3);
        ctx.lineTo(x + halfBoxWidth, q3);
        ctx.lineTo(x + halfBoxWidth, q1);
        ctx.lineTo(x - halfBoxWidth, q1);
        ctx.closePath();
        ctx.fillStroke(attr, true);

        // Stem.
        ctx.setLineDash(dash);
        ctx.beginPath();
        ctx.moveTo(x, q3);
        ctx.lineTo(x, high);
        ctx.moveTo(x, q1);
        ctx.lineTo(x, low);
        ctx.stroke();
        ctx.setLineDash([]);

        // Whiskers.
        ctx.beginPath();
        ctx.moveTo(x - halfWhiskerWidth, low);
        ctx.lineTo(x + halfWhiskerWidth, low);
        ctx.moveTo(x - halfBoxWidth, median);
        ctx.lineTo(x + halfBoxWidth, median);
        ctx.moveTo(x - halfWhiskerWidth, high);
        ctx.lineTo(x + halfWhiskerWidth, high);
        ctx.stroke();
    },

    alignLine: function(x, lineWidth) {
        lineWidth = lineWidth || this.attr.lineWidth;

        x = Math.round(x);

        if (lineWidth % 2 === 1) {
            x -= 0.5;
        }

        return x;
    },

    /**
     * @private
     * Renders a pixel-perfect single box with whiskers by aligning to the pixel grid.
     * Changes to this method have to be reflected in the {@link #softRender} as well.
     *
     * Note: crisp image is only guaranteed when `attr.lineWidth` is a whole number.
     * @param surface
     * @param ctx
     */
    crispRender: function(surface, ctx) {
        var me = this,
            attr = me.attr,

            x = attr.x,
            low = me.alignLine(attr.low),
            q1 = me.alignLine(attr.q1),
            median = me.alignLine(attr.median),
            q3 = me.alignLine(attr.q3),
            high = me.alignLine(attr.high),

            halfBoxWidth = attr.boxWidth / 2,
            halfWhiskerWidth = attr.boxWidth * attr.whiskerWidth / 2,
            stemX = me.alignLine(x),
            boxLeft = me.alignLine(x - halfBoxWidth),
            boxRight = me.alignLine(x + halfBoxWidth),
            whiskerLeft = stemX + Math.round(-halfWhiskerWidth),
            whiskerRight = stemX + Math.round(halfWhiskerWidth),
            dash = ctx.getLineDash();

        ctx.setLineDash([]); // Only stem can be dashed.

        // Box.
        ctx.beginPath();
        ctx.moveTo(boxLeft, q3);
        ctx.lineTo(boxRight, q3);
        ctx.lineTo(boxRight, q1);
        ctx.lineTo(boxLeft, q1);
        ctx.closePath();
        ctx.fillStroke(attr, true);

        // Stem.
        ctx.setLineDash(dash);
        ctx.beginPath();
        ctx.moveTo(stemX, q3);
        ctx.lineTo(stemX, high);
        ctx.moveTo(stemX, q1);
        ctx.lineTo(stemX, low);
        ctx.stroke();
        ctx.setLineDash([]);

        // Whiskers.
        ctx.beginPath();
        ctx.moveTo(whiskerLeft, low);
        ctx.lineTo(whiskerRight, low);
        ctx.moveTo(boxLeft, median);
        ctx.lineTo(boxRight, median);
        ctx.moveTo(whiskerLeft, high);
        ctx.lineTo(whiskerRight, high);
        ctx.stroke();
    }
});
