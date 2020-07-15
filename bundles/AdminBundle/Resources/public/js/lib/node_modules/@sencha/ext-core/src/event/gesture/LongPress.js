/**
 * A event recognizer which knows when you tap and hold for more than 1 second.
 */
Ext.define('Ext.event.gesture.LongPress', {
    extend: 'Ext.event.gesture.SingleTouch',

    priority: 400,

    config: {
        moveDistance: 8,
        minDuration: 1000
    },

    handledEvents: ['longpress', 'taphold'],

    /**
     * @member Ext.dom.Element
     * @event longpress
     * Fires when you touch and hold still for more than 1 second.
     * @param {Ext.event.Event} event The {@link Ext.event.Event} event encapsulating the DOM event.
     * @param {HTMLElement} node The target of the event.
     * @param {Object} options The options object passed to Ext.mixin.Observable.addListener.
     */

    /**
     * @member Ext.dom.Element
     * @event taphold
     * @inheritdoc Ext.dom.Element#longpress
     */

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]);

        if (ret !== false) {
            me.startPoint = e.changedTouches[0].point;
            me.setLongPressTimer(e);
        }

        return ret;
    },

    setLongPressTimer: function(e) {
        var me = this;

        Ext.undefer(me.timer);
        me.timer = Ext.defer(me.fireLongPress, me.getMinDuration(), me, [e]);
    },

    onTouchMove: function(e) {
        var me = this,
            point = e.changedTouches[0].point,
            scale = Ext.Element.getViewportScale(),
            // account for scale so that move distance is actual screen pixels, not page pixels
            distance = Math.round(Math.abs(point.getDistanceTo(me.startPoint) * scale));

        if (distance >= me.getMoveDistance()) {
            return me.cancel(e);
        }
    },

    reset: function() {
        var me = this;

        me.timer = me.startPoint = Ext.undefer(me.timer);

        return me.callParent();
    },

    fireLongPress: function(e) {
        var me = this,
            info = {
                touch: e.changedTouches[0],
                duration: me.getMinDuration(),
                startDrag: me.startDrag
            };

        this.fire('taphold', e, info);
        this.fire('longpress', e, info);

        this.reset();
    },

    /**
     * @member Ext.event.Event
     * @method startDrag
     *
     * Initiates a drag gesture in response to this event
     *
     * Only available when `type` is `'longpress'`.  When invoked a dragstart event
     * will be immediately fired at the coordinates of the longpress event.  Thereafter
     * drag events will fire in response to movement on the screen without regard
     * to the distance moved.
     */
    startDrag: function() {
        // the longpress event object is decorated with this function, the scope object
        // here is the event object, not the recognizer
        var dragRecognizer = Ext.event.gesture.Drag.instance,
            touchStartEvent = this.parentEvent;

        dragRecognizer.doDragStart(touchStartEvent, true);
        Ext.event.publisher.Gesture.instance.claimRecognizer(dragRecognizer, touchStartEvent);
    }
}, function(LongPress) {
    var gestures = Ext.manifest.gestures;

    LongPress.instance = new LongPress(gestures && gestures.longPress);
});
