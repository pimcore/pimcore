/**
 * Just as {@link Ext.dom.Element} wraps around a native DOM node, {@link Ext.event.Event} wraps
 * the browser's native event-object normalizing cross-browser differences such as mechanisms
 * to stop event-propagation along with a method to prevent default actions from taking place.
 *
 * Here is a simple example of how you use it:
 *
 *     @example
 *     var container = Ext.create('Ext.Container', {
 *         layout: 'fit',
 *         renderTo: Ext.getBody(),
 *         items: [{
 *             id: 'logger',
 *             html: 'Click somewhere!',
 *             padding: 5
 *         }]
 *     });
 *
 *     container.getEl().on({
 *         click: function(e, node) {
 *             var string = '';
 *
 *             string += 'You clicked at: { x: ' + e.pageX + ', y: ' + e.pageY + ' } ' +
                         '<i>(e.pageX & e.pageY)</i>';
 *             string += '<hr />';
 *             string += 'The HTMLElement you clicked has the className of: <strong>' +
                         e.target.className + '</strong> <i>(e.target)</i>';
 *             string += '<hr />';
 *             string += 'The HTMLElement which has the listener has a className of: <strong>' +
                          e.currentTarget.className + '</strong> <i>(e.currentTarget)</i>';
 *
 *             Ext.getCmp('logger').setHtml(string);
 *         }
 *     });
 *
 * ## Recognizers
 *
 * Ext JS includes many default event recognizers to know when a user interacts with
 * the application.
 *
 * For a full list of default recognizers, and more information, please view the
 * {@link Ext.event.gesture.Recognizer} documentation.
 * 
 * This class also provides a set of constants for use with key events.  These are useful
 * for determining if a specific key was pressed, and are available both on instances,
 * and as static properties of the class.  The following two statements are equivalent:
 * 
 *     if (e.getKey() === Ext.event.Event.TAB) {
 *         // tab key was pressed
 *     }
 * 
 *     if (e.getKey() === e.TAB) {
 *         // tab key was pressed
 *     }
 */
