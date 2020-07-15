/**
 * @class Ext.dom.Element
 * @alternateClassName Ext.Element
 * @mixins Ext.util.Positionable
 * @mixins Ext.mixin.Observable
 *
 * Encapsulates a DOM element, adding simple DOM manipulation facilities, normalizing for browser
 * differences.
 *
 * **Note:** The events included in this Class are the ones we've found to be the most commonly
 * used. Many events are not listed here due to the expedient rate of change across browsers.
 * For a more comprehensive list, please visit the following resources:
 *
 * + [Mozilla Event Reference Guide](https://developer.mozilla.org/en-US/docs/Web/Events)
 * + [W3 Pointer Events](http://www.w3.org/TR/pointerevents/)
 * + [W3 Touch Events](http://www.w3.org/TR/touch-events/)
 * + [W3 DOM 2 Events](http://www.w3.org/TR/DOM-Level-2-Events/)
 * + [W3 DOM 3 Events](http://www.w3.org/TR/DOM-Level-3-Events/)
 *
 * ## Usage
 *
 *     // by id
 *     var el = Ext.get("my-div");
 *
 *     // by DOM element reference
 *     var el = Ext.get(myDivElement);
 *
 * ## Selecting Descendant Elements
 *
 * Ext.dom.Element instances can be used to select descendant nodes using CSS selectors.
 * There are 3 methods that can be used for this purpose, each with a slightly different
 * twist:
 *
 * - {@link #method-query}
 * - {@link #method-selectNode}
 * - {@link #method-select}
 *
 * These methods can accept any valid CSS selector since they all use
 * [querySelectorAll](http://www.w3.org/TR/css3-selectors/) under the hood. The primary
 * difference between these three methods is their return type:
 *
 * To get an array of HTMLElement instances matching the selector '.foo' use the query
 * method:
 *
 *     element.query('.foo');
 *
 * This can easily be transformed into an array of Ext.dom.Element instances by setting
 * the `asDom` parameter to `false`:
 *
 *     element.query('.foo', false);
 *
 * If the desired result is only the first matching HTMLElement use the selectNode method:
 *
 *     element.selectNode('.foo');
 *
 * Once again, the dom node can be wrapped in an Ext.dom.Element by setting the `asDom`
 * parameter to `false`:
 *
 *     element.selectNode('.foo', false);
 *
 * The `select` method is used when the desired return type is a {@link
 * Ext.CompositeElementLite CompositeElementLite} or a {@link Ext.CompositeElement
 * CompositeElement}.  These are collections of elements that can be operated on as a
 * group using any of the methods of Ext.dom.Element.  The only difference between the two
 * is that CompositeElementLite is a collection of HTMLElement instances, while
 * CompositeElement is a collection of Ext.dom.Element instances.  To retrieve a
 * CompositeElementLite that represents a collection of HTMLElements for selector '.foo':
 *
 *     element.select('.foo');
 *
 * For a {@link Ext.CompositeElement CompositeElement} simply pass `true` as the
 * `composite` parameter:
 *
 *     element.select('.foo', true);
 *
 * The query selection methods can be used even if you don't have a Ext.dom.Element to
 * start with For example to select an array of all HTMLElements in the document that match the
 * selector '.foo', simply wrap the document object in an Ext.dom.Element instance using
 * {@link Ext#fly}:
 *
 *     Ext.fly(document).query('.foo');
 *
 * # Animations
 *
 * When an element is manipulated, by default there is no animation.
 *
 *     var el = Ext.get("my-div");
 *
 *     // no animation
 *     el.setWidth(100);
 *
 * specified as boolean (true) for default animation effects.
 *
 *     // default animation
 *     el.setWidth(100, true);
 *
 * To configure the effects, an object literal with animation options to use as the Element
 * animation configuration object can also be specified. Note that the supported Element animation
 * configuration options are a subset of the {@link Ext.fx.Anim} animation options specific to Fx
 * effects. The supported Element animation configuration options are:
 *
 *     Option    Default   Description
 *     --------- --------  ---------------------------------------------
 *     duration  350       The duration of the animation in milliseconds
 *     easing    easeOut   The easing method
 *     callback  none      A function to execute when the anim completes
 *     scope     this      The scope (this) of the callback function
 *
 * Usage:
 *
 *     // Element animation options object
 *     var opt = {
 *         duration: 1000,
 *         easing: 'elasticIn',
 *         callback: this.foo,
 *         scope: this
 *     };
 *     // animation with some options set
 *     el.setWidth(100, opt);
 *
 * The Element animation object being used for the animation will be set on the options object
 * as "anim", which allows you to stop or manipulate the animation. Here is an example:
 *
 *     // using the "anim" property to get the Anim object
 *     if(opt.anim.isAnimated()){
 *         opt.anim.stop();
 *     }
 */
