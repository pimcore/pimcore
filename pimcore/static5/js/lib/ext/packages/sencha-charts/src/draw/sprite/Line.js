/**
 * A sprite that represents a line.
 */
Ext.define('Ext.draw.sprite.Line', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'sprite.line',
    type: 'line',

    inheritableStatics: {
        def: {
            processors: {
                fromX: 'number',
                fromY: 'number',
                toX: 'number',
                toY: 'number'
            },

            defaults: {
                fromX: 0,
                fromY: 0,
                toX: 1,
                toY: 1,
                strokeStyle: 'black'
            }
        }
    },

    updatePlainBBox: function (plain) {
        var attr = this.attr,
            fromX = Math.min(attr.fromX, attr.toX),
            fromY = Math.min(attr.fromY, attr.toY),
            toX = Math.max(attr.fromX, attr.toX),
            toY = Math.max(attr.fromY, attr.toY);
        plain.x = fromX;
        plain.y = fromY;
        plain.width = toX - fromX;
        plain.height = toY - fromY;
    },

    render: function (surface, ctx) {
        var attr = this.attr,
            matrix = this.attr.matrix;

        matrix.toContext(ctx);

        ctx.beginPath();
        ctx.moveTo(attr.fromX, attr.fromY);
        ctx.lineTo(attr.toX, attr.toY);
        ctx.stroke();
    }
});