Ext.define('Ext.event.Event', {
    alternateClassName: 'Ext.EventObjectImpl',

    requires: [
        'Ext.util.Point'
    ],

    /**
     * @property {Number} distance
     * The distance of the event.
     *
     * **This is only available when the event type is `swipe` and `pinch`.**
     */

    /**
     * @property {HTMLElement} target
     * The element that fired this event.  For the element whose handlers are currently
     * being processed, i.e. the element that the event handler was attached to, use
     * `currentTarget`
     */

    /**
     * @property {HTMLElement} currentTarget
     * Refers to the element the event handler was attached to, vs the `target`, which is
     * the actual element that fired the event.  For example, if the event bubbles, the
     * `target` element may be a descendant of the `currentTarget`, as the event may
     * have been triggered on the `target` and then bubbled up to the `currentTarget`
     * where it was handled.
     */

    /**
     * @property {HTMLElement} delegatedTarget
     * Same as `currentTarget`
     * @deprecated 5.0.0 use {@link #currentTarget} instead.
     */

    /**
     * @property {Number} button
     * Indicates which mouse button caused the event for mouse events, for example
     * `mousedown`, `click`, `mouseup`:
     * - `0` for left button.
     * - `1` for middle button.
     * - `2` for right button.
     *
     * *Note*: In IE8 & IE9, the `click` event does not provide the button.
     */

    /**
     * @property {Number} pageX The browsers x coordinate of the event.
     * Note: this only works in browsers that support pageX on the native browser event
     * object (pageX is not natively supported in IE9 and earlier).  In Ext JS, for a
     * cross browser normalized x-coordinate use {@link #getX}
     */

    /**
     * @property {Number} pageY The browsers y coordinate of the event.
     * Note: this only works in browsers that support pageY on the native browser event
     * object (pageY is not natively supported in IE9 and earlier).  In Ext JS, for a
     * cross browser normalized y-coordinate use {@link #getY}
     */

    /**
     * @property {Boolean} ctrlKey
     * True if the control key was down during the event.
     * In Mac this will also be true when meta key was down.
     */
    /**
     * @property {Boolean} altKey
     * True if the alt key was down during the event.
     */
    /**
     * @property {Boolean} shiftKey
     * True if the shift key was down during the event.
     */

    /**
     * @property {Event} browserEvent
     * The raw browser event which this object wraps.
     */

    /**
     * @property {String} pointerType
     * The pointer type for this event. May be empty if the event was
     * not triggered by a pointer. Current available types are:
     * - `mouse`
     * - `touch`
     * - `pen`
     */

    /**
     * @property {Boolean}
     * `true` if {@link #stopPropagation} has been called on this instance
     * @private
     */
    stopped: false,

    /**
     * @property {Boolean}
     * `true` if {@link #claimGesture} has been called on this instance
     * @private
     */
    claimed: false,

    /**
     * @property {Boolean}
     * Indicates whether or not {@link #preventDefault preventDefault()} was called on the event.
     */
    defaultPrevented: false,

    isEvent: true,

    // With these events, the relatedTarget can sometimes be an Object literal in FF.
    geckoRelatedTargetEvents: {
        blur: 1,
        dragenter: 1,
        dragleave: 1,
        focus: 1
    },

    statics: {
        resolveTextNode: function(node) {
            return (node && node.nodeType === 3) ? node.parentNode : node;
        },

        /**
         * @private
         * An amalgamation of pointerEvents/mouseEvents/touchEvents.
         * Will be populated in class callback.
         */
        gestureEvents: {},

        /**
         * @private
         */
        pointerEvents: {
            pointerdown: 1,
            pointermove: 1,
            pointerup: 1,
            pointercancel: 1,
            pointerover: 1,
            pointerout: 1,
            pointerenter: 1,
            pointerleave: 1,
            MSPointerDown: 1,
            MSPointerMove: 1,
            MSPointerUp: 1,
            MSPointerOver: 1,
            MSPointerOut: 1,
            MSPointerCancel: 1,
            MSPointerEnter: 1,
            MSPointerLeave: 1
        },

        /**
         * @private
         */
        mouseEvents: {
            mousedown: 1,
            mousemove: 1,
            mouseup: 1,
            mouseover: 1,
            mouseout: 1,
            mouseenter: 1,
            mouseleave: 1
        },

        /**
         * @private
         * These are tracked separately from mouseEvents because the mouseEvents map
         * is used by Dom publisher to eliminate duplicate events on devices that fire
         * multiple kinds of events (mouse, touch, pointer).  Adding click events to the
         * mouse events map can cause click events to be blocked from firing in some cases.
         */
        clickEvents: {
            click: 1,
            dblclick: 1
        },

        /**
         * @private
         */
        touchEvents: {
            touchstart: 1,
            touchmove: 1,
            touchend: 1,
            touchcancel: 1
        },

        /**
         * @private
         */
        focusEvents: {
            focus: 1,
            focusin: 1,
            focusenter: 1
        },

        /**
         * @private
         */
        blurEvents: {
            blur: 1,
            focusout: 1,
            focusleave: 1
        },

        /**
         * @private
         */
        wheelEvents: {
            wheel: 1,
            mousewheel: 1
        },

        // msPointerTypes in IE10 are numbers, in the w3c spec they are strings.
        // this map allows us to normalize the pointerType for an event
        // http://www.w3.org/TR/pointerevents/#widl-PointerEvent-pointerType
        // http://msdn.microsoft.com/en-us/library/ie/hh772359(v=vs.85).aspx
        pointerTypeMap: {
            2: 'touch',
            3: 'pen',
            4: 'mouse',
            touch: 'touch',
            pen: 'pen',
            mouse: 'mouse'
        },

        keyEventRe: /^key/,

        keyFlags: {
            CTRL: 'ctrlKey',
            CONTROL: 'ctrlKey',
            ALT: 'altKey',
            SHIFT: 'shiftKey',
            CMD: 'metaKey',
            COMMAND: 'metaKey',
            CMDORCTRL: Ext.isMac ? 'metaKey' : 'ctrlKey',
            COMMANDORCONTROL: Ext.isMac ? 'metaKey' : 'ctrlKey',
            META: 'metaKey'
        },

        modifierGlyphs: {
            ctrlKey: '\u2303',
            altKey: '\u2325',
            metaKey: Ext.isMac ? '\u2318' : '\u229e',
            shiftKey: '\u21E7'
        },

        specialKeyGlyphs: {
            BACKSPACE: '\u232B',
            TAB: '\u21E5',
            ENTER: '\u23CE',
            RETURN: '\u23CE',
            SPACE: '\u2423',
            PAGE_UP: '\u21DE',
            PAGE_DOWN: '\u21DF',
            END: '\u21F2',
            HOME: '\u2302',
            LEFT: '\u2190',
            UP: '\u2191',
            RIGHT: '\u2192',
            DOWN: '\u2193',
            PRINT_SCREEN: '\u2399',
            INSERT: '\u2380',
            DELETE: '\u2326',
            CONTEXT_MENU: '\u2630'
        },

        // eslint-disable-next-line no-useless-escape
        _hyphenRe: /^[a-z]+\-/i, // eg "Ctrl-" (instead of "Ctrl+")

        /**
         * Convert a key specification in the form eg: "CTRL+ALT+DELETE" to the glyph sequence
         * for use in menu items, eg "⌃⌥⌦".
         * @private
         */
        getKeyId: function(keyName) {
            // Translate eg: 27 to "ESC"
            if (typeof keyName === 'number') {
                keyName = this.keyCodes[keyName];
            }
            else {
                keyName = keyName.toUpperCase();
            }

            // eslint-disable-next-line vars-on-top
            var me = this,
                delim = me._hyphenRe.test(keyName) ? '-' : '+',
                parts = (keyName === delim) ? [delim] : keyName.split(delim),
                numModifiers = parts.length - 1,
                rawKey = parts[numModifiers],
                result = [],
                eventFlag, i;

            //<debug>
            if (!Ext.event.Event[rawKey]) {
                Ext.raise('Invalid key name: "' + rawKey + '"');
            }
            //</debug>

            for (i = 0; i < numModifiers; i++) {
                eventFlag = me.keyFlags[parts[i]];

                //<debug>
                if (!eventFlag) {
                    Ext.raise('Invalid key modifier: "' + parts[i] + '"');
                }
                //</debug>

                result[eventFlag] = true;
            }

            if (result.ctrlKey) {
                result.push(me.modifierGlyphs.ctrlKey);
            }

            if (result.altKey) {
                result.push(me.modifierGlyphs.altKey);
            }

            if (result.shiftKey) {
                result.push(me.modifierGlyphs.shiftKey);
            }

            if (result.metaKey) {
                result.push(me.modifierGlyphs.metaKey);
            }

            result.push(this.specialKeyGlyphs[rawKey] || rawKey);

            return result.join('');
        },

        /**
         * @private
         */
        globalTabKeyDown: function(e) {
            if (e.keyCode === 9) {
                Ext.event.Event.forwardTab = !e.shiftKey;
            }
        },

        /**
         * @private
         */
        globalTabKeyUp: function(e) {
            if (e.keyCode === 9) {
                delete Ext.event.Event.forwardTab;
            }
        }
    },

    constructor: function(event) {
        var me = this,
            self = me.self,
            resolveTextNode = me.self.resolveTextNode,
            changedTouches = event.changedTouches,
            // The target object from which to obtain the coordinates (pageX, pageY). For
            // mouse and pointer events this is simply the event object itself, but touch
            // events have their coordinates on the "Touch" object(s) instead.
            coordinateOwner = changedTouches ? changedTouches[0] : event,
            type = event.type,
            pointerType, relatedTarget;

        // This is a milliseconds value with decimal precision.
        me.timeStamp = me.time = Ext.ticks();

        me.pageX = coordinateOwner.pageX;
        me.pageY = coordinateOwner.pageY;
        me.clientX = coordinateOwner.clientX;
        me.clientY = coordinateOwner.clientY;

        me.target = me.delegatedTarget = resolveTextNode(event.target);
        me.currentTarget = resolveTextNode(event.currentTarget);
        relatedTarget = event.relatedTarget;

        if (relatedTarget) {
            if (Ext.isGecko && me.geckoRelatedTargetEvents[type]) {
                try {
                    me.relatedTarget = resolveTextNode(relatedTarget);
                }
                catch (e) {
                    me.relatedTarget = null;
                }
            }
            else {
                me.relatedTarget = resolveTextNode(relatedTarget);
            }
        }

        me.browserEvent = me.event = event;
        me.type = type;

        // set button to 0 if undefined so that touchstart, touchend, and tap will quack
        // like left mouse button mousedown mouseup, and click
        me.button = event.button || 0;
        me.shiftKey = event.shiftKey;

        // mac metaKey behaves like ctrlKey
        me.ctrlKey = event.ctrlKey || event.metaKey || false;
        me.altKey = event.altKey;
        me.charCode = event.charCode;
        me.keyCode = event.keyCode;

        me.buttons = event.buttons;

        // When invoking synthetic events, current APIs do not
        // have the ability to specify the buttons config, which
        // defaults to button. For buttons, 0 means no button
        // is pressed, whereas for button, 0 means left click.
        // Normalize that here
        if (me.button === 0 && me.buttons === 0) {
            me.buttons = 1;
        }

        if (self.focusEvents[type] || self.blurEvents[type]) {
            if (self.forwardTab !== undefined) {
                me.forwardTab = self.forwardTab;
            }

            if (self.focusEvents[type]) {
                me.fromElement = event.relatedTarget;
                me.toElement = event.target;
            }
            else {
                me.fromElement = event.target;
                me.toElement = event.relatedTarget;
            }
        }
        else if (type !== 'keydown') {
            // Normally this property should be cleaned up in keyup handler;
            // however that one might never come if something prevented default
            // on the keydown. Make sure the property won't get stuck.
            delete self.forwardTab;
        }

        if (self.mouseEvents[type]) {
            pointerType = 'mouse';
        }
        else if (self.clickEvents[type]) {
            // On pointer events browsers like IE11 and Edge click events have a pointerType
            // property.  On touch events devices we check if the last touchend event
            // happened less than a second ago - if so we can reasonably assume that
            // the click event happened because of a tap on the screen.
            pointerType = self.pointerTypeMap[event.pointerType] ||
                (((Ext.now() - Ext.event.publisher.Dom.lastTouchEndTime) < 1000) ? 'touch' : 'mouse'); // eslint-disable-line max-len
        }
        else if (self.pointerEvents[type]) {
            // In electron the mousemove event when the mouse first enters the document
            // can have a pointerType of "", so default it to mouse if not found in the map.
            pointerType = self.pointerTypeMap[event.pointerType] || 'mouse';
        }
        else if (self.touchEvents[type]) {
            pointerType = 'touch';
        }

        if (pointerType) {
            me.pointerType = pointerType;
        }

        // Is this is not the primary touch for PointerEvents (first touch)
        // or there are multiples touches for Touch Events
        me.isMultitouch = event.isPrimary === false || (event.touches && event.touches.length > 1);

        if (self.wheelEvents[type]) {
            me.getWheelDeltas(); // call deprecated method to initialize deltaX and deltaY props
        }
    },

    /**
     * Creates a new Event object that is prototype-chained to this event.  Useful for
     * creating identical events so that certain properties can be changed without
     * affecting the original event.  For example, translated events have their "type"
     * corrected in this manner.
     * @param {Object} props properties to set on the chained event
     * @private
     */
    chain: function(props) {
        var e = Ext.Object.chain(this);

        e.parentEvent = this; // needed for stopPropagation

        return Ext.apply(e, props);
    },

    /**
     * Correctly scales a given wheel delta.
     * @param {Number} delta The delta value.
     * @private
     */
    correctWheelDelta: function(delta) {
        var me = this,
            // 0 = pixel
            // 1 = line
            // 2 = page
            deltaMode = me.browserEvent.deltaMode,
            correctedDelta = delta;

        if (deltaMode === 0) {
            correctedDelta = delta * me.WHEEL_PIXEL_SIZE;
        }
        else if (deltaMode === 1) {
            correctedDelta = delta * me.WHEEL_LINE_SIZE;
        }
        else if (deltaMode === 2) {
            correctedDelta = delta * me.WHEEL_PAGE_SIZE;
        }

        return Math.round(correctedDelta);
    },

    getChar: function() {
        var r = this.which();

        return String.fromCharCode(r);
    },

    /**
     * Gets the character code for the event.
     * @return {Number}
     */
    getCharCode: function() {
        return this.charCode || this.keyCode;
    },

    /**
     * Returns a normalized keyCode for the event.
     * @return {Number} The key code
     */
    getKey: function() {
        return this.keyCode || this.charCode;
    },

    /**
     * Returns the name of the keyCode for the event.
     * @return {String} The key name
     */
    getKeyName: function() {
        return this.type === 'keypress'
            ? String.fromCharCode(this.getCharCode())
            : this.keyCodes[this.keyCode];
    },

    /**
     * Returns the `key` property of a keyboard event.
     * @return {String}
     */
    key: function() {
        return this.browserEvent.key;
    },

    which: function() {
        var me = this,
            e = me.browserEvent,
            r = e.which;

        if (r == null) {
            if (me.self.keyEventRe.test(e.type)) {
                r = e.charCode || e.keyCode;
            }
            else if ((r = e.button) !== undefined) {
                // L M R button codes (1, 4, 2):
                r = (r & 1) ? 1 : ((r & 4) ? 2 : ((r & 2) ? 3 : 0));
            }
        }

        return r;
    },

    /**
     * If this is an event of {@link #type} `paste`, this returns the clipboard data
     * of the pasesd mime type.
     *
     * @param {String} [type='text/plain'] The mime type of the data to extract from the
     * clipabord.
     *
     * Note that this uses non-standard browaer APIs and may not work reliably on all
     * platforms.
     * @return {Mixed} The clipboard data.
     * @since 6.5.1
     */
    getClipboardData: function(type) {
        var clipboardData = this.browserEvent.clipboardData,
            clipIE = Ext.global.clipboardData, // IE
            result = null,
            typeIE;

        type = type || 'text/plain';

        if (clipboardData && clipboardData.getData) {
            result = clipboardData.getData(type);
        }
        else if (clipIE && clipIE.getData) {
            typeIE = this.ieMimeType[type];

            if (typeIE) {
                result = clipIE.getData(typeIE);
            }
        }

        return result;
    },

    /**
     * Returns a point object that consists of the object coordinates.
     * @return {Ext.util.Point} point
     */
    getPoint: function() {
        var me = this,
            point = me.point,
            xy;

        if (!point) {
            xy = me.getXY();
            point = me.point = new Ext.util.Point(xy[0], xy[1]);
        }

        return point;
    },

    /**
     * Gets the related target.
     * @param {String} [selector] A simple selector to filter the target or look for an
     * ancestor of the target. See {@link Ext.dom.Query} for information about simple
     * selectors.
     * @param {Number/HTMLElement} [maxDepth] The max depth to search as a number or
     * element (defaults to 10 || document.body).
     * @param {Boolean} [returnEl] `true` to return a Ext.Element object instead of DOM
     * node.
     * @return {HTMLElement}
     */
    getRelatedTarget: function(selector, maxDepth, returnEl) {
        var relatedTarget = this.relatedTarget,
            target = null;

        // In some cases in IE10/11, when the mouse is leaving the document over a scrollbar
        // the relatedTarget will be an empty object literal. So just check we have an element
        // looking object here before we proceed.
        if (relatedTarget && relatedTarget.nodeType) {
            if (selector) {
                target = Ext.fly(relatedTarget).findParent(selector, maxDepth, returnEl);
            }
            else {
                target = returnEl ? Ext.get(relatedTarget) : relatedTarget;
            }
        }

        return target;
    },

    /**
     * Gets the target for the event.
     * @param {String} selector (optional) A simple selector to filter the target or look
     * for an ancestor of the target
     * @param {Number/Mixed} [maxDepth=10||document.body] (optional) The max depth to
     * search as a number or element (defaults to 10 || document.body)
     * @param {Boolean} returnEl (optional) `true` to return a Ext.Element object instead
     * of DOM node.
     * @return {HTMLElement}
     */
    getTarget: function(selector, maxDepth, returnEl) {
        return selector
            ? Ext.fly(this.target).findParent(selector, maxDepth, returnEl)
            : (returnEl ? Ext.get(this.target) : this.target);
    },

    /**
     * Returns the time of the event.
     * @return {Date}
     */
    getTime: function() {
        return this.time;
    },

    /**
     * Normalizes mouse wheel y-delta across browsers. To get x-delta information, use
     * {@link #getWheelDeltas} instead.
     * @return {Number} The mouse wheel y-delta
     * @deprecated 6.6.0 Use {@link #deltaY} instead
     */
    getWheelDelta: function() {
        var deltas = this.getWheelDeltas();

        return deltas.y;
    },

    /**
     * Returns the mouse wheel deltas for this event.
     * @return {Object} An object with "x" and "y" properties holding the mouse wheel deltas.
     * @deprecated 6.7.0 Use {@link #deltaX} and {@link #deltaY} instead
     */
    getWheelDeltas: function() {
        var me = this,
            wheelDeltas = me.wheelDeltas,
            browserEvent, deltaX, deltaY;

        if (!wheelDeltas) {
            browserEvent = me.browserEvent;

            deltaX = me.correctWheelDelta(browserEvent.deltaX || 0);

            deltaY = browserEvent.deltaY;
            deltaY = me.correctWheelDelta(deltaY == null ? -browserEvent.wheelDelta : deltaY);

            /**
             * @property {Number} deltaX
             */
            me.deltaX = deltaX;

            /**
             * @property {Number} deltaY
             */
            me.deltaY = deltaY;

            me.wheelDeltas = wheelDeltas = {
                x: deltaX,
                y: deltaY
            };
        }

        return wheelDeltas;
    },

    /**
     * Gets the x coordinate of the event.
     * @return {Number}
     */
    getX: function() {
        return this.getXY()[0];
    },

    /**
     * Gets the X and Y coordinates of the event.
     * @return {Number[]} The xy values like [x, y]
     */
    getXY: function() {
        var me = this,
            xy = me.xy;

        if (!xy) {
            xy = me.xy = [me.pageX, me.pageY];
            //<feature legacyBrowser>
            // eslint-disable-next-line vars-on-top, one-var
            var x = xy[0],
                browserEvent, doc, docEl, body;

            // pageX/pageY not available (undefined, not null), use clientX/clientY instead
            if (!x && x !== 0) {
                browserEvent = me.browserEvent;
                doc = document;
                docEl = doc.documentElement;
                body = doc.body;
                xy[0] = browserEvent.clientX +
                    (docEl && docEl.scrollLeft || body && body.scrollLeft || 0) -
                    (docEl && docEl.clientLeft || body && body.clientLeft || 0);
                xy[1] = browserEvent.clientY +
                    (docEl && docEl.scrollTop || body && body.scrollTop || 0) -
                    (docEl && docEl.clientTop || body && body.clientTop || 0);
            }
            //</feature>
        }

        return xy;
    },

    /**
     * @private
     * Gets the event coordinates relative to the `currentTarget`s position.
     * @param {Boolean} clip
     * The returned coordinates are guaranteed to be within
     * [0, width] and [0, height] of the target, if `clip` is set to `true`.
     * @return {Number[]} The xy values like [x, y]
     */
    getLocalXY: function(clip) {
        // TODO: check with RTL
        var pageXY = this.getXY(),
            targetXY = Ext.fly(this.currentTarget).getXY(),
            localX = pageXY[0] - targetXY[0],
            localY = pageXY[1] - targetXY[1],
            size;

        if (clip) {
            size = Ext.fly(this.currentTarget).getSize();
            localX = Math.max(0, Math.min(localX, size.width));
            localY = Math.max(0, Math.min(localY, size.height));
        }

        return [localX, localY];
    },

    /**
     * Gets the y coordinate of the event.
     * @return {Number}
     */
    getY: function() {
        return this.getXY()[1];
    },

    /**
    * Returns true if the control, meta, shift or alt key was pressed during this event.
    * @return {Boolean}
    */
    hasModifier: function() {
        var me = this;

        return !!(me.ctrlKey || me.altKey || me.shiftKey || me.metaKey);
    },

    /**
     * Checks if the key pressed was a "navigation" key. A navigation key is defined by
     * these keys:
     *
     *  - Page Up
     *  - Page Down
     *  - End
     *  - Home
     *  - Left
     *  - Up
     *  - Right
     *  - Down
     *  - Return
     *  - Tab
     *  - Esc
     *
     * @param {Boolean} [scrollableOnly] Only check navigation keys that can cause
     * element scrolling by their default action.
     *
     * @return {Boolean} `true` if the press is a navigation keypress
     */
    isNavKeyPress: function(scrollableOnly) {
        var me = this,
            k = me.keyCode,
            isKeyPress = me.type === 'keypress';

        // See specs for description of behaviour
        // Page Up/Down, End, Home, Left, Up, Right, Down
        return ((!isKeyPress || Ext.isGecko) && k >= 33 && k <= 40) ||
               (!scrollableOnly && (k === me.RETURN || k === me.TAB || k === me.ESC));
    },

    /**
     * Checks if the key pressed was a "special" key. A special key is defined as one of
     * these keys:
     *
     *  - Page Up
     *  - Page Down
     *  - End
     *  - Home
     *  - Left arrow
     *  - Up arrow
     *  - Right arrow
     *  - Down arrow
     *  - Return
     *  - Tab
     *  - Esc
     *  - Backspace
     *  - Delete
     *  - Shift
     *  - Ctrl
     *  - Alt
     *  - Pause
     *  - Caps Lock
     *  - Print Screen
     *  - Insert
     *
     * @return {Boolean} `true` if the key for this event is special
     */
    isSpecialKey: function() {
        var me = this,
            k = me.keyCode,
            isGecko = Ext.isGecko,
            isKeyPress = me.type === 'keypress';

        // See specs for description of behaviour
        return (isGecko && isKeyPress && me.charCode === 0) ||
               (this.isNavKeyPress()) ||
               (k === me.BACKSPACE) ||
               (k === me.ENTER) ||
               (k >= 16 && k <= 20) ||              // Shift, Ctrl, Alt, Pause, Caps Lock
               ((!isKeyPress || isGecko) && k >= 44 && k <= 46); // Print Screen, Insert, Delete
    },

    makeUnpreventable: function() {
        this.browserEvent.preventDefault = Ext.emptyFn;
    },

    /**
     * Prevents the browsers default handling of the event.
     * @chainable
     */
    preventDefault: function() {
        var me = this,
            parentEvent = me.parentEvent;

        me.defaultPrevented = true;

        // if the event was created by prototype-chaining a new object to an existing event
        // instance, we need to make sure the parent event is defaultPrevented as well.
        if (parentEvent) {
            parentEvent.defaultPrevented = true;
        }

        me.browserEvent.preventDefault();

        return me;
    },

    setCurrentTarget: function(target) {
        this.currentTarget = this.delegatedTarget = target;
    },

    /**
     * Stop the event (`{@link #preventDefault}` and `{@link #stopPropagation}`).
     * @chainable
     */
    stopEvent: function() {
        return this.preventDefault().stopPropagation();
    },

    // map of events that should fire global mousedown even if stopped
    mousedownEvents: {
        mousedown: 1,
        pointerdown: 1,
        touchstart: 1
    },
    // map of events that should fire global mouseup even if stopped
    mouseupEvents: {
        mouseup: 1,
        pointerup: 1,
        touchend: 1
    },

    /**
     * Cancels bubbling of the event.
     * @chainable
     */
    stopPropagation: function() {
        var me = this,
            browserEvent = me.browserEvent,
            parentEvent = me.parentEvent;

        // Fire the "unstoppable" global mousedown event
        // (used for menu hiding, etc)
        if (me.mousedownEvents[me.type]) {
            Ext.GlobalEvents.fireMouseDown(me);
        }

        // Fire the "unstoppable" global mouseup event
        // (used for component unpressing, etc)
        if (me.mouseupEvents[me.type]) {
            Ext.GlobalEvents.fireMouseUp(me);
        }

        // Set stopped for delegated event listeners.  Dom publisher will check this
        // property during its emulated propagation phase (see doPublish)
        me.stopped = true;

        // if the event was created by prototype-chaining a new object to an existing event
        // instance, we need to make sure the parent event is stopped.  This feature most
        // likely comes into play when dealing with event translation.  For example on touch
        // browsers addListener('mousedown') actually attaches a 'touchstart' listener behind
        // the scenes.  When the 'touchstart' event is dispatched, the event system will
        // create a "chained" copy of the event object before correcting its type back to
        // 'mousedown' and calling the handler.  When propagating the event we look at the
        // original event, not the chained one to determine if propagation should continue,
        // so the stopped property must be set on the parentEvent or stopPropagation
        // will not work.
        if (parentEvent && !me.isGesture) {
            parentEvent.stopped = true;
        }

        //<feature legacyBrowser>
        if (!browserEvent.stopPropagation) {
            // IE < 10 does not have stopPropagation()
            browserEvent.cancelBubble = true;

            return me;
        }
        //</feature>

        // For non-delegated event listeners (those that are directly attached to the
        // DOM element) we need to call the browserEvent's stopPropagation() method.
        browserEvent.stopPropagation();

        return me;
    },

    /**
     * Claims this event as the currently active gesture.  Once a gesture is claimed
     * no other gestures will fire events until after the current gesture has completed.
     * For example, if `claimGesture()` is invoked on a dragstart or drag event, no
     * swipestart or swipe events will be fired until the drag gesture completes, even if
     * the gesture also meets the required duration and distance requirements to be recognized
     * as a swipe.
     *
     * If `claimGesture()` is invoked on a mouse, touch, or pointer event, it will disable
     * all gesture events until termination of the current gesture is indicated by a
     * mouseup, touchend, or pointerup event.
     *
     * @return {Ext.event.Event}
     */
    claimGesture: function() {
        var me = this,
            parentEvent = me.parentEvent;

        me.claimed = true;

        if (parentEvent && !me.isGesture) {
            parentEvent.claimGesture();
        }
        else {
            // Claiming a gesture should also prevent default browser actions like pan/zoom
            // if possible (only works on browsers that support touch events - browsers that
            // use pointer events must declare a CSS touch-action on elements to prevent the
            // default touch action from occurring.
            me.preventDefault();
        }

        return me;
    },

    /**
     * Returns true if the target of this event is a child of `el`. If the allowEl
     * parameter is set to false, it will return false if the target is `el`.
     * Example usage:
     * 
     *     // Handle click on any child of an element
     *     Ext.getBody().on('click', function(e){
     *         if(e.within('some-el')){
     *             alert('Clicked on a child of some-el!');
     *         }
     *     });
     * 
     *     // Handle click directly on an element, ignoring clicks on child nodes
     *     Ext.getBody().on('click', function(e,t){
     *         if((t.id == 'some-el') && !e.within(t, true)){
     *             alert('Clicked directly on some-el!');
     *         }
     *     });
     * 
     * @param {String/HTMLElement/Ext.dom.Element} el The id, DOM element or Ext.Element to check
     * @param {Boolean} [related] `true` to test if the related target is within el instead
     * of the target
     * @param {Boolean} [allowEl=true] `true` to allow the target to be considered "within" itself. 
     * `false` to only allow child elements.
     * @return {Boolean}
     */
    within: function(el, related, allowEl) {
        var t;

        if (el) {
            t = related ? this.getRelatedTarget() : this.getTarget();
        }

        if (!t || (allowEl === false && t === Ext.getDom(el))) {
            return false;
        }

        return Ext.fly(el).contains(t);
    },

    privates: {
        ieMimeType: {
            "text/plain": 'Text'
        }
    },

    deprecated: {
        '4.0': {
            methods: {

                /**
                 * @method getPageX
                 * Gets the x coordinate of the event.
                 * @return {Number}
                 * @deprecated 4.0 use {@link #getX} instead
                 * @member Ext.event.Event
                 */
                getPageX: 'getX',

                /**
                 * @method getPageY
                 * Gets the y coordinate of the event.
                 * @return {Number}
                 * @deprecated 4.0 use {@link #getY} instead
                 * @member Ext.event.Event
                 */
                getPageY: 'getY'
            }
        }
    }
}, function(Event) {
    var constants = {
            /** Key constant @type Number */
            BACKSPACE: 8,
            /** Key constant @type Number */
            TAB: 9,
            /** Key constant @type Number */
            NUM_CENTER: 12,
            /** Key constant @type Number */
            ENTER: 13,
            /** Key constant @type Number */
            RETURN: 13,
            /** Key constant @type Number */
            SHIFT: 16,
            /** Key constant @type Number */
            CTRL: 17,
            /** Key constant @type Number */
            ALT: 18,
            /** Key constant @type Number */
            PAUSE: 19,
            /** Key constant @type Number */
            CAPS_LOCK: 20,
            /** Key constant @type Number */
            ESC: 27,
            /** Key constant @type Number */
            SPACE: 32,
            /** Key constant @type Number */
            PAGE_UP: 33,
            /** Key constant @type Number */
            PAGE_DOWN: 34,
            /** Key constant @type Number */
            END: 35,
            /** Key constant @type Number */
            HOME: 36,
            /** Key constant @type Number */
            LEFT: 37,
            /** Key constant @type Number */
            UP: 38,
            /** Key constant @type Number */
            RIGHT: 39,
            /** Key constant @type Number */
            DOWN: 40,
            /** Key constant @type Number */
            PRINT_SCREEN: 44,
            /** Key constant @type Number */
            INSERT: 45,
            /** Key constant @type Number */
            DELETE: 46,
            /** Key constant @type Number */
            ZERO: 48,
            /** Key constant @type Number */
            ONE: 49,
            /** Key constant @type Number */
            TWO: 50,
            /** Key constant @type Number */
            THREE: 51,
            /** Key constant @type Number */
            FOUR: 52,
            /** Key constant @type Number */
            FIVE: 53,
            /** Key constant @type Number */
            SIX: 54,
            /** Key constant @type Number */
            SEVEN: 55,
            /** Key constant @type Number */
            EIGHT: 56,
            /** Key constant @type Number */
            NINE: 57,
            /** Key constant @type Number */
            A: 65,
            /** Key constant @type Number */
            B: 66,
            /** Key constant @type Number */
            C: 67,
            /** Key constant @type Number */
            D: 68,
            /** Key constant @type Number */
            E: 69,
            /** Key constant @type Number */
            F: 70,
            /** Key constant @type Number */
            G: 71,
            /** Key constant @type Number */
            H: 72,
            /** Key constant @type Number */
            I: 73,
            /** Key constant @type Number */
            J: 74,
            /** Key constant @type Number */
            K: 75,
            /** Key constant @type Number */
            L: 76,
            /** Key constant @type Number */
            M: 77,
            /** Key constant @type Number */
            N: 78,
            /** Key constant @type Number */
            O: 79,
            /** Key constant @type Number */
            P: 80,
            /** Key constant @type Number */
            Q: 81,
            /** Key constant @type Number */
            R: 82,
            /** Key constant @type Number */
            S: 83,
            /** Key constant @type Number */
            T: 84,
            /** Key constant @type Number */
            U: 85,
            /** Key constant @type Number */
            V: 86,
            /** Key constant @type Number */
            W: 87,
            /** Key constant @type Number */
            X: 88,
            /** Key constant @type Number */
            Y: 89,
            /** Key constant @type Number */
            Z: 90,
            /** Key constant @type Number */
            META: 91,       // Command key on mac, left window key on Windows
            /** Key constant @type Number */
            CONTEXT_MENU: 93,
            /** Key constant @type Number */
            NUM_ZERO: 96,
            /** Key constant @type Number */
            NUM_ONE: 97,
            /** Key constant @type Number */
            NUM_TWO: 98,
            /** Key constant @type Number */
            NUM_THREE: 99,
            /** Key constant @type Number */
            NUM_FOUR: 100,
            /** Key constant @type Number */
            NUM_FIVE: 101,
            /** Key constant @type Number */
            NUM_SIX: 102,
            /** Key constant @type Number */
            NUM_SEVEN: 103,
            /** Key constant @type Number */
            NUM_EIGHT: 104,
            /** Key constant @type Number */
            NUM_NINE: 105,
            /** Key constant @type Number */
            NUM_MULTIPLY: 106,
            /** Key constant @type Number */
            NUM_PLUS: 107,
            /** Key constant @type Number */
            NUM_MINUS: 109,
            /** Key constant @type Number */
            NUM_PERIOD: 110,
            /** Key constant @type Number */
            NUM_DIVISION: 111,
            /** Key constant @type Number */
            F1: 112,
            /** Key constant @type Number */
            F2: 113,
            /** Key constant @type Number */
            F3: 114,
            /** Key constant @type Number */
            F4: 115,
            /** Key constant @type Number */
            F5: 116,
            /** Key constant @type Number */
            F6: 117,
            /** Key constant @type Number */
            F7: 118,
            /** Key constant @type Number */
            F8: 119,
            /** Key constant @type Number */
            F9: 120,
            /** Key constant @type Number */
            F10: 121,
            /** Key constant @type Number */
            F11: 122,
            /** Key constant @type Number */
            F12: 123,

            /**
             * @property {Number}
             * The mouse wheel delta scaling factor when the
             * [`deltaMode`](https://developer.mozilla.org/en-US/docs/Web/API/WheelEvent/deltaMode)
             * is DOM_DELTA_PIXEL
             *
             * To change this value:
             *
             *      Ext.event.Event.prototype.WHEEL_PIXEL_SIZE = 3;
             */
            WHEEL_PIXEL_SIZE: 1,

            /**
             * @property {Number}
             * The mouse wheel delta scaling factor when the
             * [`deltaMode`](https://developer.mozilla.org/en-US/docs/Web/API/WheelEvent/deltaMode)
             * is DOM_DELTA_LINE
             *
             * To change this value:
             *
             *      Ext.event.Event.prototype.WHEEL_LINE_SIZE = 16;
             */
            WHEEL_LINE_SIZE: 20,

            /**
             * @property {Number}
             * The mouse wheel delta scaling factor when the
             * [`deltaMode`](https://developer.mozilla.org/en-US/docs/Web/API/WheelEvent/deltaMode)
             * is DOM_DELTA_PAGE
             *
             * To change this value:
             *
             *      Ext.event.Event.prototype.WHEEL_PAGE_SIZE = 400;
             */
            WHEEL_PAGE_SIZE: 600
        },
        keyCodes = {},
        gestureEvents = Event.gestureEvents,
        prototype = Event.prototype,
        i, keyName, keyCode, keys;

    Ext.apply(gestureEvents, Event.mouseEvents);
    Ext.apply(gestureEvents, Event.pointerEvents);
    Ext.apply(gestureEvents, Event.touchEvents);

    Ext.apply(Event, constants);
    Ext.apply(prototype, constants);

    // ENTER and RETURN has the same keyCode, but since RETURN
    // comes later it will win over ENTER down there. However
    // ENTER is used more often and feels more natural.
    delete constants.RETURN;

    // We need this to do reverse lookup of key name by its code
    for (keyName in constants) {
        keyCode = constants[keyName];
        keyCodes[keyCode] = keyName;
    }

    Event.keyCodes = prototype.keyCodes = keyCodes;

    // Classic Event override will install this handler in IE9-
    if (!Ext.isIE9m) {
        document.addEventListener('keydown', Event.globalTabKeyDown, true);
        document.addEventListener('keyup', Event.globalTabKeyUp, true);
    }

    /**
     * @member Ext.event.Event
     * @private
     * Returns the X and Y coordinates of this event without regard to any RTL
     * direction settings.
     */
    prototype.getTrueXY = prototype.getXY;

    if (typeof KeyboardEvent !== 'undefined' && !('key' in KeyboardEvent.prototype)) {
        prototype._keys = keys = {
            3: 'Cancel',
            6: 'Help',
            8: 'Backspace',
            9: 'Tab',
            12: 'Clear',
            13: 'Enter',
            16: 'Shift',
            17: 'Control',
            18: 'Alt',
            19: 'Pause',
            20: 'CapsLock',
            27: 'Escape',
            28: 'Convert',
            29: 'NonConvert',
            30: 'Accept',
            31: 'ModeChange',
            32: ' ',
            33: 'PageUp',
            34: 'PageDown',
            35: 'End',
            36: 'Home',
            37: 'ArrowLeft',
            38: 'ArrowUp',
            39: 'ArrowRight',
            40: 'ArrowDown',
            41: 'Select',
            42: 'Print',
            43: 'Execute',
            44: 'PrintScreen',
            45: 'Insert',
            46: 'Delete',
            48: ['0', ')'],
            49: ['1', '!'],
            50: ['2', '@'],
            51: ['3', '#'],
            52: ['4', '$'],
            53: ['5', '%'],
            54: ['6', '^'],
            55: ['7', '&'],
            56: ['8', '*'],
            57: ['9', '('],
            91: 'OS',
            93: 'ContextMenu',
            144: 'NumLock',
            145: 'ScrollLock',
            181: 'VolumeMute',
            182: 'VolumeDown',
            183: 'VolumeUp',
            186: [';', ':'],
            187: ['=', '+'],
            188: [',', '<'],
            189: ['-', '_'],
            190: ['.', '>'],
            191: ['/', '?'],
            192: ['`', '~'],
            219: ['[', '{'],
            220: ['\\', '|'],
            221: [']', '}'],
            222: ["'", '"'],
            224: 'Meta',
            225: 'AltGraph',
            246: 'Attn',
            247: 'CrSel',
            248: 'ExSel',
            249: 'EraseEof',
            250: 'Play',
            251: 'ZoomOut'
        };

        for (i = 1; i < 25; ++i) {
            keys[i + 111] = 'F' + i; // F1 - F24
        }

        for (i = 0; i < 26; ++i) { // a-z, A-Z
            keys[i] = [String.fromCharCode(i + 97), String.fromCharCode(i + 65)];
        }

        prototype.key = function() {
            var k = keys[this.browserEvent.which || this.keyCode];

            if (k && typeof k !== 'string') {
                k = k[+this.shiftKey];
            }

            return k;
        };
    }
});
