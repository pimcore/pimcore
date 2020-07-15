/**
 * A simple event recognizer which knows when you double tap.
 */
Ext.define('Ext.event.gesture.DoubleTap', {

    extend: 'Ext.event.gesture.SingleTouch',

    priority: 300,

    config: {
        /**
         * @cfg {Number}
         * The maximum distance a touch can move without canceling recognition
         */
        moveDistance: 8,
        /**
         * @cfg {Number}
         * The minimum distance the second tap can occur from the first tap and still
         * be considered a doubletap
         */
        tapDistance: 24,
        maxDuration: 300
    },

    handledEvents: ['singletap', 'doubletap'],

    /**
     * @member Ext.dom.Element
     * @event singletap
     * Fires when there is a single tap.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @member Ext.dom.Element
     * @event doubletap
     * Fires when there is a double tap.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    singleTapTimer: null,

    startTime: 0,

    lastTapTime: 0,

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]),
            lastStartPoint;

        if (ret !== false) {
            me.isStarted = true;

            // the start point of the last touch that occurred.
            lastStartPoint = me.lastStartPoint = e.changedTouches[0].point;

            // the start point of the "first" touch in this gesture
            me.startPoint = me.startPoint || lastStartPoint;

            me.startTime = e.time;

            Ext.undefer(me.singleTapTimer);
        }

        return ret;
    },

    onTouchMove: function(e) {
        var me = this,
            point = e.changedTouches[0].point,
            scale = Ext.Element.getViewportScale(),
            // account for scale so that move distance is actual screen pixels, not page pixels
            distance = Math.round(Math.abs(point.getDistanceTo(me.lastStartPoint) * scale));

        if (distance >= me.getMoveDistance()) {
            return me.cancel(e);
        }
    },

    onTouchEnd: function(e) {
        var me = this,
            maxDuration = me.getMaxDuration(),
            time = e.time,
            target = e.target,
            lastTapTime = me.lastTapTime,
            lastTarget = me.lastTarget,
            point = e.changedTouches[0].point,
            duration, scale, distance;

        me.lastTapTime = time;
        me.lastTarget = target;

        if (lastTapTime) {
            duration = time - lastTapTime;

            if (duration <= maxDuration) {
                scale = Ext.Element.getViewportScale();
                // account for scale so that move distance is actual screen pixels, not page pixels
                distance = Math.round(Math.abs(point.getDistanceTo(me.startPoint) * scale));

                if (distance <= me.getTapDistance()) {
                    if (target !== lastTarget) {
                        return me.cancel(e);
                    }

                    me.lastTarget = null;
                    me.lastTapTime = 0;

                    me.fire('doubletap', e, {
                        touch: e.changedTouches[0],
                        duration: duration
                    });

                    return me.callParent([e]);
                }
            }
        }

        if (time - me.startTime > maxDuration) {
            me.fire('singletap', e);
            me.reset();
        }
        else {
            me.setSingleTapTimer(e);
        }
    },

    setSingleTapTimer: function(e) {
        var me = this;

        me.singleTapTimer = Ext.defer(function() {
            me.fire('singletap', e);
            me.reset();
        }, me.getMaxDuration());
    },

    reset: function() {
        var me = this;

        Ext.undefer(me.singleTapTimer);

        me.startTime = me.lastTapTime = 0;

        me.lastStartPoint = me.startPoint = me.singleTapTimer = null;

        return me.callParent();
    }
}, function(DoubleTap) {
    var gestures = Ext.manifest.gestures;

    DoubleTap.instance = new DoubleTap(gestures && gestures.doubleTap);
});
