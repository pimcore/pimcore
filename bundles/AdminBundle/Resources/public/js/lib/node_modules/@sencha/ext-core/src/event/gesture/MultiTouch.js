/**
 * A base class for gesture recognizers that involve multiple simultaneous contact points
 * between the screen and the input-device, e.g. 'pinch' and 'rotate'
 * @abstract
 * @private
 */
Ext.define('Ext.event.gesture.MultiTouch', {
    extend: 'Ext.event.gesture.Recognizer',

    requiredTouchesCount: 2,

    isTracking: false,

    isMultiTouch: true,

    onTouchStart: function(e) {
        var me = this,
            requiredTouchesCount = me.requiredTouchesCount,
            touches = e.touches,
            touchesCount = touches.length;

        if (touchesCount === requiredTouchesCount) {
            me.isTracking = true;
        }
        else if (touchesCount > requiredTouchesCount) {
            return me.cancel(e);
        }
    },

    reset: function() {
        this.isTracking = false;

        return this.callParent();
    }
});
