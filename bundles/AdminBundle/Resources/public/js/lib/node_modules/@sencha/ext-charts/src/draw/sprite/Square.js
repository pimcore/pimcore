/**
 * A sprite that represents a square.
 *
 *     @example
 *     Ext.create({
 *        xtype: 'draw', 
 *        renderTo: document.body,
 *        width: 600,
 *        height: 400,
 *        sprites: [{
 *            type: 'square',
 *            x: 100,
 *            y: 100,
 *            size: 50,
 *            fillStyle: '#1F6D91'
 *        }]
 *     });
 */
Ext.define('Ext.draw.sprite.Square', {
    extend: 'Ext.draw.sprite.Path',
    alias: 'sprite.square',

    inheritableStatics: {
        def: {
            processors: {
                x: 'number',
                y: 'number',
                /**
                 * @cfg {Number} [size=4] The size of the sprite.
                 * Meant to be comparable to the size of a circle sprite with the same radius.
                 */
                size: 'number'
            },
            defaults: {
                x: 0,
                y: 0,
                size: 4
            },
            triggers: {
                x: 'path',
                y: 'path',
                size: 'size'
            }
        }
    },

    updatePath: function(path, attr) {
        var size = attr.size * 1.2,
            s = size * 2,
            x = attr.x - attr.lineWidth / 2,
            y = attr.y;

        path.fromSvgString(
            'M'.concat(x - size, ',', y - size, 'l', [s, 0, 0, s, -s, 0, 0, -s, 'z'])
        );
    }
});
