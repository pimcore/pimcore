/**
 * A event recognizer created to recognize swipe movements from the edge of a container.
 */
Ext.define('Ext.event.gesture.EdgeSwipe', {
    extend: 'Ext.event.gesture.Swipe',

    priority: 500,

    handledEvents: [
        'edgeswipe',
        'edgeswipestart',
        'edgeswipeend',
        'edgeswipecancel'
    ],

    config: {
        minDistance: 60
    },

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]),
            touch;

        if (ret !== false) {
            touch = e.changedTouches[0];

            me.direction = null;

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
            absDeltaY = Math.abs(y - me.startY),
            absDeltaX = Math.abs(x - me.startX),
            minDistance = me.getMinDistance(),
            maxOffset = me.getMaxOffset(),
            duration = e.time - me.startTime,
            elementWidth = Ext.Viewport && Ext.Element.getViewportWidth(),
            elementHeight = Ext.Viewport && Ext.Element.getViewportHeight(),
            direction, distance;

        // Check if the swipe is going off vertical
        if (me.isVertical && absDeltaX > maxOffset) {
            me.isVertical = false;
        }

        // Check if the swipe is going off horizontal
        if (me.isHorizontal && absDeltaY > maxOffset) {
            me.isHorizontal = false;
        }

        // If the swipe is both, determin which one it is from the maximum distance travelled
        if (me.isVertical && me.isHorizontal) {
            if (absDeltaY > absDeltaX) {
                me.isHorizontal = false;
            }
            else {
                me.isVertical = false;
            }
        }

        // Get the direction of the swipe
        if (me.isHorizontal) {
            direction = (deltaX < 0) ? 'left' : 'right';
            distance = deltaX;
        }
        else if (me.isVertical) {
            direction = (deltaY < 0) ? 'up' : 'down';
            distance = deltaY;
        }

        direction = me.direction || (me.direction = direction);

        // Invert the distance if we are going up or left so the distance is a positive number
        // FROM the side
        if (direction === 'up') {
            distance = deltaY * -1;
        }
        else if (direction === 'left') {
            distance = deltaX * -1;
        }

        me.distance = distance;

        if (!distance) {
            return me.cancel(e);
        }

        if (!me.isStarted) {
            if ((direction === 'right' && me.startX > minDistance) ||
                (direction === 'down' && me.startY > minDistance) ||
                (direction === 'left' && (elementWidth - me.startX) > minDistance) ||
                (direction === 'up' && (elementHeight - me.startY) > minDistance)) {
                return me.cancel(e);
            }

            me.isStarted = true;
            me.startTime = e.time;

            me.fire('edgeswipestart', e, {
                touch: touch,
                direction: direction,
                distance: distance,
                duration: duration
            });
        }
        else {
            me.fire('edgeswipe', e, {
                touch: touch,
                direction: direction,
                distance: distance,
                duration: duration
            });
        }
    },

    onTouchEnd: function(e) {
        var me = this,
            duration;

        if (me.onTouchMove(e) !== false) {
            duration = e.time - me.startTime;

            me.fire('edgeswipeend', e, {
                touch: e.changedTouches[0],
                direction: me.direction,
                distance: me.distance,
                duration: duration
            });
        }

        return this.reset();
    },

    onCancel: function(e) {
        this.fire('edgeswipecancel', e, {
            touch: e.changedTouches[0]
        }, true);
    },

    reset: function() {
        var me = this;

        me.direction = me.isHorizontal = me.isVertical = me.startX = me.startY =
            me.startTime = me.distance = null;

        return me.callParent();
    }
}, function(EdgeSwipe) {
    var gestures = Ext.manifest.gestures;

    EdgeSwipe.instance = new EdgeSwipe(gestures && gestures.edgeSwipe);
});
