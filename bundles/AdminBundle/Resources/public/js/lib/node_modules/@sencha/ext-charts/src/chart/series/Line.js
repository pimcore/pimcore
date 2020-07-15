/**
 * @class Ext.chart.series.Line
 * @extends Ext.chart.series.Cartesian
 *
 * Creates a Line Chart. A Line Chart is a useful visualization technique to display quantitative
 * information for different categories or other real values (as opposed to the bar chart),
 * that can show some progression (or regression) in the dataset.
 * As with all other series, the Line Series must be appended in the *series* Chart array
 * configuration. See the Chart documentation for more information. A typical configuration object
 * for the line series could be:
 *
 *     @example
 *     Ext.create({
 *        xtype: 'cartesian', 
 *        renderTo: document.body,
 *        width: 600,
 *        height: 400,
 *        insetPadding: 40,
 *        store: {
 *            fields: ['name', 'data1', 'data2'],
 *            data: [{
 *                'name': 'metric one',
 *                'data1': 10,
 *                'data2': 14
 *            }, {
 *                'name': 'metric two',
 *                'data1': 7,
 *                'data2': 16
 *            }, {
 *                'name': 'metric three',
 *                'data1': 5,
 *                'data2': 14
 *            }, {
 *                'name': 'metric four',
 *                'data1': 2,
 *                'data2': 6
 *            }, {
 *                'name': 'metric five',
 *                'data1': 27,
 *                'data2': 36
 *            }]
 *        },
 *        axes: [{
 *            type: 'numeric',
 *            position: 'left',
 *            fields: ['data1'],
 *            title: {
 *                text: 'Sample Values',
 *                fontSize: 15
 *            },
 *            grid: true,
 *            minimum: 0
 *        }, {
 *            type: 'category',
 *            position: 'bottom',
 *            fields: ['name'],
 *            title: {
 *                text: 'Sample Values',
 *                fontSize: 15
 *            }
 *        }],
 *        series: [{
 *            type: 'line',
 *            style: {
 *                stroke: '#30BDA7',
 *                lineWidth: 2
 *            },
 *            xField: 'name',
 *            yField: 'data1',
 *            marker: {
 *                type: 'path',
 *                path: ['M', - 4, 0, 0, 4, 4, 0, 0, - 4, 'Z'],
 *                stroke: '#30BDA7',
 *                lineWidth: 2,
 *                fill: 'white'
 *            }
 *        }, {
 *            type: 'line',
 *            fill: true,
 *            style: {
 *                fill: '#96D4C6',
 *                fillOpacity: .6,
 *                stroke: '#0A3F50',
 *                strokeOpacity: .6,
 *            },
 *            xField: 'name',
 *            yField: 'data2',
 *            marker: {
 *                type: 'circle',
 *                radius: 4,
 *                lineWidth: 2,
 *                fill: 'white'
 *            }
 *        }]
 *     });
 *
 * In this configuration we're adding two series (or lines), one bound to the `data1`
 * property of the store and the other to `data3`. The type for both configurations is
 * `line`. The `xField` for both series is the same, the `name` property of the store.
 * Both line series share the same axis, the left axis. You can set particular marker
 * configuration by adding properties onto the marker object. Both series have
 * an object as highlight so that markers animate smoothly to the properties in highlight
 * when hovered. The second series has `fill = true` which means that the line will also
 * have an area below it of the same color.
 *
 * **Note:** In the series definition remember to explicitly set the axis to bind the
 * values of the line series to. This can be done by using the `axis` configuration property.
 */
