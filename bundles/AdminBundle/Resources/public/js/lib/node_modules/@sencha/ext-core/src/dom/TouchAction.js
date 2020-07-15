/**
 * Processes the touch-action css property for an Ext.dom.Element, and provides
 * compatible behavior on devices that do not support pointer events.
 * @private
 */
Ext.define('Ext.dom.TouchAction', {
    singleton: true,
    requires: [
        'Ext.dom.Element',
        'Ext.util.Point'
    ],

    lastTouchStartTime: 0,

    /**
     * @property
     * The minimum distance a touch must move before being cancelled (only applicable
     * on browsers that use touch events).  Allows the direction of movement to be detected
     * so that panX and panY can be separately cancelled.
     * @private
     */
    minMoveDistance: 8,

    spaceRe: /\s+/,

    preventSingle: null,
    preventMulti: null,
    disabledOverflowDom: null,

    panXCls: Ext.baseCSSPrefix + 'touch-action-pan-x',
    panYCls: Ext.baseCSSPrefix + 'touch-action-pan-y',

    cssValues: [
        'none',
        'pan-x',
        'pan-y',
        'pan-x pan-y',
        'pinch-zoom',
        'pan-x pinch-zoom',
        'pan-y pinch-zoom',
        'pan-x pan-y pinch-zoom',
        'double-tap-zoom',
        'pan-x double-tap-zoom',
        'pan-y double-tap-zoom',
        'pan-x pan-y double-tap-zoom',
        'pinch-zoom double-tap-zoom',
        'pan-x pinch-zoom double-tap-zoom',
        'pan-y pinch-zoom double-tap-zoom',
        ''
    ],

    objectValues: [
        { panX: false, panY: false, pinchZoom: false, doubleTapZoom: false },
        { panX: true, panY: false, pinchZoom: false, doubleTapZoom: false },
        { panX: false, panY: true, pinchZoom: false, doubleTapZoom: false },
        { panX: true, panY: true, pinchZoom: false, doubleTapZoom: false },
        { panX: false, panY: false, pinchZoom: true, doubleTapZoom: false },
        { panX: true, panY: false, pinchZoom: true, doubleTapZoom: false },
        { panX: false, panY: true, pinchZoom: true, doubleTapZoom: false },
        { panX: true, panY: true, pinchZoom: true, doubleTapZoom: false },
        { panX: false, panY: false, pinchZoom: false, doubleTapZoom: true },
        { panX: true, panY: false, pinchZoom: false, doubleTapZoom: true },
        { panX: false, panY: true, pinchZoom: false, doubleTapZoom: true },
        { panX: true, panY: true, pinchZoom: false, doubleTapZoom: true },
        { panX: false, panY: false, pinchZoom: true, doubleTapZoom: true },
        { panX: true, panY: false, pinchZoom: true, doubleTapZoom: true },
        { panX: false, panY: true, pinchZoom: true, doubleTapZoom: true },
        { panX: true, panY: true, pinchZoom: true, doubleTapZoom: true }
    ],

    attributeName: 'data-extTouchAction',

    constructor: function() {
        var me = this,
            supports = Ext.supports;

        if (supports.TouchAction) {
            me.cssProp = 'touch-action';
        }
        else if (supports.MSPointerEvents) {
            me.cssProp = '-ms-touch-action';
        }

        if (supports.TouchEvents) {
            Ext.getWin().on({
                touchstart: 'onTouchStart',
                touchmove: 'onTouchMove',
                touchend: 'onTouchEnd',
                scope: me,
                delegated: false,
                translate: false,
                capture: true,
                priority: 5000
            });

            Ext.on({
                scroll: 'onScroll',
                scope: me,
                destroyable: true
            });
        }

        //<debug>
        if (Ext.isFunction(Object.freeze)) {
            /* eslint-disable-next-line vars-on-top, one-var */
            var objectValues = me.objectValues,
                i, ln;

            for (i = 0, ln = objectValues.length; i < ln; i++) {
                Object.freeze(objectValues[i]);
            }
        }
        //</debug>

    },

    /**
     * Returns true if all of the event's targets are contained within the element
     * @param {HTMLElement} dom
     * @param {Ext.event.Event} e
     * @private
     * @return {Boolean}
     */
    containsTargets: function(dom, e) {
        var contains = true,
            event = e.browserEvent,
            touches = e.type === 'touchend' ? event.changedTouches : event.touches,
            i, ln;

        for (i = 0, ln = touches.length; i < ln; i++) {
            if (!dom.contains(touches[i].target)) {
                contains = false;
                break;
            }
        }

        return contains;
    },

    /**
     * Forces overflow to 'hidden' on the x or y axis starting with the "el" and ascending
     * upward to all ancestors that have overflow 'auto' or 'scroll' on the given axis.
     * The added classes will remain in place until the end of the current gesture (when
     * the final touchend event is received) at which point they will be removed by invoking
     * {@link #resetOverflow}.
     *
     * This is invoked at the beginning of a gesture when we make the initial determination
     * that we are disabling scrolling on one of the axes (because touch-action contains
     * pan-x or pan-y in the value, but not both).  Dynamically manipulating the overflow
     * in this way vs just adding a static class ensures that the non-touch-scrolling axis
     * can still be scrolled using the mouse.
     *
     * We only do this on browsers that do not have space-consuming scrollbars (e.g. on
     * android, but not on chrome desktop) to avoid a situation where scrollbars disappear
     * during the gesture and re-appear afterwards.
     *
     * We also skip this on iOS because of the following bugs in safari (already filed with apple):
     * 1. Dynamically setting scroll position to hidden on either axis resets visual scroll
     * position to 0:
     * https://gist.github.com/pguerrant/105e8d91e3ffcb1b6e2eed7ecc0571d3
     * 2. Scrolling an element that has overflow set to hidden on either axis causes scroll
     * position to be reset to 0 on the hidden axis:
     * https://gist.github.com/pguerrant/e959c47a6b1d4b841cc3267a61950f33
     *
     * The downside is that on iOS, and on desktop-touch hybrid browsers such as chrome once
     * the user initiates scrolling in an allowed direction, it cannot be disabled in the
     * disallowed direction, This trade-off seems better than the alternatives -
     * vanishing/reappearing scrollbars on desktop, and scroll positions resetting to 0 on iOS.
     *
     * @param {HTMLElement} dom
     * @param {Boolean} [vertical=false] `true` to disable scrolling on the y axis, `false`
     * to disable scrolling on the x axis
     *
     * @private
     */
    disableOverflow: function(dom, vertical) {
        var me = this,
            overflowName = vertical ? 'overflow-y' : 'overflow-x',
            overflowStyle, cls;

        if (!me.disabledOverflowDom && !Ext.isiOS && !Ext.scrollbar.width()) {
            me.disabledOverflowDom = dom;
            cls = vertical ? me.panXCls : me.panYCls;

            while (dom) {
                overflowStyle = Ext.fly(dom).getStyle(overflowName);

                if (overflowStyle === 'auto' || overflowStyle === 'scroll') {
                    Ext.fly(dom).addCls(cls);
                }

                dom = dom.parentNode;
            }
        }
    },

    /**
     * Returns the touch action for the passed HTMLElement
     * @param {HTMLElement} dom
     * @return {Object}
     */
    get: function(dom) {
        var flags = dom.getAttribute(this.attributeName),
            ret = null;

        if (flags != null) {
            ret = this.objectValues[flags];
        }

        return ret;
    },

    /**
     * Accepts a touch action in the object form accepted by
     * {@link Ext.Component}, and converts it to a number representing the desired touch action(s).
     *
     * All touchActions absent from the passed object are defaulted to true.
     *
     * @param {Object} touchAction
     * @returns {Number} A number representing the touch action using the following mapping:
     *
     *     panX            1  "00000001"
     *     panY            2  "00000010"
     *     pinchZoom       4  "00000100"
     *     doubleTapZoom   8  "00001000"
     *
     * 0 represents a css value of "none" and all bits on is the same as "auto"
     * @private
     */
    getFlags: function(touchAction) {
        var flags;

        if (typeof touchAction === 'number') {
            flags = touchAction;
        }
        else {
            flags = 0;

            if (touchAction.panX !== false) {
                flags |= 1;
            }

            if (touchAction.panY !== false) {
                flags |= 2;
            }

            if (touchAction.pinchZoom !== false) {
                flags |= 4;
            }

            if (touchAction.doubleTapZoom !== false) {
                flags |= 8;
            }
        }

        return flags;
    },

    isScrollable: function(el, vertical, forward) {
        var overflowStyle = Ext.fly(el).getStyle(vertical ? 'overflow-y' : 'overflow-x'),
            isScrollable = (overflowStyle === 'auto' || overflowStyle === 'scroll');

        if (isScrollable) {
            if (vertical) {
                isScrollable = forward
                    ? (el.scrollTop + el.clientHeight) < el.scrollHeight
                    : el.scrollTop > 0;
            }
            else {
                isScrollable = forward
                    ? (el.scrollLeft + el.clientWidth) < el.scrollWidth
                    : el.scrollLeft > 0;
            }
        }

        return isScrollable;
    },

    lookupFlags: function(dom) {
        return parseInt((dom.getAttribute && dom.getAttribute(this.attributeName)) || 15, 10);
    },

    onScroll: function() {
        // This flag tracks whether or not a scroll has occurred since the last touchstart event
        this.scrollOccurred = true;

        // once scrolling begins we cannot attempt to preventDefault on the touchend event
        // or chrome will issue warnings in the console.
        this.isDoubleTap = false;
    },

    onTouchEnd: function(e) {
        var me = this,
            dom = e.target,
            touchCount, flags, doubleTapZoom;

        touchCount = e.browserEvent.touches.length;

        if (touchCount === 0) {
            if (me.isDoubleTap) {
                while (dom) {
                    flags = me.lookupFlags(dom);

                    if (flags != null) {
                        doubleTapZoom = flags & 8;

                        if (!doubleTapZoom) {
                            e.preventDefault();
                        }
                    }

                    dom = dom.parentNode;
                }
            }

            me.isDoubleTap = false;
            me.preventSingle = null;
            me.preventMulti = null;
            me.resetOverflow();
        }
    },

    onTouchMove: function(e) {
        var me = this,
            prevent = null,
            dom = e.target,
            flags, touchCount, panX, panY, point, startPoint, isVertical,
            scale, distance, deltaX, deltaY, preventSingle, preventMulti;

        preventSingle = me.preventSingle;
        preventMulti = me.preventMulti;

        touchCount = e.browserEvent.touches.length;

        // Don't check for touchCount here when checking for preventMulti.
        // This ensures that if we determined not to cancel the multi-touch gesture
        // previously we will not attempt to start canceling once touch count is
        // reduced to one (If we do attempt to start canceling at that point chrome
        // will issue warnings in the console because scrolling has already started).
        if ((touchCount === 1 && (preventSingle === false)) || (preventMulti === false)) {
            return;
        }

        if ((touchCount > 1 && (preventMulti === true)) ||
            (touchCount === 1 && (preventSingle === true))) {
            prevent = true;
        }
        else {
            if (touchCount === 1) {
                point = e.getPoint();
                startPoint = me.startPoint;
                scale = Ext.Element.getViewportScale();

                // account for scale so that move distance is actual screen pixels, not page pixels
                distance = point.getDistanceTo(me.startPoint) * scale;
                deltaX = point.x - startPoint.x;
                deltaY = point.y - startPoint.y;

                isVertical = Math.abs(deltaY) >= Math.abs(deltaX);
            }

            while (dom && (dom.nodeType === 1)) {
                flags = me.lookupFlags(dom);

                if (flags & 0) { // touch-action: none
                    prevent = true;
                }
                else if (touchCount === 1) {
                    panX = !!(flags & 1);
                    panY = !!(flags & 2);

                    if (panX && panY) {
                        prevent = false;
                    }
                    else if (!panX && !panY) {
                        prevent = true;
                    }
                    else if (distance >= me.minMoveDistance) {
                        prevent = !!((panX && isVertical) || (panY && !isVertical));
                    }

                    // if the element itself is scrollable, and has no touch action
                    // preventing it from scrolling, allow it to scroll - do
                    // not allow an ancestor's touchAction to prevent scrolling
                    if (!prevent && me.isScrollable(dom, isVertical, (isVertical ? deltaY : deltaX) < 0)) { // eslint-disable-line max-len
                        break;
                    }
                }
                else if (me.containsTargets(dom, e)) { // multi-touch, all targets contained
                    prevent = !(flags & 4);
                }
                else { // multi-touch and not all targets contained within element
                    prevent = false;
                }

                if (prevent) {
                    break;
                }

                dom = dom.parentNode;
            }
        }

        // In chrome preventing a touchmove event does not prevent the defualt
        // action such as scrolling from taking place on subsequent touchmove
        // events.  Setting these flags tells us to prevent the touchmove event
        // for the remainder of the gesture.
        // explicitly setting these flags to false means do not prevent this gesture
        // going forward. This prevents chrome from complaining because we
        // called preventDefault() after scrolling has already started
        if (touchCount === 1) {
            me.preventSingle = prevent;
        }
        else if (touchCount > 1) {
            me.preventMulti = prevent;
        }

        if (prevent) {
            e.preventDefault();
        }
    },

    onTouchStart: function(e) {
        var me = this,
            time, flags, dom, panX, panY;

        if (e.browserEvent.touches.length === 1) {
            time = e.time;

            // Use a time of 500ms between touchstart events to detecting a double tap that
            // might possibly cause the screen to zoom.  Although in reality this is usually
            // 300ms iOS can sometimes take a bit longer so 500 seems safe.
            // Can't be a double tap if a scroll occurred in between this touch and the previous
            // one.
            if (!me.scrollOccurred && ((time - me.lastTouchStartTime) <= 500)) {
                me.isDoubleTap = true;
            }

            me.lastTouchStartTime = time;
            me.scrollOccurred = false;
            me.startPoint = e.getPoint();

            dom = e.target;

            while (dom) {
                flags = me.lookupFlags(dom);

                if (flags != null) {
                    panX = !!(flags & 1);
                    panY = !!(flags & 2);

                    if (panX !== panY) {
                        me.disableOverflow(dom, panX);
                        break;
                    }
                }

                dom = dom.parentNode;
            }
        }
        else {
            // multi touch is never a double tap
            me.isDoubleTap = false;
        }
    },

    /**
     * Removes any classes that were added using {@link #disableOverflow}
     */
    resetOverflow: function() {
        var me = this,
            dom = me.disabledOverflowDom;

        while (dom) {
            Ext.fly(dom).removeCls([me.panXCls, me.panYCls]);
            dom = dom.parentNode;
        }

        me.disabledOverflowDom = null;
    },

    /**
     * Sets the touch action value for an element
     * @param {HTMLElement} dom The dom element
     * @param {Object/Number} value The touch action as an object with touch action names
     * as keys and boolean values, or as a bit flag (see {@link #getFlags})
     *
     * For example the following two calls are equivalent:
     *
     *     Ext.dom.TouchAction.set(domElement, {
     *         panX: false,
     *         pinchZoom: false
     *     });
     *
     *     Ext.dom.TouchAction.set(domElement, 5);
     *
     * valid touch action names are:
     *
     * - `'panX'`
     * - `'panY'`
     * - `'pinchZoom'`
     * - `'doubleTapZoom'`
     *
     * @private
     */
    set: function(dom, value) {
        var me = this,
            cssProp = me.cssProp,
            flags = me.getFlags(value),
            // We can only set values for CSS touch-action in the dom if they are supported
            // by the browser, otherwise the entire touch-action property is ignored.
            supportedFlags = (flags & Ext.supports.TouchAction),
            attributeName = me.attributeName;

        if (cssProp) {
            Ext.fly(dom).setStyle(cssProp, me.cssValues[supportedFlags]);
        }

        if (flags === 15) {
            dom.removeAttribute(attributeName);
        }
        else {
            dom.setAttribute(attributeName, flags);
        }
    }
});
