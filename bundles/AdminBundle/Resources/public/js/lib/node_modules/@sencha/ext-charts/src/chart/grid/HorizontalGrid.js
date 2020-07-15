/**
 * @class Ext.chart.grid.HorizontalGrid
 * @extends Ext.draw.sprite.Sprite
 * 
 * Horizontal Grid sprite. Used in Cartesian Charts.
 */
Ext.define('Ext.chart.grid.HorizontalGrid', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'grid.horizontal',

    inheritableStatics: {
        def: {
            processors: {
                x: 'number',
                y: 'number',
                width: 'number',
                height: 'number'
            },

            defaults: {
                x: 0,
                y: 0,
                width: 1,
                height: 1,
                strokeStyle: '#DDD'
            }
        }
    },

    render: function(surface, ctx, rect) {
        var attr = this.attr,
            y = surface.roundPixel(attr.y),
            halfLineWidth = ctx.lineWidth * 0.5;

        ctx.beginPath();
        ctx.rect(rect[0] - surface.matrix.getDX(), y + halfLineWidth, +rect[2], attr.height);
        ctx.fill();

        ctx.beginPath();
        ctx.moveTo(rect[0] - surface.matrix.getDX(), y + halfLineWidth);
        ctx.lineTo(rect[0] + rect[2] - surface.matrix.getDX(), y + halfLineWidth);
        ctx.stroke();
    }
});
