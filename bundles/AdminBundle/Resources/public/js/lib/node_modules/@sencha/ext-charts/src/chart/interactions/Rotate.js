/* eslint-disable max-len */
/**
 * @class Ext.chart.interactions.Rotate
 * @extends Ext.chart.interactions.Abstract
 *
 * The Rotate interaction allows the user to rotate a polar chart about its central point.
 *
 *     @example
 *     Ext.create('Ext.Container', {
 *         renderTo: Ext.getBody(),
 *         width: 600,
 *         height: 400,
 *         layout: 'fit',
 *         items: {
 *             xtype: 'polar',
 *             interactions: 'rotate',
 *             colors: ["#115fa6", "#94ae0a", "#a61120", "#ff8809", "#ffd13e"],
 *             store: {
 *                 fields: ['name', 'data1', 'data2', 'data3', 'data4', 'data5'],
 *                 data: [
 *                     {'name':'metric one', 'data1':10, 'data2':12, 'data3':14, 'data4':8, 'data5':13},
 *                     {'name':'metric two', 'data1':7, 'data2':8, 'data3':16, 'data4':10, 'data5':3},
 *                     {'name':'metric three', 'data1':5, 'data2':2, 'data3':14, 'data4':12, 'data5':7},
 *                     {'name':'metric four', 'data1':2, 'data2':14, 'data3':6, 'data4':1, 'data5':23},
 *                     {'name':'metric five', 'data1':27, 'data2':38, 'data3':36, 'data4':13, 'data5':33}
 *                 ]
 *             },
 *             series: {
 *                 type: 'pie',
 *                 label: {
 *                     field: 'name',
 *                     display: 'rotate'
 *                 },
 *                 xField: 'data3',
 *                 donut: 30
 *             }
 *         }
 *     });
 */
/* eslint-enable max-len */
Ext.define('Ext.chart.interactions.Rotate', {
    extend: 'Ext.chart.interactions.Abstract',

    type: 'rotate',

    alternateClassName: 'Ext.chart.interactions.RotatePie3D',

    alias: [
        'interaction.rotate',
        'interaction.rotatePie3d'
    ],

    /**
     * @event rotate
     * Fires on every tick of the rotation.
     * @param {Ext.chart.interactions.Rotate} this This interaction.
     * @param {Number} angle The new current rotation angle.
     */

    /**
     * @event rotatestart
     * Fires when a user initiates the rotation.
     * @param {Ext.chart.interactions.Rotate} this This interaction.
     * @param {Number} angle The new current rotation angle.
     */

    /**
     * @event rotateend
     * Fires after a user finishes the rotation.
     * @param {Ext.chart.interactions.Rotate} this This interaction.
     * @param {Number} angle The new current rotation angle.
     */

    /**
     * @deprecated 6.5.1 Use the 'rotateend' event instead.
     * @event rotationEnd
     * Fires after a user finishes the rotation
     * @param {Ext.chart.interactions.Rotate} this This interaction.
     * @param {Number} angle The new current rotation angle.
     */

    config: {
        /**
         * @cfg {String} gesture
         * Defines the gesture type that will be used to rotate the chart. Currently only
         * supports `pinch` for two-finger rotation and `drag` for single-finger rotation.
         * @private
         */
        gesture: 'rotate',

        gestures: {
            dragstart: 'onGestureStart',
            drag: 'onGesture',
            dragend: 'onGestureEnd'
        },

        /**
         * @cfg {Number} rotation
         * Saves the current rotation of the series. Accepts negative values
         * and values > 360 ( / 180 * Math.PI)
         * @private
         */
        rotation: 0
    },

    oldRotations: null,

    getAngle: function(e) {
        var me = this,
            chart = me.getChart(),
            xy = chart.getEventXY(e),
            center = chart.getCenter();

        return Math.atan2(
            xy[1] - center[1],
            xy[0] - center[0]
        );
    },

    onGestureStart: function(e) {
        var me = this;

        e.claimGesture();

        me.lockEvents('drag');
        me.angle = me.getAngle(e);
        me.oldRotations = {};
        me.getChart().suspendAnimation();
        me.fireEvent('rotatestart', me, me.getRotation());

        return false;
    },

    onGesture: function(e) {
        var me = this,
            angle = me.getAngle(e) - me.angle;

        if (me.getLocks().drag === me) {
            me.doRotateTo(angle, true);

            return false;
        }
    },

    /**
     * @private
     */
    doRotateTo: function(angle, relative) {
        var me = this,
            chart = me.getChart(),
            axes = chart.getAxes(),
            seriesList = chart.getSeries(),
            oldRotations = me.oldRotations,
            rotation, oldRotation,
            axis, series, id,
            i, ln;

        for (i = 0, ln = axes.length; i < ln; i++) {
            axis = axes[i];
            id = axis.getId();
            oldRotation = oldRotations[id] || (oldRotations[id] = axis.getRotation());
            rotation = angle + (relative ? oldRotation : 0);

            axis.setRotation(rotation);
        }

        for (i = 0, ln = seriesList.length; i < ln; i++) {
            series = seriesList[i];
            id = series.getId();
            oldRotation = oldRotations[id] || (oldRotations[id] = series.getRotation());
            // Unline axis's 'rotation', Polar series' 'rotation' is a public config and in degrees.
            rotation = Ext.draw.Draw.degrees(angle + (relative ? oldRotation : 0));

            series.setRotation(rotation);
        }

        me.setRotation(rotation);

        me.fireEvent('rotate', me, me.getRotation());

        me.sync();
    },

    /**
     * Rotates a polar chart about its center point to the specified angle.
     * @param {Number} angle The angle to rotate to.
     * @param {Boolean} [relative=false] Whether the rotation is relative to the current angle
     * or not.
     * @param {Boolean} [animate=false] Whether to animate the rotation or not.
     */
    rotateTo: function(angle, relative, animate) {
        var me = this,
            chart = me.getChart();

        if (!animate) {
            chart.suspendAnimation();
        }

        me.doRotateTo(angle, relative, animate);
        me.oldRotations = {};

        if (!animate) {
            chart.resumeAnimation();
        }
    },

    onGestureEnd: function(e) {
        var me = this;

        if (me.getLocks().drag === me) {
            me.onGesture(e);
            me.unlockEvents('drag');
            me.getChart().resumeAnimation();
            me.fireEvent('rotateend', me, me.getRotation());
            me.fireEvent('rotationEnd', me, me.getRotation());

            return false;
        }
    }
});