Ext.define('Ext.chart.series.Line', {
    extend: 'Ext.chart.series.Cartesian',
    alias: 'series.line',
    type: 'line',
    seriesType: 'lineSeries',

    isLine: true,

    requires: [
        'Ext.chart.series.sprite.Line'
    ],

    config: {
        /**
         * @cfg {Number} selectionTolerance
         * The offset distance from the cursor position to the line series to trigger events
         * (then used for highlighting series, etc).
         */
        selectionTolerance: 20,

        /**
         * @cfg {Object} curve
         * The type of curve that connects the data points.
         * Please see {@link Ext.chart.series.sprite.Line#curve line sprite documentation}
         * for the full description.
         */
        curve: {
            type: 'linear'
        },

        /**
         * @cfg {Object} style
         * An object containing styles for the visualization lines. These styles will override
         * the theme styles.
         * Some options contained within the style object will are described next.
         */

        /**
         * @cfg {Boolean} smooth
         * `true` if the series' line should be smoothed.
         * Line smoothing only works with gapless data.
         * @deprecated 6.5.0 Use the {@link #curve} config instead.
         */
        smooth: null,

        /**
         * @cfg {Boolean} step
         * If set to `true`, the line uses steps instead of straight lines to connect the dots.
         * It is ignored if `smooth` is true.
         * @deprecated 6.5.0 Use the {@link #curve} config instead.
         */
        step: null,

        /**
         * @cfg {"gap"/"connect"/"origin"} [nullStyle="gap"]
         * Possible values:
         * 'gap' - null points are rendered as gaps.
         * 'connect' - non-null points are connected across null points, so that
         * there is no gap, unless null points are at the beginning/end of the line.
         * Only the visible data points are connected - if a visible data point
         * is followed by a series of null points that go off screen and eventually
         * terminate with a non-null point, the connection won't be made.
         * 'origin' - null data points are rendered at the origin,
         * which is the y-coordinate of a point where the x and y axes meet.
         * This requires that at least the x-coordinate of a point is a valid value.
         */
        nullStyle: 'gap',

        /**
         * @cfg {Boolean} fill
         * If set to `true`, the area underneath the line is filled with the color defined
         * as follows, listed by priority:
         * - The color that is configured for this series ({@link Ext.chart.series.Series#colors}).
         * - The color that is configured for this chart ({@link Ext.chart.AbstractChart#colors}).
         * - The fill color that is set in the {@link #style} config.
         * - The stroke color that is set in the {@link #style} config, or the same color
         * as the line.
         *
         * Note: Do not confuse `series.config.fill` (which is a boolean) with `series.style.fill'
         * (which is an alias for the `fillStyle` property and contains a color). For compatibility
         * with previous versions of the API, if `config.fill` is undefined but a `style.fill' color
         * is provided, `config.fill` is considered true. So the default value below must be
         * undefined, not false.
         */
        fill: undefined,

        aggregator: { strategy: 'double' }
    },

    themeMarkerCount: function() {
        return 1;
    },

    /**
     * @private
     * Override {@link Ext.chart.series.Series#getDefaultSpriteConfig}
     */
    getDefaultSpriteConfig: function() {
        var me = this,
            parentConfig = me.callParent(arguments),
            style = Ext.apply({}, me.getStyle()),
            styleWithTheme,
            fillArea = false;

        if (me.config.fill !== undefined) {
            // If config.fill is present but there is no fillStyle, then use the
            // strokeStyle to fill (and paint the area the same color as the line).
            if (me.config.fill) {
                fillArea = true;

                if (style.fillStyle === undefined) {
                    if (style.strokeStyle === undefined) {
                        styleWithTheme = me.getStyleWithTheme();
                        style.fillStyle = styleWithTheme.fillStyle;
                        style.strokeStyle = styleWithTheme.strokeStyle;
                    }
                    else {
                        style.fillStyle = style.strokeStyle;
                    }
                }
            }
        }
        else {
            // For compatibility with previous versions of the API, if config.fill
            // is undefined but style.fillStyle is provided, we fill the area.
            if (style.fillStyle) {
                fillArea = true;
            }
        }

        // If we don't fill, then delete the fillStyle because that's what is used by
        // the Line sprite to fill below the line.
        if (!fillArea) {
            delete style.fillStyle;
        }

        style = Ext.apply(parentConfig || {}, style);

        return Ext.apply(style, {
            fillArea: fillArea,
            selectionTolerance: me.config.selectionTolerance
        });
    },

    updateFill: function(fill) {
        this.withSprite(function(sprite) {
            return sprite.setAttributes({ fillArea: fill });
        });
    },

    updateCurve: function(curve) {
        this.withSprite(function(sprite) {
            return sprite.setAttributes({ curve: curve });
        });
    },

    getCurve: function() {
        return this.withSprite(function(sprite) {
            return sprite.attr.curve;
        });
    },

    updateNullStyle: function(nullStyle) {
        this.withSprite(function(sprite) {
            return sprite.setAttributes({ nullStyle: nullStyle });
        });
    },

    updateSmooth: function(smooth) {
        this.setCurve({
            type: smooth ? 'natural' : 'linear'
        });
    },

    updateStep: function(step) {
        this.setCurve({
            type: step ? 'step-after' : 'linear'
        });
    }

});
