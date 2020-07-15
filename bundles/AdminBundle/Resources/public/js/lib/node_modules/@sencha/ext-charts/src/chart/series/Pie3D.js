/**
 * @class Ext.chart.series.Pie3D
 * @extends Ext.chart.series.Polar
 *
 * Creates a 3D Pie Chart.
 *
 * **Note:** Labels, legends, and lines are not currently available when using the
 * 3D Pie chart series.
 *
 *     @example
 *     Ext.create({
 *        xtype: 'polar', 
 *        renderTo: document.body,
 *        width: 600,
 *        height: 400,
 *        theme: 'green',
 *        interactions: 'rotate',
 *        store: {
 *            fields: ['data3'],
 *            data: [{
 *                'data3': 14
 *            }, {
 *                'data3': 16
 *            }, {
 *                'data3': 14
 *            }, {
 *                'data3': 6
 *            }, {
 *                'data3': 36
 *            }]
 *        },
 *        series: {
 *            type: 'pie3d',
 *            angleField: 'data3',
 *            donut: 30
 *        }
 *     });
 */
Ext.define('Ext.chart.series.Pie3D', {
    extend: 'Ext.chart.series.Polar',

    requires: [
        'Ext.chart.series.sprite.Pie3DPart',
        'Ext.draw.PathUtil'
    ],

    type: 'pie3d',
    seriesType: 'pie3d',
    alias: 'series.pie3d',
    is3D: true,

    config: {
        rect: [0, 0, 0, 0],
        thickness: 35,
        distortion: 0.5,

        /**
         * @cfg {String} angleField (required)
         * The store record field name to be used for the pie angles.
         * The values bound to this field name must be positive real numbers.
         */

        /**
         * @private
         * @cfg {String} radiusField
         * Not supported.
         */

        /**
         * @cfg {Number} donut Specifies the radius of the donut hole, as a percentage
         * of the chart's radius.
         * Defaults to 0 (no donut hole).
         */
        donut: 0,

        /**
         * @cfg {Array} hidden Determines which pie slices are hidden.
         */
        hidden: [], // Populated by the coordinateX method.

        /**
         * @cfg {Object} highlightCfg Default {@link #highlight} config for the 3D pie series.
         * Slides highlighted pie sector outward.
         */
        highlightCfg: {
            margin: 20
        },

        /**
         * @cfg {Number} [rotation=0] The starting angle of the pie slices.
         */

        /**
         * @private
         * @cfg {Boolean/Object} [shadow=false]
         */
        shadow: false
    },

    // Subtract 90 degrees from rotation, so that `rotation` config's default
    // zero value makes first pie sector start at noon, rather than 3 o'clock.
    rotationOffset: -Math.PI / 2,

    setField: function(value) {
        return this.setXField(value);
    },

    getField: function() {
        return this.getXField();
    },

    updateRotation: function(rotation) {
        var attributes = { baseRotation: rotation + this.rotationOffset };

        this.forEachSprite(function(sprite) {
            sprite.setAttributes(attributes);
        });
    },

    updateColors: function(colors) {
        var chart;

        this.setSubStyle({ baseColor: colors });

        if (!this.isConfiguring) {
            chart = this.getChart();

            if (chart) {
                chart.refreshLegendStore();
            }
        }
    },

    applyShadow: function(shadow) {
        if (shadow === true) {
            shadow = {
                shadowColor: 'rgba(0,0,0,0.8)',
                shadowBlur: 30
            };
        }
        else if (!Ext.isObject(shadow)) {
            shadow = {
                shadowColor: Ext.util.Color.RGBA_NONE
            };
        }

        return shadow;
    },

    updateShadow: function(shadow) {
        var me = this,
            sprites = me.getSprites(),
            spritesPerSlice = me.spritesPerSlice,
            ln = sprites && sprites.length,
            i, sprite;

        for (i = 1; i < ln; i += spritesPerSlice) {
            sprite = sprites[i];

            if (sprite.attr.part === 'bottom') {
                sprite.setAttributes(shadow);
            }
        }
    },

    // This is a temporary solution until the Series.getStyleByIndex is fixed
    // to give user styles the priority over theme ones. Also, for sprites of
    // this particular series, the fillStyle shouldn't be set directly. Instead,
    // the 'baseColor' attribute should be set, from which the stops of the
    // gradient (used for fillStyle) will be calculated. Themes can't handle
    // situations like that properly.
    getStyleByIndex: function(i) {
        var indexStyle = this.callParent([i]),
            style = this.getStyle(),
            // 'fill' and 'color' are 'fillStyle' aliases
            // (see Ext.draw.sprite.Sprite.inheritableStatics.def.aliases)
            fillStyle = indexStyle.fillStyle || indexStyle.fill || indexStyle.color,
            strokeStyle = style.strokeStyle || style.stroke;

        if (fillStyle) {
            indexStyle.baseColor = fillStyle;
            delete indexStyle.fillStyle;
            delete indexStyle.fill;
            delete indexStyle.color;
        }

        if (strokeStyle) {
            indexStyle.strokeStyle = strokeStyle;
        }

        return indexStyle;
    },

    doUpdateStyles: function() {
        var me = this,
            sprites = me.getSprites(),
            spritesPerSlice = me.spritesPerSlice,
            ln = sprites && sprites.length,
            i = 0,
            j = 0,
            k,
            style;

        for (; i < ln; i += spritesPerSlice, j++) {
            style = me.getStyleByIndex(j);

            for (k = 0; k < spritesPerSlice; k++) {
                sprites[i + k].setAttributes(style);
            }
        }
    },

    coordinateX: function() {
        var me = this,
            store = me.getStore(),
            records = store.getData().items,
            recordCount = records.length,
            xField = me.getXField(),
            animation = me.getAnimation(),
            rotation = me.getRotation(),
            hidden = me.getHidden(),
            sprites = me.getSprites(true),
            spriteCount = sprites.length,
            spritesPerSlice = me.spritesPerSlice,
            center = me.getCenter(),
            offsetX = me.getOffsetX(),
            offsetY = me.getOffsetY(),
            radius = me.getRadius(),
            thickness = me.getThickness(),
            distortion = me.getDistortion(),
            renderer = me.getRenderer(),
            rendererData = me.getRendererData(),
            highlight = me.getHighlight(), // eslint-disable-line no-unused-vars
            lastAngle = 0,
            twoPi = Math.PI * 2,
            // To avoid adjacent start/end part blinking (z-index jitter)
            // when rotating a translucent pie chart.
            delta = 1e-10,
            endAngles = [],
            sum = 0,
            value, unit,
            sprite, style,
            i, j;

        for (i = 0; i < recordCount; i++) {
            value = Math.abs(+records[i].get(xField)) || 0;

            if (!hidden[i]) {
                sum += value;
            }

            endAngles[i] = sum;

            if (i >= hidden.length) {
                hidden[i] = false;
            }
        }

        if (sum === 0) {
            return;
        }

        // Angular value of 1 in radians.
        unit = 2 * Math.PI / sum;

        for (i = 0; i < recordCount; i++) {
            endAngles[i] *= unit;
        }

        for (i = 0; i < recordCount; i++) {
            style = this.getStyleByIndex(i);

            for (j = 0; j < spritesPerSlice; j++) {
                sprite = sprites[i * spritesPerSlice + j];
                sprite.setAnimation(animation);
                sprite.setAttributes({
                    centerX: center[0] + offsetX,
                    centerY: center[1] + offsetY - thickness / 2,
                    endRho: radius,
                    startRho: radius * me.getDonut() / 100,
                    baseRotation: rotation + me.rotationOffset,
                    startAngle: lastAngle,
                    endAngle: endAngles[i] - delta,
                    thickness: thickness,
                    distortion: distortion,
                    globalAlpha: 1
                });
                sprite.setAttributes(style);
                sprite.setConfig({
                    renderer: renderer,
                    rendererData: rendererData,
                    rendererIndex: i
                });
                // if (highlight) {
                //     if (!sprite.modifiers.highlight) {
                //         debugger
                //         sprite.addModifier(highlight, true);
                //     }
                //     // sprite.modifiers.highlight.setConfig(highlight);
                // }
            }

            lastAngle = endAngles[i];
        }

        for (i *= spritesPerSlice; i < spriteCount; i++) {
            sprite = sprites[i];
            sprite.setAnimation(animation);
            sprite.setAttributes({
                startAngle: twoPi,
                endAngle: twoPi,
                globalAlpha: 0,
                baseRotation: rotation + me.rotationOffset
            });
        }
    },

    updateHighlight: function(highlight, oldHighlight) {
        this.callParent([highlight, oldHighlight]);

        this.forEachSprite(function(sprite) {
            if (highlight) {
                if (sprite.modifiers.highlight) {
                    sprite.modifiers.highlight.setConfig(highlight);
                }
                else {
                    sprite.config.highlight = highlight;
                    sprite.addModifier(highlight, true);
                }
            }
        });
    },

    updateLabelData: function() {
        var me = this,
            store = me.getStore(),
            items = store.getData().items,
            sprites = me.getSprites(),
            label = me.getLabel(),
            labelField = label && label.getTemplate().getField(),
            hidden = me.getHidden(),
            spritesPerSlice = me.spritesPerSlice,
            ln, labels, sprite,
            name = 'labels',
            i, // sprite index
            j; // record index

        if (sprites.length) {
            if (labelField) {
                labels = [];

                for (j = 0, ln = items.length; j < ln; j++) {
                    labels.push(items[j].get(labelField));
                }
            }

            // Only set labels for the sprites that compose the top lid of the pie.
            for (i = 0, j = 0, ln = sprites.length; i < ln; i += spritesPerSlice, j++) {
                sprite = sprites[i];

                if (label) {
                    if (!sprite.getMarker(name)) {
                        sprite.bindMarker(name, label);
                    }

                    if (labels) {
                        sprite.setAttributes({ label: labels[j] });
                    }

                    sprite.putMarker(name, { hidden: hidden[j] }, sprite.attr.attributeId);
                }
                else {
                    sprite.releaseMarker(name);
                }
            }
        }
    },

    // The radius here will normally be set by the PolarChart.performLayout,
    // where it's half the width or height (whichever is smaller) of the chart's rect.
    // But for 3D pie series we have to take the thickness of the pie and the
    // distortion into account to calculate the proper radius.
    // The passed value is never used (or derived from) since the radius config
    // is not really meant to be used directly, as it will be reset by the next layout.
    applyRadius: function() {
        var me = this,
            chart = me.getChart(),
            padding = chart.getInnerPadding(),
            rect = chart.getMainRect() || [0, 0, 1, 1],
            width = rect[2] - padding * 2,
            height = rect[3] - padding * 2 - me.getThickness(),
            horizontalRadius = width / 2,
            verticalRadius = horizontalRadius * me.getDistortion(),
            result;

        if (verticalRadius > height / 2) {
            result = height / (me.getDistortion() * 2);
        }
        else {
            result = horizontalRadius;
        }

        return Math.max(result, 0);
    },

    forEachSprite: function(fn) {
        var sprites = this.sprites,
            ln = sprites.length,
            i;

        for (i = 0; i < ln; i++) {
            fn(sprites[i], Math.floor(i / this.spritesPerSlice));
        }
    },

    updateRadius: function(radius) {
        var donut;

        // The side effects of the 'getChart' call will result
        // in the 'coordinateX' method call, which we want to have called
        // first, to coordinate the data and create sprites for pie slices,
        // before we set their attributes here.
        // updateChart -> onChartAttached -> processData -> coordinateX
        this.getChart();

        donut = this.getDonut();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                endRho: radius,
                startRho: radius * donut / 100
            });
        });
    },

    updateDonut: function(donut) {
        var radius;

        // See 'updateRadius' comments.
        this.getChart();

        radius = this.getRadius();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                startRho: radius * donut / 100
            });
        });
    },

    updateCenter: function(center) {
        var offsetX, offsetY, thickness;

        // See 'updateRadius' comments.
        this.getChart();

        offsetX = this.getOffsetX();
        offsetY = this.getOffsetY();
        thickness = this.getThickness();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                centerX: center[0] + offsetX,
                centerY: center[1] + offsetY - thickness / 2
            });
        });
    },

    updateThickness: function(thickness) {
        var center, offsetY;

        // See 'updateRadius' comments.
        this.getChart();

        // Radius depends on thickness and distortion,
        // this will trigger its recalculation in the applier.
        this.setRadius();

        center = this.getCenter();
        offsetY = this.getOffsetY();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                thickness: thickness,
                centerY: center[1] + offsetY - thickness / 2
            });
        });
    },

    updateDistortion: function(distortion) {
        // See 'updateRadius' comments.
        this.getChart();

        // Radius depends on thickness and distortion,
        // this will trigger its recalculation in the applier.
        this.setRadius();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                distortion: distortion
            });
        });
    },

    updateOffsetX: function(offsetX) {
        var center;

        // See 'updateRadius' comments.
        this.getChart();

        center = this.getCenter();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                centerX: center[0] + offsetX
            });
        });
    },

    updateOffsetY: function(offsetY) {
        var center, thickness;

        // See 'updateRadius' comments.
        this.getChart();

        center = this.getCenter();
        thickness = this.getThickness();

        this.forEachSprite(function(sprite) {
            sprite.setAttributes({
                centerY: center[1] + offsetY - thickness / 2
            });
        });
    },

    updateAnimation: function(animation) {
        // See 'updateRadius' comments.
        this.getChart();

        this.forEachSprite(function(sprite) {
            sprite.setAnimation(animation);
        });
    },

    updateRenderer: function(renderer) {
        var rendererData;

        // See 'updateRadius' comments.
        this.getChart();

        rendererData = this.getRendererData();

        this.forEachSprite(function(sprite, itemIndex) {
            sprite.setConfig({
                renderer: renderer,
                rendererData: rendererData,
                rendererIndex: itemIndex
            });
        });
    },

    getRendererData: function() {
        return {
            store: this.getStore(),
            angleField: this.getXField(),
            radiusField: this.getYField(),
            series: this
        };
    },

    getSprites: function(createMissing) {
        var me = this,
            store = me.getStore(),
            sprites = me.sprites;

        if (!store) {
            return Ext.emptyArray;
        }

        if (sprites && !createMissing) {
            return sprites;
        }

        // eslint-disable-next-line vars-on-top, one-var
        var surface = me.getSurface(),
            records = store.getData().items,
            spritesPerSlice = me.spritesPerSlice,
            partCount = me.partNames.length,
            recordCount = records.length,
            sprite,
            i, j;

        for (i = 0; i < recordCount; i++) {
            if (!sprites[i * spritesPerSlice]) {
                for (j = 0; j < partCount; j++) {
                    sprite = surface.add({
                        type: 'pie3dPart',
                        part: me.partNames[j],
                        series: me
                    });
                    sprite.getAnimation().setDurationOn('baseRotation', 0);
                    sprites.push(sprite);
                }
            }
        }

        return sprites;
    },

    betweenAngle: function(x, a, b) {
        var pp = Math.PI * 2,
            offset = this.rotationOffset;

        a += offset;
        b += offset;

        x -= a;
        b -= a;

        // Normalize, so that both x and b are in the [0,360) interval.
        // Since 360 * n angles will be normalized to 0,
        // we need to treat b === 0 as a special case.
        x %= pp;
        b %= pp;
        x += pp;
        b += pp;
        x %= pp;
        b %= pp;

        return x < b || b === 0;
    },

    getItemForPoint: function(x, y) {
        var me = this,
            sprites = me.getSprites(),
            spritesPerSlice = me.spritesPerSlice,
            result = null,
            store, records, hidden, i, ln, sprite, topPartIndex;

        if (!sprites) {
            return result;
        }

        store = me.getStore();
        records = store.getData().items;
        hidden = me.getHidden();

        for (i = 0, ln = records.length; i < ln; i++) {
            if (hidden[i]) {
                continue;
            }

            topPartIndex = i * spritesPerSlice;
            sprite = sprites[topPartIndex];

            // This is CPU intensive on mousemove (no visial slowdown
            // on a fast machine, but some throttling might be desirable
            // on slower machines).
            // On touch devices performance/battery hit is negligible.
            if (sprite.hitTest([x, y])) {
                result = {
                    series: me,
                    sprite: sprites.slice(topPartIndex, topPartIndex + spritesPerSlice),
                    index: i,
                    record: records[i],
                    category: 'sprites',
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
            items, labelField, field, hidden, style, color, i;

        if (store) {
            items = store.getData().items;
            labelField = me.getLabel().getTemplate().getField();
            field = me.getField();
            hidden = me.getHidden();

            for (i = 0; i < items.length; i++) {
                style = me.getStyleByIndex(i);
                color = style.baseColor;
                target.push({
                    name: labelField ? String(items[i].get(labelField)) : field + ' ' + i,
                    mark: color || 'black',
                    disabled: hidden[i],
                    series: me.getId(),
                    index: i
                });
            }
        }
    }
}, function() {
    var proto = this.prototype,
        definition = Ext.chart.series.sprite.Pie3DPart.def.getInitialConfig().processors.part;

    proto.partNames = definition.replace(/^enums\(|\)/g, '').split(',');
    proto.spritesPerSlice = proto.partNames.length;
});
