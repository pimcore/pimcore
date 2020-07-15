/**
 * A gesture recognizer for swipe events
 */
Ext.define('Ext.event.gesture.Swipe', {
    extend: 'Ext.event.gesture.SingleTouch',

    priority: 600,

    handledEvents: ['swipestart', 'swipe', 'swipecancel'],

    /**
     * @member Ext.dom.Element
     * @event swipe
     * Fires when there is a swipe
     * When listening to this, ensure you know about the {@link Ext.event.Event#direction} property
     * in the `event` object.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @property {Number} direction
     * The direction of the swipe. Available options are:
     *
     * - up
     * - down
     * - left
     * - right
     *
     * **This is only available when the event type is `swipe`**
     * @member Ext.event.Event
     */

    /**
     * @property {Number} duration
     * The duration of the swipe.
     *
     * **This is only available when the event type is `swipe`**
     * @member Ext.event.Event
     */

    config: {
        minDistance: 80,
        maxOffset: 35,
        maxDuration: 1000
    },

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]),
            touch;

        if (ret !== false) {
            touch = e.changedTouches[0];

            me.startTime = e.time;

            me.isHorizontal = true;
            me.isVertical = true;

            me.startX = touch.pageX;
            me.startY = touch.pageY;
        }

        return ret;
    },

    onTouchMove: function(e) {
        var me = this,
            touch = e.changedTouches[0],
            x = touch.pageX,
            y = touch.pageY,
            deltaX = x - me.startX,
            deltaY = y - me.startY,
            absDeltaX = Math.abs(x - me.startX),
            absDeltaY = Math.abs(y - me.startY),
            duration = e.time - me.startTime,
            minDistance, direction, distance;

        // If delta is 0 on both axes that's not swipe
        if ((absDeltaX === 0 && absDeltaY === 0) || (duration > me.getMaxDuration())) {
            return me.cancel(e);
        }

        if (me.isHorizontal && absDeltaY > me.getMaxOffset()) {
            me.isHorizontal = false;
        }

        if (me.isVertical && absDeltaX > me.getMaxOffset()) {
            me.isVertical = false;
        }

        if (!me.isVertical || !me.isHorizontal) {
            minDistance = me.getMinDistance();

            if (me.isHorizontal && absDeltaX < minDistance) {
                direction = (deltaX < 0) ? 'left' : 'right';
                distance = absDeltaX;
            }
            else if (me.isVertical && absDeltaY < minDistance) {
                direction = (deltaY < 0) ? 'up' : 'down';
                distance = absDeltaY;
            }
        }

        if (!me.isHorizontal && !me.isVertical) {
            return me.cancel(e);
        }

        if (direction && !me.isStarted) {
            me.isStarted = true;

            me.fire('swipestart', e, {
                touch: touch,
                direction: direction,
                distance: distance,
                duration: duration
            });
        }
    },

    onTouchEnd: function(e) {
        var me = this,
            touch, x, y, deltaX, deltaY, absDeltaX, absDeltaY, minDistance, duration,
            direction, distance;

        if (me.onTouchMove(e) !== false) {
            touch = e.changedTouches[0];
            x = touch.pageX;
            y = touch.pageY;
            deltaX = x - me.startX;
            deltaY = y - me.startY;
            absDeltaX = Math.abs(deltaX);
            absDeltaY = Math.abs(deltaY);
            minDistance = me.getMinDistance();
            duration = e.time - me.startTime;

            if (me.isVertical && absDeltaY < minDistance) {
                me.isVertical = false;
            }

            if (me.isHorizontal && absDeltaX < minDistance) {
                me.isHorizontal = false;
            }

            if (me.isHorizontal) {
                direction = (deltaX < 0) ? 'left' : 'right';
                distance = absDeltaX;
            }
            else if (me.isVertical) {
                direction = (deltaY < 0) ? 'up' : 'down';
                distance = absDeltaY;
            }

            me.fire('swipe', e, {
                touch: touch,
                direction: direction,
                distance: distance,
                duration: duration
            });
        }

        return this.callParent([e]);
    },

    onCancel: function(e) {
        this.fire('swipecancel', e, null, true);
    },

    reset: function() {
        var me = this;

        me.startTime = me.isHorizontal = me.isVertical = me.startX = me.startY = null;

        return me.callParent();
    }
}, function(Swipe) {
    var gestures = Ext.manifest.gestures;

    Swipe.instance = new Swipe(gestures && gestures.swipe);
});
