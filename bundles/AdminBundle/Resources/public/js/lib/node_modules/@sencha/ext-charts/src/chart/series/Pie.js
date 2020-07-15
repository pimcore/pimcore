/**
 * @class Ext.chart.series.Pie
 * @extends Ext.chart.series.Polar
 *
 * Creates a Pie Chart. A Pie Chart is a useful visualization technique to display
 * quantitative information for different categories that also have a meaning as a whole.
 * As with all other series, the Pie Series must be appended in the *series* Chart array
 * configuration. See the Chart documentation for more information. A typical configuration
 * object for the pie series could be:
 *
 *     @example
 *     Ext.create({
 *        xtype: 'polar',
 *        renderTo: document.body,
 *        width: 400,
 *        height: 400,
 *        theme: 'green',
 *        interactions: ['rotate', 'itemhighlight'],
 *        store: {
 *            fields: ['name', 'data1'],
 *            data: [{
 *                name: 'metric one',
 *                data1: 14
 *            }, {
 *                name: 'metric two',
 *                data1: 16
 *            }, {
 *                name: 'metric three',
 *                data1: 14
 *            }, {
 *                name: 'metric four',
 *                data1: 6
 *            }, {
 *                name: 'metric five',
 *                data1: 36
 *            }]
 *        },
 *        series: {
 *            type: 'pie',
 *            highlight: true,
 *            angleField: 'data1',
 *            label: {
 *                field: 'name',
 *                display: 'rotate'
 *            },
 *            donut: 30
 *        }
 *     });
 *
 * In this configuration we set `pie` as the type for the series, then set the `highlight` config
 * to `true` (we can also specify an object with specific style properties for highlighting options)
 * which is triggered when hovering or tapping elements.
 * We set `data1` as the value of the `angleField` to determine the angular span for each pie slice.
 * We also set a label configuration object where we set the name of the store field
 * to be rendered as text for the label. The labels will also be displayed rotated.
 * And finally, we specify the donut hole radius for the pie series in percentages of the series
 * radius.
 *
 */