Ext.define('Ext.dom.Element', function(Element) {
    var WIN = window,
        DOC = document,
        docEl = DOC.documentElement,
        WIN_TOP = WIN.top,
        EMPTY = [],
        elementIdCounter,
        windowId,
        documentId,
        WIDTH = 'width',
        HEIGHT = 'height',
        MIN_WIDTH = 'min-width',
        MIN_HEIGHT = 'min-height',
        MAX_WIDTH = 'max-width',
        MAX_HEIGHT = 'max-height',
        TOP = 'top',
        RIGHT = 'right',
        BOTTOM = 'bottom',
        LEFT = 'left',
        VISIBILITY = 'visibility',
        HIDDEN = 'hidden',
        DISPLAY = "display",
        NONE = "none",
        ZINDEX = "z-index",
        POSITION = "position",
        RELATIVE = "relative",
        STATIC = "static",
        wordsRe = /\w/g,
        spacesRe = /\s+/,
        classNameSplitRegex = /[\s]+/,
        transparentRe = /^(?:transparent|(?:rgba[(](?:\s*\d+\s*[,]){3}\s*0\s*[)]))$/i,
        endsQuestionRe = /\?$/,
        topRe = /top/i,
        empty = {},
        borders = {
            t: 'border-top-width',
            r: 'border-right-width',
            b: 'border-bottom-width',
            l: 'border-left-width'
        },
        paddings = {
            t: 'padding-top',
            r: 'padding-right',
            b: 'padding-bottom',
            l: 'padding-left'
        },
        margins = {
            t: 'margin-top',
            r: 'margin-right',
            b: 'margin-bottom',
            l: 'margin-left'
        },
        selectDir = {
            b: 'backward',
            back: 'backward',
            f: 'forward'
        },
        paddingsTLRB = [paddings.l, paddings.r, paddings.t, paddings.b],
        bordersTLRB = [borders.l, borders.r, borders.t, borders.b],
        numberRe = /\d+$/,
        unitRe = /\d+(px|r?em|%|vh|vw|vmin|vmax|en|ch|ex|pt|in|cm|mm|pc)$/i,
        defaultUnit = 'px',
        msRe = /^-ms-/,
        camelRe = /(-[a-z])/gi,
        /* eslint-disable-next-line no-useless-escape */
        cssRe = /([a-z0-9\-]+)\s*:\s*([^;\s]+(?:\s*[^;\s]+)*);?/gi,
        pxRe = /^\d+(?:\.\d*)?px$/i,
        relativeUnitRe = /(%|r?em|auto|vh|vw|vmin|vmax|ch|ex)$/i,

        propertyCache = {},
        ORIGINALDISPLAY = 'originalDisplay',

        camelReplaceFn = function(m, a) {
            return a.charAt(1).toUpperCase();
        },

        clearData = function(node, deep) {
            var childNodes, i, len;

            // Only Element nodes may have _extData and child nodes to clear.
            // IE8 throws an error attempting to set expandos on non-Element nodes.
            if (node.nodeType === 1) {
                node._extData = null;

                if (deep) {
                    childNodes = node.childNodes;

                    for (i = 0, len = childNodes.length; i < len; ++i) {
                        clearData(childNodes[i], deep);
                    }
                }
            }
        },

        toFloat = function(v) {
            return parseFloat(v) || 0;
        },

        opacityCls = Ext.baseCSSPrefix + 'hidden-opacity',
        visibilityCls = Ext.baseCSSPrefix + 'hidden-visibility',
        displayCls = Ext.baseCSSPrefix + 'hidden-display',
        offsetsCls = Ext.baseCSSPrefix + 'hidden-offsets',
        clipCls = Ext.baseCSSPrefix + 'hidden-clip',
        lastFocusChange = 0,
        lastKeyboardClose = 0,
        editableHasFocus = false,
        isVirtualKeyboardOpen = false,
        inputTypeSelectionSupported = /text|password|search|tel|url/i,
        visFly, scrollFly, caFly, wrapFly, grannyFly, activeElFly;

    // We use element ID counter to prevent assigning the same id to top and nested
    // window and document objects when Ext is running in <iframe>.
    // Cross-origin access might throw an exception, in which case we can't
    // reference top window. In IE8 simply getting a property does not throw
    // but trying to set it does.
    try {
        elementIdCounter = WIN_TOP.__elementIdCounter__;
        WIN_TOP.__elementIdCounter__ = elementIdCounter;
    }
    catch (e) {
        WIN_TOP = WIN;
    }

    WIN_TOP.__elementIdCounter__ = elementIdCounter = (WIN_TOP.__elementIdCounter__ || 0) + 1;
    windowId = 'ext-window-' + elementIdCounter;
    documentId = 'ext-document-' + elementIdCounter;

    //<debug>
    if (Object.freeze) {
        Object.freeze(EMPTY);
    }
    //</debug>

    return {
        alternateClassName: [ 'Ext.Element' ],

        mixins: [
            'Ext.util.Positionable',
            'Ext.mixin.Observable'
        ],

        requires: [
            'Ext.dom.Shadow',
            'Ext.dom.Shim',
            'Ext.dom.ElementEvent',
            'Ext.event.publisher.Dom',
            'Ext.event.publisher.Gesture',
            'Ext.event.publisher.ElementSize',
            'Ext.event.publisher.ElementPaint'
        ],

        uses: [
            'Ext.dom.Helper',
            'Ext.dom.CompositeElement',
            'Ext.dom.Fly',
            'Ext.dom.TouchAction',
            'Ext.event.publisher.Focus'
        ],

        observableType: 'element',

        isElement: true,

        skipGarbageCollection: true,

        $applyConfigs: true,

        identifiablePrefix: 'ext-element-',

        _selectDir: selectDir,

        styleHooks: {
            transform: {
                set: function(dom, value, el) {
                    var result = '',
                        prop;

                    if (typeof value !== 'string') {
                        for (prop in value) {
                            if (result) {
                                result += ' ';
                            }

                            if (prop.indexOf('translate') === 0) {
                                result += prop + '(' + Element.addUnits(value[prop], 'px') + ')';
                            }
                            else {
                                result += prop + '(' + value[prop] + ')';
                            }
                        }

                        value = result;
                    }

                    dom.style.transform = value;
                }
            }
        },

        validIdRe: Ext.validIdRe,

        blockedEvents: Ext.supports.EmulatedMouseOver
            ? {
                // mobile safari emulates a mouseover event on clickable elements such as
                // anchors. This event is useless because it runs after touchend. We block
                // this event to prevent mouseover handlers from running after tap events. It
                // is up to the individual component to determine if it has an analog for
                // mouseover, and implement the appropriate event handlers.
                mouseover: 1
            }
            : {},

        longpressEvents: {
            longpress: 1,
            taphold: 1
        },

        /**
         * @property {Ext.Component} component
         * A reference to the `Component` that owns this element. This is `null` if there
         * is no direct owner.
         */

        //  Mouse events
        /**
         * @event click
         * Fires when a mouse click is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event contextmenu
         * Fires when a right click is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event dblclick
         * Fires when a mouse double click is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mousedown
         * Fires when a mousedown is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mouseup
         * Fires when a mouseup is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mouseover
         * Fires when a mouseover is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mousemove
         * Fires when a mousemove is detected with the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mouseout
         * Fires when a mouseout is detected with the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mouseenter
         * Fires when the mouse enters the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event mouseleave
         * Fires when the mouse leaves the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */

        //  Keyboard events
        /**
         * @event keypress
         * Fires when a keypress is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event keydown
         * Fires when a keydown is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event keyup
         * Fires when a keyup is detected within the element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */

        //  HTML frame/object events
        /**
         * @event load
         * Fires when the user agent finishes loading all content within the element. Only supported
         * by window, frames, objects and images.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event unload
         * Fires when the user agent removes all content from a window or frame. For elements, it
         * fires when the target element or any of its content has been removed.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event abort
         * Fires when an object/image is stopped from loading before completely loaded.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event error
         * Fires when an object/image/frame cannot be loaded properly.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event painted
         * Fires whenever this Element actually becomes visible (painted) on the screen. This is
         * useful when you need to perform 'read' operations on the DOM element, i.e: calculating
         * natural sizes and positioning.
         *
         * __Note:__ This event is not available to be used with event delegation. Instead `painted`
         * only fires if you explicitly add at least one listener to it, for performance reasons.
         *
         * @param {Ext.dom.Element} this The component instance.
         */
        /**
         * @event resize
         * Important note: For the best performance on mobile devices, use this only when you
         * absolutely need to monitor a Element's size.
         *
         * __Note:__ This event is not available to be used with event delegation. Instead `resize`
         * only fires if you explicitly add at least one listener to it, for performance reasons.
         *
         * @param {Ext.dom.Element} this The component instance.
         * @param {Object} info The element's new size parameters.
         */
        /**
         * @event scroll
         * Fires when a document view is scrolled.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */

        //  Form events
        /**
         * @event select
         * Fires when a user selects some text in a text field, including input and textarea.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event change
         * Fires when a control loses the input focus and its value has been modified since gaining
         * focus.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event submit
         * Fires when a form is submitted.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event reset
         * Fires when a form is reset.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event focus
         * Fires when an element receives focus either via the pointing device or by tab navigation.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event blur
         * Fires when an element loses focus either via the pointing device or by tabbing
         * navigation.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event focusmove
         * Fires when focus is moved *within* an element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {Ext.dom.Element} e.target The {@link Ext.dom.Element} element which *recieved*
         * focus.
         * @param {Ext.dom.Element} e.relatedTarget The {@link Ext.dom.Element} element which *lost*
         * focus.
         * @param {HTMLElement} t The target of the event.
         */

        //  User Interface events
        /**
         * @event DOMFocusIn
         * Where supported. Similar to HTML focus event, but can be applied to any focusable
         * element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMFocusOut
         * Where supported. Similar to HTML blur event, but can be applied to any focusable element.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMActivate
         * Where supported. Fires when an element is activated, for instance, through a mouse click
         * or a keypress.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */

        //  DOM Mutation events
        /**
         * @event DOMSubtreeModified
         * Where supported. Fires when the subtree is modified.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMNodeInserted
         * Where supported. Fires when a node has been added as a child of another node.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMNodeRemoved
         * Where supported. Fires when a descendant node of the element is removed.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMNodeRemovedFromDocument
         * Where supported. Fires when a node is being removed from a document.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMNodeInsertedIntoDocument
         * Where supported. Fires when a node is being inserted into a document.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMAttrModified
         * Where supported. Fires when an attribute has been modified.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */
        /**
         * @event DOMCharacterDataModified
         * Where supported. Fires when the character data has been modified.
         * @param {Ext.event.Event} e The {@link Ext.event.Event} encapsulating the DOM event.
         * @param {HTMLElement} t The target of the event.
         */

        /**
         * Creates new Element directly by passing an id or the HTMLElement.  This
         * constructor should not be called directly.  Always use {@link Ext#get Ext.get()}
         * or {@link Ext#fly Ext#fly()} instead.
         *
         * In older versions of Ext JS and Sencha Touch this constructor checked to see if
         * there was already an instance of this element in the cache and if so, returned
         * the same instance. As of version 5 this behavior has been removed in order to
         * avoid a redundant cache lookup since the most common path is for the Element
         * constructor to be called from {@link Ext#get Ext.get()}, which has already
         * checked for a cache entry.
         *
         * Correct way of creating a new Ext.dom.Element (or retrieving it from the cache):
         *
         *     var el = Ext.get('foo'); // by id
         *
         *     var el = Ext.get(document.getElementById('foo')); // by DOM reference
         *
         * Incorrect way of creating a new Ext.dom.Element
         *
         *     var el = new Ext.dom.Element('foo');
         *
         * For quick and easy access to Ext.dom.Element methods use a flyweight:
         *
         *     Ext.fly('foo').addCls('foo-hovered');
         *
         * This simply attaches the DOM node with id='foo' to the global flyweight Element
         * instance to avoid allocating an extra Ext.dom.Element instance.  If, however,
         * the Element instance has already been cached by a previous call to Ext.get(),
         * then Ext.fly() will return the cached Element instance.  For more info see
         * {@link Ext#fly}.
         *
         * @param {String/HTMLElement} dom
         * @private
         */
        constructor: function(dom) {
            var me = this,
                id;

            if (typeof dom === 'string') {
                dom = DOC.getElementById(dom);
            }

            if (!dom) {
                //<debug>
                Ext.raise("Invalid domNode reference or an id of an existing domNode: " + dom);
                //</debug>

                return null;
            }

            //<debug>
            if (Ext.cache[dom.id]) {
                Ext.raise("Element cache already contains an entry for id '" +
                    dom.id + "'.  Use Ext.get() to create or retrieve Element instances.");
            }
            //</debug>

            /**
             * The DOM element
             * @property dom
             * @type HTMLElement
             */
            me.dom = dom;

            if (!(id = dom.id)) {
                dom.id = id = me.generateAutoId();
            }

            me.id = id;

            // Uncomment this when debugging orphaned Elements
            // if (id === 'ext-element-5') debugger;

            //<debug>
            if (!me.validIdRe.test(me.id)) {
                Ext.raise('Invalid Element "id": "' + me.id + '"');
            }
            //</debug>

            // set an "el" property that references "this".  This allows
            // Ext.util.Positionable methods to operate on this.el.dom since it
            // gets mixed into both Element and Component
            me.el = me;

            Ext.cache[id] = me;

            me.longpressListenerCount = 0;

            me.mixins.observable.constructor.call(me);
        },

        inheritableStatics: {
            /**
             * @property cache
             * @private
             * @static
             * @inheritable
             */
            cache: Ext.cache = {},

            /**
             * @property editableSelector
             * @static
             * @private
             * @inheritable
             */
            editableSelector: 'input,textarea,[contenteditable="true"]',

            /**
             * @property {Number} VISIBILITY
             * Visibility mode constant for use with {@link Ext.dom.Element#setVisibilityMode}.
             * Use the CSS 'visibility' property to hide the element.
             *
             * Note that in this mode, {@link Ext.dom.Element#isVisible isVisible} may return true
             * for an element even though it actually has a parent element that is hidden. For this
             * reason, and in most cases, using the {@link #OFFSETS} mode is a better choice.
             * @static
             * @inheritable
             */
            VISIBILITY: 1,

            /**
             * @property {Number} DISPLAY
             * Visibility mode constant for use with {@link Ext.dom.Element#setVisibilityMode}.
             * Use the CSS 'display' property to hide the element.
             * @static
             * @inheritable
             */
            DISPLAY: 2,

            /**
             * @property {Number} OFFSETS
             * Visibility mode constant for use with {@link Ext.dom.Element#setVisibilityMode}.
             * Use CSS absolute positioning and top/left offsets to hide the element.
             * @static
             * @inheritable
             */
            OFFSETS: 3,

            /**
             * @property {Number} CLIP
             * Visibility mode constant for use with {@link Ext.dom.Element#setVisibilityMode}.
             * Use CSS `clip` property to reduce element's dimensions to 0px by 0px, effectively
             * making it hidden while not being truly invisible. This is useful when an element
             * needs to be published to the Assistive Technologies such as screen readers.
             * @static
             * @inheritable
             */
            CLIP: 4,

            /**
             * @property {Number} OPACITY
             * Visibility mode constant for use with {@link Ext.dom.Element#setVisibilityMode}.
             * Use CSS `opacity` property to reduce element's opacity to 0
             * @static
             * @inheritable
             */
            OPACITY: 5,

            /**
             * @property minKeyboardHeight
             * @static
             * @inheritable
             * @private
             * This property indicates a minimum threshold of vertical resize movement for
             * virtual keyboard detection.
             *
             * On some mobile browsers the framework needs to keep track of whether window
             * resize events were triggered by the opening or closing of a virtual keyboard
             * so that it can prevent unnecessary re-layout of the viewport.  It does this
             * by detecting resize events in the horizontal direction that occur immediately
             * after an editable element is focused or blurred.
             */
            minKeyboardHeight: 100,

            unitRe: unitRe,

            /**
             * @property {Boolean} useDelegatedEvents
             * @private
             * @static
             * @inheritable
             * True to globally disable the delegated event system.  The results of
             * setting this to false are unpredictable since the Gesture publisher relies
             * on delegated events in order to work correctly.  Disabling delegated events
             * may cause Gestures to function incorrectly or to stop working completely.
             * Use at your own risk!
             */
            useDelegatedEvents: true,

            /**
             * @property {Object} validNodeTypes
             * @private
             * @static
             * @inheritable
             * The list of valid nodeTypes that are allowed to be wrapped
             */
            validNodeTypes: {
                1: 1, // ELEMENT_NODE
                9: 1 // DOCUMENT_NODE
            },

            namespaceURIs: {
                html: 'http://www.w3.org/1999/xhtml',
                svg: 'http://www.w3.org/2000/svg'
            },

            selectableCls: Ext.baseCSSPrefix + 'selectable',
            unselectableCls: Ext.baseCSSPrefix + 'unselectable',

            /**
             * Determines the maximum size for all ripples
             */
            maxRippleDiameter: 75,

            /**
             * Test if size has a unit, otherwise appends the passed unit string, or the default
             * for this Element.
             * @param {Object} size The size to set.
             * @param {String} units The units to append to a numeric size value.
             * @return {String}
             * @private
             * @static
             * @inheritable
             */
            addUnits: function(size, units) {
                // Most common case first: Size is set to a number
                if (typeof size === 'number') {
                    return size + (units || defaultUnit);
                }

                // Values which mean "auto"
                // - ""
                // - "auto"
                // - undefined
                // - null
                if (size === "" || size === "auto" || size == null) {
                    return size || '';
                }

                // less common use case: number formatted as a string.  save this case until
                // last to avoid regex execution if possible.
                if (numberRe.test(size)) {
                    return size + (units || defaultUnit);
                }

                // Warn if it's not a valid CSS measurement
                if (!unitRe.test(size)) {
                    //<debug>
                    // Don't warn about calc() expressions
                    if (!(Ext.isString(size) && size.indexOf('calc') === 0)) {
                        Ext.Logger.warn("Warning, size detected (" + size +
                                        ") not a valid property value on Element.addUnits.");
                    }
                    //</debug>

                    return size || '';
                }

                return size;
            },

            /**
             * @private
             * Create method to add support for a DomHelper config. Creates
             * and appends elements/children using document.createElement/appendChild.
             * This method is used by the modern toolkit for a significant performance gain
             * in webkit browsers as opposed to using DomQuery which generates HTML
             * markup and sets it as innerHTML.
             *
             * However, the createElement/appendChild
             * method of creating elements is significantly slower in all versions of IE
             * at the time of this writing (6 - 11), so classic toolkit should not use this method,
             * but should instead use DomHelper methods, or Element methods that use
             * DomHelper under the hood (e.g. createChild).
             * see https:*fiddle.sencha.com/#fiddle/tj
             *
             * @static
             * @inheritable
             */
            create: function(attributes, domNode, namespace) {
                var me = this,
                    classes, element, elementStyle, tag, value, name, i, ln, tmp, ns;

                attributes = attributes || {};

                if (attributes.isElement) {
                    return domNode ? attributes.dom : attributes;
                }
                else if ('nodeType' in attributes) {
                    return domNode ? attributes : Ext.get(attributes);
                }

                if (typeof attributes === 'string') {
                    return DOC.createTextNode(attributes);
                }

                tag = attributes.tag;

                if (!tag) {
                    tag = 'div';
                }

                ns = attributes.namespace || namespace;

                if (ns) {
                    element = DOC.createElementNS(me.namespaceURIs[ns] || ns, tag);
                }
                else {
                    element = DOC.createElement(tag);
                }

                elementStyle = element.style;

                for (name in attributes) {
                    if (name !== 'tag' && name !== 'namespace') {
                        value = attributes[name];

                        switch (name) {
                            case 'style':
                                if (typeof value === 'string') {
                                    element.setAttribute(name, value);
                                }
                                else {
                                    for (i in value) {
                                        elementStyle[i] = value[i];
                                    }
                                }

                                break;

                            case 'className':
                            case 'cls':
                                tmp = value.split(spacesRe);
                                classes = classes ? classes.concat(tmp) : tmp;
                                break;

                            case 'classList':
                                classes = classes ? classes.concat(value) : value;
                                break;

                            case 'text':
                                element.textContent = value;
                                break;

                            case 'html':
                                element.innerHTML = value;
                                break;

                            case 'hidden':
                                if (classes) {
                                    classes.push(displayCls);
                                }
                                else {
                                    classes = [displayCls];
                                }

                                break;

                            case 'children':
                                if (value != null) {
                                    for (i = 0, ln = value.length; i < ln; i++) {
                                        element.appendChild(me.create(value[i], true, ns));
                                    }
                                }

                                break;

                            default:
                                if (value != null) { // skip null or undefined values
                                    element.setAttribute(name, value);
                                }
                        }
                    }
                }

                if (classes) {
                    element.className = classes.join(' ');
                }

                if (domNode) {
                    return element;
                }
                else {
                    return me.get(element);
                }
            },

            /**
             * @method fly
             * @inheritdoc Ext#method-fly
             * @inheritable
             * @static
             */
            fly: function(dom, named) {
                return Ext.fly(dom, named);
            },

            /**
             * Returns the top Element that is located at the passed coordinates in the current
             * viewport.
             * @param {Number} x The x coordinate
             * @param {Number} y The y coordinate
             * @param {Boolean} [asDom=false] `true` to return a DOM element.
             * @return {Ext.dom.Element/HTMLElement} The found element.
             * @static
             * @inheritable
             * @method
             */
            fromPoint: (function() {
                // IE has a weird bug where elementFromPoint can fail on the first call when inside
                // an iframe. It seems to happen more consistently on older IE, but sometimes crops
                // up even in IE11. This plays havoc especially while running tests.
                var elementFromPointBug;

                if (Ext.isIE || Ext.isEdge) {
                    try {
                        elementFromPointBug = window.self !== window.top;
                    }
                    catch (e) {
                        elementFromPointBug = true;
                    }
                }

                return function(x, y, asDom) {
                    var el = null;

                    el = DOC.elementFromPoint(x, y);

                    if (!el && elementFromPointBug) {
                        el = DOC.elementFromPoint(x, y);
                    }

                    return asDom ? el : Ext.get(el);
                };
            })(),

            /**
             * Returns the top Element that is located at the passed coordinates taking into account
             * the scroll position of the document.
             * @static
             * @inheritable
             * @param {Number} x The x coordinate
             * @param {Number} y The y coordinate
             * @param {Boolean} [asDom=false] `true` to return a DOM element.
             * @return {Ext.dom.Element/HTMLElement} The found element.
             *
             * @since 6.2.0
             */
            fromPagePoint: function(x, y, asDom) {
                var scroll = Ext.getDoc().getScroll();

                return Element.fromPoint(x - scroll.left, y - scroll.top, asDom);
            },

            /**
             * Retrieves Ext.dom.Element objects. {@link Ext#get} is alias for
             * {@link Ext.dom.Element#get}.
             *
             * **This method does not retrieve {@link Ext.Component Component}s.** This method
             * retrieves Ext.dom.Element objects which encapsulate DOM elements. To retrieve
             * a Component by its ID, use {@link Ext.ComponentManager#get}.
             *
             * When passing an id, it should not include the `#` character that is used for a css
             * selector.
             *
             *     // For an element with id 'foo'
             *     Ext.get('foo'); // Correct
             *     Ext.get('#foo'); // Incorrect
             *
             * Uses simple caching to consistently return the same object. Automatically fixes
             * if an object was recreated with the same id via AJAX or DOM.
             *
             * @param {String/HTMLElement/Ext.dom.Element} el The `id` of the node, a DOM Node
             * or an existing Element.
             * @return {Ext.dom.Element} The Element object (or `null` if no matching element
             * was found).
             * @static
             * @inheritable
             */
            get: function(el) {
                var me = this,
                    cache = Ext.cache,
                    nodeType, dom, id, entry, isDoc, isWin, isValidNodeType;

                if (!el) {
                    return null;
                }

                //<debug>
                function warnDuplicate(id) {
                    Ext.raise("DOM element with id " + id +
                        " in Element cache is not the same as element in the DOM. " +
                        "Make sure to clean up Element instances using destroy()");
                }
                //</debug>

                // Ext.get(flyweight) must return an Element instance, not the flyweight
                if (el.isFly) {
                    el = el.dom;
                }

                if (typeof el === 'string') {
                    id = el;

                    if (cache.hasOwnProperty(id)) {
                        entry = cache[id];

                        if (entry.skipGarbageCollection || !Ext.isGarbage(entry.dom)) {
                            //<debug>
                            // eslint-disable-next-line max-len
                            dom = Ext.getElementById ? Ext.getElementById(id) : DOC.getElementById(id);

                            if (dom && (dom !== entry.dom)) {
                                warnDuplicate(id);
                            }
                            //</debug>

                            return entry;
                        }
                        else {
                            entry.destroy();
                        }
                    }

                    if (id === windowId) {
                        return Element.get(WIN);
                    }
                    else if (id === documentId) {
                        return Element.get(DOC);
                    }

                    // using Ext.getElementById() allows us to check the detached
                    // body in addition to the body (Ext JS only).
                    dom = Ext.getElementById ? Ext.getElementById(id) : DOC.getElementById(id);

                    if (dom) {
                        return new Element(dom);
                    }
                }

                nodeType = el.nodeType;

                if (nodeType) {
                    isDoc = (nodeType === 9);
                    isValidNodeType = me.validNodeTypes[nodeType];
                }
                else {
                    // if an object has a window property that refers to itself we can
                    // reasonably assume that it is a window object.
                    // have to use == instead of === for IE8
                    isWin = (el.window == el); // eslint-disable-line eqeqeq
                }

                // check if we have a valid node type or if the el is a window object before
                // proceeding. This allows elements, document fragments, and document/window
                // objects (even those inside iframes) to be wrapped.
                if (isValidNodeType || isWin) {
                    id = el.id;

                    if (el === DOC) {
                        el.id = id = documentId;
                    }
                    // Must use == here, otherwise IE fails to recognize the window
                    else if (el == WIN) { // eslint-disable-line eqeqeq
                        el.id = id = windowId;
                    }

                    if (cache.hasOwnProperty(id)) {
                        entry = cache[id];

                        // eslint-disable-next-line max-len
                        if (entry.skipGarbageCollection || el === entry.dom || !Ext.isGarbage(entry.dom)) {
                            //<debug>
                            if (el !== entry.dom) {
                                warnDuplicate(id);
                            }
                            //</debug>

                            return entry;
                        }
                        else {
                            entry.destroy();
                        }
                    }

                    el = new Element(el);

                    if (isWin || isDoc) {
                        // document and window objects can never be garbage
                        el.skipGarbageCollection = true;
                    }

                    return el;
                }

                if (el.isElement) {
                    return el;
                }

                if (el.isComposite) {
                    return el;
                }

                // Test for iterable. Allow the resulting Composite to be based upon an Array
                // or HtmlCollection of nodes.
                if (Ext.isIterable(el)) {
                    return me.select(el);
                }

                return null;
            },

            /**
             * Returns the active element in the DOM. If the browser supports activeElement
             * on the document, this is returned. If not, the focus is tracked and the active
             * element is maintained internally.
             * @static
             * @inheritable
             *
             * @param {Boolean} asElement Return Ext.Element instance instead of DOM node.
             *
             * @return {HTMLElement} The active (focused) element in the document.
             */
            getActiveElement: function(asElement) {
                var active = DOC.activeElement;

                // The activeElement can be null, however there also appears to be a very odd
                // and inconsistent bug in IE where the activeElement is simply an empty object
                // literal. Test if the returned active element has focus, if not, we've hit the bug
                // so just default back to the document body.
                if (!active || !active.focus) {
                    active = DOC.body;
                }

                return asElement ? Ext.get(active) : active;
            },

            /**
             * Retrieves the document height
             * @static
             * @inheritable
             * @return {Number} documentHeight
             */
            getDocumentHeight: function() {
                // eslint-disable-next-line max-len
                return Math.max(!Ext.isStrict ? DOC.body.scrollHeight : docEl.scrollHeight, this.getViewportHeight());
            },

            /**
             * Retrieves the document width
             * @static
             * @inheritable
             * @return {Number} documentWidth
             */
            getDocumentWidth: function() {
                // eslint-disable-next-line max-len
                return Math.max(!Ext.isStrict ? DOC.body.scrollWidth : docEl.scrollWidth, this.getViewportWidth());
            },

            /**
             * Retrieves the current orientation of the window. This is calculated by
             * determining if the height is greater than the width.
             * @static
             * @inheritable
             * @return {String} Orientation of window: 'portrait' or 'landscape'
             */
            getOrientation: function() {
                if (Ext.supports.OrientationChange) {
                    /* eslint-disable-next-line eqeqeq */
                    return (WIN.orientation == 0) ? 'portrait' : 'landscape';
                }

                return (WIN.innerHeight > WIN.innerWidth) ? 'portrait' : 'landscape';
            },

            /**
             * Retrieves the viewport height of the window.
             * @static
             * @inheritable
             * @return {Number} viewportHeight
             */
            getViewportHeight: function() {
                var viewportHeight = Element._viewportHeight;

                //<feature legacyBrowser>
                if (Ext.isIE9m) {
                    return DOC.documentElement.clientHeight;
                }
                //</feature>

                return (viewportHeight != null) ? viewportHeight : docEl.clientHeight;
            },

            /**
             * Retrieves the viewport width of the window.
             * @static
             * @inheritable
             * @return {Number} viewportWidth
             */
            getViewportWidth: function() {
                var viewportWidth = Element._viewportWidth;

                //<feature legacyBrowser>
                if (Ext.isIE9m) {
                    return DOC.documentElement.clientWidth;
                }
                //</feature>

                return (viewportWidth != null) ? viewportWidth : docEl.clientWidth;
            },

            /**
             * Returns the current zoom level of the viewport as a ratio of page pixels to
             * screen pixels.
             * @private
             * @static
             * @return {Number}
             */
            getViewportScale: function() {
                // on desktop devices, the devicePixel ratio gives us the level of zoom that
                // the user specified using ctrl +/- and or by selecting a zoom level from
                // the menu.
                // On android/iOS devicePixel ratio is a fixed number that represents the
                // screen pixel density (e.g. always "2" on apple retina devices)

                // WIN_TOP is guarded against cross-frame access in the closure above
                var top = WIN_TOP;

                return ((Ext.isiOS || Ext.isAndroid)
                    ? 1
                    : (top.devicePixelRatio || // modern browsers
                       top.screen.deviceXDPI / top.screen.logicalXDPI)) * // IE10m
                       this.getViewportTouchScale();
            },

            /**
             * On touch-screen devices there may be an additional level of zooming
             * that occurs when the user performs a pinch or double-tap to zoom
             * gesture.  This is separate from and in addition to the
             * devicePixelRatio.  We can detect it by comparing the width
             * of the documentElement to window.innerWidth
             * @private
             */
            getViewportTouchScale: function(forceRead) {
                var scale = 1,
                    // WIN_TOP is guarded against cross-frame access in the closure above
                    top = WIN_TOP,
                    cachedScale;

                if (!forceRead) {
                    cachedScale = this._viewportTouchScale;

                    if (cachedScale) {
                        return cachedScale;
                    }
                }

                if (Ext.isIE10p || Ext.isEdge || Ext.isiOS) {
                    scale = docEl.offsetWidth / WIN.innerWidth;
                }
                else if (Ext.isChromeMobile) {
                    scale = top.outerWidth / top.innerWidth;
                }

                return scale;
            },

            /**
             * Retrieves the viewport size of the window.
             * @static
             * @inheritable
             * @return {Object} object containing width and height properties
             */
            getViewSize: function() {
                return {
                    width: Element.getViewportWidth(),
                    height: Element.getViewportHeight()
                };
            },

            /**
             * Checks if the passed size has a css unit attached.
             * @param {String} size The size.
             * @return {Boolean} `true` if the size has a css unit.
             *
             * @since 6.2.1
             */
            hasUnit: function(size) {
                return !!(size && unitRe.test(size));
            },

            /**
             * Checks if the passed css unit is a relative unit. This includes:
             * - `auto`
             * - `%`
             * - `em`
             * - `rem`
             * - `auto`
             * - `vh`
             * - `vw`
             * - `vmin`
             * - `vmax`
             * - `ex
             * - `ch`
             * @param {String} size The css unit and value.
             * @return {Boolean} `true` if the value is relative.
             *
             * @since 6.2.0
             */
            isRelativeUnit: function(size) {
                return !size || relativeUnitRe.test(size);
            },

            /**
             * Mask iframes when shim is true. See {@link Ext.util.Floating#shim}.
             * @private
             */
            maskIframes: function() {
                var iframes = document.getElementsByTagName('iframe'),
                    fly = new Ext.dom.Fly();

                Ext.each(iframes, function(iframe) {
                    var myMask;

                    myMask = fly.attach(iframe.parentNode).mask();
                    myMask.setStyle('background-color', 'transparent');
                });
            },

            /**
             * Normalizes CSS property keys from dash delimited to camel case JavaScript Syntax.
             * For example:
             *
             * - border-width -> borderWidth
             * - padding-top -> paddingTop
             *
             * @static
             * @inheritable
             * @param {String} prop The property to normalize
             * @return {String} The normalized string
             */
            normalize: function(prop) {
                // For '-ms-foo' we need msFoo
                // eslint-disable-next-line max-len
                return propertyCache[prop] || (propertyCache[prop] = prop.replace(msRe, 'ms-').replace(camelRe, camelReplaceFn));
            },

            /**
             * @private
             * @static
             * @inheritable
             */
            _onWindowFocusChange: function(e) {
                // Tracks the timestamp of focus entering or leaving an editable element
                // so that we can compare this timestamp to the time of the next window
                // resize for the purpose of determining if the virtual keyboard is displayed
                // see _onWindowResize for more details
                if (Ext.fly(e.target).is(Element.editableSelector)) {
                    lastFocusChange = new Date();
                    editableHasFocus = (e.type === 'focusin' || e.type === 'pointerup');
                }
            },

            /**
             * @private
             * @static
             * @inheritable
             */
            _onWindowResize: function() {
                var documentWidth = docEl.clientWidth,
                    documentHeight = docEl.clientHeight,
                    now = new Date(),
                    threshold = 1000,
                    deltaX, deltaY;

                deltaX = documentWidth - Element._documentWidth;
                deltaY = documentHeight - Element._documentHeight;

                Element._documentWidth = documentWidth;
                Element._documentHeight = documentHeight;

                // If the focus entered or left an editable element within a brief threshold
                // of time, then this resize event MAY be due to a virtual keyboard being
                // shown or hidden.  Let's do some additional checking to find out.
                if (((now - lastFocusChange) < threshold) || ((now - lastKeyboardClose) < threshold)) { // eslint-disable-line max-len
                    // If the resize is ONLY in the vertical direction, and an editable
                    // element has the focus, and the vertical movement was significant,
                    // we can be reasonably certain that the resize event was due to
                    // a virtual keyboard being opened.
                    if (deltaX === 0 && (editableHasFocus && (deltaY <= -Element.minKeyboardHeight))) { // eslint-disable-line max-len
                        isVirtualKeyboardOpen = true;

                        return;
                    }
                }

                if (isVirtualKeyboardOpen && (deltaX === 0) && (deltaY >= Element.minKeyboardHeight)) { // eslint-disable-line max-len
                    isVirtualKeyboardOpen = false;

                    // when windows tablets are rotated while keyboard is open, the keyboard closes
                    // and then immediately reopens.  Track the timestamp of the last keyboard
                    // close so that we can detect a successive resize event that might indicate
                    // reopening
                    lastKeyboardClose = new Date();
                }

                if (isVirtualKeyboardOpen) {
                    return;
                }

                // These cached variables are used by getViewportWidth and getViewportHeight
                // They do not get updated if we returned early due to detecting  that the
                // resize event was triggered by virtual keyboard.
                Element._viewportWidth = documentWidth;
                Element._viewportHeight = documentHeight;
            },

            /**
             * Parses a number or string representing margin sizes into an object. Supports
             * CSS-style margin declarations (e.g. 10, "10", "10 10", "10 10 10" and "10 10 10 10"
             * are all valid options and would return the same result)
             * @static
             * @inheritable
             * @param {Number/String} box The encoded margins
             * @return {Object} An object with margin sizes for top, right, bottom and left
             * containing the unit
             */
            parseBox: function(box) {
                var type, parts, ln;

                box = box || 0;
                type = typeof box;

                if (type === 'number') {
                    return {
                        top: box,
                        right: box,
                        bottom: box,
                        left: box
                    };
                }
                else if (type !== 'string') {
                    // If not a number or a string, assume we've been given a box config.
                    return box;
                }

                parts = box.split(' ');
                ln = parts.length;

                if (ln === 1) {
                    parts[1] = parts[2] = parts[3] = parts[0];
                }
                else if (ln === 2) {
                    parts[2] = parts[0];
                    parts[3] = parts[1];
                }
                else if (ln === 3) {
                    parts[3] = parts[1];
                }

                return {
                    top: parseFloat(parts[0]) || 0,
                    right: parseFloat(parts[1]) || 0,
                    bottom: parseFloat(parts[2]) || 0,
                    left: parseFloat(parts[3]) || 0
                };
            },

            /**
             * Converts a CSS string into an object with a property for each style.
             *
             * The sample code below would return an object with 2 properties, one
             * for background-color and one for color.
             *
             *     var css = 'background-color: red; color: blue;';
             *     console.log(Ext.dom.Element.parseStyles(css));
             *
             * @static
             * @inheritable
             * @param {String} styles A CSS string
             * @return {Object} styles
             */
            parseStyles: function(styles) {
                var out = {},
                    matches;

                if (styles) {
                    // Since we're using the g flag on the regex, we need to set the lastIndex.
                    // This automatically happens on some implementations, but not others, see:
                    // http://stackoverflow.com/questions/2645273/javascript-regular-expression-literal-persists-between-function-calls
                    // http://blog.stevenlevithan.com/archives/fixing-javascript-regexp
                    cssRe.lastIndex = 0;

                    while ((matches = cssRe.exec(styles))) {
                        out[matches[1]] = matches[2] || '';
                    }
                }

                return out;
            },

            /**
             * Selects elements based on the passed CSS selector to enable
             * {@link Ext.dom.Element Element} methods to be applied to many related
             * elements in one statement through the returned
             * {@link Ext.dom.CompositeElementLite CompositeElementLite} object.
             * @static
             * @inheritable
             * @param {String/HTMLElement[]} selector The CSS selector or an array of
             * elements
             * @param {Boolean} [composite=false] Return a CompositeElement as opposed to
             * a CompositeElementLite. Defaults to false.
             * @param {HTMLElement/String} [root] The root element of the query or id of
             * the root
             * @return {Ext.dom.CompositeElementLite/Ext.dom.CompositeElement}
             */
            select: function(selector, composite, root) {
                return Ext.fly(root || DOC).select(selector, composite);
            },

            /**
             * Selects child nodes of a given root based on the passed CSS selector.
             * @static
             * @inheritable
             * @param {String} selector The CSS selector.
             * @param {Boolean} [asDom=true] `false` to return an array of Ext.dom.Element
             * @param {HTMLElement/String} [root] The root element of the query or id of
             * the root
             * @return {HTMLElement[]/Ext.dom.Element[]} An Array of elements that match
             * the selector.  If there are no matches, an empty Array is returned.
             */
            query: function(selector, asDom, root) {
                return Ext.fly(root || DOC).query(selector, asDom);
            },

            /**
             * Parses a number or string representing margin sizes into an object. Supports
             * CSS-style margin declarations (e.g. 10, "10", "10 10", "10 10 10" and "10 10 10 10"
             * are all valid options and would return the same result)
             * @static
             * @inheritable
             * @param {Number/String/Object} box The encoded margins, or an object with top, right,
             * @param {String} units The type of units to add
             * @return {String} An string with unitized (px if units is not specified) metrics for
             * top, right, bottom and left
             */
            unitizeBox: function(box, units) {
                var me = this;

                box = me.parseBox(box);

                return me.addUnits(box.top, units) + ' ' +
                    me.addUnits(box.right, units) + ' ' +
                    me.addUnits(box.bottom, units) + ' ' +
                    me.addUnits(box.left, units);
            },

            /**
             * Unmask iframes when shim is true. See {@link Ext.util.Floating#cfg-shim}.
             * @private
             */
            unmaskIframes: function() {
                var iframes = document.getElementsByTagName('iframe'),
                    fly = new Ext.dom.Fly();

                Ext.each(iframes, function(iframe) {
                    fly.attach(iframe.parentNode).unmask();
                });
            },

            /**
             * Serializes a DOM form into a url encoded string
             * @param {Object} form The form
             * @return {String} The url encoded form
             * @static
             * @inheritable
             */
            serializeForm: function(form) {
                var fElements = form.elements || (DOC.forms[form] || Ext.getDom(form)).elements,
                    hasSubmit = false,
                    encoder = encodeURIComponent,
                    data = '',
                    eLen = fElements.length,
                    element, name, type, options, hasValue, e, o, oLen, opt;

                for (e = 0; e < eLen; e++) {
                    element = fElements[e];
                    name = element.name;
                    type = element.type;
                    options = element.options;

                    if (!element.disabled && name) {
                        if (/select-(one|multiple)/i.test(type)) {
                            oLen = options.length;

                            for (o = 0; o < oLen; o++) {
                                opt = options[o];

                                if (opt.selected) {
                                    hasValue = opt.hasAttribute('value');
                                    data += Ext.String.format('{0}={1}&', encoder(name), encoder(hasValue ? opt.value : opt.text)); // eslint-disable-line max-len
                                }
                            }
                        }
                        else if (!(/file|undefined|reset|button/i.test(type))) {
                            if (!(/radio|checkbox/i.test(type) && !element.checked) && !(type === 'submit' && hasSubmit)) { // eslint-disable-line max-len
                                data += encoder(name) + '=' + encoder(element.value) + '&';
                                hasSubmit = /submit/i.test(type);
                            }
                        }
                    }
                }

                return data.substr(0, data.length - 1);
            },

            /**
             * Returns the common ancestor of the two passed elements.
             * @static
             * @inheritable
             *
             * @param {Ext.dom.Element/HTMLElement} nodeA
             * @param {Ext.dom.Element/HTMLElement} nodeB
             * @param {Boolean} returnDom Pass `true` to return a DOM element. Otherwise an
             * {@link Ext.dom.Element Element} will be returned.
             * @return {Ext.dom.Element/HTMLElement} The common ancestor.
             */
            getCommonAncestor: function(nodeA, nodeB, returnDom) {
                caFly = caFly || new Ext.dom.Fly();
                caFly.attach(Ext.getDom(nodeA));

                while (!caFly.isAncestor(nodeB)) {
                    if (caFly.dom.parentNode) {
                        caFly.attach(caFly.dom.parentNode);
                    }
                    // If Any of the nodes in in a detached state, have to use the document.body
                    else {
                        caFly.attach(DOC.body);

                        break;
                    }
                }

                return returnDom ? caFly.dom : Ext.get(caFly);
            }
        },

        /**
        * Enable text selection for this element (normalized across browsers)
        * @return {Ext.dom.Element} this
        */
        selectable: function() {
            var me = this;

            // We clear this property for all browsers, not just Opera. This is so that rendering
            // templates don't need to condition on Opera when making elements unselectable.
            me.dom.unselectable = '';

            me.removeCls(Element.unselectableCls);
            me.addCls(Element.selectableCls);

            return me;
        },

        /**
         * Disables text selection for this element (normalized across browsers)
         * @return {Ext.dom.Element} this
         */
        unselectable: function() {
            // The approach used to disable text selection combines CSS, HTML attributes and DOM
            // events. Importantly the strategy is designed to be expressible in markup, so that
            // elements can be rendered unselectable without needing modifications post-render.
            // e.g.:
            //
            // <div class="x-unselectable" unselectable="on"></div>
            //
            // Changes to this method may need to be reflected elsewhere, e.g. ProtoElement.
            var me = this;

            // The unselectable property (or similar) is supported by various browsers but Opera
            // is the only browser that doesn't support any of the other techniques. The problem
            // with it is that it isn't inherited by child elements. Theoretically we could add it
            // to all children but the performance would be terrible. In certain key locations
            // (e.g. panel headers) we add unselectable="on" to extra elements during rendering
            // just for Opera's benefit.
            if (Ext.isOpera) {
                me.dom.unselectable = 'on';
            }

            // In Mozilla and WebKit the CSS properties -moz-user-select and -webkit-user-select
            // prevent a selection originating in an element. These are inherited, which is what
            // we want.
            //
            // In IE we rely on a listener for the selectstart event instead. We don't need to
            // register a listener on the individual element, instead we use a single listener
            // and rely on event propagation to listen for the event at the document level.
            // That listener will walk up the DOM looking for nodes that have either of the classes
            // x-selectable or x-unselectable. This simulates the CSS inheritance approach.
            //
            // IE 10 is expected to support -ms-user-select so the listener may not be required.
            me.removeCls(Element.selectableCls);
            me.addCls(Element.unselectableCls);

            return me;
        },

        // statics
        statics: {
            // This selector will be modified at runtime in the _init() method above
            // to include the elements with saved tabindex in the returned set
            tabbableSelector: Ext.supports.CSS3NegationSelector
                ? 'a[href],button,iframe,input,select,textarea,[tabindex]:not([tabindex="-1"]),[contenteditable="true"]' // eslint-disable-line max-len
                : 'a[href],button,iframe,input,select,textarea,[tabindex],[contenteditable="true"]',

            // Anchor and link tags are special; they are only naturally focusable (and tabbable)
            // if they have href attribute, and tabbabledness is further platform/browser specific.
            // Thus we check it separately in the code.
            naturallyFocusableTags: {
                BUTTON: true,
                IFRAME: true,
                EMBED: true,
                INPUT: true,
                OBJECT: true,
                SELECT: true,
                TEXTAREA: true,
                HTML: Ext.isIE ? true : false,
                BODY: Ext.isIE ? false : true
            },

            // <object> element is naturally tabbable only in IE8 and below
            naturallyTabbableTags: {
                BUTTON: true,
                IFRAME: true,
                INPUT: true,
                SELECT: true,
                TEXTAREA: true,
                OBJECT: Ext.isIE8m ? true : false
            },

            inputTags: {
                INPUT: true,
                TEXTAREA: true
            },

            tabbableSavedCounterAttribute: 'data-tabindex-counter',
            tabbableSavedValueAttribute: 'data-tabindex-value',

            splitCls: function(cls) {
                if (typeof cls === 'string') {
                    cls = cls.split(spacesRe);
                }

                return cls;
            }
        }, // statics

        _init: function(E) {
            // Allow overriding the attribute name and/or selector; this is
            // done only once for performance reasons
            E.tabbableSelector += ',[' + E.tabbableSavedCounterAttribute + ']';
        },

        /**
         * Adds the given CSS class(es) to this Element.
         * @param {String/String[]} names The CSS classes to add separated by space,
         * or an array of classes
         * @param {String} [prefix] Prefix to prepend to each class. The separator `-` will be
         * appended to the prefix.
         * @param {String} [suffix] Suffix to append to each class. The separator `-` will be
         * prepended to the suffix.
         * @return {Ext.dom.Element} this
         */
        addCls: function(names, prefix, suffix) {
            return this.replaceCls(null, names, prefix, suffix);
        },

        /**
         * Sets up event handlers to add and remove a css class when the mouse is down and then up
         * on this element (a click effect)
         * @param {String} className The class to add
         * @param {Function} [testFn] A test function to execute before adding the class. The passed
         * parameter will be the Element instance. If this functions returns false, the class
         * will not be added.
         * @param {Object} [scope] The scope to execute the testFn in.
         * @return {Ext.dom.Element} this
         */
        addClsOnClick: function(className, testFn, scope) {
            var me = this,
                hasTest = Ext.isFunction(testFn);

            me.on("mousedown", function() {
                if (hasTest && testFn.call(scope || me, me) === false) {
                    return false;
                }

                me.addCls(className);

                Ext.getDoc().on({
                    mouseup: function() {
                        // In case me was destroyed prior to mouseup
                        if (me.dom) {
                            me.removeCls(className);
                        }
                    },
                    single: true
                });
            });

            return me;
        },

        /**
         * Sets up event handlers to add and remove a css class when this element has the focus
         * @param {String} className The class to add
         * @param {Function} [testFn] A test function to execute before adding the class. The passed
         * parameter will be the Element instance. If this functions returns false, the class
         * will not be added.
         * @param {Object} [scope] The scope to execute the testFn in.
         * @return {Ext.dom.Element} this
         */
        addClsOnFocus: function(className, testFn, scope) {
            var me = this,
                hasTest = Ext.isFunction(testFn);

            me.on("focus", function() {
                if (hasTest && testFn.call(scope || me, me) === false) {
                    return false;
                }

                me.addCls(className);
            });

            me.on("blur", function() {
                // In case blur is caused by destruction of me
                if (me.dom) {
                    me.removeCls(className);
                }
            });

            return me;
        },

        /**
         * Sets up event handlers to add and remove a css class when the mouse is over this element
         * @param {String} className The class to add
         * @param {Function} [testFn] A test function to execute before adding the class. The passed
         * parameter will be the Element instance. If this functions returns false, the class
         * will not be added.
         * @param {Object} [scope] The scope to execute the testFn in.
         * @return {Ext.dom.Element} this
         */
        addClsOnOver: function(className, testFn, scope) {
            var me = this,
                hasTest = Ext.isFunction(testFn);

            me.hover(
                function() {
                    if (hasTest && testFn.call(scope || me, me) === false) {
                        return;
                    }

                    me.addCls(className);
                },
                function() {
                    me.removeCls(className);
                }
            );

            return me;
        },

        addStyles: function(sides, styles) {
            var totalSize = 0,
                sidesArr = (sides || '').match(wordsRe),
                styleSides = [],
                len = sidesArr.length,
                side, i;

            if (len === 1) {
                totalSize = parseFloat(this.getStyle(styles[sidesArr[0]])) || 0;
            }
            else if (len) {
                for (i = 0; i < len; i++) {
                    side = sidesArr[i];
                    styleSides.push(styles[side]);
                }

                // Gather all at once, returning a hash
                styleSides = this.getStyle(styleSides);

                for (i = 0; i < len; i++) {
                    side = sidesArr[i];
                    totalSize += parseFloat(styleSides[styles[side]]) || 0;
                }
            }

            return totalSize;
        },

        addUnits: function(size, units) {
            return Element.addUnits(size, units);
        },

        // The following 3 methods add just enough of an animation api to make the scroller work
        // in Sencha Touch
        // TODO: unify touch/ext animations
        animate: function(animation) {
            animation = new Ext.fx.Animation(animation);
            animation.setElement(this);
            this._activeAnimation = animation;

            animation.on({
                animationend: this._onAnimationEnd,
                scope: this
            });

            Ext.Animator.run(animation);

            return animation;
        },

        _onAnimationEnd: function() {
            this._activeAnimation = null;
        },

        getActiveAnimation: function() {
            return this._activeAnimation;
        },

        append: function() {
            return this.appendChild.apply(this, arguments);
        },

        /**
         * Appends the passed element(s) to this element
         * @param {String/HTMLElement/Ext.dom.Element/Object} el The id or element to insert
         * or a DomHelper config
         * @param {Boolean} [returnDom=false] True to return the raw DOM element instead
         * of Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The inserted Ext.dom.Element (or
         * HTMLElement if _returnDom_ is _true_).
         */
        appendChild: function(el, returnDom) {
            var me = this,
                insertEl,
                eLen, e;

            if (el.nodeType || el.dom || typeof el === 'string') { // element
                el = Ext.getDom(el);
                me.dom.appendChild(el);

                return !returnDom ? Ext.get(el) : el;
            }
            else if (el.length) {
                // append all elements to a documentFragment
                insertEl = Ext.fly(DOC.createDocumentFragment());
                eLen = el.length;

                for (e = 0; e < eLen; e++) {
                    insertEl.appendChild(el[e], returnDom);
                }

                el = Ext.Array.toArray(insertEl.dom.childNodes);
                me.dom.appendChild(insertEl.dom);

                return returnDom ? el : new Ext.dom.CompositeElementLite(el);
            }
            else { // dh config
                return me.createChild(el, null, returnDom);
            }
        },

        /**
         * Appends this element to the passed element.
         * @param {String/HTMLElement/Ext.dom.Element} el The new parent element.
         * The id of the node, a DOM Node or an existing Element.
         * @return {Ext.dom.Element} This element.
         */
        appendTo: function(el) {
            Ext.getDom(el).appendChild(this.dom);

            return this;
        },

        /**
         * More flexible version of {@link #setStyle} for setting style properties.
         *
         * Styles in object form should be a valid DOM element style property.
         * [Valid style property names](http://www.w3schools.com/jsref/dom_obj_style.asp)
         * (_along with the supported CSS version for each_)
         *
         *     // <div id="my-el">Phineas Flynn</div>
         *
         *     var el = Ext.get('my-el');
         *
         *     el.applyStyles('color: white;');
         *
         *     el.applyStyles({
         *         fontWeight: 'bold',
         *         backgroundColor: 'gray',
         *         padding: '10px'
         *     });
         *
         *     el.applyStyles(function () {
         *         if (name.initialConfig.html === 'Phineas Flynn') {
         *             return 'font-style: italic;';
         *             // OR return { fontStyle: 'italic' };
         *         }
         *     });
         *
         * @param {String/Object/Function} styles A style specification string, e.g.
         * "width:100px", or object in the form `{width:"100px"}`, or a function which returns
         * such a specification.
         * @return {Ext.dom.Element} this
         */
        applyStyles: function(styles) {
            if (styles) {
                if (typeof styles === "function") {
                    styles = styles.call();
                }

                if (typeof styles === "string") {
                    styles = Element.parseStyles(styles);
                }

                if (typeof styles === "object") {
                    this.setStyle(styles);
                }
            }

            return this;
        },

        /**
         * Tries to blur the element. Any exceptions are caught and ignored.
         * @return {Ext.dom.Element} this
         */
        blur: function() {
            var me = this,
                dom = me.dom;

            // In IE, blurring the body can cause the browser window to hide.
            // Blurring the body is redundant, so instead we just focus it
            if (dom !== DOC.body) {
                try {
                    dom.blur();
                }
                catch (e) {
                    // This block is intentionally left blank
                }

                return me;
            }
            else {
                return me.focus(undefined, dom);
            }
        },

        /**
         * When an element is moved around in the DOM, or is hidden using `display:none`, it loses
         * layout, and therefore all scroll positions of all descendant elements are lost.
         *
         * This function caches them, and returns a function, which when run will restore the cached
         * positions. In the following example, the Panel is moved from one Container to another
         * which will cause it to lose all scroll positions:
         *
         *     var restoreScroll = myPanel.el.cacheScrollValues();
         *     myOtherContainer.add(myPanel);
         *     restoreScroll();
         *
         * @return {Function} A function which will restore all descendant elements of this Element
         * to their scroll positions recorded when this function was executed. Be aware that the
         * returned function is a closure which has captured the scope of `cacheScrollValues`, so
         * take care to dereference it as soon as not needed - if is it is a `var` it will drop out
         * of scope, and the reference will be freed.
         */
        cacheScrollValues: function() {
            var me = this,
                scrollValues = [],
                scrolledDescendants = [],
                descendants, descendant, i, len;

            scrollFly = scrollFly || new Ext.dom.Fly();

            descendants = me.query('*');

            for (i = 0, len = descendants.length; i < len; i++) {
                descendant = descendants[i];

                // use !== 0 for scrollLeft because it can be a negative number
                // in RTL mode in some browsers.
                if (descendant.scrollTop > 0 || descendant.scrollLeft !== 0) {
                    scrolledDescendants.push(descendant);
                    scrollValues.push(scrollFly.attach(descendant).getScroll());
                }
            }

            return function() {
                var scroll, i, len;

                for (i = 0, len = scrolledDescendants.length; i < len; i++) {
                    scroll = scrollValues[i];
                    scrollFly.attach(scrolledDescendants[i]);
                    scrollFly.setScrollLeft(scroll.left);
                    scrollFly.setScrollTop(scroll.top);
                }
            };
        },

        /**
         * Centers the Element in either the viewport, or another Element.
         * @param {String/HTMLElement/Ext.dom.Element} centerIn element in
         * which to center the element.
         * @return {Ext.dom.Element} This element
         *
         * @chainable
         */
        center: function(centerIn) {
            return this.alignTo(centerIn || DOC, 'c-c');
        },

        /**
         * Selects a single *direct* child based on the passed CSS selector (the selector
         * should not contain an id).
         * @param {String} selector The CSS selector.
         * @param {Boolean} [returnDom=false] `true` to return the DOM node instead of
         * Ext.dom.Element.
         * @return {HTMLElement/Ext.dom.Element} The child Ext.dom.Element (or DOM node
         * if `returnDom` is `true`)
         */
        child: function(selector, returnDom) {
            var me = this,
                id;

            // If possible, avoid caching the root element.
            if (Ext.supports.Selectors2) {
                return me.selectNode(':scope>' + selector, !!returnDom);
            }
            else {
                // Pull the ID from the DOM (Ext.id also ensures that there *is* an ID).
                // If this object is a Flyweight, it will not have an ID
                id = me.id != null ? me.id : Ext.get(me).id;

                return me.selectNode(Ext.makeIdSelector(id) + " > " + selector, !!returnDom);
            }
        },

        /**
         * Clone this element.
         * @param {Boolean} [deep=false] `true` if the children of the node should also be cloned.
         * @param {Boolean} [returnDom=false] `true` to return the DOM node instead of
         * Ext.dom.Element.
         * @return {HTMLElement/Ext.dom.Element} The newly cloned Ext.dom.Element (or DOM node
         * if `returnDom` is `true`).
         */
        clone: function(deep, returnDom) {
            var clone = this.dom.cloneNode(deep);

            if (Ext.supports.CloneNodeCopiesExpando) {
                clearData(clone, deep);
            }

            return returnDom ? clone : Ext.get(clone);
        },

        constrainScrollLeft: function(left) {
            var dom = this.dom;

            return Math.max(Math.min(left, dom.scrollWidth - dom.clientWidth), 0);
        },

        constrainScrollTop: function(top) {
            var dom = this.dom;

            return Math.max(Math.min(top, dom.scrollHeight - dom.clientHeight), 0);
        },

        /**
         * Creates the passed DomHelper config and appends it to this element or optionally
         * inserts it before the passed child element.
         * @param {Object} config DomHelper element config object.  If no tag is specified
         * (e.g., {tag:'input'}) then a div will be automatically generated with the specified
         * attributes.
         * @param {HTMLElement} [insertBefore] a child element of this element
         * @param {Boolean} [returnDom=false] true to return the dom node instead of creating
         * an Element
         * @return {Ext.dom.Element/HTMLElement} The new child element (or HTMLElement if
         * _returnDom_ is _true_)
         */
        createChild: function(config, insertBefore, returnDom) {
            config = config || { tag: 'div' };

            if (insertBefore) {
                return Ext.DomHelper.insertBefore(insertBefore, config, returnDom !== true);
            }
            else {
                return Ext.DomHelper.append(this.dom, config, returnDom !== true);
            }
        },

        /**
         * Returns `true` if this element is an ancestor of the passed element, or is
         * the element.
         * @param {String/HTMLElement/Ext.dom.Element} element The dom element,
         * Ext.dom.Element, or id (string) of the dom element to check.
         * @return {Boolean} True if this element is an ancestor of el or the el itself, else false
         */
        contains: function(element) {
            if (!element) {
                return false;
            }

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                dom = Ext.getDom(element);

            // we need el-contains-itself logic here because isAncestor does not do that:
            // https://developer.mozilla.org/en-US/docs/Web/API/Node.contains
            return (dom === me.dom) || me.isAncestor(dom);
        },

        /**
         * Destroys this element by removing it from the cache, removing its DOM reference,
         * and removing all of its event listeners.
         */
        destroy: function() {
            var me = this,
                dom = me.dom;

            //<debug>
            if (me.destroyed) {
                Ext.Logger.warn("Cannot destroy Element \"" + me.id + "\". Already destroyed.");

                return;
            }

            if (me.resumeFocusEventsTimer) {
                Ext.unasap(me.resumeFocusEventsTimer);
                me.resumeFocusEventsTimer = null;
            }

            if (me.repaintTimer) {
                me.repaintTimer = Ext.undefer(me.repaintTimer);
            }

            if (me.deferFocusTimer) {
                me.deferFocusTimer = Ext.undefer(me.deferFocusTimer);
            }

            if (dom) {
                if (dom === DOC.body) {
                    Ext.raise("Cannot destroy body element.");
                }
                else if (dom === DOC) {
                    Ext.raise("Cannot destroy document object.");
                }
                else if (dom === WIN) {
                    Ext.raise("Cannot destroy window object");
                }
            }
            //</debug>

            if (dom && dom.parentNode) {
                dom.parentNode.removeChild(dom);
            }

            if (me.$ripples) {
                me.destroyAllRipples();
            }

            me.collect();
        },

        detach: function() {
            var dom = this.dom,
                component = this.component;

            if (dom && dom.parentNode && dom.tagName !== 'BODY') {
                // Ensure focus is never lost
                if (component) {
                    component.revertFocus();
                }

                dom.parentNode.removeChild(dom);
            }

            return this;
        },

        /**
         * Disables the shadow element created by {@link #enableShadow}.
         * @private
         */
        disableShadow: function() {
            var shadow = this.shadow;

            if (shadow) {
                shadow.hide();
                shadow.disabled = true;
            }
        },

        /**
         * Disables the shim element created by {@link #enableShim}.
         * @private
         */
        disableShim: function() {
            var shim = this.shim;

            if (shim) {
                shim.hide();
                shim.disabled = true;
            }
        },

        /**
         * @private
         */
        doReplaceWith: function(element) {
            var dom = this.dom;

            dom.parentNode.replaceChild(Ext.getDom(element), dom);
        },

        /**
         * @private
         * A scrollIntoView implementation for scrollIntoView/rtlScrollIntoView to call
         * after current scrollX has been determined.
         */
        doScrollIntoView: function(container, hscroll, animate, highlight, getScrollX, scrollTo) {
            scrollFly = scrollFly || new Ext.dom.Fly();

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                dom = me.dom,
                scrollX = scrollFly.attach(container)[getScrollX](),
                scrollY = container.scrollTop,
                position = me.getScrollIntoViewXY(container, scrollX, scrollY),
                newScrollX = position.x,
                newScrollY = position.y;

            // Highlight upon end of scroll
            if (highlight) {
                if (animate) {
                    animate = Ext.apply({
                        listeners: {
                            afteranimate: function() {
                                scrollFly.attach(dom).highlight();
                            }
                        }
                    }, animate);
                }
                else {
                    scrollFly.attach(dom).highlight();
                }
            }

            if (newScrollY !== scrollY) {
                scrollFly.attach(container).scrollTo('top', newScrollY, animate);
            }

            if (hscroll !== false && (newScrollX !== scrollX)) {
                scrollFly.attach(container)[scrollTo]('left', newScrollX, animate);
            }

            return me;
        },

        /**
         * Selects a single child at any depth below this element based on the passed
         * CSS selector (the selector should not contain an id).
         *
         * Use {@link #getById} if you need to get a reference to a child element via id.
         *
         * @param {String} selector The CSS selector
         * @param {Boolean} [returnDom=false] `true` to return the DOM node instead of
         * Ext.dom.Element
         * @return {HTMLElement/Ext.dom.Element} The child Ext.dom.Element (or DOM node
         * if `returnDom` is `true`)
         */
        down: function(selector, returnDom) {
            return this.selectNode(selector, !!returnDom);
        },

        /**
         * Enables a shadow element that will always display behind this element
         * @param {Object} [options] Configuration options for the shadow
         * @param {Number} [options.offset=4] Number of pixels to offset the shadow
         * @param {String} [options.mode='sides'] The shadow display mode.  Supports the following
         * options:
         *
         *     - `'sides'`: Shadow displays on both sides and bottom only
         *     - `'frame'`: Shadow displays equally on all four sides
         *     - `'drop'`: Traditional bottom-right drop shadow
         *     - `'bottom'`: Shadow is offset to the bottom
         *
         * @param {Boolean} [options.animate=false] `true` to animate the shadow while
         * the element is animating.  By default the shadow will be hidden during animation.
         * @param {Boolean} isVisible (private)
         * @private
         */
        enableShadow: function(options, isVisible) {
            var me = this,
                shadow = me.shadow || (me.shadow = new Ext.dom.Shadow(Ext.apply({
                    target: me
                }, options))),
                shim = me.shim;

            if (shim) {
                shim.offsets = shadow.outerOffsets;
                shim.shadow = shadow;
                shadow.shim = shim;
            }

            // Components pass isVisible to avoid the extra dom read to determine
            // whether or not this element is visible.
            if (isVisible === true || (isVisible !== false && me.isVisible())) {
                // the shadow element may have just been retrieved from an OverlayPool, so
                // we need to explicitly show it to be sure hidden styling is removed
                shadow.show();
            }
            else {
                shadow.hide();
            }

            shadow.disabled = false;
        },

        /**
         * Enables an iframe shim for this element to keep windowed objects from
         * showing through.  The position, size, and visibility of the shim will be
         * automatically synchronized as the position, size, and visibility of this
         * Element are changed.
         * @param {Object} [options] Configuration options for the shim
         * @param {Boolean} isVisible (private)
         * @return {Ext.dom.Shim} The new Shim
         * @private
         */
        enableShim: function(options, isVisible) {
            var me = this,
                shim = me.shim || (me.shim = new Ext.dom.Shim(Ext.apply({
                    target: me
                }, options))),
                shadow = me.shadow;

            if (shadow) {
                shim.offsets = shadow.outerOffsets;
                shim.shadow = shadow;
                shadow.shim = shim;
            }

            // Components pass isVisible to avoid the extra dom read to determine
            // whether or not this element is visible.
            if (isVisible === true || (isVisible !== false && me.isVisible())) {
                // the shim element may have just been retrieved from an OverlayPool, so
                // we need to explicitly show it to be sure hidden styling is removed
                shim.show();
            }
            else {
                shim.hide();
            }

            shim.disabled = false;

            return shim;
        },

        /**
         * Looks at this node and then at parent nodes for a match of the passed simple selector.
         * @param {String} simpleSelector The simple selector to test. See {@link Ext.dom.Query}
         * for information about simple selectors.
         * @param {Number/String/HTMLElement/Ext.dom.Element} [limit]
         * The max depth to search as a number or an element which causes the upward traversal
         * to stop and is **not** considered for inclusion as the result.
         * (defaults to 50 || document.documentElement)
         * @param {Boolean} [returnEl=false] True to return a Ext.dom.Element object instead of
         * DOM node
         * @return {HTMLElement/Ext.dom.Element} The matching DOM node (or
         * Ext.dom.Element if _returnEl_ is _true_).  Or null if no match was found.
         */
        findParent: function(simpleSelector, limit, returnEl) {
            var me = this,
                target = me.dom,
                topmost = docEl,
                depth = 0;

            if (limit || limit === 0) {
                if (typeof limit !== 'number') {
                    topmost = Ext.getDom(limit);
                    limit = Number.MAX_VALUE;
                }
            }
            else {
                // No limit passed, default to 50
                limit = 50;
            }

            while (target && target.nodeType === 1 && depth < limit && target !== topmost) {
                if (Ext.fly(target).is(simpleSelector)) {
                    return returnEl ? Ext.get(target) : target;
                }

                depth++;
                target = target.parentNode;
            }

            return null;
        },

        /**
         * Looks at parent nodes for a match of the passed simple selector.
         * @param {String} simpleSelector The simple selector to test. See {@link Ext.dom.Query}
         * for information about simple selectors.
         * @param {Number/String/HTMLElement/Ext.dom.Element} [limit]
         * The max depth to search as a number or an element which causes the upward traversal
         * to stop and is **not** considered for inclusion as the result.
         * (defaults to 50 || document.documentElement)
         * @param {Boolean} [returnEl=false] True to return a Ext.dom.Element object instead of
         * DOM node
         * @return {HTMLElement/Ext.dom.Element} The matching DOM node (or
         * Ext.dom.Element if _returnEl_ is _true_).  Or null if no match was found.
         */
        findParentNode: function(simpleSelector, limit, returnEl) {
            var p = Ext.fly(this.dom.parentNode);

            return p ? p.findParent(simpleSelector, limit, returnEl) : null;
        },

        /**
         * Gets the first child, skipping text nodes
         * @param {String} [selector] Find the next sibling that matches the passed simple selector.
         * See {@link Ext.dom.Query} for information about simple selectors.
         * @param {Boolean} [returnDom=false] `true` to return a raw DOM node instead of
         * an Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The first child or null
         */
        first: function(selector, returnDom) {
            return this.matchNode('nextSibling', 'firstChild', selector, returnDom);
        },

        /**
         * Try to focus the element either immediately or after a timeout
         * if `defer` argument is specified.
         *
         * @param {Number} [defer] Milliseconds to defer the focus
         * @param {HTMLElement} [dom] (private)
         *
         * @return {Ext.dom.Element} this
         */
        focus: function(defer, dom) {
            var me = this;

            dom = dom || me.dom;

            if (Number(defer)) {
                Ext.defer(me.focus, defer, me, [null, dom]);
            }
            else {
                Ext.fireEvent('beforefocus', dom);
                dom.focus();
            }

            return me;
        },

        /**
         * @private
         * Removes the element from the cache and removes listeners.
         * Used for cleaning up orphaned elements after they have been removed from the dom.
         * Similar to {@link #destroy} except it assumes the element has already been
         * removed from the dom.
         */
        collect: function() {
            var me = this,
                dom = me.dom,
                shadow = me.shadow,
                shim = me.shim;

            // The parent destroy sets the destroy to emptyFn, which we don't
            // want on a shared fly
            if (!me.isFly) {
                me.mixins.observable.destroy.call(me);
                delete Ext.cache[me.id];
                me.el = null;
            }

            if (dom) {
                dom._extData = me.dom = null;
            }

            // we do not destroy the shadow and shim because they are returned to their
            // OverlayPools for reuse.
            if (shadow) {
                shadow.hide();
                me.shadow = null;
            }

            if (shim) {
                shim.hide();
                me.shim = null;
            }
        },

        getAnchorToXY: function(el, anchor, local, mySize) {
            return el.getAnchorXY(anchor, local, mySize);
        },

        /**
         * Returns the value of an attribute from the element's underlying DOM node.
         * @param {String} name The attribute name.
         * @param {String} [namespace] The namespace in which to look for the attribute.
         * @return {String} The attribute value.
         */
        getAttribute: function(name, namespace) {
            var dom = this.dom;

            return namespace
                ? (dom.getAttributeNS(namespace, name) || dom.getAttribute(namespace + ":" + name))
                : (dom.getAttribute(name) || dom[name] || null);
        },

        /**
         * Returns an object containing a map of all attributes of this element's DOM node.
         *
         * @return {Object} Key/value pairs of attribute names and their values.
         */
        getAttributes: function() {
            var attributes = this.dom.attributes,
                result = {},
                attr, i, len;

            for (i = 0, len = attributes.length; i < len; i++) {
                attr = attributes[i];

                result[attr.name] = attr.value;
            }

            return result;
        },

        /**
         * Gets the bottom Y coordinate of the element (element Y position + element height)
         * @param {Boolean} local True to get the local css position instead of page
         * coordinate
         * @return {Number}
         */
        getBottom: function(local) {
            return (local ? this.getLocalY() : this.getY()) + this.getHeight();
        },

        /**
         * Returns a child element of this element given its `id`.
         * @param {String} id The id of the desired child element.
         * @param {Boolean} [asDom=false] True to return the DOM element, false to return a
         * wrapped Element object.
         * @return {Ext.dom.Element/HTMLElement} The child element (or HTMLElement if
         * _asDom_ is _true_).  Or null if no match was found.
         */
        getById: function(id, asDom) {
            // for normal elements getElementById is the best solution, but if the el is
            // not part of the document.body, we have to resort to querySelector
            var dom = DOC.getElementById(id) ||
                this.dom.querySelector(Ext.makeIdSelector(id));

            return asDom ? dom : (dom ? Ext.get(dom) : null);
        },

        getBorderPadding: function() {
            var paddingWidth = this.getStyle(paddingsTLRB),
                bordersWidth = this.getStyle(bordersTLRB);

            /* eslint-disable max-len */
            return {
                beforeX: (parseFloat(bordersWidth[borders.l]) || 0) + (parseFloat(paddingWidth[paddings.l]) || 0),
                afterX: (parseFloat(bordersWidth[borders.r]) || 0) + (parseFloat(paddingWidth[paddings.r]) || 0),
                beforeY: (parseFloat(bordersWidth[borders.t]) || 0) + (parseFloat(paddingWidth[paddings.t]) || 0),
                afterY: (parseFloat(bordersWidth[borders.b]) || 0) + (parseFloat(paddingWidth[paddings.b]) || 0)
            };
            /* eslint-enable max-len */
        },

        /**
         * @private
         */
        getBorders: function() {
            var bordersWidth = this.getStyle(bordersTLRB);

            return {
                beforeX: (parseFloat(bordersWidth[borders.l]) || 0),
                afterX: (parseFloat(bordersWidth[borders.r]) || 0),
                beforeY: (parseFloat(bordersWidth[borders.t]) || 0),
                afterY: (parseFloat(bordersWidth[borders.b]) || 0)
            };
        },

        /**
         * Gets the width of the border(s) for the specified side(s)
         * @param {String} side Can be t, l, r, b or any combination of those to add
         * multiple values. For example, passing `'lr'` would get the border **l**eft
         * width + the border **r**ight width.
         * @return {Number} The width of the sides passed added together
         */
        getBorderWidth: function(side) {
            return this.addStyles(side, borders);
        },

        /**
         * Returns an object holding as properties this element's CSS classes. The object
         * can be modified to effect arbitrary class manipulations that won't immediately
         * go to the DOM. When completed, the DOM node can be updated by calling
         * {@link #setClassMap setClassMap}. The values of the properties should either
         * be set to truthy values (such as `1` or `true`) or removed via `delete`.
         *
         *      var classes = el.getClassMap();
         *
         *      // Add the 'foo' class:
         *      classes.foo = 1;
         *
         *      // Remove the 'bar' class:
         *      delete classes.bar;
         *
         *      // Update the DOM in one step:
         *      el.setClassMap(classes);
         *
         * @param {Boolean} [clone=true] (private) Pass `false` to return the underlying
         * (readonly) object.
         * @return {Object}
         * @since 6.5.0
         */
        getClassMap: function(clone) {
            var data = this.getData();

            if (data) {
                data = data.classMap;

                if (clone !== false) {
                    data = Ext.apply({}, data);
                }
            }

            return data;
        },

        /**
         * Returns this element's data object. This object holds options that may be
         * needed by an `Ext.fly()` call when there is no (cached) `Ext.dom.Element`
         * instance tracked.
         *
         * This method will only return `null` if this instance has no associated DOM
         * node. If the DOM node does not have a data object, one will be created. To
         * avoid this creation, use {@link #peekData peekData()} instead.
         *
         * @param {Boolean} [sync=true] (private) Pass `false` to skip synchronization.
         * @return {Object}
         * @private
         */
        getData: function(sync) {
            var dom = this.dom,
                data;

            if (dom) {
                data = dom._extData || (dom._extData = {});

                if (sync !== false && !data.isSynchronized) {
                    this.synchronize();
                }
            }

            return data || null;
        },

        getFirstChild: function() {
            return Ext.get(this.dom.firstElementChild);
        },

        getLastChild: function() {
            return Ext.get(this.dom.lastElementChild);
        },

        /**
         * Returns the offset height of the element.
         * @param {Boolean} [contentHeight] `true` to get the height minus borders and padding.
         * @param {Boolean} [preciseHeight] `true` to get the precise height
         * @return {Number} The element's height.
         */
        getHeight: function(contentHeight, preciseHeight) {
            var me = this,
                dom = me.dom,
                hidden = me.isStyle('display', 'none'),
                height,
                floating;

            if (hidden) {
                return 0;
            }

            // Use the viewport height if they are asking for body height
            if (dom.nodeName === 'BODY') {
                height = Element.getViewportHeight();
            }
            else {
                if (preciseHeight) {
                    height = dom.getBoundingClientRect().height;
                }
                else {
                    height = dom.offsetHeight;

                    // SVG nodes do not have offsetHeight, so use boundingClientRect instead.
                    if (height == null) {
                        height = dom.getBoundingClientRect().height;
                    }
                }
            }

            // IE9/10 Direct2D dimension rounding bug
            if (Ext.supports.Direct2DBug) {
                floating = me.adjustDirect2DDimension(HEIGHT);

                if (preciseHeight) {
                    height += floating;
                }
                else if (floating > 0 && floating < 0.5) {
                    height++;
                }
            }

            if (contentHeight) {
                height -= me.getBorderWidth("tb") + me.getPadding("tb");
            }

            return (height < 0) ? 0 : height;
        },

        /**
         * Returns the `innerHTML` of an Element or an empty string if the element's
         * dom no longer exists.
         * @return {String}
         */
        getHtml: function() {
            return this.dom ? this.dom.innerHTML : '';
        },

        /**
         * Gets the left X coordinate
         * @param {Boolean} local True to get the local css position instead of
         * page coordinate
         * @return {Number}
         */
        getLeft: function(local) {
            return local ? this.getLocalX() : this.getX();
        },

        getLocalX: function() {
            var me = this,
                offsetParent,
                x = me.getStyle('left');

            if (!x || x === 'auto') {
                x = 0;
            }
            else if (pxRe.test(x)) {
                x = parseFloat(x);
            }
            else {
                x = me.getX();

                // Reading offsetParent causes forced async layout.
                // Do not do it unless needed.
                offsetParent = me.dom.offsetParent;

                if (offsetParent) {
                    x -= Ext.fly(offsetParent).getX();
                }
            }

            return x;
        },

        getLocalXY: function() {
            var me = this,
                offsetParent,
                style = me.getStyle(['left', 'top']),
                x = style.left,
                y = style.top;

            if (!x || x === 'auto') {
                x = 0;
            }
            else if (pxRe.test(x)) {
                x = parseFloat(x);
            }
            else {
                x = me.getX();

                // Reading offsetParent causes forced async layout.
                // Do not do it unless needed.
                offsetParent = me.dom.offsetParent;

                if (offsetParent) {
                    x -= Ext.fly(offsetParent).getX();
                }
            }

            if (!y || y === 'auto') {
                y = 0;
            }
            else if (pxRe.test(y)) {
                y = parseFloat(y);
            }
            else {
                y = me.getY();

                // Reading offsetParent causes forced async layout.
                // Do not do it unless needed.
                offsetParent = me.dom.offsetParent;

                if (offsetParent) {
                    y -= Ext.fly(offsetParent).getY();
                }
            }

            return [x, y];
        },

        getLocalY: function() {
            var me = this,
                offsetParent,
                y = me.getStyle('top');

            if (!y || y === 'auto') {
                y = 0;
            }
            else if (pxRe.test(y)) {
                y = parseFloat(y);
            }
            else {
                y = me.getY();

                // Reading offsetParent causes forced async layout.
                // Do not do it unless needed.
                offsetParent = me.dom.offsetParent;

                if (offsetParent) {
                    y -= Ext.fly(offsetParent).getY();
                }
            }

            return y;
        },

        /**
         * @method
         *
         * Returns an object with properties top, left, right and bottom representing the margins
         * of this element unless sides is passed, then it returns the calculated width
         * of the sides (see {@link #getPadding}).
         * @param {String} [sides] Any combination of 'l', 'r', 't', 'b' to get the sum
         * of those sides.
         * @return {Object/Number}
         */
        getMargin: (function() {
            var hash = { t: "top", l: "left", r: "right", b: "bottom" },
                allMargins = ['margin-top', 'margin-left', 'margin-right', 'margin-bottom'];

            return function(side) {
                var me = this,
                    style, key, o;

                if (!side) {
                    style = me.getStyle(allMargins);
                    o = {};

                    if (style && typeof style === 'object') {
                        o = {};

                        for (key in margins) {
                            o[key] = o[hash[key]] = parseFloat(style[margins[key]]) || 0;
                        }
                    }
                }
                else {
                    o = me.addStyles(side, margins);
                }

                return o;
            };
        })(),

        /**
         * Gets the width of the padding(s) for the specified side(s).
         * @param {String} side Can be t, l, r, b or any combination of those to add
         * multiple values. For example, passing `'lr'` would get the padding **l**eft +
         * the padding **r**ight.
         * @return {Number} The padding of the sides passed added together.
         */
        getPadding: function(side) {
            return this.addStyles(side, paddings);
        },

        getParent: function() {
            return Ext.get(this.dom.parentNode);
        },

        /**
         * Gets the right X coordinate of the element (element X position + element width)
         * @param {Boolean} local True to get the local css position instead of page
         * coordinates
         * @return {Number}
         */
        getRight: function(local) {
            return (local ? this.getLocalX() : this.getX()) + this.getWidth();
        },

        /**
         * Returns the current scroll position of the element.
         * @return {Object} An object containing the scroll position in the format
         * `{left: (scrollLeft), top: (scrollTop)}`
         */
        getScroll: function() {
            var me = this,
                dom = me.dom,
                docElement = docEl,
                left, top,
                body = DOC.body;

            if (dom === DOC || dom === body) {
                // the scrollLeft/scrollTop may be either on the body or documentElement,
                // depending on browser. It is possible to use window.pageXOffset/pageYOffset
                // in most modern browsers but this complicates things when in rtl mode because
                // pageXOffset does not always behave the same as scrollLeft when direction is
                // rtl. (e.g. pageXOffset can be an offset from the right, while scrollLeft
                // is offset from the left, one can be positive and the other negative, etc.)
                // To avoid adding an extra layer of feature detection in rtl mode to deal with
                // these differences, it's best just to always use scrollLeft/scrollTop
                left = docElement.scrollLeft || (body ? body.scrollLeft : 0);
                top = docElement.scrollTop || (body ? body.scrollTop : 0);
            }
            else {
                left = dom.scrollLeft;
                top = dom.scrollTop;
            }

            return {
                left: left,
                top: top
            };
        },

        /**
         * Gets the x and y coordinates needed for scrolling an element into view within
         * a given container.  These coordinates translate into the scrollLeft and scrollTop
         * positions that will need to be set on an ancestor of the element in order to make
         * this element visible within its container.
         * @param {String/HTMLElement/Ext.Element/Ext.util.Region} container The container
         * @param {Number} scrollX The container's current scroll position on the x axis
         * @param {Number} scrollY The container's current scroll position on the y axis
         * @param {Object} [align] The alignment for the scroll.
         * @param {'start'/'center'/'end'} [align.x] The alignment of the x scroll. If not
         * specified, the minimum will be done to make the element visible. The behavior
         * is undefined if the request cannot be honored. If the alignment is suffixed with a ?,
         * the alignment will only take place if the item is not already in the visible area.
         * @param {'start'/'center'/'end'} [align.y] The alignment of the x scroll. If not
         * specified, the minimum will be done to make the element visible. The behavior
         * is undefined if the request cannot be honored. If the alignment is suffixed with a ?,
         * the alignment will only take place if the item is not already in the visible area.
         * @return {Object} An object with "x" and "y" properties
         * @private
         */
        getScrollIntoViewXY: function(container, scrollX, scrollY, align) {
            var me = this,
                dom = me.dom,
                offsets, clientWidth, clientHeight;

            align = align || empty;

            if (container.isRegion) {
                clientHeight = container.height;
                clientWidth = container.width;
            }
            else {
                container = Ext.getDom(container);
                clientHeight = container.clientHeight;
                clientWidth = container.clientWidth;
            }

            offsets = me.getOffsetsTo(container);

            return {
                y: me.calcScrollPos(offsets[1] + scrollY, dom.offsetHeight,
                                    scrollY, clientHeight, align.y),
                x: me.calcScrollPos(offsets[0] + scrollX, dom.offsetWidth,
                                    scrollX, clientWidth, align.x)
            };
        },

        calcScrollPos: function(start, size, viewStart, viewSize, align) {
            var end = start + size,
                viewEnd = viewStart + viewSize,
                force = align && !endsQuestionRe.test(align),
                ret = viewStart;

            if (!force) {
                if (align) {
                    align = align.slice(0, -1);
                }

                if (size > viewSize || start < viewStart) {
                    align = align || 'start';
                    force = true;
                }
                else if (end > viewEnd) {
                    align = align || 'end';
                    force = true;
                }
            }

            if (force) {
                if (align === 'start') {
                    ret = start;
                }
                else if (align === 'center') {
                    ret = Math.max(0, start - Math.floor((viewSize / 2)));
                }
                else if (align === 'end') {
                    ret = Math.max(0, end - viewSize);
                }
            }

            return ret;
        },

        /**
         * Gets the left scroll position
         * @return {Number} The left scroll position
         */
        getScrollLeft: function() {
            var dom = this.dom;

            if (dom === DOC || dom === DOC.body) {
                return this.getScroll().left;
            }
            else {
                return dom.scrollLeft;
            }
        },

        /**
         * Gets the top scroll position
         * @return {Number} The top scroll position
         */
        getScrollTop: function() {
            var dom = this.dom;

            if (dom === DOC || dom === DOC.body) {
                return this.getScroll().top;
            }
            else {
                return dom.scrollTop;
            }
        },

        /**
         * Returns the size of the element.
         * @param {Boolean} [contentSize] `true` to get the width/size minus borders and padding.
         * @return {Object} An object containing the element's size:
         * @return {Number} return.width
         * @return {Number} return.height
         */
        getSize: function(contentSize) {
            return { width: this.getWidth(contentSize), height: this.getHeight(contentSize) };
        },

        /**
         * Returns a named style property based on computed/currentStyle (primary) and
         * inline-style if primary is not available.
         *
         * @param {String/String[]} property The style property (or multiple property names
         * in an array) whose value is returned.
         * @param {Boolean} [inline=false] if `true` only inline styles will be returned.
         * @return {String/Object} The current value of the style property for this element
         * (or a hash of named style values if multiple property arguments are requested).
         * @method
         */
        getStyle: function(property, inline) {
            var me = this,
                dom = me.dom,
                multiple = typeof property !== 'string',
                hooks = me.styleHooks,
                prop = property,
                props = prop,
                len = 1,
                domStyle, camel, values, hook, out, style, i;

            if (multiple) {
                values = {};
                prop = props[0];
                i = 0;

                if (!(len = props.length)) {
                    return values;
                }
            }

            if (!dom || dom.documentElement) {
                return values || '';
            }

            domStyle = dom.style;

            if (inline) {
                style = domStyle;
            }
            else {
                // Caution: Firefox will not render "presentation" (i.e. computed styles) in
                // iframes that are display:none or those inheriting display:none. Similar
                // issues with legacy Safari.
                //
                style = dom.ownerDocument.defaultView.getComputedStyle(dom, null);

                // fallback to inline style if rendering context not available
                if (!style) {
                    inline = true;
                    style = domStyle;
                }
            }

            do {
                hook = hooks[prop];

                if (!hook) {
                    hooks[prop] = hook = { name: Element.normalize(prop) };
                }

                if (hook.get) {
                    out = hook.get(dom, me, inline, style);
                }
                else {
                    camel = hook.name;
                    out = style[camel];
                }

                if (!multiple) {
                    return out;
                }

                values[prop] = out;
                prop = props[++i];
            } while (i < len);

            return values;
        },

        getStyleValue: function(name) {
            return this.dom.style.getPropertyValue(name);
        },

        getCaretPos: function() {
            var dom = this.dom,
                pos, selection;

            if (inputTypeSelectionSupported.test(dom.type)) {
                pos = dom.selectionStart;
                selection = (typeof pos !== 'number') && this.getTextSelection();

                if (selection) {
                    pos = selection[0];
                }
            }
            //<debug>
            else {
                Ext.raise('Input type of "' + dom.type + '" does not support selectionStart');
            }
            //</debug>

            return pos;
        },

        setCaretPos: function(pos) {
            this.selectText(pos, pos);
        },

        /**
         * Returns the selection range of an input element as an array of three values:
         *
         *      [ start, end, direction ]
         *
         * These have the same meaning as the parameters to `selectText`.
         * @return {Array}
         * @since 6.5.0
         */
        getTextSelection: function() {
            var dom = this.dom;

            if (inputTypeSelectionSupported.test(dom.type)) {
                return [ dom.selectionStart, dom.selectionEnd, dom.selectionDirection ];
            }
            else {
                //<debug>
                Ext.raise('Input type of "' + this.dom.type +
                          '" does not support selectionStart, selectionEnd and selectionDirection');
                //</debug>

                return [];
            }
            // FYI - Classic overrides this for older browsers...
        },

        /**
         * Selects the specified contents of the input element (all by default).
         * @param {Number} [start=0] The starting index to select.
         * @param {Number} [end] The end index to select (defaults to all remaining text).
         * @param {"f"/"b"/"forward"/"backward"} [direction="f"] Pass "f" for forward,
         * "b" for backwards.
         * @return {Ext.dom.Element} this
         * @chainable
         * @since 6.5.0
         */
        selectText: function(start, end, direction) {
            var me = this,
                //<feature legacyBrowser>
                range,
                //</feature>
                dom = me.dom,
                len;

            if (dom && inputTypeSelectionSupported.test(dom.type)) {
                start = start || 0;
                len = dom.value.length;

                if (end === undefined) {
                    end = len;
                }

                direction = selectDir[direction] || direction || 'forward';

                if (dom.setSelectionRange) {
                    dom.setSelectionRange(start, end, direction);
                }
                //<feature legacyBrowser>
                else if (dom.createTextRange) {
                    if (start > end) {
                        start = end;
                    }

                    range = dom.createTextRange();
                    range.moveStart('character', start);
                    range.moveEnd('character', -(len - end));
                    range.select();
                }
                //</feature>
            }
            //<debug>
            else if (!inputTypeSelectionSupported.test(dom.type)) {
                Ext.raise('Input type of "' + dom.type + '" does not support setSelectionRange');
            }
            //</debug>

            return me;
        },

        /**
         * Gets the top Y coordinate
         * @param {Boolean} local True to get the local css position instead of page
         * coordinates
         * @return {Number}
         */
        getTop: function(local) {
            return local ? this.getLocalY() : this.getY();
        },

        /**
         * Returns this element's touch action.  (see {@link #setTouchAction})
         *
         * The returned object is shared and should not be mutated.
         *
         * @returns {Object}
         */
        getTouchAction: function() {
            return Ext.dom.TouchAction.get(this.dom);
        },

        /**
         * Returns the value of the `value` attribute.
         * @param {Boolean} asNumber `true` to parse the value as a number.
         * @return {String/Number}
         */
        getValue: function(asNumber) {
            var value = this.dom.value;

            return asNumber ? parseInt(value, 10) : value;
        },

        /**
         * Returns the dimensions of the element available to lay content out in.  For
         * most elements this is the clientHeight/clientWidth.  If the element is
         * the document/document.body the window's innerHeight/innerWidth is returned
         *
         * If the element (or any ancestor element) has CSS style `display: none`, the
         * dimensions will be zero.
         *
         * @return {Object} Object describing width and height.
         * @return {Number} return.width
         * @return {Number} return.height
         */
        getViewSize: function() {
            var dom = this.dom;

            if (dom === DOC || dom === DOC.body) {
                return {
                    width: Element.getViewportWidth(),
                    height: Element.getViewportHeight()
                };
            }
            else {
                return {
                    width: dom.clientWidth,
                    height: dom.clientHeight
                };
            }
        },

        getVisibilityMode: function() {
            var me = this,
                data = me.getData(),
                mode = data.visibilityMode;

            if (mode === undefined) {
                data.visibilityMode = mode = Element.DISPLAY;
            }

            return mode;
        },

        /**
         * Returns the offset width of the element.
         * @param {Boolean} [contentWidth] `true` to get the width minus borders and padding.
         * @param {Boolean} [preciseWidth] `true` to get the precise width
         * @return {Number} The element's width.
         */
        getWidth: function(contentWidth, preciseWidth) {
            var me = this,
                dom = me.dom,
                hidden = me.isStyle('display', 'none'),
                rect, width, floating;

            if (hidden) {
                return 0;
            }

            // Gecko will in some cases report an offsetWidth that is actually less than the width
            // of the text contents, because it measures fonts with sub-pixel precision but rounds
            // the calculated value down. Using getBoundingClientRect instead of offsetWidth allows
            // us to get the precise subpixel measurements so we can force them to always be
            // rounded up. See
            // https://bugzilla.mozilla.org/show_bug.cgi?id=458617
            // Rounding up ensures that the width includes the full width of the text contents.
            if (Ext.supports.BoundingClientRect) {
                rect = dom.getBoundingClientRect();
                width = (me.vertical && !Ext.supports.RotatedBoundingClientRect)
                    ? (rect.bottom - rect.top)
                    : (rect.right - rect.left);

                width = preciseWidth ? width : Math.ceil(width);
            }
            else {
                width = dom.offsetWidth;
            }

            // IE9/10 Direct2D dimension rounding bug: https://sencha.jira.com/browse/EXTJSIV-603
            // there is no need make adjustments for this bug when the element is vertically
            // rotated because the width of a vertical element is its rotated height
            if (Ext.supports.Direct2DBug && !me.vertical) {
                // get the fractional portion of the sub-pixel precision width of the element's text
                // contents
                floating = me.adjustDirect2DDimension(WIDTH);

                if (preciseWidth) {
                    width += floating;
                }
                // IE9 also measures fonts with sub-pixel precision, but unlike Gecko, instead of
                // rounding the offsetWidth down, it rounds to the nearest integer. This means that
                // in order to ensure that the width includes the full width of the text contents
                // we need to increment the width by 1 only if the fractional portion is less
                // than 0.5
                else if (floating > 0 && floating < 0.5) {
                    width++;
                }
            }

            if (contentWidth) {
                width -= me.getBorderWidth("lr") + me.getPadding("lr");
            }

            return (width < 0) ? 0 : width;
        },

        /**
         * Gets element X position in page coordinates
         *
         * @return {Number}
         */
        getX: function() {
            return this.getXY()[0];
        },

        /**
         * Gets element X and Y positions in page coordinates
         *
         * @return {Array} [x, y]
         */
        getXY: function() {
            var round = Math.round,
                dom = this.dom,
                body = DOC.body,
                x = 0,
                y = 0,
                bodyRect, rect;

            if (dom !== DOC && dom !== body) {
                // IE (including IE10) throws an error when getBoundingClientRect
                // is called on an element not attached to dom
                try {
                    bodyRect = body.getBoundingClientRect();
                    rect = dom.getBoundingClientRect();

                    x = rect.left - bodyRect.left;
                    y = rect.top - bodyRect.top;
                }
                catch (ex) {
                    // This block is intentionally left blank
                }
            }

            return [round(x), round(y)];
        },

        /**
         * Gets element Y position in page coordinates
         *
         * @return {Number}
         */
        getY: function() {
            return this.getXY()[1];
        },

        /**
         * Returns this element's z-index
         * @return {Number}
         */
        getZIndex: function() {
            return parseInt(this.getStyle('z-index'), 10);
        },

        /**
         * Checks if the specified CSS class exists on this element's DOM node.
         * @param {String} name The CSS class to check for.
         * @return {Boolean} `true` if the class exists, else `false`.
         */
        hasCls: function(name) {
            var classMap = this.getClassMap();

            return classMap.hasOwnProperty(name);
        },

        /**
         * Hide this element - Uses display mode to determine whether to use "display",
         * "visibility", or "offsets". See {@link #setVisible}.
         * @return {Ext.dom.Element} this
         */
        hide: function() {
            return this.setVisible(false);
        },

        /**
         * Sets up event handlers to call the passed functions when the mouse is moved into and
         * out of the Element.
         * @param {Function} overFn The function to call when the mouse enters the Element.
         * @param {Function} outFn The function to call when the mouse leaves the Element.
         * @param {Object} [scope] The scope (`this` reference) in which the functions are executed.
         * Defaults to the Element's DOM element.
         * @param {Object} [options] Options for the listener. See
         * {@link Ext.util.Observable#addListener the options parameter}.
         * @return {Ext.dom.Element} this
         */
        hover: function(overFn, outFn, scope, options) {
            var me = this;

            me.on('mouseenter', overFn, scope || me.dom, options);
            me.on('mouseleave', outFn, scope || me.dom, options);

            return me;
        },

        /**
         * Returns the index of the given element in the `childNodes` of this element. If
         * not present, `-1` is returned.
         *
         * @param {String/HTMLElement/Ext.dom.Element} childEl The potential child element.
         * @return {number} The index of `childEl` in `childNodes` or `-1` if not found.
         * @since 6.5.0
         */
        indexOf: function(childEl) {
            var children = this.dom,
                c = childEl && Ext.getDom(childEl);

            children = children && children.childNodes;

            return (c && children) ? Array.prototype.indexOf.call(children, c) : -1;
        },

        /**
         * Inserts this element after the passed element in the DOM.
         * @param {String/HTMLElement/Ext.dom.Element} el The element to insert after.
         * The `id` of the node, a DOM Node or an existing Element.
         * @return {Ext.dom.Element} This element.
         */
        insertAfter: function(el) {
            el = Ext.getDom(el);
            el.parentNode.insertBefore(this.dom, el.nextSibling);

            return this;
        },

        /**
         * Inserts this element before the passed element in the DOM.
         * @param {String/HTMLElement/Ext.dom.Element} el The element before which this element
         * will be inserted. The id of the node, a DOM Node or an existing Element.
         * @return {Ext.dom.Element} This element.
         */
        insertBefore: function(el) {
            el = Ext.getDom(el);
            el.parentNode.insertBefore(this.dom, el);

            return this;
        },

        /**
         * Inserts (or creates) an element as the first child of this element
         * @param {String/HTMLElement/Ext.dom.Element/Object} el The id or element to insert
         * or a DomHelper config to create and insert
         * @param {Boolean} [returnDom=false] True to return the raw DOM element instead
         * of Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The new child element (or HTMLElement if
         * _returnDom_ is _true_).
         */
        insertFirst: function(el, returnDom) {
            el = el || {};

            if (el.nodeType || el.dom || typeof el === 'string') { // element
                el = Ext.getDom(el);
                this.dom.insertBefore(el, this.dom.firstChild);

                return !returnDom ? Ext.get(el) : el;
            }
            else { // dh config
                return this.createChild(el, this.dom.firstChild, returnDom);
            }
        },

        /**
         * Inserts an html fragment into this element
         * @param {String} where Where to insert the html in relation to this element - beforeBegin,
         * afterBegin, beforeEnd, afterEnd. See {@link Ext.dom.Helper#insertHtml} for details.
         * @param {String} html The HTML fragment
         * @param {Boolean} [returnEl=false] True to return an Ext.dom.Element
         * @return {HTMLElement/Ext.dom.Element} The inserted node (or nearest related if more than
         * 1 inserted)
         */
        insertHtml: function(where, html, returnEl) {
            var el = Ext.DomHelper.insertHtml(where, this.dom, html);

            return returnEl ? Ext.get(el) : el;
        },

        /**
         * Inserts (or creates) the passed element (or DomHelper config) as a sibling of this
         * element
         * @param {String/HTMLElement/Ext.dom.Element/Object/Array} el The id, element to insert
         * or a DomHelper config to create and insert *or* an array of any of those.
         * @param {String} [where='before'] 'before' or 'after'
         * @param {Boolean} [returnDom=false] True to return the raw DOM element instead of
         * Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The inserted Ext.dom.Element (or
         * HTMLElement if _returnDom_ is _true_). If an array is passed, the last
         * inserted element is returned.
         */
        insertSibling: function(el, where, returnDom) {
            var me = this,
                DomHelper = Ext.DomHelper,
                isAfter = (where || 'before').toLowerCase() === 'after',
                rt, insertEl, eLen, e;

            if (Ext.isIterable(el)) {
                eLen = el.length;
                insertEl = Ext.fly(DOC.createDocumentFragment());

                // append all elements to a documentFragment               
                if (Ext.isArray(el)) {

                    for (e = 0; e < eLen; e++) {
                        rt = insertEl.appendChild(el[e], returnDom);
                    }
                }
                // Iterable, but not an Array, must be an HtmlCollection
                else {
                    for (e = 0; e < eLen; e++) {
                        insertEl.dom.appendChild(rt = el[0]);
                    }

                    if (returnDom === false) {
                        rt = Ext.get(rt);
                    }
                }

                // Insert fragment into document
                me.dom.parentNode.insertBefore(insertEl.dom, isAfter ? me.dom.nextSibling : me.dom);

                return rt;
            }

            el = el || {};

            if (el.nodeType || el.dom) {
                rt = me.dom.parentNode.insertBefore(Ext.getDom(el), isAfter ? me.dom.nextSibling : me.dom); // eslint-disable-line max-len

                if (!returnDom) {
                    rt = Ext.get(rt);
                }
            }
            else {
                if (isAfter && !me.dom.nextSibling) {
                    rt = DomHelper.append(me.dom.parentNode, el, !returnDom);
                }
                else {
                    rt = DomHelper[isAfter ? 'insertAfter' : 'insertBefore'](me.dom, el,
                                                                             !returnDom);
                }
            }

            return rt;
        },

        /**
         * Returns `true` if this element matches the passed simple selector
         * (e.g. 'div.some-class' or 'span:first-child').
         * @param {String/Function} selector The simple selector to test or a function
         * which is passed candidate nodes, and should return `true` for nodes which match.
         * @return {Boolean} `true` if this element matches the selector, else `false`.
         */
        is: function(selector) {
            var dom = this.dom,
                is;

            if (!selector) {
                // In Ext 4 is() called through to DomQuery methods, and would always
                // return true if the selector was ''.  The new query() method in v5 uses
                // querySelector/querySelectorAll() which consider '' to be an invalid
                // selector and throw an error as a result.  To maintain compatibility
                // with the various users of is() we have to return true if the selector
                // is an empty string.  For example: el.up('') should return the element's
                // direct parent.
                is = true;
            }
            else if (!dom.tagName) {
                // document and window objects can never match a selector
                is = false;
            }
            else if (Ext.isFunction(selector)) {
                is = selector(dom);
            }
            else {
                is = dom[Ext.supports.matchesSelector](selector);
            }

            return is;
        },

        /**
         * Returns `true` if this element is an ancestor of the passed element
         * @param {String/HTMLElement/Ext.dom.Element} el The element or id of the element
         * to search for in this elements descendants.
         * @return {Boolean}
         */
        isAncestor: function(el) {
            var ret = false,
                dom = this.dom,
                child = Ext.getDom(el);

            if (dom && child) {
                // This handles the window object, which is not a Node and throws an error
                if (!child.nodeType) {
                    return false;
                }

                if (dom.contains) {
                    return dom.contains(child);
                }
                else if (dom.compareDocumentPosition) {
                    return !!(dom.compareDocumentPosition(child) & 16);
                }
                else {
                    while ((child = child.parentNode)) {
                        ret = child === dom || ret;
                    }
                }
            }

            return ret;
        },

        isPainted: (function() {
            return !Ext.browser.is.IE
                ? function() {
                    var dom = this.dom;

                    return Boolean(dom && dom.offsetParent);
                }
                : function() {
                    var dom = this.dom;

                    return Boolean(dom && (dom.offsetHeight !== 0 || dom.offsetWidth !== 0));
                };
        })(),

        /**
         * Returns true if this element is scrollable.
         * @return {Boolean}
         */
        isScrollable: function() {
            var dom = this.dom;

            return dom.scrollHeight > dom.clientHeight || dom.scrollWidth > dom.clientWidth;
        },

        /**
         * Checks if the current value of a style is equal to a given value.
         * @param {String} style property whose value is returned.
         * @param {String} val to check against.
         * @return {Boolean} `true` for when the current value equals the given value.
         */
        isStyle: function(style, val) {
            return this.getStyle(style) === val;
        },

        /**
         * Checks whether the element is currently visible using both visibility and display
         * properties.
         * @param {Boolean} [deep=false] True to walk the dom and see if parent elements are hidden.
         * If false, the function only checks the visibility of the element itself and it may return
         * `true` even though a parent is not visible.
         * @param {Number} [mode=3] Bit flag indicating which CSS properties to test:
         *
         * - `1` - check display only
         * - `2` - check visibility only
         * - `3` - check both visibility and display
         *
         * @return {Boolean} `true` if the element is currently visible, else `false`
         */
        isVisible: function(deep, mode) {
            var dom = this.dom,
                visible = true,
                end;

            if (!dom) {
                return false;
            }

            mode = mode || 3;

            if (!visFly) {
                visFly = new Ext.dom.Fly();
            }

            for (end = dom.ownerDocument.documentElement; dom !== end; dom = dom.parentNode) {
                if (!dom || dom.nodeType === 11) {
                    // parent node does not exist or is a document fragment
                    visible = false;
                }

                if (visible) {
                    visFly.attach(dom);

                    if (mode & 1) {
                        visible = !visFly.isStyle(DISPLAY, NONE);
                    }

                    if (visible && (mode & 2)) {
                        visible = !visFly.isStyle(VISIBILITY, HIDDEN);
                    }
                }

                if (!visible || !deep) {
                    break;
                }
            }

            return visible;
        },

        /**
         * Gets the last child, skipping text nodes
         * @param {String} [selector] Find the previous sibling that matches the passed simple
         * selector. See {@link Ext.dom.Query} for information about simple selectors.
         * @param {Boolean} [returnDom=false] `true` to return a raw DOM node instead of an
         * Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The last child Ext.dom.Element (or
         * HTMLElement if _returnDom_ is _true_).  Or null if no match is found.
         */
        last: function(selector, returnDom) {
            return this.matchNode('previousSibling', 'lastChild', selector, returnDom);
        },

        /**
         * @cfg listeners
         * @hide
         */
        matchNode: function(dir, start, selector, returnDom) {
            var dom = this.dom,
                n;

            if (!dom) {
                return null;
            }

            n = dom[start];

            while (n) {
                if (n.nodeType === 1 && (!selector || Ext.fly(n, '_matchNode').is(selector))) {
                    return !returnDom ? Ext.get(n) : n;
                }

                n = n[dir];
            }

            return null;
        },

        /**
         * Measures and returns the size of this element. When `dimension` is `null` (or
         * not specified), this will be an object with `width` and `height` properties.
         *
         * If `dimension` is `'w'` the value returned will be this element's width. If
         * `dimension` is `'h'` the returned value will be this element's height.
         *
         * Unlike `getWidth` and `getHeight` this method only returns "precise" (sub-pixel)
         * sizes based on the `getBoundingClientRect` API.
         *
         * @param {'w'/'h'} [dimension] Specifies which dimension is desired. If omitted
         * then an object with `width` and `height` properties is returned.
         * @return {Number/Object} This element's width, height or both as a readonly
         * object. This object may be the direct result of `getBoundingClientRect` and
         * hence immutable on some browsers.
         * @private
         * @since 6.5.0
         */
        measure: function(dimension) {
            // This method doesn't use getBoundingClientRect because
            // the values it returns are affected by transforms (scale etc).
            // For this method we want the real size that's not affected by
            // transforms.
            var me = this,
                dom = me.dom,
                includeWidth = dimension !== 'h',
                includeHeight = dimension !== 'w',
                width = 0,
                height = 0,
                addPadding = !Ext.supports.ComputedSizeIncludesPadding,
                style, rect, offsetParent;

            // Use the viewport height if they are asking for body height
            if (dom.nodeName === 'BODY') {
                height = includeHeight && Element.getViewportHeight();
                width = includeWidth && Element.getViewportWidth();
            }
            else {
                //<if legacyBrowser>
                if (Ext.supports.ComputedStyle) {
                //</if legacyBrowser>
                    offsetParent = dom.offsetParent;
                    style = dom.ownerDocument.defaultView.getComputedStyle(dom, null);

                    // We also have to add the padding if the element uses content-box sizing
                    addPadding |= style.boxSizing === 'content-box';

                    // offsetParent will be null with position fixed
                    if (offsetParent !== null || style.position === 'fixed') {
                        if (includeHeight) {
                            height = toFloat(style.height);

                            if (addPadding) {
                                height += toFloat(style.paddingTop) +
                                          toFloat(style.paddingBottom) +
                                          toFloat(style.borderTopWidth) +
                                          toFloat(style.borderBottomWidth);
                            }
                        }

                        if (includeWidth) {
                            width = toFloat(style.width);

                            if (addPadding) {
                                width += toFloat(style.paddingLeft) +
                                         toFloat(style.paddingRight) +
                                         toFloat(style.borderLeftWidth) +
                                         toFloat(style.borderRightWidth);
                            }
                        }
                    }
                //<if legacyBrowser>
                }
                else {
                    // Browsers that don't support computed style don't
                    // support transforms (IE8), so gbcr is good enough.
                    rect = dom.getBoundingClientRect();
                    width = rect.width || rect.right - rect.left;
                    height = rect.height || rect.bottom - rect.top;
                }

                // IE9/10 Direct2D dimension rounding bug
                if (Ext.supports.Direct2DBug) {
                    if (includeHeight) {
                        height += me.adjustDirect2DDimension(HEIGHT);
                    }

                    if (includeWidth) {
                        width += me.adjustDirect2DDimension(WIDTH);
                    }
                }
                //</if legacyBrowser>
            }

            // Don't create a temporary object unless we need to return it...
            rect = dimension ? null : { width: width, height: height };

            // NOTE: The modern override ignores all these IE8/9/10 issues
            return dimension ? (includeWidth ? width : height) : rect;
        },

        /**
         * Measures and returns this element's content. When `dimension` is `null` (or
         * not specified), this will be an object with `width` and `height` properties.
         *
         * If `dimension` is `'w'` the value returned will be this element's width. If
         * `dimension` is `'h'` the returned value will be this element's height.
         *
         * Unlike `getWidth` and `getHeight` this method only returns "precise" (sub-pixel)
         * sizes based on the `getBoundingClientRect` API.
         *
         * @param {'w'/'h'} [dimension] Specifies which dimension is desired. If omitted
         * then an object with `width` and `height` properties is returned.
         * @return {Number/Object} This element's width, height or both as a readonly
         * object. This object may be the direct result of `getBoundingClientRect` and
         * hence immutable on some browsers.
         * @private
         * @since 6.5.0
         */
        measureContent: function(dimension) {
            var me = this,
                includeWidth = dimension !== 'h',
                size = me.measure(dimension),  // see modern/classic overrides
                h = dimension ? size : size.height,
                w = dimension ? size : size.width;

            if (dimension !== 'w') {
                h -= me.getBorderWidth('tb') + me.getPadding('tb');
            }

            if (includeWidth) {
                w -= me.getBorderWidth('lr') + me.getPadding('lr');
            }

            return dimension ? (includeWidth ? w : h) : { width: w, height: h };
        },

        /**
         * Monitors this Element for the mouse leaving. Calls the function after the specified delay
         * only if the mouse was not moved back into the Element within the delay. If the mouse
         * *was* moved back in, the function is not called.
         * @param {Number} delay The delay **in milliseconds** to wait for possible mouse re-entry
         * before calling the handler function.
         * @param {Function} handler The function to call if the mouse remains outside of this
         * Element for the specified time.
         * @param {Object} [scope] The scope (`this` reference) in which the handler function
         * executes. Defaults to this Element.
         * @return {Object} The listeners object which was added to this element so that monitoring
         * can be stopped. Example usage:
         *
         *     // Hide the menu if the mouse moves out for 250ms or more
         *     this.mouseLeaveMonitor = this.menuEl.monitorMouseLeave(250, this.hideMenu, this);
         *
         *     ...
         *     // Remove mouseleave monitor on menu destroy
         *     this.mouseLeaveMonitor.destroy();
         *
         */
        monitorMouseLeave: function(delay, handler, scope) {
            var me = this,
                timer,
                listeners = {
                    mouseleave: function(e) {
                        if (Ext.isIE9m) {
                            e.enableIEAsync();
                        }

                        timer = Ext.defer(handler, delay, scope || me, [ e ]);
                    },
                    mouseenter: function() {
                        Ext.undefer(timer);
                    },
                    destroy: function() {
                        Ext.undefer(timer);

                        if (!me.destroyed) {
                            me.un(listeners);
                        }
                    }
                };

            me.on(listeners);

            return listeners;
        },

        /**
         * Gets the next sibling, skipping text nodes
         * @param {String} [selector] Find the next sibling that matches the passed simple selector.
         * See {@link Ext.dom.Query} for information about simple selectors.
         * @param {Boolean} [returnDom=false] `true` to return a raw dom node instead of an
         * Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The next sibling Ext.dom.Element (or
         * HTMLElement if _asDom_ is _true_).  Or null if no match is found.
         */
        next: function(selector, returnDom) {
            return this.matchNode('nextSibling', 'nextSibling', selector, returnDom);
        },

        /**
         * Gets the parent node for this element, optionally chaining up trying to match a selector
         * @param {String} [selector] Find a parent node that matches the passed simple selector.
         * See {@link Ext.dom.Query} for information about simple selectors.
         * @param {Boolean} [returnDom=false] True to return a raw dom node instead of an
         * Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The parent node (Ext.dom.Element or
         * HTMLElement if _returnDom_ is _true_).  Or null if no match is found.
         */
        parent: function(selector, returnDom) {
            return this.matchNode('parentNode', 'parentNode', selector, returnDom);
        },

        peekData: function() {
            var dom = this.dom;

            return dom && dom._extData || null;
        },

        /**
         * Initializes positioning on this element. If a desired position is not passed,
         * it will make the the element positioned relative IF it is not already positioned.
         * @param {String} [pos] Positioning to use "relative", "absolute" or "fixed"
         * @param {Number} [zIndex] The zIndex to apply
         * @param {Number} [x] Set the page X position
         * @param {Number} [y] Set the page Y position
         */
        position: function(pos, zIndex, x, y) {
            var me = this;

            if (me.dom.tagName !== 'BODY') {
                if (!pos && me.isStyle(POSITION, STATIC)) {
                    me.setStyle(POSITION, RELATIVE);
                }
                else if (pos) {
                    me.setStyle(POSITION, pos);
                }

                if (zIndex) {
                    me.setStyle(ZINDEX, zIndex);
                }

                if (x || y) {
                    me.setXY([x || false, y || false]);
                }
            }
        },

        /**
         * Gets the previous sibling, skipping text nodes
         * @param {String} [selector] Find the previous sibling that matches the passed simple
         * selector. See {@link Ext.dom.Query} for information about simple selectors.
         * @param {Boolean} [returnDom=false] `true` to return a raw DOM node instead of an
         * Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The previous sibling (Ext.dom.Element or
         * HTMLElement if _returnDom_ is _true_).  Or null if no match is found.
         */
        prev: function(selector, returnDom) {
            return this.matchNode('previousSibling', 'previousSibling', selector, returnDom);
        },

        /**
         * Selects child nodes based on the passed CSS selector.
         * Delegates to document.querySelectorAll. More information can be found at
         * [http://www.w3.org/TR/css3-selectors/](http://www.w3.org/TR/css3-selectors/)
         *
         * All selectors, attribute filters and pseudos below can be combined infinitely
         * in any order. For example `div.foo:nth-child(odd)[@foo=bar].bar:first` would be
         * a perfectly valid selector.
         *
         * ## Element Selectors:
         *
         * * \* any element
         * * E an element with the tag E
         * * E F All descendant elements of E that have the tag F
         * * E > F or E/F all direct children elements of E that have the tag F
         * * E + F all elements with the tag F that are immediately preceded by an element
         * with the tag E
         * * E ~ F all elements with the tag F that are preceded by a sibling element with the tag E
         *
         * ## Attribute Selectors:
         *
         * The use of @ and quotes are optional. For example, div[@foo='bar'] is also a valid
         * attribute selector.
         *
         * * E[foo] has an attribute "foo"
         * * E[foo=bar] has an attribute "foo" that equals "bar"
         * * E[foo^=bar] has an attribute "foo" that starts with "bar"
         * * E[foo$=bar] has an attribute "foo" that ends with "bar"
         * * E[foo*=bar] has an attribute "foo" that contains the substring "bar"
         * * E[foo%=2] has an attribute "foo" that is evenly divisible by 2
         * * E[foo!=bar] has an attribute "foo" that does not equal "bar"
         *
         * ## Pseudo Classes:
         *
         * * E:first-child E is the first child of its parent
         * * E:last-child E is the last child of its parent
         * * E:nth-child(n) E is the nth child of its parent (1 based as per the spec)
         * * E:nth-child(odd) E is an odd child of its parent
         * * E:nth-child(even) E is an even child of its parent
         * * E:only-child E is the only child of its parent
         * * E:checked E is an element that is has a checked attribute that is true (e.g. a radio
         * or checkbox)
         * * E:first the first E in the resultset
         * * E:last the last E in the resultset
         * * E:nth(n) the nth E in the resultset (1 based)
         * * E:odd shortcut for :nth-child(odd)
         * * E:even shortcut for :nth-child(even)
         * * E:not(S) an E element that does not match simple selector S
         * * E:has(S) an E element that has a descendant that matches simple selector S
         * * E:next(S) an E element whose next sibling matches simple selector S
         * * E:prev(S) an E element whose previous sibling matches simple selector S
         * * E:any(S1|S2|S2) an E element which matches any of the simple selectors S1, S2 or S3
         *
         * ## CSS Value Selectors:
         *
         * * E{display=none} CSS value "display" that equals "none"
         * * E{display^=none} CSS value "display" that starts with "none"
         * * E{display$=none} CSS value "display" that ends with "none"
         * * E{display*=none} CSS value "display" that contains the substring "none"
         * * E{display%=2} CSS value "display" that is evenly divisible by 2
         * * E{display!=none} CSS value "display" that does not equal "none"
         *
         * @param {String} selector The CSS selector.
         * @param {Boolean} [asDom=true] `false` to return an array of Ext.dom.Element
         * @param {Boolean} single (private)
         * @return {HTMLElement[]/Ext.dom.Element[]} An Array of elements (
         * HTMLElement or Ext.dom.Element if _asDom_ is _false_) that match the selector.
         * If there are no matches, an empty Array is returned.
         */
        query: function(selector, asDom, single) {
            var dom = this.dom,
                results, len, nlen, node, nodes, i, j;

            if (!dom) {
                return null;
            }

            asDom = (asDom !== false);

            selector = selector.split(",");

            if (!single) {
                // only allocate the results array if the full result set is being
                // requested.  selectNode() uses the 'single' param.
                results = [];
            }

            for (i = 0, len = selector.length; i < len; i++) {
                if (typeof selector[i] === 'string') {
                    if (single) {
                        // take the "fast path" if single was requested (selectNode)
                        node = dom.querySelector(selector[i]);

                        return asDom ? node : Ext.get(node);
                    }

                    nodes = dom.querySelectorAll(selector[i]);

                    for (j = 0, nlen = nodes.length; j < nlen; j++) {
                        results.push(asDom ? nodes[j] : Ext.get(nodes[j]));
                    }
                }
            }

            return results;
        },

        /**
         * Adds one or more CSS classes to this element and removes the same class(es) from
         * all siblings.
         * @param {String/String[]} className The CSS class to add, or an array of classes.
         * @return {Ext.dom.Element} this
         */
        radioCls: function(className) {
            var cn = this.dom.parentNode.childNodes,
                v, i, len;

            className = Ext.isArray(className) ? className : [className];

            for (i = 0, len = cn.length; i < len; i++) {
                v = cn[i];

                if (v && v.nodeType === 1) {
                    Ext.fly(v).removeCls(className);
                }
            }

            return this.addCls(className);
        },

        redraw: function() {
            var dom = this.dom,
                domStyle = dom.style;

            domStyle.display = 'none';
            // eslint-disable-next-line no-unused-expressions
            dom.offsetHeight;
            domStyle.display = '';
        },

        /**
         * @method remove
         * @inheritdoc Ext.dom.Element#method-destroy
         * @deprecated 5.0.0 Please use {@link #destroy} instead.
         */
        remove: function() {
            this.destroy();
        },

        removeChild: function(element) {
            this.dom.removeChild(Ext.getDom(element));

            return this;
        },

        /**
         * Removes the given CSS class(es) from this Element.
         * @param {String/String[]} names The CSS classes to remove separated by space,
         * or an array of classes
         * @param {String} [prefix] Prefix to prepend to each class. The separator `-` will be
         * appended to the prefix.
         * @param {String} [suffix] Suffix to append to each class. The separator `-` will be
         * prepended to the suffix.
         * return {Ext.dom.Element} this
         */
        removeCls: function(names, prefix, suffix) {
            return this.replaceCls(names, null, prefix, suffix);
        },

        /**
         * Forces the browser to repaint this element.
         * @return {Ext.dom.Element} this
         */
        repaint: function(cls, state) {
            var me = this,
                off, on;

            if (!cls) {
                cls = Ext.baseCSSPrefix + 'repaint';
                on = !(off = false);
            }
            else if (state != null) {
                // If state is null or undefined then just toggle
                on = state;
                off = !state;
            }

            me.toggleCls(cls, on);

            if (!me.repaintTimer) {
                me.repaintTimer = Ext.defer(function() {
                    me.repaintTimer = null;

                    if (me.dom) {  // may have been removed already on slower UAs
                        me.toggleCls(cls, off);
                    }
                }, 1);
            }

            return me;
        },

        /**
         * Replaces the passed element with this element
         * @param {String/HTMLElement/Ext.dom.Element} el The element to replace.
         * The id of the node, a DOM Node or an existing Element.
         * @param {Boolean} [destroy=true] `false` to prevent destruction of the replaced
         * element
         * @return {Ext.dom.Element} This element
         */
        replace: function(el, destroy) {
            el = Ext.getDom(el);

            /* eslint-disable-next-line vars-on-top */
            var parentNode = el.parentNode,
                id = el.id,
                dom = this.dom;

            //<debug>
            if (!parentNode) {
                Ext.raise('Cannot replace element "' + id +
                    '". It is not attached to a parent node.');
            }
            //</debug>

            if (destroy !== false && id && Ext.cache[id]) {
                parentNode.insertBefore(dom, el);
                Ext.get(el).destroy();
            }
            else {
                parentNode.replaceChild(dom, el);
            }

            return this;
        },

        /**
         * Replaces one or more CSS classes on this element with other classes. If the old
         * name does not exist, the new name will simply be added.
         *
         * @param {String/String[]} [remove] The CSS class(es) to be removed.
         * @param {String/String[]} [add] The CSS class(es) to be added.
         * @param {String} [prefix] The string to prepend to each class name.
         * @param {String} [suffix] The string to append to each class name.
         * @return {Ext.dom.Element} this
         */
        replaceCls: function(remove, add, prefix, suffix) {
            var me = this,
                dom = me.dom,
                added = 0,
                removed = 0,
                rem = remove,
                data = (add || remove) && me.getData(),
                list, map, i, n, name;

            if (data) {
                list = data.classList;
                map = data.classMap;

                add = add ? ((typeof add === 'string') ? add.split(spacesRe) : add) : EMPTY;
                rem = rem ? ((typeof rem === 'string') ? rem.split(spacesRe) : rem) : EMPTY;

                // Include the '-' but only if the caller hasn't already...
                prefix = prefix || '';

                if (prefix && prefix[prefix.length - 1] !== '-') {
                    prefix += '-';
                }

                suffix = suffix || '';

                if (suffix && suffix[0] !== '-') {
                    suffix = '-' + suffix;
                }

                for (i = 0, n = rem.length; i < n; i++) {
                    if (!(name = rem[i])) {
                        // Sadly ... 'foo '.split(spacesRe) == ['foo', '']
                        continue;
                    }

                    name = prefix + name + suffix;

                    //<debug>
                    if (spacesRe.test(name)) {
                        Ext.raise('Class names in arrays must not contain spaces');
                    }
                    //</debug>

                    if (map[name]) {
                        delete map[name];
                        ++removed;
                    }
                }

                for (i = 0, n = add.length; i < n; i++) {
                    if (!(name = add[i])) {
                        continue;
                    }

                    name = prefix + name + suffix;

                    //<debug>
                    if (spacesRe.test(name)) {
                        Ext.raise('Class names in arrays must not contain spaces');
                    }
                    //</debug>

                    if (!map[name]) {
                        map[name] = true;

                        // If we are only adding, we can be more efficient...
                        if (!removed) {
                            list.push(name);
                            ++added;
                        }
                    }
                }

                if (removed) {
                    me.setClassMap(map, /* keep= */ true);
                }
                else if (added) {
                    list = list.join(' ');

                    if (!Ext.isIE8 && dom instanceof SVGElement) {
                        // dom.className is an instance of SVGAnimatedString
                        // for SVG elements. Setting the `class` attribute
                        // of an SVG element will update its `dom.className.baseVal`.
                        dom.setAttribute('class', list);
                    }
                    else {
                        dom.className = list;
                    }
                }
            }

            return me;
        },

        /**
         * Replaces this element with the passed element
         * @param {String/HTMLElement/Ext.dom.Element/Object} el The new element (id of the
         * node, a DOM Node or an existing Element) or a DomHelper config of an element to create
         * @return {Ext.dom.Element} This element
         */
        replaceWith: function(el) {
            var me = this,
                dom = me.dom,
                parent = dom.parentNode,
                cache = Ext.cache,
                newDom;

            me.clearListeners();

            if (el.nodeType || el.dom || typeof el === 'string') {
                el = Ext.get(el);
                newDom = parent.insertBefore(el.dom, dom);
            }
            else {
                // domhelper config
                newDom = Ext.DomHelper.insertBefore(dom, el);
            }

            parent.removeChild(dom);

            me.dom = newDom;

            if (!me.isFly) {
                delete cache[me.id];
                cache[me.id = Ext.id(newDom)] = me;
            }

            return me;
        },

        resolveListenerScope: function(defaultScope) {
            // Override this to pass along to our owning component (if we have one).
            var component = this.component;

            return component ? component.resolveListenerScope(defaultScope) : this;
        },

        /**
         * Scrolls this element the specified direction. Does bounds checking to make sure
         * the scroll is within this element's scrollable range.
         * @param {String} direction Possible values are:
         *
         * - `"l"` (or `"left"`)
         * - `"r"` (or `"right"`)
         * - `"t"` (or `"top"`, or `"up"`)
         * - `"b"` (or `"bottom"`, or `"down"`)
         *
         * @param {Number} distance How far to scroll the element in pixels
         * @param {Boolean/Object} [animate] true for the default animation or a standard Element
         * animation config object
         * @return {Boolean} Returns true if a scroll was triggered or false if the element
         * was scrolled as far as it could go.
         */
        scroll: function(direction, distance, animate) {
            if (!this.isScrollable()) {
                return false;
            }

            // Allow full word, or initial to be sent.
            // (Ext.dd package uses full word)
            direction = direction.charAt(0);

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                dom = me.dom,
                side = direction === 'r' || direction === 'l' ? 'left' : 'top',
                scrolled = false,
                currentScroll, constrainedScroll;

            if (direction === 'l' || direction === 't' || direction === 'u') {
                distance = -distance;
            }

            if (side === 'left') {
                currentScroll = dom.scrollLeft;
                constrainedScroll = me.constrainScrollLeft(currentScroll + distance);
            }
            else {
                currentScroll = dom.scrollTop;
                constrainedScroll = me.constrainScrollTop(currentScroll + distance);
            }

            if (constrainedScroll !== currentScroll) {
                this.scrollTo(side, constrainedScroll, animate);
                scrolled = true;
            }

            return scrolled;
        },

        /**
         * Scrolls this element by the passed delta values, optionally animating.
         *
         * All of the following are equivalent:
         *
         *      el.scrollBy(10, 10, true);
         *      el.scrollBy([10, 10], true);
         *      el.scrollBy({ x: 10, y: 10 }, true);
         *
         * @param {Number/Number[]/Object} deltaX Either the x delta, an Array specifying x and y
         * deltas or an object with "x" and "y" properties.
         * @param {Number/Boolean/Object} deltaY Either the y delta, or an animate flag or config
         * object.
         * @param {Boolean/Object} animate Animate flag/config object if the delta values were
         * passed separately.
         * @return {Ext.dom.Element} this
         */
        scrollBy: function(deltaX, deltaY, animate) {
            var me = this,
                dom = me.dom;

            // Extract args if deltas were passed as an Array.
            if (deltaX.length) {
                animate = deltaY;
                deltaY = deltaX[1];
                deltaX = deltaX[0];
            }
            else if (typeof deltaX !== 'number') { // or an object
                animate = deltaY;
                deltaY = deltaX.y;
                deltaX = deltaX.x;
            }

            if (deltaX) {
                me.scrollTo('left', me.constrainScrollLeft(dom.scrollLeft + deltaX), animate);
            }

            if (deltaY) {
                me.scrollTo('top', me.constrainScrollTop(dom.scrollTop + deltaY), animate);
            }

            return me;
        },

        /**
         * @private
         */
        scrollChildIntoView: function(child, hscroll) {
            // scrollFly is used inside scrollInfoView, must use a method-unique fly here
            Ext.fly(child).scrollIntoView(this, hscroll);
        },

        /**
         * Scrolls this element into view within the passed container.
         *
         *       Ext.create('Ext.data.Store', {
         *           storeId:'simpsonsStore',
         *           fields:['name', 'email', 'phone'],
         *           data:{'items':[
         *               { 'name': 'Lisa',  "email":"lisa@simpsons.com", "phone":"555-111-1224" },
         *               { 'name': 'Bart',  "email":"bart@simpsons.com",  "phone":"555-222-1234" },
         *               { 'name': 'Homer', "email":"homer@simpsons.com",  "phone":"555-222-1244" },
         *               { 'name': 'Marge', "email":"marge@simpsons.com", "phone":"555-222-1254" },
         *               { 'name': 'Milhouse', "email":"milhouse@simpsons.com",
         *                 "phone":"555-222-1244" },
         *               { 'name': 'Willy', "email":"willy@simpsons.com", "phone":"555-222-1254" },
         *               { 'name': 'Skinner', "email":"skinner@simpsons.com",
         *                 "phone":"555-222-1244" },
         *               { 'name': 'Hank (last row)', "email":"hank@simpsons.com",
         *                 "phone":"555-222-1254" }
         *           ]},
         *           proxy: {
         *               type: 'memory',
         *               reader: {
         *                   type: 'json',
         *                   rootProperty: 'items'
         *               }
         *           }
         *       });
         *
         *       var grid = Ext.create('Ext.grid.Panel', {
         *           title: 'Simpsons',
         *           store: Ext.data.StoreManager.lookup('simpsonsStore'),
         *           columns: [
         *               { text: 'Name',  dataIndex: 'name', width: 125 },
         *               { text: 'Email', dataIndex: 'email', flex: 1 },
         *               { text: 'Phone', dataIndex: 'phone' }
         *           ],
         *           height: 190,
         *           width: 400,
         *           renderTo: Ext.getBody(),
         *           tbar: [{
         *               text: 'Scroll row 7 into view',
         *               handler: function () {
         *                   var view = grid.getView();
         *
         *                   Ext.get(view.getRow(7)).scrollIntoView(view.getEl(), null, true);
         *               }
         *           }]
         *       });
         *
         * @param {String/HTMLElement/Ext.Element} [container=document.body] The container element
         * to scroll.  Should be a string (id), dom node, or Ext.Element.
         * @param {Boolean} [hscroll=true] False to disable horizontal scroll.
         * @param {Boolean/Object} [animate] true for the default animation or a standard Element
         * animation config object
         * @param {Boolean} [highlight=false] true to {@link #highlight} the element when it is
         * in view.
         * @return {Ext.dom.Element} this
         */
        scrollIntoView: function(container, hscroll, animate, highlight) {
            container = Ext.getDom(container) || Ext.getBody().dom;

            return this.doScrollIntoView(container, hscroll, animate, highlight,
                                         'getScrollLeft', 'scrollTo');
        },

        /**
         * Scrolls this element the specified scroll point. It does NOT do bounds checking so
         * if you scroll to a weird value it will try to do it. For auto bounds checking,
         * use #scroll.
         * @param {String} side Either "left" for scrollLeft values or "top" for scrollTop values.
         * @param {Number} value The new scroll value
         * @param {Boolean/Object} [animate] true for the default animation or a standard Element
         * animation config object
         * @return {Ext.dom.Element} this
         */
        scrollTo: function(side, value, animate) {
            // check if we're scrolling top or left
            var top = topRe.test(side),
                me = this,
                prop = top ? 'scrollTop' : 'scrollLeft',
                dom = me.dom,
                animCfg;

            if (!animate || !me.anim) {
                // just setting the value, so grab the direction
                dom[prop] = value;

                // corrects IE, other browsers will ignore
                dom[prop] = value;
            }
            else {
                animCfg = {
                    to: {}
                };

                animCfg.to[prop] = value;

                if (Ext.isObject(animate)) {
                    Ext.applyIf(animCfg, animate);
                }

                me.animate(animCfg);
            }

            return me;
        },

        /**
         * Selects descendant elements of this element based on the passed CSS selector to
         * enable {@link Ext.dom.Element Element} methods to be applied to many related
         * elements in one statement through the returned
         * {@link Ext.dom.CompositeElementLite CompositeElementLite} object.
         *
         * @param {String/HTMLElement[]} selector The CSS selector or an array of elements
         * @param {Boolean} composite Return a CompositeElement as opposed to a
         * CompositeElementLite. Defaults to false.
         * @return {Ext.dom.CompositeElementLite/Ext.dom.CompositeElement}
         */
        select: function(selector, composite) {
            var isElementArray, elements;

            if (typeof selector === "string") {
                elements = this.query(selector, !composite);
            }
            //<debug>
            else if (selector.length === undefined) {
                Ext.raise("Invalid selector specified: " + selector);
            }
            //</debug>
            else {
                // if selector is not a string, assume it is already an array of
                // HTMLElement
                elements = selector;
                isElementArray = true;
            }

            // if the selector parameter was a string we will have called through
            // to query, and it will have constructed either an array of
            // HTMLElement or Ext.Element, depending on the composite param we gave
            // it.  If this is the case we can take the fast path through the 
            // CompositeElementLite constructor to avoid calling getDom() or get()
            // on every element in the array.
            return composite
                ? new Ext.CompositeElement(elements, !isElementArray)
                : new Ext.CompositeElementLite(elements, true);
        },

        /**
         * Selects a single descendant element of this element using a CSS selector
         * (see {@link #method-query}).
         * @param {String} selector The selector query
         * @param {Boolean} [asDom=true] `false` to return an Ext.dom.Element
         * @return {HTMLElement/Ext.dom.Element} The DOM element (or Ext.dom.Element if
         * _asDom_ is _false_) which matched the selector.
         */
        selectNode: function(selector, asDom) {
            return this.query(selector, asDom, true);
        },

        /**
         * Sets the passed attributes as attributes of this element (a `style` attribute
         * can be a string, object or function).
         *
         * Example component (though any Ext.dom.Element would suffice):
         *
         *     var cmp = Ext.create({
         *         xtype: 'component',
         *         html: 'test',
         *         renderTo: Ext.getBody()
         *     });
         *
         * Once the component is rendered, you can fetch a reference to its outer
         * element to use `set`:
         *
         *     cmp.el.set({
         *         foo: 'bar'
         *     });
         *
         * This sets an attribute on the element of **foo="bar"**:
         *
         *     <div class="x-component x-component-default x-border-box"
         *          id="component-1009" foo="bar">test</div>
         *
         * To remove the attribute pass a value of **undefined**:
         *
         *     cmp.el.set({
         *         foo: undefined
         *     });
         *
         * **Note:**
         *
         *  - You cannot remove an attribute by passing `undefined` when the
         * `expandos` param is set to **false**.
         *  - Passing an attribute of `style` results in the request being handed off to
         * {@link #method-applyStyles}.
         *  - Passing an attribute of `cls` results in the element's dom's
         * [className](http://www.w3schools.com/jsref/prop_html_classname.asp) property
         * being set directly.  For additional flexibility when setting / removing
         * classes see:
         *     - {@link #method-addCls}
         *     - {@link #method-removeCls}
         *     - {@link #method-replaceCls}
         *     - {@link #method-setCls}
         *     - {@link #method-toggleCls}
         *
         * @param {Object} attributes The object with the attributes.
         * @param {Boolean} [useSet=true] `false` to override the default `setAttribute`
         * to use [expandos](http://help.dottoro.com/ljvovanq.php).
         * @return {Ext.dom.Element} this
         */
        set: function(attributes, useSet) {
            var me = this,
                dom = me.dom,
                attribute, value;

            for (attribute in attributes) {
                if (attributes.hasOwnProperty(attribute)) {
                    value = attributes[attribute];

                    if (attribute === 'style') {
                        me.applyStyles(value);
                    }
                    else if (attribute === 'cls') {
                        dom.className = value;
                    }
                    else if (useSet !== false) {
                        if (value === undefined) {
                            dom.removeAttribute(attribute);
                        }
                        else {
                            dom.setAttribute(attribute, value);
                        }
                    }
                    else {
                        dom[attribute] = value;
                    }
                }
            }

            return me;
        },

        /**
         * Sets the element's CSS bottom style.
         * @param {Number/String} bottom Number of pixels or CSS string value to set as
         * the bottom CSS property value
         * @return {Ext.dom.Element} this
         */
        setBottom: function(bottom) {
            this.dom.style[BOTTOM] = Element.addUnits(bottom);

            return this;
        },

        /**
         * Sets the CSS classes of this element to the keys of the given object. The
         * `classMap` object is typically returned by {@link #getClassMap}. The values of
         * the properties in the `classMap` should be truthy (such as `1` or `true`).
         *
         * @param {Object} classMap The object whose keys will be the CSS classes.
         * @param {Boolean} [keep=false] Pass `true` to indicate the the `classMap`
         * object can be kept (instead of copied).
         */
        setClassMap: function(classMap, keep) {
            var data = this.getData(/* sync= */ false),
                classList;

            if (data) {
                classMap = (keep && classMap) || Ext.apply({}, classMap);

                data.classMap = classMap;
                data.classList = classList = Ext.Object.getKeys(classMap);
                data.isSynchronized = true;

                // We won't get a data object if !this.dom:
                this.dom.className = classList.join(' ');
            }
        },

        /**
         * Sets the specified CSS class on this element's DOM node.
         * @param {String/String[]} className The CSS class to set on this element.
         */
        setCls: function(className) {
            var me = this,
                elementData = me.getData(/* sync= */ false),
                i, ln, map, classList;

            if (typeof className === 'string') {
                className = className.split(spacesRe);
            }

            elementData.classList = classList = className.slice();
            elementData.classMap = map = {};

            for (i = 0, ln = classList.length; i < ln; i++) {
                map[classList[i]] = true;
            }

            me.dom.className = classList.join(' ');
        },

        /**
         * Sets the CSS display property. Uses originalDisplay if the specified value is a
         * boolean true.
         * @param {Boolean/String} value Boolean value to display the element using its
         * default display, or a string to set the display directly.
         * @return {Ext.dom.Element} this
         */
        setDisplayed: function(value) {
            var me = this;

            if (typeof value === "boolean") {
                value = value ? me._getDisplay() : NONE;
            }

            me.setStyle(DISPLAY, value);

            if (me.shadow || me.shim) {
                me.setUnderlaysVisible(value !== NONE);
            }

            return me;
        },

        /**
         * Set the height of this Element.
         * @param {Number/String} height The new height.
         * @return {Ext.dom.Element} this
         */
        setHeight: function(height) {
            var me = this;

            me.dom.style[HEIGHT] = Element.addUnits(height);

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        /**
         * Sets the `innerHTML` of this element.
         * @param {String} html The new HTML.
         * @return {Ext.dom.Element} this
         */
        setHtml: function(html) {
            if (this.dom) {
                this.dom.innerHTML = html;
            }

            return this;
        },

        setId: function(id) {
            var me = this,
                currentId = me.id,
                cache = Ext.cache;

            if (currentId) {
                delete cache[currentId];
            }

            me.dom.id = id;

            /**
             * The DOM element ID
             * @property id
             * @type String
             */
            me.id = id;

            cache[id] = me;

            return me;
        },

        /**
         * Sets the element's left position directly using CSS style
         * (instead of {@link #setX}).
         * @param {Number/String} left Number of pixels or CSS string value to
         * set as the left CSS property value
         * @return {Ext.dom.Element} this
         */
        setLeft: function(left) {
            var me = this;

            me.dom.style[LEFT] = Element.addUnits(left);

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        setLocalX: function(x) {
            var me = this,
                style = me.dom.style;

            // clear right style just in case it was previously set by rtlSetLocalXY
            style.right = '';
            style.left = (x === null) ? 'auto' : x + 'px';

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        setLocalXY: function(x, y) {
            var me = this,
                style = me.dom.style;

            // clear right style just in case it was previously set by rtlSetLocalXY
            style.right = '';

            if (x && x.length) {
                y = x[1];
                x = x[0];
            }

            if (x === null) {
                style.left = 'auto';
            }
            else if (x !== undefined) {
                style.left = x + 'px';
            }

            if (y === null) {
                style.top = 'auto';
            }
            else if (y !== undefined) {
                style.top = y + 'px';
            }

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        setLocalY: function(y) {
            var me = this;

            me.dom.style.top = (y === null) ? 'auto' : y + 'px';

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        setMargin: function(margin) {
            var me = this,
                domStyle = me.dom.style;

            if (margin || margin === 0) {
                margin = me.self.unitizeBox((margin === true) ? 5 : margin);
                domStyle.setProperty('margin', margin, 'important');
            }
            else {
                domStyle.removeProperty('margin-top');
                domStyle.removeProperty('margin-right');
                domStyle.removeProperty('margin-bottom');
                domStyle.removeProperty('margin-left');
            }
        },

        /**
         * Set the maximum height of this Element.
         * @param {Number/String} height The new maximum height.
         * @return {Ext.dom.Element} this
         */
        setMaxHeight: function(height) {
            this.dom.style[MAX_HEIGHT] = Element.addUnits(height);

            return this;
        },

        /**
         * Set the maximum width of this Element.
         * @param {Number/String} width The new maximum width.
         * @return {Ext.dom.Element} this
         */
        setMaxWidth: function(width) {
            this.dom.style[MAX_WIDTH] = Element.addUnits(width);

            return this;
        },

        /**
         * Set the minimum height of this Element.
         * @param {Number/String} height The new minimum height.
         * @return {Ext.dom.Element} this
         */
        setMinHeight: function(height) {
            this.dom.style[MIN_HEIGHT] = Element.addUnits(height);

            return this;
        },

        /**
         * Set the minimum width of this Element.
         * @param {Number/String} width The new minimum width.
         * @return {Ext.dom.Element} this
         */
        setMinWidth: function(width) {
            this.dom.style[MIN_WIDTH] = Element.addUnits(width);

            return this;
        },

        /**
         * Set the opacity of the element
         * @param {Number} opacity The new opacity. 0 = transparent, .5 = 50% visibile,
         * 1 = fully visible, etc
         * @return {Ext.dom.Element} this
         */
        setOpacity: function(opacity) {
            var me = this;

            if (me.dom) {
                me.setStyle('opacity', opacity);
            }

            return me;
        },

        setPadding: function(padding) {
            var me = this,
                domStyle = me.dom.style;

            if (padding || padding === 0) {
                padding = me.self.unitizeBox((padding === true) ? 5 : padding);
                domStyle.setProperty('padding', padding, 'important');
            }
            else {
                domStyle.removeProperty('padding-top');
                domStyle.removeProperty('padding-right');
                domStyle.removeProperty('padding-bottom');
                domStyle.removeProperty('padding-left');
            }
        },

        /**
         * Sets the element's CSS right style.
         * @param {Number/String} right Number of pixels or CSS string value to
         * set as the right CSS property value
         * @return {Ext.dom.Element} this
         */
        setRight: function(right) {
            this.dom.style[RIGHT] = Element.addUnits(right);

            return this;
        },

        /**
         * Sets the left scroll position
         * @param {Number} left The left scroll position
         * @return {Ext.dom.Element} this
         */
        setScrollLeft: function(left) {
            this.dom.scrollLeft = left;

            return this;
        },

        /**
         * Sets the top scroll position
         * @param {Number} top The top scroll position
         * @return {Ext.dom.Element} this
         */
        setScrollTop: function(top) {
            this.dom.scrollTop = top;

            return this;
        },

        /**
         * Set the size of this Element.
         *
         * @param {Number/String} width The new width. This may be one of:
         *
         * - A Number specifying the new width in pixels.
         * - A String used to set the CSS width style. Animation may **not** be used.
         * - A size object in the format `{width: widthValue, height: heightValue}`.
         *
         * @param {Number/String} height The new height. This may be one of:
         *
         * - A Number specifying the new height in pixels.
         * - A String used to set the CSS height style. Animation may **not** be used.
         * @return {Ext.dom.Element} this
         */
        setSize: function(width, height) {
            var me = this,
                style = me.dom.style;

            if (Ext.isObject(width)) {
                // in case of object from getSize()
                height = width.height;
                width = width.width;
            }

            if (width !== undefined) {
                style.width = Element.addUnits(width);
            }

            if (height !== undefined) {
                style.height = Element.addUnits(height);
            }

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        /**
         * Wrapper for setting style properties, also takes single object parameter of
         * multiple styles.
         *
         * Styles should be a valid DOM element style property.
         * [Valid style property names](http://www.w3schools.com/jsref/dom_obj_style.asp)
         * (_along with the supported CSS version for each_)
         *
         *     // <div id="my-el">Phineas Flynn</div>
         *
         *     var el = Ext.get('my-el');
         *
         *     // two-param syntax
         *     el.setStyle('color', 'white');
         *
         *     // single-param syntax
         *     el.setStyle({
         *         fontWeight: 'bold',
         *         backgroundColor: 'gray',
         *         padding: '10px'
         *     });
         *
         * @param {String/Object} prop The style property to be set, or an object of
         * multiple styles.
         * @param {String} [value] The value to apply to the given property, or null if
         * an object was passed.
         * @return {Ext.dom.Element} this
         */
        setStyle: function(prop, value) {
            var me = this,
                dom = me.dom,
                hooks = me.styleHooks,
                style = dom.style,
                name = prop,
                hook;

            // we don't promote the 2-arg form to object-form to avoid the overhead...
            if (typeof name === 'string') {
                hook = hooks[name];

                if (!hook) {
                    hooks[name] = hook = { name: Element.normalize(name) };
                }

                value = (value == null) ? '' : value; // map null && undefined to ''

                if (hook.set) {
                    hook.set(dom, value, me);
                }
                else {
                    style[hook.name] = value;
                }

                if (hook.afterSet) {
                    hook.afterSet(dom, value, me);
                }
            }
            else {
                for (name in prop) {
                    hook = hooks[name];

                    if (!hook) {
                        hooks[name] = hook = { name: Element.normalize(name) };
                    }

                    value = prop[name];
                    value = (value == null) ? '' : value; // map null && undefined to ''

                    if (hook.set) {
                        hook.set(dom, value, me);
                    }
                    else {
                        style[hook.name] = value;
                    }

                    if (hook.afterSet) {
                        hook.afterSet(dom, value, me);
                    }
                }
            }

            return me;
        },

        setText: function(text) {
            this.dom.textContent = text;
        },

        getText: function() {
            return this.dom.textContent;
        },

        /**
         * Sets the element's top position directly using CSS style
         * (instead of {@link #setY}).
         * @param {Number/String} top Number of pixels or CSS string value to
         * set as the top CSS property value
         * @return {Ext.dom.Element} this
         */
        setTop: function(top) {
            var me = this;

            me.dom.style[TOP] = Element.addUnits(top);

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        /**
         * Sets the CSS [touch-action](https://www.w3.org/TR/pointerevents/#the-touch-action-css-property)
         * property on this element and emulates its behavior on browsers where touch-action
         * is not supported.
         *
         * @param {Object} touchAction An object with touch-action names as the keys, and
         * boolean values to enable or disable specific touch actions. Accepted keys are:
         *
         * - `panX`
         * - `panY`
         * - `pinchZoom`
         * - `doubleTapZoom`
         *
         * All touch actions are enabled (`true`) by default, so it is usually only necessary
         * to specify which touch actions to disable.  For example, the following disables
         * only vertical scrolling and double-tap-zoom on an element
         *
         *     element.setTouchAction({
         *         panY: false,
         *         doubleTapZoom: false
         *     });
         *
         * @return {Ext.dom.Element} this
         */
        setTouchAction: function(touchAction) {
            Ext.dom.TouchAction.set(this.dom, touchAction);
        },

        setUnderlaysVisible: function(visible) {
            var shadow = this.shadow,
                shim = this.shim;

            if (shadow && !shadow.disabled) {
                if (visible) {
                    shadow.show();
                }
                else {
                    shadow.hide();
                }
            }

            if (shim && !shim.disabled) {
                if (visible) {
                    shim.show();
                }
                else {
                    shim.hide();
                }
            }
        },

        /**
         * @private
         */
        setVisibility: function(isVisible) {
            var domStyle = this.dom.style;

            if (isVisible) {
                domStyle.removeProperty('visibility');
            }
            else {
                domStyle.setProperty('visibility', 'hidden', 'important');
            }
        },

        /* eslint-disable max-len */
        /**
         * Use this to change the visibility mode between {@link #VISIBILITY},
         * {@link #DISPLAY}, {@link #OFFSETS}, {@link #CLIP}, or {@link #OPACITY}.
         *
         * @param {Ext.dom.Element.VISIBILITY/Ext.dom.Element.DISPLAY/Ext.dom.Element.OFFSETS/Ext.dom.Element.CLIP/Ext.dom.Element.OPACITY} mode
         * The method by which the element will be {@link #hide hidden} (you can
         * also use the {@link #setVisible} or {@link #toggle} method to toggle element
         * visibility).
         *
         * @return {Ext.dom.Element} this
         */
        setVisibilityMode: function(mode) {
        /* eslint-enable max-len */
            //<debug>
            if (mode !== 1 && mode !== 2 && mode !== 3 && mode !== 4 && mode !== 5) {
                Ext.raise("visibilityMode must be one of the following: " +
                    "Ext.Element.DISPLAY, Ext.Element.VISIBILITY, Ext.Element.OFFSETS, " +
                    "Ext.Element.CLIP, or Element.OPACITY");
            }
            //</debug>

            this.getData().visibilityMode = mode;

            return this;
        },

        /**
         * Sets the visibility of the element based on the current visibility mode. Use
         * {@link #setVisibilityMode} to switch between the following visibility modes:
         *
         * - {@link #DISPLAY} (the default)
         * - {@link #VISIBILITY}
         * - {@link #OFFSETS}
         * - {@link #CLIP}
         * - {@link #OPACITY}
         *
         * @param {Boolean} visible Whether the element is visible.
         * @return {Ext.dom.Element} this
         */
        setVisible: function(visible) {
            var me = this,
                mode = me.getVisibilityMode(),
                addOrRemove = visible ? 'removeCls' : 'addCls';

            switch (mode) {
                case Element.DISPLAY:
                    me.removeCls([visibilityCls, offsetsCls, clipCls, opacityCls]);
                    me[addOrRemove](displayCls);
                    break;

                case Element.VISIBILITY:
                    me.removeCls([displayCls, offsetsCls, clipCls, opacityCls]);
                    me[addOrRemove](visibilityCls);
                    break;

                case Element.OFFSETS:
                    me.removeCls([visibilityCls, displayCls, clipCls, opacityCls]);
                    me[addOrRemove](offsetsCls);
                    break;

                case Element.CLIP:
                    me.removeCls([visibilityCls, displayCls, offsetsCls, opacityCls]);
                    me[addOrRemove](clipCls);
                    break;

                case Element.OPACITY:
                    me.removeCls([visibilityCls, displayCls, offsetsCls, clipCls]);
                    me[addOrRemove](opacityCls);
                    break;
            }

            if (me.shadow || me.shim) {
                me.setUnderlaysVisible(visible);
            }

            if (!visible && me.$ripples) {
                me.destroyAllRipples();
            }

            return me;
        },

        /**
         * Set the width of this Element.
         * @param {Number/String} width The new width.
         * @return {Ext.dom.Element} this
         */
        setWidth: function(width) {
            var me = this;

            me.dom.style[WIDTH] = Element.addUnits(width);

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        /**
         * Sets this Element's page-level x coordinate
         * @param {Number} x
         * @return {Ext.dom.Element} this
         */
        setX: function(x) {
            return this.setXY([x, false]);
        },

        /**
         * Sets this Element's page-level x and y coordinates
         * @param {Number[]} xy
         * @return {Ext.dom.Element} this
         */
        setXY: function(xy) {
            var me = this,
                pts = me.translatePoints(xy),
                style = me.dom.style,
                pos;

            me.position();

            // right position may have been previously set by rtlSetLocalXY 
            // so clear it here just in case.
            style.right = '';

            for (pos in pts) {
                if (!isNaN(pts[pos])) {
                    style[pos] = pts[pos] + 'px';
                }
            }

            if (me.shadow || me.shim) {
                me.syncUnderlays();
            }

            return me;
        },

        /**
         * Sets this Element's page-level y coordinate
         * @param {Number} y
         * @return {Ext.dom.Element} this
         */
        setY: function(y) {
            return this.setXY([false, y]);
        },

        /**
         * Sets the z-index of this Element and synchronizes the z-index of shadow and/or
         * shim if present.
         *
         * @param {Number} zindex The new z-index to set
         * @return {Ext.dom.Element} this
         */
        setZIndex: function(zindex) {
            var me = this;

            if (me.shadow) {
                me.shadow.setZIndex(zindex);
            }

            if (me.shim) {
                me.shim.setZIndex(zindex);
            }

            return me.setStyle('z-index', zindex);
        },

        /**
         * Show this element - Uses display mode to determine whether to use "display",
         * "visibility", "offsets", or "clip". See {@link #setVisible}.
         *
         * @return {Ext.dom.Element} this
         */
        show: function() {
            return this.setVisible(true);
        },

        /**
         * Stops the specified event(s) from bubbling and optionally prevents the default action
         *
         *     var store = Ext.create('Ext.data.Store', {
         *         fields: ['name', 'email'],
         *         data: [{
         *             'name': 'Finn',
         *             "email": "finn@adventuretime.com"
         *         }]
         *     });
         *
         *     Ext.create('Ext.grid.Panel', {
         *         title: 'Land of Ooo',
         *         store: store,
         *         columns: [{
         *             text: 'Name',
         *             dataIndex: 'name'
         *         }, {
         *             text: 'Email <img style="vertical-align:middle;" src="{some-image-src}" />',
         *             dataIndex: 'email',
         *             flex: 1,
         *             listeners: {
         *                 render: function(col) {
         *                     // Swallow the click event when the click occurs on the
         *                     // help icon - preventing the sorting of data by that
         *                     // column and instead performing an action specific to
         *                     // the help icon
         *                     var img = col.getEl().down('img');
         *                     img.swallowEvent(['click', 'mousedown'], true);
         *                     col.on('click', function() {
         *                         // logic to show a help dialog
         *                         console.log('image click handler');
         *                     }, col);
         *                 }
         *             }
         *         }],
         *         height: 200,
         *         width: 400,
         *         renderTo: document.body
         *     });
         *
         * @param {String/String[]} eventName an event / array of events to stop from bubbling
         * @param {Boolean} [preventDefault] true to prevent the default action too
         * @return {Object} Object with a destroy method to unswallow events
         * @return {Function} return.destroy method to clean up any listeners that are swallowing
         * events
         */
        swallowEvent: function(eventName, preventDefault) {
            var me = this,
                e, eLen,
                listeners = {
                    destroyable: true
                },
                fn = function(e) {
                    e.stopPropagation();

                    if (preventDefault) {
                        e.preventDefault();
                    }
                };

            if (Ext.isArray(eventName)) {
                eLen = eventName.length;

                for (e = 0; e < eLen; e++) {
                    listeners[eventName[e]] = fn;
                }
            }
            else {
                listeners[eventName] = fn;
            }

            return me.on(listeners);
        },

        /**
         * @private
         * @param {String} firstClass
         * @param {String} secondClass
         * @param {Boolean} flag
         * @param {String} prefix
         * @return {Mixed}
         */
        swapCls: function(firstClass, secondClass, flag, prefix) {
            if (flag === undefined) {
                flag = true;
            }

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                addedClass = flag ? firstClass : secondClass,
                removedClass = flag ? secondClass : firstClass;

            if (removedClass) {
                me.removeCls(prefix ? prefix + '-' + removedClass : removedClass);
            }

            if (addedClass) {
                me.addCls(prefix ? prefix + '-' + addedClass : addedClass);
            }

            return me;
        },

        /**
         * @private
         */
        synchronize: function() {
            var me = this,
                dom = me.dom,
                hasClassMap = {},
                className = dom.className,
                classList, i, ln, name,
                elementData = me.getData(/* sync= */ false);

            if (className && className.length > 0) {
                classList = dom.className.split(classNameSplitRegex);

                for (i = 0, ln = classList.length; i < ln; i++) {
                    name = classList[i];
                    hasClassMap[name] = true;
                }
            }
            else {
                classList = [];
            }

            elementData.classList = classList;
            elementData.classMap = hasClassMap;
            elementData.isSynchronized = true;

            return me;
        },

        /**
         * @private
         */
        syncUnderlays: function() {
            var me = this,
                shadow = me.shadow,
                shim = me.shim,
                dom = me.dom,
                xy, x, y, w, h;

            if (me.isVisible()) {
                xy = me.getXY();
                x = xy[0];
                y = xy[1];
                w = dom.offsetWidth;
                h = dom.offsetHeight;

                if (shadow && !shadow.hidden) {
                    shadow.realign(x, y, w, h);
                }

                if (shim && !shim.hidden) {
                    shim.realign(x, y, w, h);
                }
            }
        },

        /**
         * Toggles the specified CSS class on this element (removes it if it already exists,
         * otherwise adds it).
         * @param {String} className The CSS class to toggle.
         * @param {Boolean} [state] If specified as `true`, causes the class to be added.
         * If specified as `false`, causes the class to be removed.
         * @return {Ext.dom.Element} this
         */
        toggleCls: function(className, state) {
            if (state == null) {
                state = !this.hasCls(className);
            }

            return state ? this.addCls(className) : this.removeCls(className);
        },

        /**
         * Toggles the element's visibility, depending on visibility mode.
         * @return {Ext.dom.Element} this
         */
        toggle: function() {
            this.setVisible(!this.isVisible());

            return this;
        },

        translate: function() {
            var transformStyleName = 'webkitTransform' in DOC.createElement('div').style
                ? 'webkitTransform'
                : 'transform';

            return function(x, y, z) {

                x = Math.round(x);
                y = Math.round(y);
                z = Math.round(z);

                this.dom.style[transformStyleName] = 'translate3d(' + (x || 0) + 'px, ' +
                                                     (y || 0) + 'px, ' + (z || 0) + 'px)';
            };
        }(),

        /**
         * @private
         */
        unwrap: function() {
            var dom = this.dom,
                parentNode = dom.parentNode,
                activeElement = (activeElFly || (activeElFly = new Ext.dom.Fly())).attach(Ext.Element.getActiveElement()), // eslint-disable-line max-len
                grandparentNode, cached, resumeFocus, tabIndex;

            grannyFly = grannyFly || new Ext.dom.Fly();

            cached = Ext.cache[activeElement.dom.id];

            // If the element is in the cache, we need to get the instance so
            // we can suspend events on it. If it's not in the cache, it can't
            // have any events so we don't need to suspend on it.
            if (cached) {
                activeElement = cached;
            }

            if (this.contains(activeElement)) {
                if (cached) {
                    cached.suspendFocusEvents();
                }

                resumeFocus = true;
            }

            if (parentNode) {
                grandparentNode = parentNode.parentNode;

                // See wrap() for the explanation of this jiggery-trickery
                if (resumeFocus) {
                    tabIndex = grandparentNode.getAttribute('tabIndex');

                    grannyFly.attach(grandparentNode);
                    grannyFly.set({ tabIndex: -1 });
                    grannyFly.suspendFocusEvents();
                    grannyFly.focus();
                }

                grandparentNode.insertBefore(dom, parentNode);
                grandparentNode.removeChild(parentNode);
            }
            else {
                grandparentNode = DOC.createDocumentFragment();
                grandparentNode.appendChild(dom);
            }

            if (resumeFocus) {
                if (cached) {
                    cached.focus();
                    cached.resumeFocusEvents();
                }
                else {
                    activeElement.focus();
                }

                if (grannyFly) {
                    grannyFly.resumeFocusEvents();
                    grannyFly.set({ tabIndex: tabIndex });
                }
            }

            return this;
        },

        /**
         * Walks up the dom looking for a parent node that matches the passed simple selector
         * (e.g. 'div.some-class' or 'span:first-child').
         * This is a shortcut for findParentNode() that always returns an Ext.dom.Element.
         * @param {String} simpleSelector The simple selector to test. See {@link Ext.dom.Query}
         * for information about simple selectors.
         * @param {Number/String/HTMLElement/Ext.dom.Element} [limit]
         * The max depth to search as a number or an element that causes the upward
         * traversal to stop and is **not** considered for inclusion as the result.
         * (defaults to 50 || document.documentElement)
         * @param {Boolean} [returnDom=false] True to return the DOM node instead of Ext.dom.Element
         * @return {Ext.dom.Element/HTMLElement} The matching DOM node (or HTMLElement if
         * _returnDom_ is _true_).  Or null if no match was found.
         */
        up: function(simpleSelector, limit, returnDom) {
            return this.findParentNode(simpleSelector, limit, !returnDom);
        },

        /**
         * @method update
         * @inheritdoc Ext.dom.Element#method-setHtml
         * @deprecated 5.0.0 Please use {@link #setHtml} instead.
         */
        update: function(html) {
            return this.setHtml(html);
        },

        /**
         * Creates and wraps this element with another element
         * @param {Object} [config] DomHelper element config object for the wrapper element or null
         * for an empty div
         * @param {Boolean} [returnDom=false] True to return the raw DOM element instead of
         * Ext.dom.Element
         * @param {String} [selector] A CSS selector to select a descendant node within the created
         * element to use as the wrapping element.
         * @return {HTMLElement/Ext.dom.Element} The newly created wrapper element
         */
        wrap: function(config, returnDom, selector) {
            var me = this,
                dom = me.dom,
                // In case they pass returnDom: true:
                result = Ext.DomHelper.insertBefore(dom, config || { tag: "div" }, !returnDom),
                newEl = (wrapFly || (wrapFly = new Ext.dom.Fly())).attach(Ext.getDom(result)),
                target = newEl,
                activeElement = (activeElFly || (activeElFly = new Ext.dom.Fly())).attach(Ext.Element.getActiveElement()), // eslint-disable-line max-len
                cached, resumeFocus, tabIndex;

            cached = Ext.cache[activeElement.dom.id];

            // If the element is in the cache, we need to get the instance so
            // we can suspend events on it. If it's not in the cache, it can't
            // have any events so we don't need to suspend on it.
            if (cached) {
                activeElement = cached;
            }

            if (selector) {
                target = newEl.selectNode(selector, returnDom);
            }

            if (me.contains(activeElement)) {
                if (cached) {
                    cached.suspendFocusEvents();
                }

                // This is workaround for the nasty IE behavior w/r/t removing and adding
                // DOM nodes that contain focus. When this happens, focus will fall back
                // to the document body *after* the present code execution path finishes,
                // with no way to control this. Instead of trying to refocus the element
                // asynchronously in a callback, we're focusing the wrapper instead,
                // adding the dom to the wrapper, and then refocusing the dom;
                // all synchronous and dandy.
                // The only side effect of all this focus juggling is that focus/blur
                // events will fire asynchronously after this code path finishes (in IE),
                // but we deal with that by ignoring these events *on particular elements*.
                // Focus event publisher will look for focus suspension flag on the element,
                // and since the flag is cleared asynchronously in the immediate callback,
                // we have enough cycles to ignore unwanted events to get away with it
                // but not too many to step on someone else's toes (hopefully).
                tabIndex = Ext.getDom(newEl).getAttribute('tabIndex');
                newEl.set({ tabIndex: -1 });

                newEl.suspendFocusEvents();
                newEl.focus();

                resumeFocus = true;
            }

            (target.dom || target).appendChild(dom);

            if (resumeFocus) {
                if (cached) {
                    cached.focus();
                    cached.resumeFocusEvents();
                }
                else {
                    activeElement.focus();
                }

                newEl.resumeFocusEvents();

                // Most often tabIndex will be undefined, and we don't want to
                // make the wrapper focusable by accident.
                newEl.set({ tabIndex: tabIndex });
            }

            return result;
        },

        /**
         * Checks whether this element can be focused programmatically or by clicking.
         * To check if an element is in the document tab flow, use {@link #isTabbable}.
         *
         * @return {Boolean} True if the element is focusable
         */
        isFocusable: function(skipVisibility) {
            var dom = this.dom,
                focusable = false,
                nodeName;

            if (dom && !dom.disabled) {
                nodeName = dom.nodeName;

                /*
                 * An element is focusable if:
                 *   - It is naturally focusable, or
                 *   - It is an anchor or link with href attribute, or
                 *   - It has a tabIndex, or
                 *   - It is an editing host (contenteditable="true")
                 *
                 * Also note that we can't check dom.tabIndex because IE will return 0
                 * for elements that have no tabIndex attribute defined, regardless of
                 * whether they are naturally focusable or not.
                 */
                focusable = !!Ext.Element.naturallyFocusableTags[nodeName] ||
                            ((nodeName === 'A' || nodeName === 'LINK') && !!dom.href) ||
                            dom.getAttribute('tabIndex') != null ||
                            dom.contentEditable === 'true';

                // In IE8, <input type="hidden"> does not have a corresponding style
                // so isVisible() will assume that it's not hidden.
                if (Ext.isIE8 && nodeName === 'INPUT' && dom.type === 'hidden') {
                    focusable = false;
                }

                // Invisible elements cannot be focused, so check that as well
                // uness the caller doesn't care.
                focusable = focusable && (skipVisibility || this.isVisible(true));
            }

            return focusable;
        },

        /**
         * Returns `true` if this Element is an input field, or is editable in any way.
         * @return {Boolean} `true` if this Element is an input field, or is editable in any way.
         */
        isInputField: function() {
            var dom = this.dom,
                contentEditable = dom.contentEditable;

            // contentEditable will default to inherit if not specified, only check if the
            // attribute has been set or explicitly set to true
            // http://html5doctor.com/the-contenteditable-attribute/
            // Also skip <input> tags of type="button", we use them for checkboxes
            // and radio buttons
            if ((Ext.Element.inputTags[dom.tagName] && dom.type !== 'button') ||
                (contentEditable === '' || contentEditable === 'true')) {
                return true;
            }

            return false;
        },

        /**
         * Checks whether this element participates in the sequential focus navigation,
         * and can be reached by using Tab key.
         *
         * @param {Boolean} [includeHidden=false] pass `true` if hidden, or unattached elements
         * should be returned.
         * @return {Boolean} True if the element is tabbable.
         */
        isTabbable: function(includeHidden) {
            var dom = this.dom,
                tabbable = false,
                nodeName, hasIndex, tabIndex;

            if (dom && !dom.disabled) {
                nodeName = dom.nodeName;

                // Can't use dom.tabIndex here because IE will return 0 for elements
                // that have no tabindex attribute defined, regardless of whether they are
                // naturally tabbable or not.
                tabIndex = dom.getAttribute('tabIndex');
                hasIndex = tabIndex != null;

                tabIndex -= 0;

                // Anchors and links are only naturally tabbable if they have href attribute
                // See http://www.w3.org/TR/html5/editing.html#specially-focusable
                if (nodeName === 'A' || nodeName === 'LINK') {
                    if (dom.href) {
                        // It is also possible to make an anchor untabbable by setting
                        // tabIndex < 0 on it
                        tabbable = hasIndex && tabIndex < 0 ? false : true;
                    }

                    // Anchor w/o href is tabbable if it has tabIndex >= 0,
                    // or if it's editable 
                    else {
                        if (dom.contentEditable === 'true') {
                            tabbable = !hasIndex || (hasIndex && tabIndex >= 0) ? true : false;
                        }
                        else {
                            tabbable = hasIndex && tabIndex >= 0 ? true : false;
                        }
                    }
                }

                // If an element has contenteditable="true" or is naturally tabbable,
                // then it is a potential candidate unless its tabIndex is < 0.
                else if (dom.contentEditable === 'true' ||
                         Ext.Element.naturallyTabbableTags[nodeName]) {
                    tabbable = hasIndex && tabIndex < 0 ? false : true;
                }

                // That leaves non-editable elements that can only be made tabbable
                // by slapping tabIndex >= 0 on them
                else {
                    if (hasIndex && tabIndex >= 0) {
                        tabbable = true;
                    }
                }

                // In IE8, <input type="hidden"> does not have a corresponding style
                // so isVisible() will assume that it's not hidden.
                if (Ext.isIE8 && nodeName === 'INPUT' && dom.type === 'hidden') {
                    tabbable = false;
                }

                // Invisible elements can't be tabbed into. If we have a component ref
                // we'll also check if the component itself is visible before incurring
                // the expense of DOM style reads.
                // Allow caller to specify that hiddens should be included.
                tabbable = tabbable && (includeHidden ||
                           ((!this.component || this.component.isVisible(true)) &&
                           this.isVisible(true)));
            }

            return tabbable;
        },

        ripplingCls: Ext.baseCSSPrefix + 'rippling',
        ripplingTransitionCls: Ext.baseCSSPrefix + 'ripple-transition',
        ripplingUnboundCls: Ext.baseCSSPrefix + 'rippling-unbound',
        rippleBubbleCls: Ext.baseCSSPrefix + 'ripple-bubble',
        rippleContainerCls: Ext.baseCSSPrefix + 'ripple-container',
        rippleWrapperCls: Ext.baseCSSPrefix + 'ripple-wrapper',

        // elements with 'display' property in this map cannot act as ripple container
        noRippleDisplayMap: {
            table: 1,
            'table-row': 1,
            'table-row-group': 1
        },

        // elements with tag name in this map cannot act as ripple container
        noRippleTagMap: {
            TABLE: 1,
            TR: 1,
            TBODY: 1
        },

        /**
         * Creates a ripple effect over this element. The element should be positioned
         * (either `relative` or `absolute`) prior to calling this method.
         * 
         * @param {String/Event/Ext.event.Event} [event] The event to use for ripple
         * positioning.
         * 
         * @param {Object/String} [options] Ripple options object or color to use for ripple
         * @param {String} [options.color] The color to use for the ripple effect or
         * `'default'` to use the stylesheet default color
         * {@link Global_CSS#$ripple-background-color}. When no `color` is given, the
         * element's `color` style is used.
         * @param {Boolean} [options.release] Optional determines if the ripple should happen
         * on release. Defaults to down/start.
         * @param {String} [options.delegate] Optional selector for which child to add the ripple
         * into
         * @param {String} [options.measureSelector] Optional selector for which child to use
         * to measure for ripple size.
         * @param {Number[]} [options.position] The [x,y] position in which to start the ripple.
         * @param {Boolean} [options.centered] Set to `true` to override all position
         * information and forces the ripple to be centered inside its parent.
         * @param {Boolean/String} [options.bound] Determines if the ripple is bound to
         * the parent container (default). If false ripple will expand outside of container.
         * @param {Boolean/Number} [options.diameterLimit] Maximum size, in pixels, that a ripple
         * can be.
         * A value of `false` or `0` will cause the ripple to fill its container. A value of `true`
         * will cause the ripple to use the default maximum size.
         * @param {Boolean/String} [options.fit=true] For bound ripples only. Determines if
         * ripple should search up the dom for an element that will fit the ripple
         * without clipping. Setting to false will force the unbound ripple into the specified
         * container. Defaults to `true`.
         * @param {Number} [options.destroyTime] The time (in milliseconds) to wait until
         * the ripple is destroyed.
         */
        ripple: function(event, options) {
            if (options === true || !options) {
                options = {};
            }
            else if (Ext.isString(options)) {
                options = {
                    color: options
                };
            }

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                rippleParent = Ext.isString(options.delegate) ? me.down(options.delegate) : me,
                rippleMeasureEl = Ext.isString(options.measureSelector) ? me.down(options.measureSelector) : null, // eslint-disable-line max-len
                color = window.getComputedStyle(rippleParent.dom).color,
                unbound = options.bound === false,
                position = options.position,
                ripplingCls = me.ripplingCls,
                ripplingTransitionCls = me.ripplingTransitionCls,
                ripplingUnboundCls = me.ripplingUnboundCls,
                rippleBubbleCls = me.rippleBubbleCls,
                rippleContainerCls = me.rippleContainerCls,
                rippleWrapperCls = me.rippleWrapperCls,
                offset, width, height, rippleDiameter, center,
                measureElWidth, measureElHeight, rippleSize,
                pos, posX, posY, rippleWrapper, rippleContainer, rippleBubble,
                rippleDestructor, rippleClearFn, rippleDestructionTimer, rippleBox, unboundEl,
                unboundElData, timeout;

            if (rippleParent) {
                offset = rippleParent.getXY();
                width = rippleParent.getWidth();
                height = rippleParent.getHeight();

                timeout = rippleParent.$rippleClearTimeout;

                if (timeout) {
                    rippleParent.$rippleClearTimeout = Ext.undefer(timeout);
                }

                // If a measure element exists use that to determine the ripple diameter
                // otherwise we will just use the parent
                if (rippleMeasureEl) {
                    measureElWidth = rippleMeasureEl.getWidth();
                    measureElHeight = rippleMeasureEl.getHeight();
                    rippleDiameter = Math.max(measureElWidth, measureElHeight);
                }
                else {
                    rippleDiameter = width > height ? width : height;
                }

                // Cap the diameter based on the default or provided limit
                if (options.diameterLimit === undefined || options.diameterLimit === true) {
                    rippleDiameter = Math.min(rippleDiameter, Element.maxRippleDiameter);
                }
                else if (options.diameterLimit && options.diameterLimit !== false &&
                         options.diameterLimit !== 0) {
                    rippleDiameter = Math.min(rippleDiameter, options.diameterLimit);
                }

                // determine the center of the element we are going to ripple over
                center = [offset[0] + width / 2, offset[1] + height / 2];

                if (unbound) {
                    if (options.fit !== false) {
                        // Compute the bounding rippleBox that is centered over the
                        // target element.
                        rippleSize = rippleDiameter * 2.15;  // anim scales to 2.15
                        rippleBox = rippleParent.getRegion();
                        rippleBox.setPosition(rippleBox.getCenter()).setSize(rippleSize)
                                 .translateBy(-rippleSize / 2, -rippleSize / 2);

                        // Find the nearest parent el that will be able to fully contain
                        // the ripple... we assume that the el we are rippling is not too
                        // close to the edge
                        unboundEl = me.up(function(candidate) {
                            var fly = Ext.fly(candidate, 'ripple');

                            return !(candidate.tagName in me.noRippleTagMap) &&
                                    !(fly.getStyle('display') in me.noRippleDisplayMap) &&
                                    (fly.getRegion().contains(rippleBox));
                        }) || Ext.getBody();
                    }
                    else {
                        unboundEl = rippleParent;
                    }
                }

                // if the first param was a string, it was meant to be a color
                // otherwise if it an unwrapped event lets wrap it up
                if (Ext.isString(event)) {
                    options.color = event;
                    event = null;
                }
                else if (event && !event.isEvent) {
                    event = new Ext.event.Event(event);
                }

                // Check for preventRipple on the event and skip everything if its true
                if (event && event.isEvent) {
                    // Prevent ripples from the same event
                    if (event.browserEvent.$preventRipple) {
                        return;
                    }

                    position = event.getXY();
                    event.browserEvent.$preventRipple = true;
                }

                //  unbound or centered items always center, otherwise if position is
                // provided use it
                pos = (!unbound && !options.centered && position) || center;
                posX = pos[0] - offset[0] - (rippleDiameter / 2);
                posY = pos[1] - offset[1] - (rippleDiameter / 2);

                // Ripple Parent always needs to be notified that it should transition
                // with the ripple bound or not
                rippleParent.addCls(ripplingTransitionCls);

                if (!unbound) {
                    rippleParent.addCls(ripplingCls);
                    // Is there already a container for ripples, reuse it.
                    rippleContainer = rippleParent.child('.' + rippleContainerCls);
                }
                else {
                    // unbound ripples are added to the body inside the ripple wrapper.
                    // check to see if this wrapper exists, if not create it
                    unboundElData = unboundEl.getData();
                    rippleWrapper = unboundElData.rippleWrapper;

                    if (!rippleWrapper) {
                        // insertFirst is important because of Field margin-top:0 rules
                        // to collapse field spacing in form panels.
                        unboundElData.rippleWrapper = rippleWrapper = unboundEl.insertFirst({
                            style: 'position: absolute; top: 0; left: 0',
                            cls: rippleWrapperCls + ' ' + ripplingCls + ' ' + ripplingUnboundCls
                        });
                    }
                }

                if (!rippleContainer) {
                    if (unbound) {
                        // unbound ripples are positioned inside the ripple-wrapper,
                        // which is inside the body
                        rippleContainer = rippleWrapper.append({
                            cls: rippleContainerCls
                        });

                        // position the unbound ripple over-top the element
                        rippleContainer.setXY(offset);
                    }
                    else {
                        // body ripples are positioned inside the rippleParent, which is
                        // the element being rippled
                        rippleContainer = rippleParent.append({
                            cls: rippleContainerCls
                        });
                    }
                }

                // create the actual ripple bubble element
                rippleBubble = rippleContainer.append({
                    cls: rippleBubbleCls
                });

                if (options.color !== 'default') {
                    rippleBubble.setStyle('backgroundColor', options.color || color);
                }

                rippleBubble.setWidth(rippleDiameter);
                rippleBubble.setHeight(rippleDiameter);
                rippleBubble.setTop(posY);
                rippleBubble.setLeft(posX);

                rippleClearFn = function() {
                    // Allow for transition to happen then remove classes
                    // we do this instead of a transtionend listener
                    // as we do not know which element is transitioning
                    if (!rippleParent.destroyed) {
                        rippleParent.$rippleClearTimeout = Ext.defer(function() {
                            rippleParent.removeCls([ripplingCls, ripplingTransitionCls]);
                            rippleParent.$rippleClearTimeout = null;
                        }, 50);
                    }
                };

                rippleDestructor = function() {
                    var ripple, timeout;

                    // destroy the ripple
                    rippleBubble.destroy();

                    // remove from lookup
                    if (me.$ripples) {
                        delete me.$ripples[rippleBubble.id];
                    }

                    timeout = rippleParent.$rippleClearTimeout;

                    if (timeout) {
                        rippleParent.$rippleClearTimeout = Ext.undefer(timeout);
                    }

                    if (unbound) {
                        // always destroy unbound ripple containers as they are never
                        // re-used. only the ripple-wrapper is reused
                        rippleContainer.destroy();

                        // Determine if there are any other ripples still active in the wrapper
                        ripple = rippleWrapper.child('.' + rippleContainerCls);

                        // If there are no other ripples, clean up all ripple related DOM
                        if (!ripple) {
                            unboundElData.rippleWrapper = null;
                            rippleWrapper.destroy();
                            rippleClearFn();
                        }
                    }
                    else {
                        // Determine if there are any other ripples still active in the parent
                        ripple = rippleContainer.child('.' + rippleBubbleCls);

                        // If there are no other ripples, clean up all ripple related DOM
                        if (!ripple) {
                            rippleContainer.destroy();
                            rippleClearFn();
                        }
                    }

                };

                rippleDestructionTimer = Ext.defer(rippleDestructor,
                                                   options.destroyTime || 1000, me);

                // Keep a list of all the current ripples, for cleanup later
                if (!me.$ripples) {
                    me.$ripples = {};
                }

                me.$ripples[rippleBubble.id] = {
                    timerId: rippleDestructionTimer,
                    destructor: rippleDestructor
                };

                rippleBubble.addCls(Ext.baseCSSPrefix + 'ripple');
            }
        },

        destroyAllRipples: function() {
            var me = this,
                rippleEl, ripple;

            for (rippleEl in me.$ripples) {
                ripple = me.$ripples[rippleEl];
                Ext.undefer(ripple.timerId);

                if (ripple.destructor) {
                    ripple.destructor();
                }
            }

            me.$ripples = null;
        },

        privates: {
            /**
             * @private
             */
            findTabbableElements: function(options) {
                var skipSelf, skipChildren, excludeRoot, includeSaved, includeHidden,
                    dom = this.dom,
                    cAttr = Ext.Element.tabbableSavedCounterAttribute,
                    selection = [],
                    idx = 0,
                    nodes, node, fly, i, len, tabIndex;

                if (!dom) {
                    return selection;
                }

                if (options) {
                    skipSelf = options.skipSelf;
                    skipChildren = options.skipChildren;
                    excludeRoot = options.excludeRoot;
                    includeSaved = options.includeSaved;
                    includeHidden = options.includeHidden;
                }

                excludeRoot = excludeRoot && Ext.getDom(excludeRoot);

                if (excludeRoot && excludeRoot.contains(dom)) {
                    return selection;
                }

                if (!skipSelf &&
                    ((includeSaved && dom.hasAttribute(cAttr)) || this.isTabbable(includeHidden))) {
                    selection[idx++] = dom;
                }

                if (skipChildren) {
                    return selection;
                }

                nodes = dom.querySelectorAll(Ext.Element.tabbableSelector);
                len = nodes.length;

                if (!len) {
                    return selection;
                }

                fly = new Ext.dom.Fly();

                // We're only interested in the elements that an user can *tab into*,
                // not all programmatically focusable elements. So we have to filter
                // these out.
                for (i = 0; i < len; i++) {
                    node = nodes[i];

                    // A node with tabIndex < 0 absolutely can't be tabbable
                    // so we can save a function call if that is the case.
                    // Note that we can't use node.tabIndex here because IE
                    // will return 0 for elements that have no tabindex
                    // attribute defined, regardless of whether they are
                    // tabbable or not.
                    tabIndex = +node.getAttribute('tabIndex'); // quicker than parseInt

                    // tabIndex value may be null for nodes with no tabIndex defined;
                    // most of those may be naturally tabbable. We don't want to
                    // check this here, that's isTabbable()'s job and it's not trivial.
                    // We explicitly check that tabIndex is not negative. The expression
                    // below is purposeful if hairy; this is a very hot code path so care
                    // is taken to minimize the amount of DOM calls that could be avoided.

                    // A node may have its tabindex saved by previous calls to
                    // saveTabbableState(); in that case we need to return that node
                    // so that its saved counter could be properly incremented or
                    // decremented.
                    if (((includeSaved && node.hasAttribute(cAttr)) || (!(tabIndex < 0) && fly.attach(node).isTabbable(includeHidden))) && // eslint-disable-line max-len
                        !(excludeRoot && (excludeRoot === node || excludeRoot.contains(node)))) {
                        selection[idx++] = node;
                    }
                }

                return selection;
            },

            /**
             * @private
             */
            saveTabbableState: function(options) {
                var counterAttr = Ext.Element.tabbableSavedCounterAttribute,
                    savedAttr = Ext.Element.tabbableSavedValueAttribute,
                    counter, nodes, node, i, len;

                // By default include already saved tabbables, and just increase their save counter.
                // For example, if a View with saved tabbables is covered by a modal Window,
                // saveTabbableState must disable tabbability for the whole document.
                // But upon unmask, the View must not be restored to tabbability. It must only have
                // its save level decremented.
                // AbstractView#toggleChildrenTabbability however pases this as false so that
                // it may be called upon row add and it does not increment save levels on already
                // saved tabbables.
                if (!options || options.includeSaved == null) {
                    options = Ext.Object.chain(options || null);
                    options.includeSaved = true;
                }

                nodes = this.findTabbableElements(options);

                for (i = 0, len = nodes.length; i < len; i++) {
                    node = nodes[i];

                    counter = +node.getAttribute(counterAttr);

                    if (counter > 0) {
                        node.setAttribute(counterAttr, ++counter);
                    }
                    else {
                        // tabIndex could be set on both naturally tabbable and generic elements.
                        // Either way we need to save it to restore later.
                        if (node.hasAttribute('tabIndex')) {
                            node.setAttribute(savedAttr, node.getAttribute('tabIndex'));
                        }

                        // When no tabIndex is specified, that means a naturally tabbable element.
                        else {
                            node.setAttribute(savedAttr, 'none');
                        }

                        // We disable the tabbable state by setting tabIndex to -1.
                        // The element can still be focused programmatically though.
                        node.setAttribute('tabIndex', '-1');
                        node.setAttribute(counterAttr, '1');
                    }
                }

                return nodes;
            },

            /**
             * @private
             */
            restoreTabbableState: function(options) {
                var dom = this.dom,
                    counterAttr = Ext.Element.tabbableSavedCounterAttribute,
                    savedAttr = Ext.Element.tabbableSavedValueAttribute,
                    nodes = [],
                    skipSelf = options && options.skipSelf,
                    skipChildren = options && options.skipChildren,
                    reset = options && options.reset,
                    idx, counter, node, i, len;

                if (!dom) {
                    return this;
                }

                if (!skipChildren) {
                    nodes = Ext.Array.from(dom.querySelectorAll('[' + counterAttr + ']'));
                }

                if (!skipSelf) {
                    nodes.unshift(dom);
                }

                for (i = 0, len = nodes.length; i < len; i++) {
                    node = nodes[i];

                    if (!node.hasAttribute(counterAttr) || !node.hasAttribute(savedAttr)) {
                        continue;
                    }

                    counter = +node.getAttribute(counterAttr);

                    if (!reset && counter > 1) {
                        node.setAttribute(counterAttr, --counter);

                        continue;
                    }

                    idx = node.getAttribute(savedAttr);

                    // That is a naturally tabbable element
                    if (idx === 'none') {
                        node.removeAttribute('tabIndex');
                    }
                    else {
                        node.setAttribute('tabIndex', idx);
                    }

                    node.removeAttribute(savedAttr);
                    node.removeAttribute(counterAttr);
                }

                return nodes;
            },

            /**
             * @private
             */
            setTabIndex: function(tabIndex) {
                var dom = this.dom,
                    savedAttr = Ext.Element.tabbableSavedValueAttribute;

                if (dom.hasAttribute(savedAttr)) {
                    if (tabIndex == null) {
                        // Equivalent to removing tabIndex while not saved
                        dom.setAttribute(savedAttr, 'none');
                        dom.removeAttribute('tabIndex');
                    }
                    else {
                        dom.setAttribute(savedAttr, tabIndex);
                    }
                }
                else {
                    if (tabIndex == null) {
                        dom.removeAttribute('tabIndex');
                    }
                    else {
                        dom.setAttribute('tabIndex', tabIndex);
                    }
                }
            },

            doAddListener: function(eventName, fn, scope, options, order, caller, manager) {
                var me = this,
                    originalName = eventName,
                    observableDoAddListener, additiveEventName,
                    translatedEventName;

                // Even though the superclass method does conversion to lowercase, we need
                // to do it here because we need to use the lowercase name for lookup
                // in the event translation map.
                eventName = Ext.canonicalEventName(eventName);

                // Blocked events (such as emulated mouseover in mobile webkit) are prevented
                // from firing
                if (!me.blockedEvents[eventName]) {
                    observableDoAddListener = me.mixins.observable.doAddListener;
                    options = options || {};

                    if (Element.useDelegatedEvents === false) {
                        options.delegated = options.delegated || false;
                    }

                    if (options.translate !== false) {
                        // translate events where applicable.  This allows applications that
                        // were written for desktop to work on mobile devices and vice versa.
                        additiveEventName = me.additiveEvents[eventName];

                        if (additiveEventName) {
                            // additiveEvents means the translation is "additive" - meaning we
                            // need to attach the original event in addition to the translated
                            // one.  An example of this is devices that have both mousedown
                            // and touchstart
                            options.type = eventName;
                            eventName = additiveEventName;
                            observableDoAddListener.call(me, eventName, fn, scope, options, order,
                                                         caller, manager);
                        }

                        translatedEventName = me.eventMap[eventName];

                        if (translatedEventName) {
                            // options.type may have already been set above
                            options.type = options.type || eventName;

                            if (manager) {
                                options.managedName = originalName;
                            }

                            eventName = translatedEventName;
                        }
                    }

                    if (observableDoAddListener.call(me, eventName, fn, scope, options, order, caller, manager)) { // eslint-disable-line max-len
                        if (me.longpressEvents[eventName] && (++me.longpressListenerCount === 1)) {
                            me.on('MSHoldVisual', 'preventMsHoldVisual', me);
                        }
                    }

                    if (manager && translatedEventName) {
                        delete options.managedName;
                    }

                    // after the listener has been added to the ListenerStack, it's original
                    // "type" (for translated events) will be stored on the listener object in
                    // the ListenerStack.  We can now delete type from the options object
                    // since it is not a user-supplied option
                    delete options.type;
                }
            },

            doRemoveListener: function(eventName, fn, scope) {
                var me = this,
                    observableDoRemoveListener, translatedEventName, additiveEventName,
                    removed;

                // Even though the superclass method does conversion to lowercase, we need
                // to do it here because we need to use the lowercase name for lookup
                // in the event translation map.
                eventName = Ext.canonicalEventName(eventName);

                // Blocked events (such as emulated mouseover in mobile webkit) are prevented
                // from firing
                if (!me.blockedEvents[eventName]) {
                    observableDoRemoveListener = me.mixins.observable.doRemoveListener;

                    // translate events where applicable.  This allows applications that
                    // were written for desktop to work on mobile devices and vice versa.
                    additiveEventName = me.additiveEvents[eventName];

                    if (additiveEventName) {
                        // additiveEvents means the translation is "additive" - meaning we
                        // need to remove the original event in addition to the translated
                        // one.  An example of this is devices that have both mousedown
                        // and touchstart
                        eventName = additiveEventName;
                        observableDoRemoveListener.call(me, eventName, fn, scope);
                    }

                    translatedEventName = me.eventMap[eventName];

                    if (translatedEventName) {
                        removed = observableDoRemoveListener.call(me, translatedEventName, fn,
                                                                  scope);
                    }

                    // no "else" here because we need to ensure that we remove translate:false
                    // listeners
                    removed = observableDoRemoveListener.call(me, eventName, fn, scope) || removed;

                    if (removed) {
                        if (me.longpressEvents[eventName] && !--me.longpressListenerCount) {
                            me.un('MSHoldVisual', 'preventMsHoldVisual', me);
                        }
                    }
                }
            },

            _initEvent: function(eventName) {
                return (this.events[eventName] = new Ext.dom.ElementEvent(this, eventName));
            },

            _getDisplay: function() {
                var data = this.getData(),
                    display = data[ORIGINALDISPLAY];

                if (display === undefined) {
                    data[ORIGINALDISPLAY] = display = '';
                }

                return display;
            },

            /**
             * Returns the publisher for a given event
             * @param {String} eventName
             * @param {Boolean} [noTranslate] `true` if the event is a non translated event
             * @private
             * @return {Ext.event.publisher.Publisher}
             */
            _getPublisher: function(eventName, noTranslate) {
                var Publisher = Ext.event.publisher.Publisher,
                    publisher = Publisher.publishersByEvent[eventName],
                    isNative = noTranslate && !Ext.event.Event.gestureEvents[eventName];

                // Dom publisher acts as the default publisher for all events that are
                // not explicitly handled by another publisher.
                // ElementSize handles the 'resize' event, except on the window
                // object, where it is handled by Dom publisher.
                // If the event is a native event (not translated), we want to use the
                // DOM publisher.
                // For example the dragstart gesture would automatically shadow any native
                // drag events, so we force the lower level publisher to be used. The exception
                // is for touch events, they all need to be handled by the gesture publisher
                // so they can be interrogated and produce the correct output.
                if (isNative || !publisher || (this.dom === window && eventName === 'resize')) {
                    publisher = Publisher.publishers.dom;
                }

                return publisher;
            },

            isFocusSuspended: function() {
                var data = this.peekData();

                return data && data.suspendFocusEvents;
            },

            preventMsHoldVisual: function(e) {
                e.preventDefault();
            },

            suspendFocusEvents: function() {
                if (!this.isFly) {
                    this.suspendEvent('focus', 'blur');
                }

                this.getData().suspendFocusEvents = true;
            },

            resumeFocusEvents: function() {
                function resumeFn() {
                    var data;

                    if (!this.destroyed) {
                        data = this.getData();

                        if (data) {
                            data.suspendFocusEvents = false;
                        }

                        if (!this.isFly) {
                            this.resumeEvent('focus', 'blur');
                        }
                    }
                }

                if (!this.destroyed && this.getData().suspendFocusEvents) {
                    if (Ext.isIE && !this.isFly) {
                        this.resumeFocusEventsTimer = Ext.asap(resumeFn, this);
                    }
                    else {
                        resumeFn.call(this);
                    }
                }
            }
        },

        deprecated: {
            '5.0': {
                methods: {
                    /**
                     * @method getHTML
                     * @inheritdoc Ext.dom.Element#getHtml
                     * @deprecated 5.0.0 Please use {@link #getHtml} instead.
                     */
                    getHTML: 'getHtml',

                    /**
                     * @method getPageBox
                     * Returns an object defining the area of this Element which can be passed to
                     * {@link Ext.util.Positionable#setBox} to set another Element's size/location
                     * to match this element.
                     *
                     * @param {Boolean} [getRegion] If true an Ext.util.Region will be returned
                     * @return {Object/Ext.util.Region} box An object in the following format:
                     *
                     *     {
                     *         left: <Element's X position>,
                     *         top: <Element's Y position>,
                     *         width: <Element's width>,
                     *         height: <Element's height>,
                     *         bottom: <Element's lower bound>,
                     *         right: <Element's rightmost bound>
                     *     }
                     *
                     * The returned object may also be addressed as an Array where index 0 contains
                     * the X position and index 1 contains the Y position. So the result may also be
                     * used for {@link #setXY}
                     * @deprecated 5.0.0 use {@link Ext.util.Positionable#getBox} to get a box
                     * object, and {@link Ext.util.Positionable#getRegion} to get a
                     * {@link Ext.util.Region Region}.
                     */
                    getPageBox: function(getRegion) {
                        var me = this,
                            dom = me.dom,
                            isDoc = dom.nodeName === 'BODY',
                            w = isDoc ? Element.getViewportWidth() : dom.offsetWidth,
                            h = isDoc ? Element.getViewportHeight() : dom.offsetHeight,
                            xy = me.getXY(),
                            t = xy[1],
                            r = xy[0] + w,
                            b = xy[1] + h,
                            l = xy[0];

                        if (getRegion) {
                            return new Ext.util.Region(t, r, b, l);
                        }
                        else {
                            return {
                                left: l,
                                top: t,
                                width: w,
                                height: h,
                                right: r,
                                bottom: b
                            };
                        }
                    },

                    /**
                     * @method isTransparent
                     * Returns `true` if the value of the given property is visually transparent.
                     * This may be due to a 'transparent' style value or an rgba value with 0
                     * in the alpha component.
                     * @param {String} prop The style property whose value is to be tested.
                     * @return {Boolean} `true` if the style property is visually transparent.
                     * @deprecated 5.0.0 This method is deprecated.
                     */
                    isTransparent: function(prop) {
                        var value = this.getStyle(prop);

                        return value ? transparentRe.test(value) : false;
                    },

                    /**
                     * @method purgeAllListeners
                     * @inheritdoc Ext.dom.Element#clearListeners
                     * @deprecated 5.0.0 Please use {@link #clearListeners} instead.
                     */
                    purgeAllListeners: 'clearListeners',

                    /**
                     * @method removeAllListeners
                     * @inheritdoc Ext.dom.Element#clearListeners
                     * @deprecated 5.0.0 Please use {@link #clearListeners} instead.
                     */
                    removeAllListeners: 'clearListeners',

                    /**
                     * @method setHTML
                     * @inheritdoc Ext.dom.Element#setHtml
                     * @deprecated 5.0.0 Please use {@link #setHtml} instead.
                     */
                    setHTML: 'setHtml'
                }
            }
        }
    };
}, function(Element) {
    var DOC = document,
        docEl = DOC.documentElement,
        prototype = Element.prototype,
        supports = Ext.supports,
        pointerdown = 'pointerdown',
        pointermove = 'pointermove',
        pointerup = 'pointerup',
        pointercancel = 'pointercancel',
        MSPointerDown = 'MSPointerDown',
        MSPointerMove = 'MSPointerMove',
        MSPointerUp = 'MSPointerUp',
        MSPointerCancel = 'MSPointerCancel',
        mousedown = 'mousedown',
        mousemove = 'mousemove',
        mouseup = 'mouseup',
        mouseover = 'mouseover',
        mouseout = 'mouseout',
        mouseenter = 'mouseenter',
        mouseleave = 'mouseleave',
        touchstart = 'touchstart',
        touchmove = 'touchmove',
        touchend = 'touchend',
        touchcancel = 'touchcancel',
        click = 'click',
        dblclick = 'dblclick',
        tap = 'tap',
        doubletap = 'doubletap',
        eventMap = prototype.eventMap = {},
        additiveEvents = prototype.additiveEvents = {},
        oldId = Ext.id,
        eventOptions;

    prototype._init(Element);
    delete prototype._init;

    /**
     * Generates unique ids. If the element already has an id, it is unchanged
     * @member Ext
     * @param {Object/HTMLElement/Ext.dom.Element} [obj] The element to generate an id for
     * @param {String} prefix (optional) Id prefix (defaults "ext-gen")
     * @return {String} The generated Id.
     */
    Ext.id = function(obj, prefix) {
        var el = obj && Ext.getDom(obj, true),
            sandboxPrefix, id;

        if (!el) {
            id = oldId(obj, prefix);
        }
        else if (!(id = el.id)) {
            id = oldId(null, prefix || Element.prototype.identifiablePrefix);

            if (Ext.isSandboxed) {
                sandboxPrefix = Ext.sandboxPrefix ||
                    (Ext.sandboxPrefix = Ext.sandboxName.toLowerCase() + '-');
                id = sandboxPrefix + id;
            }

            el.id = id;
        }

        return id;
    };

    if (supports.PointerEvents) {
        eventMap[mousedown] = pointerdown;
        eventMap[mousemove] = pointermove;
        eventMap[mouseup] = pointerup;
        eventMap[touchstart] = pointerdown;
        eventMap[touchmove] = pointermove;
        eventMap[touchend] = pointerup;
        eventMap[touchcancel] = pointercancel;

        // On devices that support pointer events we block pointerover, pointerout,
        // pointerenter, and pointerleave when triggered by touch input (see
        // Ext.event.publisher.Dom#blockedPointerEvents).  This is because mouseover
        // behavior is typically not desired when touching the screen.  This covers the
        // use case where user code requested a pointer event, however mouseover/mouseout
        // events are not cancellable, period.
        // http://www.w3.org/TR/pointerevents/#mapping-for-devices-that-do-not-support-hover
        // To ensure mouseover/out handlers don't fire when touching the screen, we need
        // to translate them to their pointer equivalents
        eventMap[mouseover] = 'pointerover';
        eventMap[mouseout] = 'pointerout';
        eventMap[mouseenter] = 'pointerenter';

        // No decent way to feature detect this, pointerleave relatedTarget is
        // incorrect on IE11, so force it to use mouseleave here.
        // See: https://connect.microsoft.com/IE/feedback/details/851111/ev-relatedtarget-in-pointerleave-indicates-departure-element-not-destination-element
        if (!Ext.isIE11) {
            eventMap[mouseleave] = 'pointerleave';
        }
    }
    else if (supports.MSPointerEvents) {
        // IE10
        eventMap[pointerdown] = MSPointerDown;
        eventMap[pointermove] = MSPointerMove;
        eventMap[pointerup] = MSPointerUp;
        eventMap[pointercancel] = MSPointerCancel;
        eventMap[mousedown] = MSPointerDown;
        eventMap[mousemove] = MSPointerMove;
        eventMap[mouseup] = MSPointerUp;
        eventMap[touchstart] = MSPointerDown;
        eventMap[touchmove] = MSPointerMove;
        eventMap[touchend] = MSPointerUp;
        eventMap[touchcancel] = MSPointerCancel;

        // translate mouseover/out so they can be prevented on touch screens.
        // (see above comment in the PointerEvents section)
        eventMap[mouseover] = 'MSPointerOver';
        eventMap[mouseout] = 'MSPointerOut';
    }
    else if (supports.TouchEvents) {
        eventMap[pointerdown] = touchstart;
        eventMap[pointermove] = touchmove;
        eventMap[pointerup] = touchend;
        eventMap[pointercancel] = touchcancel;
        eventMap[mousedown] = touchstart;
        eventMap[mousemove] = touchmove;
        eventMap[mouseup] = touchend;
        eventMap[click] = tap;
        eventMap[dblclick] = doubletap;

        if (Ext.os.is.Desktop) {
            // Touch enabled desktop browsers on windows such as Firefox and Chrome fire
            // both mouse events and touch events, so we have to attach listeners for both
            // kinds when either one is requested.  There are a couple rules to keep in mind:
            // 1. When the mouse is used, only a mouse event is fired
            // 2. When interacting with the touch screen touch events are fired.
            // 3. After a touchstart/touchend sequence, if there was no touchmove in
            // between, the browser will fire a mousemove/mousedown/mousup sequence
            // immediately after.  This can cause problems because if we are listening
            // for both kinds of events, handlers may run twice.  To work around this
            // issue we filter out the duplicate emulated mouse events by checking their
            // coordinates and timing (see Ext.event.publisher.Gesture#onDelegatedEvent)
            eventMap[touchstart] = mousedown;
            eventMap[touchmove] = mousemove;
            eventMap[touchend] = mouseup;
            eventMap[touchcancel] = mouseup;

            additiveEvents[mousedown] = mousedown;
            additiveEvents[mousemove] = mousemove;
            additiveEvents[mouseup] = mouseup;
            additiveEvents[touchstart] = touchstart;
            additiveEvents[touchmove] = touchmove;
            additiveEvents[touchend] = touchend;
            additiveEvents[touchcancel] = touchcancel;

            additiveEvents[pointerdown] = mousedown;
            additiveEvents[pointermove] = mousemove;
            additiveEvents[pointerup] = mouseup;
            additiveEvents[pointercancel] = mouseup;
        }
    }
    else {
        // browser does not support either pointer or touch events, map all pointer and
        // touch events to their mouse equivalents
        eventMap[pointerdown] = mousedown;
        eventMap[pointermove] = mousemove;
        eventMap[pointerup] = mouseup;
        eventMap[pointercancel] = mouseup;
        eventMap[touchstart] = mousedown;
        eventMap[touchmove] = mousemove;
        eventMap[touchend] = mouseup;
        eventMap[touchcancel] = mouseup;
    }

    if (Ext.isWebKit) {
        // These properties were carried forward from touch-2.x. This translation used
        // do be done by DomPublisher.  TODO: do we still need this?
        eventMap.transitionend = Ext.browser.getVendorProperyName('transitionEnd');
        eventMap.animationstart = Ext.browser.getVendorProperyName('animationStart');
        eventMap.animationend = Ext.browser.getVendorProperyName('animationEnd');
    }

    if (!Ext.supports.MouseWheel && !Ext.isOpera) {
        eventMap.mousewheel = 'DOMMouseScroll';
    }

    eventOptions = prototype.$eventOptions = Ext.Object.chain(prototype.$eventOptions);
    eventOptions.translate = eventOptions.capture = eventOptions.delegate = eventOptions.delegated =
            eventOptions.stopEvent = eventOptions.preventDefault = eventOptions.stopPropagation =
            // Ext.Element also needs "element" as one of its event options.  Even though
            // it does not directly process an element option, it may receive a listeners
            // object that was passed through from a Component with the "element" option
            // included. Including "element" in the event options ensures we don't attempt
            // to process "element" as an event name.
            eventOptions.element = 1;

    prototype.styleHooks.opacity = {
        name: 'opacity',
        afterSet: function(dom, value, el) {
            var shadow = el.shadow;

            if (shadow) {
                shadow.setOpacity(value);
            }
        }
    };

    /**
     * @member Ext
     * @private
     * Returns the `X,Y` position of this element without regard to any RTL
     * direction settings.
     */
    prototype.getTrueXY = prototype.getXY;

    /**
     * @member Ext
     * @method getViewportHeight
     * @inheritdoc Ext.dom.Element#getViewportHeight
     * @since 6.5.0
     */
    Ext.getViewportHeight = Element.getViewportHeight;

    /**
     * @member Ext
     * @method getViewportWidth
     * @inheritdoc Ext.dom.Element#getViewportWidth
     * @since 6.5.0
     */
    Ext.getViewportWidth = Element.getViewportWidth;

    /**
     * @member Ext
     * @method select
     * Shorthand for {@link Ext.dom.Element#method-select Ext.dom.Element.select}<br><br>
     * @inheritdoc Ext.dom.Element#method-select
     */
    Ext.select = Element.select;

    /**
     * @member Ext
     * @method query
     * Shorthand for {@link Ext.dom.Element#method-query Ext.dom.Element.query}<br><br>
     * @inheritdoc Ext.dom.Element#method-query
     */
    Ext.query = Element.query;

    Ext.apply(Ext, {
        /**
         * @member Ext
         * @method get
         */
        get: function(element) {
            return Element.get(element);
        },

        /**
         * @member Ext
         * @method getDom
         * Return the dom node for the passed String (id), dom node, or Ext.Element.
         * Here are some examples:
         *
         *     // gets dom node based on id
         *     var elDom = Ext.getDom('elId');
         *
         *     // gets dom node based on the dom node
         *     var elDom1 = Ext.getDom(elDom);
         *
         *     // If we don't know if we are working with an
         *     // Ext.Element or a dom node use Ext.getDom
         *     function(el){
         *         var dom = Ext.getDom(el);
         *         // do something with the dom node
         *     }
         *
         * __Note:__ the dom node to be found actually needs to exist (be rendered, etc)
         * when this method is called to be successful.
         *
         * @param {String/HTMLElement/Ext.dom.Element} el
         * @return {HTMLElement}
         */
        getDom: function(el) {
            if (!el || !DOC) {
                return null;
            }

            // We could be passed an Element whos dom has been nulled on destruction;
            // use 'dom' in rather than truthiness.
            return typeof el === 'string' ? Ext.getElementById(el) : 'dom' in el ? el.dom : el;
        },

        /**
         * @member Ext
         * Returns the current document body as an {@link Ext.dom.Element}.
         * @return {Ext.dom.Element} The document body.
         */
        getBody: function() {
            if (!Ext._bodyEl) {
                if (!DOC.body) {
                    throw new Error("[Ext.getBody] document.body does not yet exist");
                }

                Ext._bodyEl = Ext.get(DOC.body);
                Ext._bodyEl.skipGarbageCollection = true;
            }

            return Ext._bodyEl;
        },

        /**
         * @member Ext
         * Returns the current document head as an {@link Ext.dom.Element}.
         * @return {Ext.dom.Element} The document head.
         */
        getHead: function() {
            if (!Ext._headEl) {
                Ext._headEl = Ext.get(DOC.head || DOC.getElementsByTagName('head')[0]);
                Ext._headEl.skipGarbageCollection = true;
            }

            return Ext._headEl;
        },

        /**
         * @member Ext
         * Returns the current HTML document object as an {@link Ext.dom.Element}.
         * Typically used for attaching event listeners to the document.  Note: since
         * the document object is not an HTMLElement many of the Ext.dom.Element methods
         * are not applicable and may throw errors if called on the returned
         * Element instance.
         * @return {Ext.dom.Element} The document.
         */
        getDoc: function() {
            if (!Ext._docEl) {
                Ext._docEl = Ext.get(DOC);
                Ext._docEl.skipGarbageCollection = true;
            }

            return Ext._docEl;
        },

        /**
         * @member Ext
         * Returns the current window object as an {@link Ext.dom.Element}.
         * Typically used for attaching event listeners to the window.  Note: since
         * the window object is not an HTMLElement many of the Ext.dom.Element methods
         * are not applicable and may throw errors if called on the returned
         * Element instance.
         * @return {Ext.dom.Element} The window.
         */
        getWin: function() {
            if (!Ext._winEl) {
                Ext._winEl = Ext.get(window);
                Ext._winEl.skipGarbageCollection = true;
            }

            return Ext._winEl;
        },

        /**
         * @member Ext
         * Removes an HTMLElement from the document.  If the HTMLElement was previously
         * cached by a call to Ext.get(), removeNode will call the {@link Ext.Element#destroy
         * destroy} method of the {@link Ext.dom.Element} instance, which removes all DOM
         * event listeners, and deletes the cache reference.
         * @param {HTMLElement} node The node to remove
         * @method
         */
        removeNode: function(node) {
            node = node.dom || node;

            /* eslint-disable-next-line vars-on-top */
            var id = node && node.id,
                el = Ext.cache[id],
                parent;

            if (el) {
                el.destroy();
            }
            else if (node && (node.nodeType === 3 || node.tagName.toUpperCase() !== 'BODY')) {
                parent = node.parentNode;

                if (parent) {
                    parent.removeChild(node);
                }
            }
        }
    });

    // TODO: make @inline work - SDKTOOLS-686
    // @inline
    Ext.isGarbage = function(dom) {
        // determines if the dom element is in the document or in the detached body element
        // use by collectGarbage and Ext.get()
        return dom &&
            // window, document, documentElement, and body can never be garbage.
            dom.nodeType === 1 && dom.tagName !== 'BODY' && dom.tagName !== 'HTML' &&
            // if the element does not have a parent node, it is definitely not in the
            // DOM - we can exit immediately
            (!dom.parentNode ||
            // If the element has an offsetParent we can bail right away, it is
            // definitely in the DOM. If offsetParent is null, the element is detached.
            // If offsetParent is undefined, the element doesn't support offsetParent
            // (e.g. SVGElement) and is not necessarily garbage; parentNode check above
            // should be sufficient in this case.
            (dom.offsetParent === null &&
            // if the element does not have an offsetParent it can mean the element is
            // either not in the dom or it is hidden.  The next step is to check to see
            // if it can be found by id using either document.all or getElementById(),
            // whichever is faster for the current browser.  Normally we would not
            // include IE-specific checks in the core package, however,  in this
            // case the function will be inlined and therefore cannot be overridden in
            // the ext package.
                ((Ext.isIE8 ? DOC.all[dom.id] : DOC.getElementById(dom.id)) !== dom) &&
                // finally if the element was not found in the dom by id, we need to check
                // the detachedBody element
                !(Ext.detachedBodyEl && Ext.detachedBodyEl.isAncestor(dom))));
    };

    Ext.onInternalReady(function() {
        var bodyCls = [],
            theme;

        // Element.unselectable relies on this listener to prevent selection in IE. Some other
        // browsers support the event too but it is only strictly required for IE. In WebKit
        // this listener causes subtle differences to how the browser handles the non-selection,
        // e.g. whether or not the mouse cursor changes when attempting to select text.
        Ext.getDoc().on('selectstart', function(ev, dom) {
            var selectableCls = Element.selectableCls,
                unselectableCls = Element.unselectableCls,
                tagName = dom && dom.tagName,
                el = new Ext.dom.Fly();

            tagName = tagName && tagName.toLowerCase();

            // Element.unselectable is not really intended to handle selection within text fields
            // and it is important that fields inside menus or panel headers don't inherit
            // the unselectability. In most browsers this is automatic but in IE 9 the selectstart
            // event can bubble up from text fields so we have to explicitly handle that case.
            if (tagName === 'input' || tagName === 'textarea') {
                return;
            }

            // Walk up the DOM checking the nodes. This may be 'slow' but selectstart events
            // don't fire very often
            while (dom && dom.nodeType === 1 && dom !== DOC.documentElement) {
                el.attach(dom);

                // If the node has the class x-selectable then stop looking, the text selection
                // is allowed
                if (el.hasCls(selectableCls)) {
                    return;
                }

                // If the node has class x-unselectable then the text selection needs to be stopped
                if (el.hasCls(unselectableCls)) {
                    ev.stopEvent();

                    return;
                }

                dom = dom.parentNode;
            }
        });

        if (Ext.os.is.Android || (Ext.os.is.Windows && Ext.supports.Touch)) {
            // Some mobile devices (android and windows) fire window resize events
            // When the virtual keyboard is displayed. This causes unexpected visual
            // results due to extra layouts of the viewport.  Here we attach a couple
            // of event listeners that will help us detect if the virtual keyboard
            // is open so tha getViewportWidth/getViewportHeight can report the
            // original size as the viewport size while the keyboard is open
            var win = Ext.getWin(); // eslint-disable-line vars-on-top, one-var

            Element._documentWidth = Element._viewportWidth = docEl.clientWidth;
            Element._documentHeight = Element._viewportHeight = docEl.clientHeight;

            win.on({
                // Focus in/out listeners track the last focus change so we can detect
                // the proximity of the last focus change relative to window resize events
                // alowing us to guess with reasonable certainty that a virtual keyboard
                // is being shown.
                focusin: '_onWindowFocusChange',
                focusout: '_onWindowFocusChange',
                // This pointerup listener is needed because in windowsthe virtual keyboard
                // can be hidden manually while the editable element retains focus by tapping
                // a hide button on the virtual keyboard itself. The virtual keyboard can then
                // be re-shown by tapping on the editable element.  In this case the editable
                // element does not fire a focusin event since it already has the focus, but
                // we still need to track that an event occurred which will cause the virtual
                // keyboard to show momentarily.
                pointerup: '_onWindowFocusChange',
                capture: true,
                delegated: false,
                delay: 1,
                scope: Element
            });

            win.on({
                // Resize listener for tracking virtual keyboard state.
                resize: '_onWindowResize',
                priority: 2000,
                scope: Element
            });
        }

        if (supports.Touch) {
            bodyCls.push(Ext.baseCSSPrefix + 'touch');
        }

        if (Ext.isIE && Ext.isIE9m) {
            bodyCls.push(Ext.baseCSSPrefix + 'ie', Ext.baseCSSPrefix + 'ie9m');

            // very often CSS needs to do checks like "IE7+" or "IE6 or 7". To help
            // reduce the clutter (since CSS/SCSS cannot do these tests), we add some
            // additional classes:
            //
            //      x-ie7p      : IE7+      :  7 <= ieVer
            //      x-ie7m      : IE7-      :  ieVer <= 7
            //      x-ie8p      : IE8+      :  8 <= ieVer
            //      x-ie8m      : IE8-      :  ieVer <= 8
            //      x-ie9p      : IE9+      :  9 <= ieVer
            //      x-ie78      : IE7 or 8  :  7 <= ieVer <= 8
            //
            bodyCls.push(Ext.baseCSSPrefix + 'ie8p');

            if (Ext.isIE8) {
                bodyCls.push(Ext.baseCSSPrefix + 'ie8');
            }
            else {
                bodyCls.push(Ext.baseCSSPrefix + 'ie9', Ext.baseCSSPrefix + 'ie9p');
            }

            if (Ext.isIE8m) {
                bodyCls.push(Ext.baseCSSPrefix + 'ie8m');
            }
        }

        if (Ext.isIE10) {
            bodyCls.push(Ext.baseCSSPrefix + 'ie10');
        }

        if (Ext.isIE10p) {
            bodyCls.push(Ext.baseCSSPrefix + 'ie10p');
        }

        if (Ext.isIE11) {
            bodyCls.push(Ext.baseCSSPrefix + 'ie11');
        }

        if (Ext.isEdge) {
            bodyCls.push(Ext.baseCSSPrefix + 'edge');
        }

        if (Ext.isGecko) {
            bodyCls.push(Ext.baseCSSPrefix + 'gecko');
        }

        if (Ext.isOpera) {
            bodyCls.push(Ext.baseCSSPrefix + 'opera');
        }

        if (Ext.isOpera12m) {
            bodyCls.push(Ext.baseCSSPrefix + 'opera12m');
        }

        if (Ext.isWebKit) {
            bodyCls.push(Ext.baseCSSPrefix + 'webkit');
        }

        if (Ext.isSafari) {
            bodyCls.push(Ext.baseCSSPrefix + 'safari');
        }

        if (Ext.isSafari9) {
            bodyCls.push(Ext.baseCSSPrefix + 'safari9');
        }

        if (Ext.isSafari10) {
            bodyCls.push(Ext.baseCSSPrefix + 'safari10');
        }

        if (Ext.isSafari) {
            if (Ext.browser.version.isLessThan(11)) {
                bodyCls.push(Ext.baseCSSPrefix + 'safari10m');
            }

            if (Ext.browser.version.isLessThan(9)) {
                bodyCls.push(Ext.baseCSSPrefix + 'safari8m');
            }
        }

        if (Ext.isChrome) {
            bodyCls.push(Ext.baseCSSPrefix + 'chrome');
        }

        if (Ext.isMac) {
            bodyCls.push(Ext.baseCSSPrefix + 'mac');
        }

        if (Ext.isWindows) {
            bodyCls.push(Ext.baseCSSPrefix + 'windows');
        }

        if (Ext.isLinux) {
            bodyCls.push(Ext.baseCSSPrefix + 'linux');
        }

        if (!supports.CSS3BorderRadius) {
            bodyCls.push(Ext.baseCSSPrefix + 'nbr');
        }

        if (!supports.CSS3LinearGradient) {
            bodyCls.push(Ext.baseCSSPrefix + 'nlg');
        }

        if (supports.Touch) {
            bodyCls.push(Ext.baseCSSPrefix + 'touch');
        }

        if (Ext.os.deviceType) {
            bodyCls.push(Ext.baseCSSPrefix + Ext.os.deviceType.toLowerCase());
        }

        if (Ext.os.is.BlackBerry) {
            bodyCls.push(Ext.baseCSSPrefix + 'bb');

            if (Ext.browser.userAgent.match(/Kbd/gi)) {
                // blackberry with physical keyboard
                bodyCls.push(Ext.baseCSSPrefix + 'bb-keyboard');
            }
        }

        if (Ext.os.is.iOS && Ext.isSafari) {
            bodyCls.push(Ext.baseCSSPrefix + 'mobile-safari');
        }

        if (Ext.os.is.iOS && Ext.browser.is.WebView && !Ext.browser.is.Standalone) {
            // ios cordova app
            bodyCls.push(Ext.baseCSSPrefix + 'ios-native');
        }

        if (Ext.supports.FlexBoxBasisBug) {
            bodyCls.push(Ext.baseCSSPrefix + 'has-flexbasis-bug');
        }

        Ext.getBody().addCls(bodyCls);

        theme = Ext.theme;

        if (theme && theme.getDocCls) {
            // hook for theme overrides to add css classes to the <html> element
            Ext.fly(document.documentElement).addCls(theme.getDocCls());
        }
    }, null, { priority: 1500 });
});
