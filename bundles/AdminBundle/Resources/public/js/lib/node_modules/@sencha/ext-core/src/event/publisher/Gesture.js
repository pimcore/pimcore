/**
 * @private
 */
Ext.define('Ext.event.publisher.Gesture', {
    extend: 'Ext.event.publisher.Dom',

    requires: [
        'Ext.util.Point',
        'Ext.AnimationQueue'
    ],

    uses: 'Ext.event.gesture.*',

    type: 'gesture',

    isCancelEvent: {
        touchcancel: 1,
        pointercancel: 1,
        MSPointerCancel: 1
    },

    isEndEvent: {
        mouseup: 1,
        touchend: 1,
        pointerup: 1,
        MSPointerUp: 1
    },

    handledEvents: [],
    handledDomEvents: [],

    constructor: function(config) {
        var me = this,
            handledDomEvents = me.handledDomEvents,
            supports = Ext.supports,
            supportsTouchEvents = supports.TouchEvents,
            onTouchStart = me.onTouchStart,
            onTouchMove = me.onTouchMove,
            onTouchEnd = me.onTouchEnd;

        me.handlers = {
            touchstart: onTouchStart,
            touchmove: onTouchMove,
            touchend: onTouchEnd,
            touchcancel: onTouchEnd,
            pointerdown: onTouchStart,
            pointermove: onTouchMove,
            pointerup: onTouchEnd,
            pointercancel: onTouchEnd,
            MSPointerDown: onTouchStart,
            MSPointerMove: onTouchMove,
            MSPointerUp: onTouchEnd,
            MSPointerCancel: onTouchEnd,
            mousedown: onTouchStart,
            mousemove: onTouchMove,
            mouseup: onTouchEnd
        };

        me.activeTouchesMap = {};
        me.activeTouches = [];
        me.changedTouches = [];
        me.recognizers = [];
        me.eventToRecognizer = {};
        me.cancelEvents = [];

        if (supportsTouchEvents) {
            // bind handlers that are only invoked when the browser has touchevents
            me.onTargetTouchMove = me.onTargetTouchMove.bind(me);
            me.onTargetTouchEnd = me.onTargetTouchEnd.bind(me);
        }

        if (supports.PointerEvents) {
            handledDomEvents.push('pointerdown', 'pointermove', 'pointerup', 'pointercancel');
            me.mousePointerType = 'mouse';
        }
        else if (supports.MSPointerEvents) {
            // IE10 uses vendor prefixed pointer events, IE11+ use unprefixed names.
            handledDomEvents.push('MSPointerDown', 'MSPointerMove', 'MSPointerUp',
                                  'MSPointerCancel');
            me.mousePointerType = 4;
        }
        else if (supportsTouchEvents) {
            handledDomEvents.push('touchstart', 'touchmove', 'touchend', 'touchcancel');
        }

        if (!handledDomEvents.length || (supportsTouchEvents && Ext.os.is.Desktop)) {
            // If the browser doesn't have pointer events or touch events we use mouse events
            // to trigger gestures.  The exception to this rule is touch enabled desktop
            // browsers such as chrome and firefox on Windows touch screen devices.  These
            // browsers accept both touch and mouse input, so we need to listen for both
            // touch events and mouse events.
            handledDomEvents.push('mousedown', 'mousemove', 'mouseup');
        }

        me.initConfig(config);

        return me.callParent();
    },

    onReady: function() {
        this.callParent();

        Ext.Array.sort(this.recognizers, function(recognizerA, recognizerB) {
            var a = recognizerA.priority,
                b = recognizerB.priority;

            return (a > b) ? 1 : (a < b) ? -1 : 0;
        });
    },

    registerRecognizer: function(recognizer) {
        var me = this,
            handledEvents = recognizer.handledEvents,
            ln = handledEvents.length,
            eventName, i;

        // The recognizer will call our onRecognized method when it determines that a
        // gesture has occurred.
        recognizer.setOnRecognized(me.onRecognized);
        recognizer.setCallbackScope(me);

        // the gesture publishers handledEvents array is derived from the handledEvents
        // of all of its recognizers
        for (i = 0; i < ln; i++) {
            eventName = handledEvents[i];
            me.handledEvents.push(eventName);
            me.eventToRecognizer[eventName] = recognizer;
        }

        me.registerEvents(handledEvents);

        me.recognizers.push(recognizer);
    },

    onRecognized: function(recognizer, eventName, e, info, isCancel) {
        var me = this,
            touches = e.touches,
            changedTouches = e.changedTouches,
            ln = changedTouches.length,
            events = me.events,
            queueWasEmpty = !events.length,
            cancelEvents = me.cancelEvents,
            targetGroups, targets, i, touch;

        info = info || {};

        // At this point "e" still refers to the originally dispatched Ext.event.Event that
        // wraps a native browser event such as "touchend", or "mousemove".  We need to
        // dispatch with an an event object that has the correct "recognized" type such
        // as "tap", or "drag".  We don't want to change the type of the original event
        // object because it may be used asynchronously by event handlers, so we create a
        // new object that is chained to the original event.
        info.type = eventName;
        // Touch events have a handy feature - the original target of a touchstart is
        // always the target of successive touchmove/touchend events event if the touch
        // is dragged off of the original target.  Pointer events also have this behavior
        // via the setPointerCapture method, unless their target is removed from the dom
        // mid-gesture, however, we do not currently use setPointerCapture because it
        // can change the target of translated mouse events.  Mouse events do not have this
        // "capturing" feature at all - the target is always the element that was under
        // the mouse at the time the event occurred.  To be safe, and to ensure consistency,
        // we just always set the target of recognized events to be the original target
        // that was cached when the first "start" event was received.
        info.target = changedTouches[0].target;

        // reset stopped and claimed just in case the event that we are wrapping had
        // stoppedPropagation or claimGesture called
        info.stopped = false;
        info.claimed = false;
        info.isGesture = true;

        e = e.chain(info);

        if (!me.gestureTargets) {
            if (ln > 1) {
                targetGroups = [];

                for (i = 0; i < ln; i++) {
                    touch = changedTouches[i];
                    targetGroups.push(touch.targets);
                }

                targets = me.getCommonTargets(targetGroups);
            }
            else {
                targets = changedTouches[0].targets;
            }

            // Cache targets so that they only have to be computed once if multiple
            // gestures are currently being recognized.
            me.gestureTargets = targets;
        }

        if (isCancel && recognizer.isSingleTouch && (touches.length > 1)) {
            // single touch recognizer cancelled by the start of a second touch.
            // push into a separate queue which does not use the targets common to all
            // touches (this.gestureTargets) as the targets for publishing but rather
            // only uses the targets for the initial touch.
            e.target = touches[0].target;
            cancelEvents.push(e);
        }
        else {
            events.push(e);
        }

        if (queueWasEmpty) {
            // if there were no events in the queue previously, it means the dom event
            // has already been published, which means a recognizer must have recognized
            // a gesture asynchronously (e.g. singletap fires on a timer)
            // if this is the case we must publish now, otherwise we wait for the dom
            // event handler to publish after it is finished invoking the recognizers
            me.publishGestures();
        }
    },

    getCommonTargets: function(targetGroups) {
        var firstTargetGroup = targetGroups[0],
            ln = targetGroups.length,
            commonTargets = [],
            i = 1,
            target, targets, j;

        if (ln === 1) {
            return firstTargetGroup;
        }

        while (true) { // eslint-disable-line no-constant-condition
            target = firstTargetGroup[firstTargetGroup.length - i];

            if (!target) {
                return commonTargets;
            }

            for (j = 1; j < ln; j++) {
                targets = targetGroups[j];

                if (targets[targets.length - i] !== target) {
                    return commonTargets;
                }
            }

            commonTargets.unshift(target);
            i++;
        }

        return commonTargets; // eslint-disable-line no-unreachable
    },

    invokeRecognizers: function(methodName, e) {
        var recognizers = this.recognizers,
            ln = recognizers.length,
            i, recognizer;

        if (methodName === 'onStart') {
            for (i = 0; i < ln; i++) {
                recognizers[i].isActive = true;
            }
        }

        for (i = 0; i < ln; i++) {
            recognizer = recognizers[i];

            if (recognizer.isActive && recognizer[methodName].call(recognizer, e) === false) {
                recognizer.isActive = false;
            }
        }
    },

    /**
     * When a gesture has been claimed this method is invoked to remove gesture events of
     * other kinds.  See implementation in Gesture publisher.
     * @param {Ext.event.Event[]} events
     * @param {String} claimedEvent
     * @return {Number} The new index of the claimed event
     * @private
     */
    filterClaimed: function(events, claimedEvent) {
        var me = this,
            eventToRecognizer = me.eventToRecognizer,
            claimedEventType = claimedEvent.type,
            claimedRecognizer = eventToRecognizer[claimedEventType],
            claimedEventIndex, recognizer, type, i;

        for (i = events.length; i--;) {
            type = events[i].type;

            if (type === claimedEventType) {
                claimedEventIndex = i;
            }
            else {
                recognizer = eventToRecognizer[type];

                // if there is no claimed recognizer it means the user must have invoked
                // claimGesture on a dom event (touchstart, touchmove etc).  If this is the
                // case we need to cease firing all gesture events, otherwise we allow only
                // the "claimed" recognizer to continue to dispatch events.
                if (!claimedRecognizer || (recognizer && (recognizer !== claimedRecognizer))) {
                    events.splice(i, 1);

                    if (claimedEventIndex) {
                        claimedEventIndex--;
                    }
                }
            }
        }

        me.claimRecognizer(claimedRecognizer, events[0]);

        return claimedEventIndex;
    },

    /**
     * Deactivates all recognizers other than the "claimed" recognizer
     * @param {Ext.event.gesture.Recognizer} claimedRecognizer
     * @param {Ext.event.Event} e
     * @private
     */
    claimRecognizer: function(claimedRecognizer, e) {
        var me = this,
            recognizers = me.recognizers,
            i, ln, recognizer;

        for (i = 0, ln = recognizers.length; i < ln; i++) {
            recognizer = recognizers[i];

            // cancel recognition for all recognizers other than the one that was claimed
            if (recognizer !== claimedRecognizer) {
                recognizer.isActive = false;
                recognizer.cancel(e);
            }
        }

        if (me.events.length) {
            // if any recognizers added cancelation events...
            me.publishGestures(true);
        }
    },

    publishGestures: function(claimed) {
        var me = this,
            cancelEvents = me.cancelEvents,
            events = me.events,
            gestureTargets = me.gestureTargets;

        if (cancelEvents.length) {
            me.cancelEvents = [];
            // Since cancellation events cannot be claimed we pass true here which
            // prevents them from being claimed.
            me.publish(cancelEvents, me.getPropagatingTargets(cancelEvents[0].target), true);
        }

        if (events.length) {
            // It is important to reset the events property to an empty array before
            // publishing since since events may be added to the array during publishing.
            // This can happen if an event is claimed, thus triggering "cancel" gesture events.
            me.events = [];
            me.gestureTargets = null;

            me.publish(events, gestureTargets || me.getPropagatingTargets(events[0].target),
                       claimed);
        }
    },

    updateTouches: function(e) {
        var me = this,
            browserEvent = e.browserEvent,
            type = e.type,
            // the touchSource is the object from which we get data about the changed touch
            // point or points related to an event object, such as identifier, target, and
            // coordinates. For touch event the source is changedTouches, for mouse and
            // pointer events it is the event object itself.
            touchSources = browserEvent.changedTouches || [browserEvent],
            activeTouches = me.activeTouches,
            activeTouchesMap = me.activeTouchesMap,
            changedTouches = [],
            touchSource, identifier, touch, target, i, ln, x, y;

        for (i = 0, ln = touchSources.length; i < ln; i++) {
            touchSource = touchSources[i];

            if ('identifier' in touchSource) {
                // touch events have an identifier property on their touches objects.
                // It can be 0, hence the "in" check
                identifier = touchSource.identifier;
            }
            else if ('pointerId' in touchSource) {
                // Pointer events have a pointerId on the event object itself
                identifier = touchSource.pointerId;
            }
            else {
                // Mouse events don't have an identifier, so we always use 1 since there
                // can only be one mouse touch point active at a time
                identifier = 1;
            }

            touch = activeTouchesMap[identifier];

            if (!touch) {
                target = Ext.event.Event.resolveTextNode(touchSource.target);
                touch = activeTouchesMap[identifier] = {
                    identifier: identifier,
                    target: target,
                    // There are 2 main advantages to caching the targets here, vs.
                    // waiting until onRecognized
                    // 1. for "move" events we don't have to construct the targets array
                    // for every event - a theoretical performance win
                    // 2. if the target is removed from the dom mid-gesture we still
                    // want any gestures listeners on elements that were above the
                    // target to complete.  This means the propagating targets must reflect
                    // the target element's initial hierarchy when the gesture began
                    targets: me.getPropagatingTargets(target)
                };
                activeTouches.push(touch);
            }

            if (me.isEndEvent[type] || me.isCancelEvent[type]) {
                delete activeTouchesMap[identifier];
                Ext.Array.remove(activeTouches, touch);
            }

            x = Math.round(touchSource.pageX);
            y = Math.round(touchSource.pageY);

            touch.pageX = x;
            touch.pageY = y;
            // recognizers frequently use Point methods, so go ahead and create a Point.
            touch.point = new Ext.util.Point(x, y);
            changedTouches.push(touch);
        }

        // decorate the event object with the touch point info so that it can be used from
        // within gesture recognizers (clone touches, just in case event object is used
        // asynchronously since this.activeTouches is continuously modified)
        e.touches = Ext.Array.clone(activeTouches);
        // no need to clone changedTouches since we just created it from scratch
        e.changedTouches = changedTouches;
    },

    publishDelegatedDomEvent: function(e) {
        var me = this;

        if (!e.button || e.button < 1) {
            // mouse gestures (and pointer gestures triggered by a mouse) can only be
            // initiated using the left button (0).  button value < 0 is also acceptable
            // (e.g. pointermove has a button value of -1)

            // Track the event on the instance so it can be fired after gesture recognition
            // completes (if any gestures are recognized they will be added to this array)
            me.events = [e];

            // This property on the browser event object indicates that the event has bubbled
            // up to the window object and has begun being handled by the gesture publisher.
            // If the user calls stopPropagation on an event that has not yet been "handled"
            // it triggers gesture cancellation and cleanup.
            e.browserEvent.$extHandled = true;

            me.handlers[e.type].call(me, e);
        }
        else {
            // mouse events *with* button still need to be published.
            me.callParent([e]);
        }
    },

    onTouchStart: function(e) {
        var me = this,
            target = e.target,
            touches = e.browserEvent.touches;

        if (e.browserEvent.type === 'touchstart') {
            // When using touch events, if the target is removed from the dom mid-gesture
            // the touchend event cannot be handled normally because it will not bubble
            // to the top of the dom since the target el is no longer attached to the dom.
            // Add some special handlers to clean everything up. (see onTargetTouchEnd)
            // use addEventListener directly so that we don't have to spin up an instance
            // of Ext.Element for every event target.
            target.addEventListener('touchmove', me.onTargetTouchMove);
            target.addEventListener('touchend', me.onTargetTouchEnd);
            target.addEventListener('touchcancel', me.onTargetTouchEnd);
        }

        // There is a bug in IOS8 where touchstart, but not touchend event is
        // fired when clicking on controls for audio/video, which can leave
        // us in a bad state here.
        if (touches && touches.length <= me.activeTouches.length) {
            me.removeGhostTouches(touches);
        }

        me.updateTouches(e);

        if (!me.isStarted) {
            // Disable garbage collection during gestures so that if the target element
            // of a gesture is removed from the dom, it does not get garbage collected
            // until the gesture is complete
            if (Ext.enableGarbageCollector) {
                Ext.dom.GarbageCollector.pause();
            }

            // this is the first active touch - invoke "onStart" which indicates the
            // beginning of a gesture
            me.isStarted = true;
            me.invokeRecognizers('onStart', e);
        }

        me.invokeRecognizers('onTouchStart', e);

        me.publishGestures();
    },

    onTouchMove: function(e) {
        var me = this,
            mousePointerType = me.mousePointerType,
            isStarted = me.isStarted;

        if (isStarted || (e.pointerType !== 'mouse')) {
            me.updateTouches(e);
        }

        if (isStarted) {
            // In IE10/11, the corresponding pointerup event is not fired after the pointerdown
            // after the mouse is released from the scrollbar. However, it does fire a pointermove
            // event with buttons: 0, so we capture that here and ensure the touch end process
            // is completed.
            if (mousePointerType && e.browserEvent.pointerType === mousePointerType &&
                e.buttons === 0) {
                e.type = Ext.dom.Element.prototype.eventMap.touchend;
                e.button = 0;
                me.onTouchEnd(e);

                return;
            }

            if (e.changedTouches.length > 0) {
                me.invokeRecognizers('onTouchMove', e);
            }
        }

        me.publishGestures();
    },

    // This method serves as the handler for both "end" and "cancel" events.  This is
    // because they are handled identically with the exception of the recognizer method
    // that is called.
    onTouchEnd: function(e) {
        var me = this,
            isStarted = me.isStarted,
            touchCount;

        if (isStarted || (e.pointerType !== 'mouse')) {
            me.updateTouches(e);
        }

        if (!isStarted) {
            me.publishGestures();

            return;
        }

        touchCount = me.activeTouches.length;

        // If an exception is thrown in any of the recognizers, we still need to run
        // the cleanup. Otherwise the gesture might get "stuck" and *every* pointer event
        // after that will fire the same handlers over and over, potentially spewing
        // the same exceptions endlessly. See https://sencha.jira.com/browse/EXTJS-15674.
        // We don't want to mask the original exception though, let it propagate.
        try {
            me.invokeRecognizers(me.isCancelEvent[e.type] ? 'onTouchCancel' : 'onTouchEnd', e);
        }
        finally {
            // This can throw too
            try {
                if (!touchCount) {
                    // no more active touches - invoke onEnd to indicate the end of the gesture
                    me.isStarted = false;
                    me.invokeRecognizers('onEnd', e);
                }
            }
            finally {
                // Right, THIS can throw again!
                try {
                    me.publishGestures();
                }
                finally {
                    if (!touchCount) {
                        // Gesture is finished, safe to resume garbage collection so that any target
                        // elements destroyed while gesture was in progress can be collected
                        if (Ext.enableGarbageCollector) {
                            Ext.dom.GarbageCollector.resume();
                        }
                    }

                    // The parent code may not to be reached in this case
                    me.reEnterCountAdjusted = true;
                    me.reEnterCount--;
                }
            }
        }
    },

    onTargetTouchMove: function(e) {
        if (Ext.elevateFunction) {
            // using [e] is faster than using arguments in most browsers
            // http://jsperf.com/passing-arguments
            Ext.elevateFunction(this.doTargetTouchMove, this, [e]);
        }
        else {
            this.doTargetTouchMove(e);
        }
    },

    doTargetTouchMove: function(e) {
        var me = this;

        // handle touchmove if the target el was removed from dom mid-gesture.
        // see onTouchStart/onTargetTouchEnd for further explanation
        if (!Ext.getBody().contains(e.target)) {
            me.reEnterCountAdjusted = false;
            me.reEnterCount++;

            this.onTouchMove(new Ext.event.Event(e));

            if (!me.reEnterCountAdjusted) {
                me.reEnterCount--;
            }
        }
    },

    onTargetTouchEnd: function(e) {
        if (Ext.elevateFunction) {
            // using [e] is faster than using arguments in most browsers
            // http://jsperf.com/passing-arguments
            Ext.elevateFunction(this.doTargetTouchEnd, this, [e]);
        }
        else {
            this.doTargetTouchEnd(e);
        }
    },

    doTargetTouchEnd: function(e) {
        var me = this,
            target = e.target;

        target.removeEventListener('touchmove', me.onTargetTouchMove);
        target.removeEventListener('touchend', me.onTargetTouchEnd);
        target.removeEventListener('touchcancel', me.onTargetTouchEnd);

        // if the target el was removed from the dom mid-gesture, then the touchend event,
        // when it occurs, will not be handled because it will not bubble to the top of
        // the dom. This is because the "target" of the touchend is the removed element.
        // If this is the case, go ahead and trigger touchend handling now.
        // Detect whether the target was removed from the DOM mid gesture by using Element.contains.
        // Originally we attempted to detect this by listening for the DOMNodeRemovedFromDocument
        // event, and setting a flag on the element when it was removed, however that
        // approach only works when the element is removed using removedChild, and fails
        // if the element is removed because some ancestor had innerHTML assigned.
        // note: this handling is applicable for actual touchend events, pointer and mouse
        // events will fire on whatever element is under the cursor/pointer after the
        // original target has been removed.
        if (!Ext.getBody().contains(target)) {
            me.reEnterCountAdjusted = false;
            me.reEnterCount++;

            me.onTouchEnd(new Ext.event.Event(e));

            if (!me.reEnterCountAdjusted) {
                me.reEnterCount--;
            }
        }
    },

    /**
     * Resets the internal state of the Gesture publisher and all of its recognizers.
     * Applications will not typically need to use this method, but it is useful for
     * Unit-testing situations where a clean slate is required for each test.
     *
     * Calling this method will also reset the state of Ext.event.publisher.Dom
     */
    reset: function() {
        var me = this,
            recognizers = me.recognizers,
            ln = recognizers.length,
            i, recognizer;

        me.activeTouchesMap = {};
        me.activeTouches = [];
        me.changedTouches = [];
        me.isStarted = false;
        me.gestureTargets = null;
        me.events = [];
        me.cancelEvents = [];

        for (i = 0; i < ln; i++) {
            recognizer = recognizers[i];
            recognizer.reset();
            recognizer.isActive = false;
        }

        this.callParent();
    },

    privates: {
        removeGhostTouches: function(touches) {
            var ids = {},
                len = touches.length,
                activeTouches = this.activeTouches,
                map = this.activeTouchesMap,
                i, id, touch;

            // Collect the actual touches
            for (i = 0; i < len; ++i) {
                ids[touches[i].identifier] = true;
            }

            i = activeTouches.length;

            while (i--) {
                touch = activeTouches[i];
                id = touch.identifier;

                if (!touches[id]) {
                    Ext.Array.remove(activeTouches, touch);
                    delete map[id];
                }
            }
        }
    }
}, function(Gesture) {
    var EventProto = Event.prototype,
        stopPropagation = EventProto.stopPropagation;

    if (stopPropagation) {
        EventProto.stopPropagation = function() {
            var me = this,
                publisher = Gesture.instance,
                type = me.type,
                e;

            if (!me.$extHandled && publisher.handles[type]) {
                // User called stop propagation on a native event used by the gesture publisher
                // to synthesize gesture events. Cancel gesture recognition and reset the publisher.
                e = new Ext.event.Event(me);

                publisher.updateTouches(e);
                publisher.invokeRecognizers('onTouchCancel', e);
                publisher.reset();
                publisher.reEnterCountAdjusted = true;
            }

            stopPropagation.apply(me, arguments);
        };
    }

    Gesture.instance = Ext.$gesturePublisher = new Gesture();
});