Ext.define('Ext.chart.series.Pie', {
    extend: 'Ext.chart.series.Polar',
    requires: [
        'Ext.chart.series.sprite.PieSlice'
    ],
    type: 'pie',
    alias: 'series.pie',
    seriesType: 'pieslice',
    isPie: true,

    config: {
        /**
         * @cfg {String} radiusField
         * The store record field name to be used for the pie slice lengths.
         * The values bound to this field name must be positive real numbers.
         */

        /**
         * @cfg {Number} donut Specifies the radius of the donut hole, as a percentage
         * of the chart's radius.
         * Defaults to 0 (no donut hole).
         */
        donut: 0,

        /**
         * @cfg {Number} rotation The starting angle of the pie slices.
         */
        rotation: 0,

        /**
         * @cfg {Boolean} clockwise
         * Whether the pie slices are displayed clockwise. Default's true.
         */
        clockwise: true,

        /**
         * @cfg {Number} [totalAngle=2*PI] The total angle of the pie series.
         */
        totalAngle: 2 * Math.PI,

        /**
         * @cfg {Array} hidden Determines which pie slices are hidden.
         */
        hidden: [],

        /**
         * @cfg {Number} [radiusFactor=100] Allows adjustment of the radius
         * by a specific percentage.
         */
        radiusFactor: 100,

        /**
         * @cfg {Ext.chart.series.sprite.PieSlice/Object} highlightCfg
         * Default highlight config for the pie series.
         * Slides highlighted pie sector outward by default.
         * 
         * highlightCfg accepts as its value a config object (or array of configs) for a 
         * {@link Ext.chart.series.sprite.PieSlice pie sprite}.
         * 
         * 
         * Example config:
         * 
         *     Ext.create('Ext.chart.PolarChart', {
         *         renderTo: document.body,
         *         width: 600,
         *         height: 400,
         *         innerPadding: 5,
         *         store: {
         *             fields: ['name', 'data1'],
         *             data: [{
         *                 name: 'metric one',
         *                 data1: 10
         *             }, {
         *                 name: 'metric two',
         *                 data1: 7
         *             }, {
         *                 name: 'metric three',
         *                 data1: 5
         *             }]
         *         },
         *         series: {
         *             type: 'pie',
         *             label: {
         *                 field: 'name',
         *                 display: 'rotate'
         *             },
         *             xField: 'data1',
         *             donut: 30,
         *             highlightCfg: {
         *                 margin: 10,
         *                 fillOpacity: .7
         *             }
         *         }
         *     });
         */
        highlightCfg: {
            margin: 20
        },

        style: {}
    },

    directions: ['X'],

    applyLabel: function(newLabel, oldLabel) {
        if (Ext.isObject(newLabel) && !Ext.isString(newLabel.orientation)) {
            // Override default label orientation from '' to 'vertical'.
            Ext.apply(newLabel = Ext.Object.chain(newLabel), { orientation: 'vertical' });
        }

        return this.callParent([newLabel, oldLabel]);
    },

    updateLabelData: function() {
        var me = this,
            store = me.getStore(),
            items = store.getData().items,
            sprites = me.getSprites(),
            label = me.getLabel(),
            labelField = label && label.getTemplate().getField(),
            hidden = me.getHidden(),
            i, ln, labels, sprite;

        if (sprites.length && labelField) {
            labels = [];

            for (i = 0, ln = items.length; i < ln; i++) {
                labels.push(items[i].get(labelField));
            }

            for (i = 0, ln = sprites.length; i < ln; i++) {
                sprite = sprites[i];
                sprite.setAttributes({ label: labels[i] });
                sprite.putMarker('labels', { hidden: hidden[i] }, sprite.attr.attributeId);
            }
        }
    },

    coordinateX: function() {
        var me = this,
            store = me.getStore(),
            records = store.getData().items,
            recordCount = records.length,
            xField = me.getXField(),
            yField = me.getYField(),
            x,
            sumX = 0,
            unit,
            y,
            maxY = 0,
            hidden = me.getHidden(),
            summation = [],
            i,
            lastAngle = 0,
            totalAngle = me.getTotalAngle(),
            clockwise = me.getClockwise() ? 1 : -1,
            sprites = me.getSprites(),
            sprite, labels;

        if (!sprites) {
            return;
        }

        for (i = 0; i < recordCount; i++) {
            x = Math.abs(Number(records[i].get(xField))) || 0;
            y = yField && Math.abs(Number(records[i].get(yField))) || 0;

            if (!hidden[i]) {
                sumX += x;

                if (y > maxY) {
                    maxY = y;
                }
            }

            summation[i] = sumX;

            if (i >= hidden.length) {
                hidden[i] = false;
            }
        }

        hidden.length = recordCount;
        me.maxY = maxY;

        if (sumX !== 0) {
            unit = totalAngle / sumX;
        }

        for (i = 0; i < recordCount; i++) {
            sprites[i].setAttributes({
                startAngle: lastAngle,
                endAngle: lastAngle = (unit ? clockwise * summation[i] * unit : 0),
                globalAlpha: 1
            });
        }

        if (recordCount < sprites.length) {
            for (i = recordCount; i < sprites.length; i++) {
                sprite = sprites[i];
                labels = sprite.getMarker('labels');

                if (labels) {
                    // Don't want the 'labels' Markers and its 'template' sprite to be destroyed
                    // with the PieSlice MarkerHolder, as it is also used by other pie slices.
                    // So we release 'labels' before destroying the PieSlice.
                    // But first, we have to clear the instances of the 'labels'
                    // Markers created by the PieSlice MarkerHolder.
                    labels.clear(sprite.getId());
                    sprite.releaseMarker('labels');
                }

                sprite.destroy();
            }

            sprites.length = recordCount;
        }

        for (i = recordCount; i < sprites.length; i++) {
            sprites[i].setAttributes({
                startAngle: totalAngle,
                endAngle: totalAngle,
                globalAlpha: 0
            });
        }
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
            startRho: radius * this.getDonut() * 0.01,
            endRho: radius * this.getRadiusFactor() * 0.01
        });
        this.doUpdateStyles();
    },

    getStyleByIndex: function(i) {
        var me = this,
            store = me.getStore(),
            item = store.getAt(i),
            yField = me.getYField(),
            radius = me.getRadius(),
            style = {},
            startRho, endRho, y;

        if (item) {
            y = yField && Math.abs(Number(item.get(yField))) || 0;
            startRho = radius * me.getDonut() * 0.01;
            endRho = radius * me.getRadiusFactor() * 0.01;
            style = me.callParent([i]);
            style.startRho = startRho;
            style.endRho = me.maxY ? (startRho + (endRho - startRho) * y / me.maxY) : endRho;
        }

        return style;
    },

    updateDonut: function(donut) {
        var radius = this.getRadius();

        this.setStyle({
            startRho: radius * donut * 0.01,
            endRho: radius * this.getRadiusFactor() * 0.01
        });
        this.doUpdateStyles();
    },

    // Subtract 90 degrees from rotation, so that `rotation` config's default
    // value of 0 makes first pie sector start at noon, rather than 3 o'clock.
    rotationOffset: -Math.PI / 2,

    updateRotation: function(rotation) {
        this.setStyle({
            rotationRads: rotation + this.rotationOffset
        });
        this.doUpdateStyles();
    },

    updateTotalAngle: function(totalAngle) {
        this.processData();
    },

    getSprites: function() {
        var me = this,
            chart = me.getChart(),
            store = me.getStore();

        if (!chart || !store) {
            return Ext.emptyArray;
        }

        me.getColors();
        me.getSubStyle();

        // eslint-disable-next-line vars-on-top, one-var
        var items = store.getData().items,
            length = items.length,
            animation = me.getAnimation() || chart && chart.getAnimation(),
            sprites = me.sprites,
            sprite,
            spriteCreated = false,
            spriteIndex = 0,
            label = me.getLabel(),
            labelTpl = label && label.getTemplate(),
            i, rendererData;

        rendererData = {
            store: store,
            field: me.getXField(), // for backward compatibility only (deprecated in 5.5)
            angleField: me.getXField(),
            radiusField: me.getYField(),
            series: me
        };

        for (i = 0; i < length; i++) {
            sprite = sprites[i];

            if (!sprite) {
                sprite = me.createSprite();

                if (me.getHighlight()) {
                    sprite.config.highlight = me.getHighlight();
                    sprite.addModifier('highlight', true);
                }

                if (labelTpl && labelTpl.getField()) {
                    labelTpl.setAttributes({
                        labelOverflowPadding: me.getLabelOverflowPadding()
                    });
                    labelTpl.getAnimation().setCustomDurations({ 'callout': 200 });
                }

                sprite.setAttributes(me.getStyleByIndex(i));
                sprite.setRendererData(rendererData);
                spriteCreated = true;
            }

            sprite.setRendererIndex(spriteIndex++);
            sprite.setAnimation(animation);
        }

        if (spriteCreated) {
            me.doUpdateStyles();
        }

        return me.sprites;
    },

    betweenAngle: function(x, a, b) {
        var pp = Math.PI * 2,
            offset = this.rotationOffset;

        if (a === b) {
            return false;
        }

        if (!this.getClockwise()) {
            x *= -1;
            a *= -1;
            b *= -1;
            a -= offset;
            b -= offset;
        }
        else {
            a += offset;
            b += offset;
        }

        x -= a;
        b -= a;

        // Normalize, so that both x and b are in the [0,360) interval.
        x %= pp;
        b %= pp;
        x += pp;
        b += pp;
        x %= pp;
        b %= pp;

        // Because 360 * n angles will be normalized to 0,
        // we need to treat b ~= 0 as a special case.
        return x < b || Ext.Number.isEqual(b, 0, 1e-8);
    },

    getItemByIndex: function(index, category) {
        category = category || 'sprites';

        return this.callParent([index, category]);
    },

    /**
     * Returns the pie slice for a given angle
     * @param {Number} angle The angle to search for the slice
     * @return {Object} An object containing the reocord, sprite, scope etc.
     */
    getItemForAngle: function(angle) {
        var me = this,
            sprites = me.getSprites(),
            attr, store, items, hidden, i, ln;

        angle %= Math.PI * 2;

        while (angle < 0) {
            angle += Math.PI * 2;
        }

        if (sprites) {
            store = me.getStore();
            items = store.getData().items;
            hidden = me.getHidden();

            for (i = 0, ln = store.getCount(); i < ln; i++) {
                if (!hidden[i]) {
                    // Fortunately, item's id equals its index in the instances list.
                    attr = sprites[i].attr;

                    if (attr.startAngle <= angle && attr.endAngle >= angle) {
                        return {
                            series: me,
                            sprite: sprites[i],
                            index: i,
                            record: items[i],
                            field: me.getXField()
                        };
                    }
                }
            }
        }

        return null;
    },

    getItemForPoint: function(x, y) {
        var me = this,
            sprites = me.getSprites(),
            center = me.getCenter(),
            offsetX = me.getOffsetX(),
            offsetY = me.getOffsetY(),
            // Distance from the center of the series to the cursor.
            dx = x - center[0] + offsetX,
            dy = y - center[1] + offsetY,
            store = me.getStore(),
            donut = me.getDonut(),
            records = store.getData().items,
            direction = Math.atan2(dy, dx) - me.getRotation(),
            radius = Math.sqrt(dx * dx + dy * dy),
            startRadius = me.getRadius() * donut * 0.01,
            hidden = me.getHidden(),
            result = null,
            i, ln, attr, sprite;

        for (i = 0, ln = records.length; i < ln; i++) {
            if (hidden[i]) {
                continue;
            }

            sprite = sprites[i];

            if (!sprite) {
                break;
            }

            attr = sprite.attr;

            if (radius >= startRadius + attr.margin &&
                radius <= attr.endRho + attr.margin &&
                me.betweenAngle(direction, attr.startAngle, attr.endAngle)) {
                result = {
                    series: me,
                    sprite: sprites[i],
                    index: i,
                    record: records[i],
                    field: me.getXField()
                };
                break;
            }
        }

        return result;
    },

    provideLegendInfo: function(target) {
        var me = this,
            store = me.getStore(),
            items, labelField, xField, hidden, i, style, fill;

        if (store) {
            items = store.getData().items;
            labelField = me.getLabel().getTemplate().getField();
            xField = me.getXField();
            hidden = me.getHidden();

            for (i = 0; i < items.length; i++) {
                style = me.getStyleByIndex(i);
                fill = style.fillStyle;

                if (Ext.isObject(fill)) {
                    fill = fill.stops && fill.stops[0].color;
                }

                target.push({
                    name: labelField ? String(items[i].get(labelField)) : xField + ' ' + i,
                    mark: fill || style.strokeStyle || 'black',
                    disabled: hidden[i],
                    series: me.getId(),
                    index: i
                });
            }
        }
    }
});
