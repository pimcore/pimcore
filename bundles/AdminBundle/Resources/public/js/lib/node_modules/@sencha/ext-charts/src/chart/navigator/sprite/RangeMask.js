/**
 * The overlay sprite used by the {@link Ext.chart.navigator.Navigator} component
 * to render the selected visible range or a chart's horizontal axis.
 */
Ext.define('Ext.chart.navigator.sprite.RangeMask', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'sprite.rangemask',

    inheritableStatics: {
        def: {
            processors: {
                min: 'limited01',
                max: 'limited01',
                thumbOpacity: 'limited01'
            },
            defaults: {
                min: 0,
                max: 1,

                lineWidth: 2,
                miterLimit: 1,
                strokeStyle: '#787878',
                thumbOpacity: 1
            }
        }
    },

    getBBox: function(isWithoutTransform) {
        var me = this,
            attr = me.attr,
            bbox = attr.bbox;

        bbox.plain = {
            x: 0,
            y: 0,
            width: 1,
            height: 1
        };

        if (isWithoutTransform) {
            return bbox.plain;
        }

        return bbox.transform || (bbox.transform = attr.matrix.transformBBox(bbox.plain));
    },

    renderThumb: function(surface, ctx, x, y) {
        var me = this,
            shapeSprite = me.shapeSprite,
            textureSprite = me.textureSprite,
            thumbOpacity = me.attr.thumbOpacity,
            thumbAttributes = {
                opacity: thumbOpacity,
                translationX: x,
                translationY: y
            };

        if (!shapeSprite) {
            shapeSprite = me.shapeSprite = new Ext.draw.sprite.Rect({
                x: -9.5,
                y: -9.5,
                width: 19,
                height: 19,
                radius: 4,
                lineWidth: 1,
                fillStyle: {
                    type: 'linear',
                    degrees: 90,
                    stops: [
                        {
                            offset: 0,
                            color: '#EEE'
                        },
                        {
                            offset: 1,
                            color: '#FFF'
                        }
                    ]
                },
                strokeStyle: '#999'
            });
            textureSprite = me.textureSprite = new Ext.draw.sprite.Path({
                path: 'M -4, -5, -4, 5 M 0, -5, 0, 5 M 4, -5, 4, 5',
                strokeStyle: {
                    type: 'linear',
                    degrees: 90,
                    stops: [
                        {
                            offset: 0,
                            color: '#CCC'
                        },
                        {
                            offset: 1,
                            color: '#BBB'
                        }
                    ]
                },
                lineWidth: 2
            });
        }

        ctx.save();

        shapeSprite.setAttributes(thumbAttributes);
        shapeSprite.applyTransformations();

        textureSprite.setAttributes(thumbAttributes);
        textureSprite.applyTransformations();

        shapeSprite.useAttributes(ctx);
        shapeSprite.render(surface, ctx);

        textureSprite.useAttributes(ctx);
        textureSprite.render(surface, ctx);

        ctx.restore();
    },

    render: function(surface, ctx) {
        var me = this,
            attr = me.attr,
            matrix = attr.matrix.elements,
            sx = matrix[0],
            sy = matrix[3],
            tx = matrix[4],
            ty = matrix[5],
            min = attr.min,
            max = attr.max,
            // s_min and s_max are range values in screen coordinates (scaled and translated)
            s_min = min * sx + tx,
            s_max = max * sx + tx,
            s_y = Math.round(0.5 * sy + ty); // thumb position in screen coordinates (mid-height)

        ctx.beginPath();

        // Rect that represents the whole range.
        ctx.moveTo(tx, ty);
        ctx.lineTo(sx + tx, ty);
        ctx.lineTo(sx + tx, sy + ty);
        ctx.lineTo(tx, sy + ty);
        ctx.lineTo(tx, ty);

        // Rect that represents the visible range.
        ctx.moveTo(s_min, ty);
        ctx.lineTo(s_min, sy + ty);
        ctx.lineTo(s_max, sy + ty);
        ctx.lineTo(s_max, ty);
        ctx.lineTo(s_min, ty);

        ctx.fillStroke(attr, true);

        me.renderThumb(surface, ctx, Math.round(s_min), s_y);
        me.renderThumb(surface, ctx, Math.round(s_max), s_y);
    }
});
