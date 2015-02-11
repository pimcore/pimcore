/**
 * @class Ext.draw.sprite.Arc
 * @extend Ext.draw.sprite.Circle
 * 
 *  A sprite that represents a circular arc.
 *
 *     @example
 *     Ext.create('Ext.Container', {
 *         renderTo: Ext.getBody(),
 *         width: 600,
 *         height: 400,
 *         layout: 'fit',
 *         items: {
 *             xtype: 'draw',
 *             sprites: [{
 *                 type: 'arc',
 *                 cx: 100,
 *                 cy: 100,
 *                 r: 25,
 *                 fillStyle: 'blue',
 *                 startAngle: 0,
 *                 endAngle: Math.PI,
 *                 anticlockwise: true
 *             }]
 *         }
 *     });
 */
Ext.define('Ext.draw.sprite.Arc', {
    extend: 'Ext.draw.sprite.Circle',
    alias: 'sprite.arc',
    type: 'arc',
    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [startAngle=0] The beginning angle of the arc.
                 */
                startAngle: 'number',

                /**
                 * @cfg {Number} [endAngle=Math.PI*2] The ending angle of the arc.
                 */
                endAngle: 'number',

                /**
                 * @cfg {Boolean} [anticlockwise=false] Determines whether or not the arc is drawn clockwise.
                 */
                anticlockwise: 'bool'
            },
            aliases: {
                from: 'startAngle',
                to: 'endAngle',
                start: 'startAngle',
                end: 'endAngle'
            },
            defaults: {
                startAngle: 0,
                endAngle: Math.PI * 2,
                anticlockwise: false
            },
            triggers: {
                startAngle: 'path',
                endAngle: 'path',
                anticlockwise: 'path'
            }
        }
    },

    updatePath: function (path, attr) {
        path.arc(attr.cx, attr.cy, attr.r, attr.startAngle, attr.endAngle, attr.anticlockwise);
    }
});