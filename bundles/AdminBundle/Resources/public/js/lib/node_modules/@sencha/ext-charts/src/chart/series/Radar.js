/**
 * @class Ext.chart.series.Radar
 * @extends Ext.chart.series.Polar
 *
 * Creates a Radar Chart. A Radar Chart is a useful visualization technique for comparing different
 * quantitative values for a constrained number of categories.
 * As with all other series, the Radar series must be appended in the *series* Chart array
 * configuration. See the Chart documentation for more information. A typical configuration object
 * for the radar series could be:
 *
 *     @example
 *     Ext.create({
 *        xtype: 'polar',
 *        renderTo: document.body,
 *        width: 500,
 *        height: 400,
 *        interactions: 'rotate',
 *        store: {
 *            fields: ['name', 'data1'],
 *            data: [{
 *                'name': 'metric one',
 *                'data1': 8
 *            }, {
 *                'name': 'metric two',
 *                'data1': 10
 *            }, {
 *                'name': 'metric three',
 *                'data1': 12
 *            }, {
 *                'name': 'metric four',
 *                'data1': 1
 *            }, {
 *                'name': 'metric five',
 *                'data1': 13
 *            }]
 *        },
 *        series: {
 *            type: 'radar',
 *            angleField: 'name',
 *            radiusField: 'data1',
 *            style: {
 *                fillStyle: '#388FAD',
 *                fillOpacity: .1,
 *                strokeStyle: '#388FAD',
 *                strokeOpacity: .8,
 *                lineWidth: 1
 *            }
 *        },
 *        axes: [{
 *            type: 'numeric',
 *            position: 'radial',
 *            fields: 'data1',
 *            style: {
 *                estStepSize: 10
 *            },
 *            grid: true
 *        }, {
 *            type: 'category',
 *            position: 'angular',
 *            fields: 'name',
 *            style: {
 *                estStepSize: 1
 *            },
 *            grid: true
 *        }]
 *     });
 *
 */
Ext.define('Ext.chart.series.Radar', {
    extend: 'Ext.chart.series.Polar',
    type: 'radar',
    seriesType: 'radar',
    alias: 'series.radar',
    requires: ['Ext.chart.series.sprite.Radar'],

    themeColorCount: function() {
        return 1;
    },

    isStoreDependantColorCount: false,

    themeMarkerCount: function() {
        return 1;
    },

    updateAngularAxis: function(axis) {
        axis.processData(this);
    },

    updateRadialAxis: function(axis) {
        axis.processData(this);
    },

    coordinateX: function() {
        return this.coordinate('X', 0, 2);
    },

    coordinateY: function() {
        return this.coordinate('Y', 1, 2);
    },

    updateCenter: function(center) {
        this.setStyle({
            translationX: center[0] + this.getOffsetX(),
            translationY: center[1] + this.getOffsetY()
        });
        this.doUpdateStyles();
    },

    updateRadius: function(radius) {
        this.setStyle({
            endRho: radius
        });
        this.doUpdateStyles();
    },

    updateRotation: function(rotation) {
        // Overrides base class method.
        var me = this,
            chart = me.getChart(),
            axes = chart.getAxes(),
            i, ln, axis;

        for (i = 0, ln = axes.length; i < ln; i++) {
            axis = axes[i];
            axis.setRotation(rotation);
        }

        me.setStyle({
            rotationRads: rotation
        });
        me.doUpdateStyles();
    },

    updateTotalAngle: function(totalAngle) {
        this.processData();
    },

    getItemForPoint: function(x, y) {
        var me = this,
            sprite = me.sprites && me.sprites[0],
            attr = sprite.attr,
            dataX = attr.dataX,
            length = dataX.length,
            store = me.getStore(),
            marker = me.getMarker(),
            threshhold, item, xy, i, bbox, markers;

        if (me.getHidden()) {
            return null;
        }

        if (sprite && marker) {
            markers = sprite.getMarker('markers');

            for (i = 0; i < length; i++) {
                bbox = markers.getBBoxFor(i);
                threshhold = (bbox.width + bbox.height) * 0.25;
                xy = sprite.getDataPointXY(i);

                if (Math.abs(xy[0] - x) < threshhold &&
                    Math.abs(xy[1] - y) < threshhold) {
                    item = {
                        series: me,
                        sprite: sprite,
                        index: i,
                        category: 'markers',
                        record: store.getData().items[i],
                        field: me.getYField()
                    };

                    return item;
                }
            }
        }

        return me.callParent(arguments);
    },

    getDefaultSpriteConfig: function() {
        var config = this.callParent(),
            animation = {
                customDurations: {
                    translationX: 0,
                    translationY: 0,
                    rotationRads: 0,
                    // Prevent animation of 'dataMinX' and 'dataMaxX' attributes in order
                    // to react instantaniously to changes to the 'hidden' attribute.
                    dataMinX: 0,
                    dataMaxX: 0
                }
            };

        if (config.animation) {
            Ext.apply(config.animation, animation);
        }
        else {
            config.animation = animation;
        }

        return config;
    },

    getSprites: function() {
        var me = this,
            chart = me.getChart(),
            sprites = me.sprites;

        if (!chart) {
            return Ext.emptyArray;
        }

        if (!sprites.length) {
            me.createSprite();
        }

        return sprites;
    },

    provideLegendInfo: function(target) {
        var me = this,
            style = me.getSubStyleWithTheme(),
            fill = style.fillStyle;

        if (Ext.isArray(fill)) {
            fill = fill[0];
        }

        target.push({
            name: me.getTitle() || me.getYField() || me.getId(),
            mark: (Ext.isObject(fill) ? fill.stops && fill.stops[0].color : fill) ||
                   style.strokeStyle || 'black',
            disabled: me.getHidden(),
            series: me.getId(),
            index: 0
        });
    }
});
