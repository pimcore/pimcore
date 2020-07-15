/**
 * A base class for all gesture recognizers.
 *
 * The following gestures are enabled by default in both Ext JS and Sencha Touch:
 *
 * * {@link Ext.event.gesture.Tap}
 * * {@link Ext.event.gesture.DoubleTap}
 * * {@link Ext.event.gesture.LongPress}
 * * {@link Ext.event.gesture.Drag}
 * * {@link Ext.event.gesture.Swipe}
 * * {@link Ext.event.gesture.Pinch}
 * * {@link Ext.event.gesture.Rotate}
 * * {@link Ext.event.gesture.EdgeSwipe}
 *
 * @abstract
 * @private
 */
Ext.define('Ext.event.gesture.Recognizer', {
    requires: ['Ext.event.publisher.Gesture'],
    mixins: ['Ext.mixin.Identifiable'],

    /**
     * @property {Number}
     * The priority of the recognizer. Determines the order in which it recognizes gestures
     * relative to other recognizers.  The default recognizers use the following priorities:
     *
     * - Ext.event.gesture.Drag: 100
     * - Ext.event.gesture.Tap: 200
     * - Ext.event.gesture.DoubleTap: 300
     * - Ext.event.gesture.LongPress: 400
     * - Ext.event.gesture.EdgeSwipe: 500
     * - Ext.event.gesture.Swipe: 600
     * - Ext.event.gesture.Pinch: 700
     * - Ext.event.gesture.Rotate: 800
     */
    priority: 0,

    handledEvents: [],

    isStarted: false,

    config: {
        onRecognized: Ext.emptyFn,
        callbackScope: null
    },

    constructor: function(config) {
        this.initConfig(config);
        Ext.event.publisher.Gesture.instance.registerRecognizer(this);
    },

    onStart: Ext.emptyFn,

    onEnd: Ext.emptyFn,

    onTouchStart: Ext.emptyFn,

    onTouchMove: Ext.emptyFn,

    onTouchEnd: function() {
        return this.reset();
    },

    onTouchCancel: function(e) {
        return this.cancel(e);
    },

    fire: function(eventName, e, info, isCancel) {
        this.getOnRecognized().call(this.getCallbackScope(), this, eventName, e, info, isCancel);
    },

    cancel: function(e) {
        if (this.isStarted) {
            // If the recognizer is started, that is to say, it has already begun publishing
            // events for the current gesture, then we need to make sure it fires a "cancel"
            // event (implementation determined by subclasses).
            this.onCancel(e);
        }

        return this.reset();
    },

    onCancel: Ext.emptyFn,

    reset: function() {
        this.isStarted = false;

        return false;
    }
});
