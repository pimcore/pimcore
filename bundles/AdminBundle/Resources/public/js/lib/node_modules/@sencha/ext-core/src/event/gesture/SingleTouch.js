/**
 * A base class for gesture recognizers that are only concerned with a single point of
 * contact between the screen and the input-device.
 * @abstract
 * @private
 */
Ext.define('Ext.event.gesture.SingleTouch', {
    extend: 'Ext.event.gesture.Recognizer',

    isSingleTouch: true,

    onTouchStart: function(e) {
        if (e.touches.length > 1) {
            return this.cancel(e);
        }
    }
});

