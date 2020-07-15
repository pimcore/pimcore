/**
 * A event recognizer which knows when you pinch.
 */
Ext.define('Ext.event.gesture.Pinch', {
    extend: 'Ext.event.gesture.MultiTouch',

    priority: 700,

    handledEvents: ['pinchstart', 'pinch', 'pinchend', 'pinchcancel'],

    /**
     * @member Ext.dom.Element
     * @event pinchstart
     * Fired once when a pinch has started.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @member Ext.dom.Element
     * @event pinch
     * Fires continuously when there is pinching (the touch must move for this to be fired).
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @member Ext.dom.Element
     * @event pinchend
     * Fires when a pinch has ended.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @property {Number} scale
     * The scape of a pinch event.
     *
     * **This is only available when the event type is `pinch`**
     * @member Ext.event.Event
     */

    startDistance: 0,

    lastTouches: null,

    onTouchMove: function(e) {
        var me = this,
            touches, firstPoint, secondPoint, distance;

        if (me.isTracking) {
            touches = e.touches;

            firstPoint = touches[0].point;
            secondPoint = touches[1].point;

            distance = firstPoint.getDistanceTo(secondPoint);

            if (distance === 0) {
                return;
            }

            if (!me.isStarted) {

                me.isStarted = true;

                me.startDistance = distance;

                me.fire('pinchstart', e, {
                    touches: touches,
                    distance: distance,
                    scale: 1
                });
            }
            else {
                me.fire('pinch', e, {
                    touches: touches,
                    distance: distance,
                    scale: distance / me.startDistance
                });
            }
        }
    },

    onTouchEnd: function(e) {
        if (this.isStarted) {
            this.fire('pinchend', e);
        }

        return this.callParent([e]);
    },

    onCancel: function(e) {
        this.fire('pinchcancel', e, null, true);
    },

    reset: function() {
        this.lastTouches = null;
        this.startDistance = 0;

        return this.callParent();
    }
}, function(Pinch) {
    var gestures = Ext.manifest.gestures;

    Pinch.instance = new Pinch(gestures && gestures.pinch);
});
