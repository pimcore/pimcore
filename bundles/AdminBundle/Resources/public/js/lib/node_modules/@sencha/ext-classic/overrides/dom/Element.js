/**
 * @class Ext.dom.Element
 * @override Ext.dom.Element
 */

Ext.define('Ext.overrides.dom.Element', (function() {
    var Element, // we cannot do this yet "= Ext.dom.Element"
        WIN = window,
        DOC = document,
        HIDDEN = 'hidden',
        ISCLIPPED = 'isClipped',
        OVERFLOW = 'overflow',
        OVERFLOWX = 'overflow-x',
        OVERFLOWY = 'overflow-y',
        ORIGINALCLIP = 'originalClip',
        HEIGHT = 'height',
        WIDTH = 'width',
        VISIBILITY = 'visibility',
        DISPLAY = 'display',
        NONE = 'none',
        OFFSETS = 'offsets',
        CLIP = 'clip',
        ORIGINALDISPLAY = 'originalDisplay',
        VISMODE = 'visibilityMode',
        ISVISIBLE = 'isVisible',
        OFFSETCLASS = Ext.baseCSSPrefix + 'hidden-offsets',
        CLIPCLASS = Ext.baseCSSPrefix + 'hidden-clip',
        /* eslint-disable indent */
        boxMarkup = [
            '<div class="{0}-tl" role="presentation">',
                '<div class="{0}-tr" role="presentation">',
                    '<div class="{0}-tc" role="presentation"></div>',
                '</div>',
            '</div>',
            '<div class="{0}-ml" role="presentation">',
                '<div class="{0}-mr" role="presentation">',
                    '<div class="{0}-mc" role="presentation"></div>',
                '</div>',
            '</div>',
            '<div class="{0}-bl" role="presentation">',
                '<div class="{0}-br" role="presentation">',
                    '<div class="{0}-bc" role="presentation"></div>',
                '</div>',
            '</div>'
        ].join(''),
        /* eslint-enable indent */
        scriptTagRe = /(?:<script([^>]*)?>)((\n|\r|.)*?)(?:<\/script>)/ig,
        replaceScriptTagRe = /(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/ig,
        srcRe = /\ssrc=(['"])(.*?)\1/i,
        nonSpaceRe = /\S/,
        typeRe = /\stype=(['"])(.*?)\1/i,
        adjustDirect2DTableRe = /table-row|table-.*-group/,
        msRe = /^-ms-/,
        camelRe = /(-[a-z])/gi,
        camelReplaceFn = function(m, a) {
            return a.charAt(1).toUpperCase();
        },
        XMASKED = Ext.baseCSSPrefix + "masked",
        XMASKEDRELATIVE = Ext.baseCSSPrefix + "masked-relative",
        EXTELMASKMSG = Ext.baseCSSPrefix + "mask-msg",
        bodyRe = /^body/i,
        propertyCache = {},
        getVisMode = function(el) {
            var data = el.getData(),
                visMode = data[VISMODE];

            if (visMode === undefined) {
                data[VISMODE] = visMode = Element.VISIBILITY;
            }

            return visMode;
        },
        emptyRange = DOC.createRange ? DOC.createRange() : null,
        syncContentFly;

    //<feature legacyBrowser>
    if (Ext.isIE8) {
        // eslint-disable-next-line vars-on-top
        var garbageBin = DOC.createElement('div'),
            destroyQueue = [],

            // prevent memory leaks in IE8
            // see http://social.msdn.microsoft.com/Forums/ie/en-US/c76967f0-dcf8-47d0-8984-8fe1282a94f5/ie-appendchildremovechild-memory-problem?forum=iewebdevelopment
            // This function is called to fully destroy an element on a timer so that code
            // following the remove call can still access the element.
            clearGarbage,
            clearGarbageFn = function() {
                var len = destroyQueue.length,
                    i;

                for (i = 0; i < len; i++) {
                    garbageBin.appendChild(destroyQueue[i]);
                }

                garbageBin.innerHTML = '';
                destroyQueue.length = 0;
            };

        //<debug>
        clearGarbageFn.$skipTimerCheck = true;
        //</debug>

        clearGarbage = Ext.Function.createBuffered(clearGarbageFn, 10);
    }
    //</feature>

    return {
        override: 'Ext.dom.Element',

        mixins: [
            'Ext.util.Animate'
        ],

        uses: [
            'Ext.dom.GarbageCollector',
            'Ext.dom.Fly',
            'Ext.event.publisher.MouseEnterLeave',
            'Ext.fx.Manager',
            'Ext.fx.Anim'
        ],

        skipGarbageCollection: false,

        _init: function(E) {
            Element = E; // now we can poke this into closure scope

            // We want to expose destroyQueue on the prototype for testing purposes
            //<debug>
            if (WIN.__UNIT_TESTING__) {
                E.destroyQueue = destroyQueue;
            }
            //</debug>
        },

        statics: {
            normalize: function(prop) {
                if (prop === 'float') {
                    prop = Ext.supports.Float ? 'cssFloat' : 'styleFloat';
                }

                // For '-ms-foo' we need msFoo
                return propertyCache[prop] ||
                      (propertyCache[prop] = prop.replace(msRe, 'ms-')
                                                 .replace(camelRe, camelReplaceFn));
            }
        },

        /**
         * Convenience method for constructing a KeyMap
         * @param {String/Number/Number[]/Object} key Either a string with the keys to listen for,
         * the numeric key code, array of key codes or an object with the following options:
         * @param {Number/Array} key.key
         * @param {Boolean} key.shift
         * @param {Boolean} key.ctrl
         * @param {Boolean} key.alt
         * @param {Function} fn The function to call
         * @param {Object} [scope] The scope (`this` reference) in which the specified function
         * is executed. Defaults to this Element.
         * @return {Ext.util.KeyMap} The KeyMap created
         */
        addKeyListener: function(key, fn, scope) {
            var config;

            if (typeof key !== 'object' || Ext.isArray(key)) {
                config = {
                    target: this,
                    key: key,
                    fn: fn,
                    scope: scope
                };
            }
            else {
                config = {
                    target: this,
                    key: key.key,
                    shift: key.shift,
                    ctrl: key.ctrl,
                    alt: key.alt,
                    fn: fn,
                    scope: scope
                };
            }

            return new Ext.util.KeyMap(config);
        },

        /**
         * Creates a KeyMap for this element
         * @param {Object} config The KeyMap config. See {@link Ext.util.KeyMap} for more details
         * @return {Ext.util.KeyMap} The KeyMap created
         */
        addKeyMap: function(config) {
            return new Ext.util.KeyMap(Ext.apply({
                target: this
            }, config));
        },

        /**
         * @private
         * Returns the fractional portion of this element's measurement in the given dimension.
         * (IE9+ only)
         * @return {Number}
         */
        adjustDirect2DDimension: function(dimension) {
            var me = this,
                dom = me.dom,
                display = me.getStyle('display'),
                inlineDisplay = dom.style.display,
                inlinePosition = dom.style.position,
                originIndex = dimension === WIDTH ? 0 : 1,
                currentStyle = dom.currentStyle,
                floating;

            if (display === 'inline') {
                dom.style.display = 'inline-block';
            }

            dom.style.position = display.match(adjustDirect2DTableRe) ? 'absolute' : 'static';

            // floating will contain digits that appears after the decimal point
            // if height or width are set to auto we fallback to msTransformOrigin calculation

            // Use currentStyle here instead of getStyle. In some difficult to reproduce
            // instances it resets the scrollWidth of the element
            floating =
                (parseFloat(currentStyle[dimension]) ||
                parseFloat(currentStyle.msTransformOrigin.split(' ')[originIndex]) * 2) % 1;

            dom.style.position = inlinePosition;

            if (display === 'inline') {
                dom.style.display = inlineDisplay;
            }

            return floating;
        },

        /**
         * @private
         */
        afterAnimate: function() {
            var shadow = this.shadow;

            if (shadow && !shadow.disabled && !shadow.animate) {
                shadow.show();
            }
        },

        /**
         * @private
         */
        anchorAnimX: function(anchor) {
            var xName = (anchor === 'l') ? 'right' : 'left';

            this.dom.style[xName] = '0px';
        },

        /**
         * @private
         * process the passed fx configuration.
         */
        anim: function(config) {
            if (!Ext.isObject(config)) {
                return (config) ? {} : false;
            }

            // eslint-disable-next-line vars-on-top
            var me = this,
                duration = config.duration || Ext.fx.Anim.prototype.duration,
                easing = config.easing || 'ease',
                animConfig;

            if (config.stopAnimation) {
                me.stopAnimation();
            }

            Ext.applyIf(config, Ext.fx.Manager.getFxDefaults(me.id));

            // Clear any 'paused' defaults.
            Ext.fx.Manager.setFxDefaults(me.id, {
                delay: 0
            });

            animConfig = {
                // Pass the DOM reference. That's tested first so will be converted
                // to an Ext.fx.Target fastest.
                target: me.dom,
                remove: config.remove,
                alternate: config.alternate || false,
                duration: duration,
                easing: easing,
                callback: config.callback,
                listeners: config.listeners,
                iterations: config.iterations || 1,
                scope: config.scope,
                block: config.block,
                concurrent: config.concurrent,
                delay: config.delay || 0,
                paused: true,
                keyframes: config.keyframes,
                from: config.from || {},
                to: Ext.apply({}, config),
                userConfig: config
            };

            Ext.apply(animConfig.to, config.to);

            // Anim API properties - backward compat
            delete animConfig.to.to;
            delete animConfig.to.from;
            delete animConfig.to.remove;
            delete animConfig.to.alternate;
            delete animConfig.to.keyframes;
            delete animConfig.to.iterations;
            delete animConfig.to.listeners;
            delete animConfig.to.target;
            delete animConfig.to.paused;
            delete animConfig.to.callback;
            delete animConfig.to.scope;
            delete animConfig.to.duration;
            delete animConfig.to.easing;
            delete animConfig.to.concurrent;
            delete animConfig.to.block;
            delete animConfig.to.stopAnimation;
            delete animConfig.to.delay;

            return animConfig;
        },

        /**
         * Calls `{@link #addAnimation}` and returns this Element (for call chaining). For
         * details, see `{@link #addAnimation}`.
         *
         * @param {Object} config  Configuration for {@link Ext.fx.Anim}.
         * Note that the {@link Ext.fx.Anim#to to} config is required.
         * @return {Ext.dom.Element} this
         */
        animate: function(config) {
            this.addAnimation(config);

            return this;
        },

        /**
         * Starts a custom animation on this Element.
         *
         * The following properties may be specified in `from`, `to`, and `keyframe` objects:
         *
         *   - `x` - The page X position in pixels.
         *   - `y` - The page Y position in pixels
         *   - `left` - The element's CSS `left` value. Units must be supplied.
         *   - `top` - The element's CSS `top` value. Units must be supplied.
         *   - `width` - The element's CSS `width` value. Units must be supplied.
         *   - `height` - The element's CSS `height` value. Units must be supplied.
         *   - `scrollLeft` - The element's `scrollLeft` value.
         *   - `scrollTop` - The element's `scrollTop` value.
         *   - `opacity` - The element's `opacity` value (between `0` and `1`).
         *
         * **Be aware** that animating an Element which is being used by an Ext Component
         * without in some way informing the Component about the changed element state will
         * result in incorrect Component behaviour. This is because the Component will be
         * using the old state of the element. To avoid this problem, it is now possible
         * to directly animate certain properties of Components.
         *
         * @param {Object} config  Configuration for {@link Ext.fx.Anim}.
         * Note that the {@link Ext.fx.Anim#to to} config is required.
         * @return {Ext.fx.Anim} The new animation.
         */
        addAnimation: function(config) {
            var me = this,
                animId = me.dom.id || Ext.id(me.dom),
                listeners, anim, end;

            if (!Ext.fx.Manager.hasFxBlock(animId)) {
                // Bit of gymnastics here to ensure our internal listeners get bound first
                if (config.listeners) {
                    listeners = config.listeners;
                    delete config.listeners;
                }

                if (config.internalListeners) {
                    config.listeners = config.internalListeners;
                    delete config.internalListeners;
                }

                end = config.autoEnd;
                delete config.autoEnd;

                anim = new Ext.fx.Anim(me.anim(config));
                anim.on({
                    afteranimate: 'afterAnimate',
                    beforeanimate: 'beforeAnimate',
                    scope: me,
                    single: true
                });

                if (listeners) {
                    anim.on(listeners);
                }

                Ext.fx.Manager.queueFx(anim);

                if (end) {
                    anim.jumpToEnd();
                }
            }

            return anim;
        },

        /**
         * @private
         */
        beforeAnimate: function() {
            var shadow = this.shadow;

            if (shadow && !shadow.disabled && !shadow.animate) {
                shadow.hide();
            }
        },

        /**
         * Wraps the specified element with a special 9 element markup/CSS block that renders
         * by default as a gray container with a gradient background, rounded corners
         * and a 4-way shadow.
         *
         * This special markup is used throughout Ext when box wrapping elements
         * ({@link Ext.button.Button}, {@link Ext.panel.Panel} when
         * {@link Ext.panel.Panel#frame frame=true}, {@link Ext.window.Window}).
         * The markup is of this form:
         *
         *     <div class="{0}-tl"><div class="{0}-tr"><div class="{0}-tc"></div></div></div>
         *     <div class="{0}-ml"><div class="{0}-mr"><div class="{0}-mc"></div></div></div>
         *     <div class="{0}-bl"><div class="{0}-br"><div class="{0}-bc"></div></div></div>
         *
         * Example usage:
         *
         *     // Basic box wrap
         *     Ext.get("foo").boxWrap();
         *
         *     // You can also add a custom class and use CSS inheritance rules to customize
         *     // the box look.
         *     // 'x-box-blue' is a built-in alternative -- look at the related CSS definitions
         *     // as an example for how to create a custom box wrap style.
         *     Ext.get("foo").boxWrap().addCls("x-box-blue");
         *
         * @param {String} [cls='x-box'] A base CSS class to apply to the containing wrapper
         * element. Note that there are a number of CSS rules that are dependent on this name
         * to make the overall effect work, so if you supply an alternate base class, make sure
         * you also supply all of the necessary rules.
         * @return {Ext.dom.Element} The outermost wrapping element of the created box structure.
         */
        boxWrap: function(cls) {
            var el;

            cls = cls || Ext.baseCSSPrefix + 'box';
            el = Ext.get(this.insertHtml(
                "beforeBegin",
                "<div class='" + cls + "' role='presentation'>" +
                    Ext.String.format(boxMarkup, cls) + "</div>")
            );

            el.selectNode('.' + cls + '-mc').appendChild(this.dom);

            return el;
        },

        /**
         * Removes Empty, or whitespace filled text nodes. Combines adjacent text nodes.
         * @param {Boolean} [forceReclean=false] By default the element keeps track if it has been
         * cleaned already so you can call this over and over. However, if you update the element
         * and need to force a re-clean, you can pass true.
         */
        clean: function(forceReclean) {
            var me = this,
                dom = me.dom,
                data = me.getData(),
                n = dom.firstChild,
                ni = -1,
                nx;

            if (data.isCleaned && forceReclean !== true) {
                return me;
            }

            while (n) {
                nx = n.nextSibling;

                if (n.nodeType === 3) {
                    // Remove empty/whitespace text nodes
                    if (!(nonSpaceRe.test(n.nodeValue))) {
                        dom.removeChild(n);
                    }
                    // Combine adjacent text nodes
                    else if (nx && nx.nodeType === 3) {
                        n.appendData(Ext.String.trim(nx.data));
                        dom.removeChild(nx);
                        nx = n.nextSibling;
                        n.nodeIndex = ++ni;
                    }
                }
                else {
                    // Recursively clean
                    Ext.fly(n, '_clean').clean();
                    n.nodeIndex = ++ni;
                }

                n = nx;
            }

            data.isCleaned = true;

            return me;
        },

        /**
         * @method
         * Empties this element. Removes all child nodes.
         */
        empty: emptyRange
            ? function() {
                var dom = this.dom;

                if (dom.firstChild) {
                    emptyRange.setStartBefore(dom.firstChild);
                    emptyRange.setEndAfter(dom.lastChild);
                    emptyRange.deleteContents();
                }
            }
            : function() {
                var dom = this.dom;

                while (dom.lastChild) {
                    dom.removeChild(dom.lastChild);
                }
            },

        clearListeners: function() {
            this.removeAnchor();
            this.callParent();
        },

        /**
         * Clears positioning back to the default when the document was loaded.
         * @param {String} [value=''] The value to use for the left, right, top, bottom.
         * You could use 'auto'.
         * @return {Ext.dom.Element} this
         */
        clearPositioning: function(value) {
            value = value || '';

            return this.setStyle({
                left: value,
                right: value,
                top: value,
                bottom: value,
                'z-index': '',
                position: 'static'
            });
        },

        /**
         * Creates a proxy element of this element
         * @param {String/Object} config The class name of the proxy element or a DomHelper config
         * object
         * @param {String/HTMLElement} [renderTo] The element or element id to render the proxy to.
         * Defaults to: document.body.
         * @param {Boolean} [matchBox=false] True to align and size the proxy to this element now.
         * @return {Ext.dom.Element} The new proxy element
         */
        createProxy: function(config, renderTo, matchBox) {
            config = (typeof config === 'object')
                ? config
                : { tag: "div", role: 'presentation', cls: config };

            // eslint-disable-next-line vars-on-top
            var me = this,
                proxy = renderTo
                    ? Ext.DomHelper.append(renderTo, config, true)
                    : Ext.DomHelper.insertBefore(me.dom, config, true);

            proxy.setVisibilityMode(Element.DISPLAY);
            proxy.hide();

            // check to make sure Element_position.js is loaded
            if (matchBox && me.setBox && me.getBox) {
                proxy.setBox(me.getBox());
            }

            return proxy;
        },

        /**
         * Clears any opacity settings from this element. Required in some cases for IE.
         * @return {Ext.dom.Element} this
         */
        clearOpacity: function() {
            return this.setOpacity('');
        },

        /**
         * Store the current overflow setting and clip overflow on the element - use {@link #unclip}
         * to remove
         * @return {Ext.dom.Element} this
         */
        clip: function() {
            var me = this,
                data = me.getData(),
                style;

            if (!data[ISCLIPPED]) {
                data[ISCLIPPED] = true;

                style = me.getStyle([OVERFLOW, OVERFLOWX, OVERFLOWY]);

                data[ORIGINALCLIP] = {
                    o: style[OVERFLOW],
                    x: style[OVERFLOWX],
                    y: style[OVERFLOWY]
                };

                me.setStyle(OVERFLOW, HIDDEN);
                me.setStyle(OVERFLOWX, HIDDEN);
                me.setStyle(OVERFLOWY, HIDDEN);
            }

            return me;
        },

        destroy: function() {
            var me = this,
                dom = me.dom,
                data = me.peekData(),
                maskEl, maskMsg;

            if (dom) {
                if (me.isAnimate) {
                    me.stopAnimation(true);
                }

                me.removeAnchor();
            }

            if (me.deferredFocusTimer) {
                Ext.undefer(me.deferredFocusTimer);
                me.deferredFocusTimer = null;
            }

            me.callParent();

            //<feature legacyBrowser>
            // prevent memory leaks in IE8
            // see http://social.msdn.microsoft.com/Forums/ie/en-US/c76967f0-dcf8-47d0-8984-8fe1282a94f5/ie-appendchildremovechild-memory-problem?forum=iewebdevelopment
            // must not be document, documentElement, body or window object
            // Have to use != instead of !== for IE8 or it will not recognize that the window
            // objects are equal
            // eslint-disable-next-line eqeqeq
            if (dom && Ext.isIE8 && (dom.window != dom) && (dom.nodeType !== 9) &&
                    (dom.tagName !== 'BODY') && (dom.tagName !== 'HTML')) {
                destroyQueue[destroyQueue.length] = dom;

                // Will perform extra IE8 cleanup in 10 milliseconds
                // see http://social.msdn.microsoft.com/Forums/ie/en-US/c76967f0-dcf8-47d0-8984-8fe1282a94f5/ie-appendchildremovechild-memory-problem?forum=iewebdevelopment
                clearGarbage();
            }
            //</feature>

            if (data) {
                maskEl = data.maskEl;
                maskMsg = data.maskMsg;

                if (maskEl) {
                    maskEl.destroy();
                }

                if (maskMsg) {
                    maskMsg.destroy();
                }
            }
        },

        /**
         * Convenience method for setVisibilityMode(Element.DISPLAY).
         * @param {String} [display] What to set display to when visible
         * @return {Ext.dom.Element} this
         */
        enableDisplayMode: function(display) {
            var me = this;

            me.setVisibilityMode(Element.DISPLAY);

            if (display !== undefined) {
                me.getData()[ORIGINALDISPLAY] = display;
            }

            return me;
        },

        /**
         * Fade an element in (from transparent to opaque). The ending opacity can be specified
         * using the `opacity` config option. Usage:
         *
         *     // default: fade in from opacity 0 to 100%
         *     el.fadeIn();
         *
         *     // custom: fade in from opacity 0 to 75% over 2 seconds
         *     el.fadeIn({ opacity: .75, duration: 2000});
         *
         *     // common config options shown with default values
         *     el.fadeIn({
         *         opacity: 1, //can be any value between 0 and 1 (e.g. .5)
         *         easing: 'easeOut',
         *         duration: 500
         *     });
         *
         * @param {Object} options (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        fadeIn: function(options) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly();

            me.animate(Ext.apply({}, options, {
                opacity: 1,
                internalListeners: {
                    beforeanimate: function(anim) {
                        // Reattach to the DOM in case the caller animated a Fly
                        // in which case the dom reference will have changed by now.
                        animFly.attach(dom);

                        // restore any visibility/display that may have 
                        // been applied by a fadeout animation
                        if (animFly.isStyle('display', 'none')) {
                            animFly.setDisplayed('');
                        }
                        else {
                            animFly.show();
                        }
                    }
                }
            }));

            return this;
        },

        /**
         * Fade an element out (from opaque to transparent). The ending opacity can be specified
         * using the `opacity` config option. Note that IE may require `useDisplay: true` in order
         * to redisplay correctly. Usage:
         *
         *     // default: fade out from the element's current opacity to 0
         *     el.fadeOut();
         *
         *     // custom: fade out from the element's current opacity to 25% over 2 seconds
         *     el.fadeOut({ opacity: .25, duration: 2000});
         *
         *     // common config options shown with default values
         *     el.fadeOut({
         *         opacity: 0, //can be any value between 0 and 1 (e.g. .5)
         *         easing: 'easeOut',
         *         duration: 500,
         *         remove: false,
         *         useDisplay: false
         *     });
         *
         * @param {Object} options (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        fadeOut: function(options) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly();

            options = Ext.apply({
                opacity: 0,
                internalListeners: {
                    afteranimate: function(anim) {
                        if (anim.to.opacity === 0) {
                            // Reattach to the DOM in case the caller animated a Fly
                            // in which case the dom reference will have changed by now.
                            animFly.attach(dom);

                            // Reattach to the DOM in case the caller animated a Fly
                            // in which case the dom reference will have changed by now.
                            animFly.attach(dom);

                            if (options.useDisplay) {
                                animFly.setDisplayed(false);
                            }
                            else {
                                animFly.hide();
                            }
                        }
                    }
                }
            }, options);

            me.animate(options);

            return me;
        },

        /**
         * @private
         */
        fixDisplay: function() {
            var me = this;

            if (me.isStyle(DISPLAY, NONE)) {
                me.setStyle(VISIBILITY, HIDDEN);
                me.setStyle(DISPLAY, me._getDisplay()); // first try reverting to default

                if (me.isStyle(DISPLAY, NONE)) { // if that fails, default to block
                    me.setStyle(DISPLAY, "block");
                }
            }
        },

        /**
         * Shows a ripple of exploding, attenuating borders to draw attention to an Element. Usage:
         *
         *     // default: a single light blue ripple
         *     el.frame();
         *
         *     // custom: 3 red ripples lasting 3 seconds total
         *     el.frame("#ff0000", 3, { duration: 3000 });
         *
         *     // common config options shown with default values
         *     el.frame("#C3DAF9", 1, {
         *         duration: 1000 // duration of each individual ripple.
         *         // Note: Easing is not configurable and will be ignored if included
         *     });
         *
         * @param {String} [color='#C3DAF9'] The hex color value for the border.
         * @param {Number} [count=1] The number of ripples to display.
         * @param {Object} [obj] Object literal with any of the {@link Ext.fx.Anim} config options
         * @return {Ext.dom.Element} The Element
         */
        frame: function(color, count, obj) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly(),
                beforeAnim;

            color = color || '#C3DAF9';
            count = count || 1;
            obj = obj || {};

            beforeAnim = function() {
                var animScope = this,
                    box, proxy, proxyAnim;

                // Reattach to the DOM in case the caller animated a Fly
                // in which case the dom reference will have changed by now.
                animFly.attach(dom);
                animFly.show();

                box = animFly.getBox();
                proxy = Ext.getBody().createChild({
                    role: 'presentation',
                    id: animFly.dom.id + '-anim-proxy',
                    style: {
                        position: 'absolute',
                        'pointer-events': 'none',
                        'z-index': 35000,
                        border: '0px solid ' + color
                    }
                });

                proxyAnim = new Ext.fx.Anim({
                    target: proxy,
                    duration: obj.duration || 1000,
                    iterations: count,
                    from: {
                        top: box.y,
                        left: box.x,
                        borderWidth: 0,
                        opacity: 1,
                        height: box.height,
                        width: box.width
                    },
                    to: {
                        top: box.y - 20,
                        left: box.x - 20,
                        borderWidth: 10,
                        opacity: 0,
                        height: box.height + 40,
                        width: box.width + 40
                    }
                });
                proxyAnim.on('afteranimate', function() {
                    proxy.destroy();

                    // kill the no-op element animation created below
                    animScope.end();
                });
            };

            me.animate({
                // See "A Note About Wrapped Animations" at the top of this class:
                duration: (Math.max(obj.duration, 500) * 2) || 2000,
                listeners: {
                    beforeanimate: {
                        fn: beforeAnim
                    }
                },
                callback: obj.callback,
                scope: obj.scope
            });

            return me;
        },

        /**
         * Return the CSS color for the specified CSS attribute. rgb, 3 digit (like `#fff`)
         * and valid values are convert to standard 6 digit hex color.
         * @param {String} attr The css attribute
         * @param {String} defaultValue The default value to use when a valid color isn't found
         * @param {String} [prefix] defaults to #. Use an empty string when working with
         * color anims.
         * @private
         */
        getColor: function(attr, defaultValue, prefix) {
            var v = this.getStyle(attr),
                color = prefix || prefix === '' ? prefix : '#',
                h, len, i;

            if (!v || (/transparent|inherit/.test(v))) {
                return defaultValue;
            }

            if (/^r/.test(v)) {
                v = v.slice(4, v.length - 1).split(',');
                len = v.length;

                for (i = 0; i < len; i++) {
                    h = parseInt(v[i], 10);
                    color += (h < 16 ? '0' : '') + h.toString(16);
                }
            }
            else {
                v = v.replace('#', '');
                color += v.length === 3 ? v.replace(/^(\w)(\w)(\w)$/, '$1$1$2$2$3$3') : v;
            }

            return (color.length > 5 ? color.toLowerCase() : defaultValue);
        },

        /**
         * Gets this element's {@link Ext.ElementLoader ElementLoader}
         * @return {Ext.ElementLoader} The loader
         */
        getLoader: function() {
            var me = this,
                data = me.getData(),
                loader = data.loader;

            if (!loader) {
                data.loader = loader = new Ext.ElementLoader({
                    target: me
                });
            }

            return loader;
        },

        /**
         * Gets an object with all CSS positioning properties. Useful along with
         * `setPositioning` to get snapshot before performing an update and then restoring
         * the element.
         * @param {Boolean} [autoPx=false] true to return pixel values for "auto" styles.
         * @return {Object}
         */
        getPositioning: function(autoPx) {
            var styles = this.getStyle(['left', 'top', 'position', 'z-index']),
                dom = this.dom;

            if (autoPx) {
                if (styles.left === 'auto') {
                    styles.left = dom.offsetLeft + 'px';
                }

                if (styles.top === 'auto') {
                    styles.top = dom.offsetTop + 'px';
                }
            }

            return styles;
        },

        /**
         * Slides the element while fading it out of view. An anchor point can be optionally passed
         * to set the ending point of the effect. Usage:
         *
         *     // default: slide the element downward while fading out
         *     el.ghost();
         *
         *     // custom: slide the element out to the right with a 2-second duration
         *     el.ghost('r', { duration: 2000 });
         *
         *     // common config options shown with default values
         *     el.ghost('b', {
         *         easing: 'easeOut',
         *         duration: 500
         *     });
         *
         * @param {String} [anchor] One of the valid {@link Ext.fx.Anim} anchor positions
         * (defaults to bottom: 'b')
         * @param {Object} [options] Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        ghost: function(anchor, options) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly(),
                beforeAnim;

            anchor = anchor || "b";

            beforeAnim = function() {
                // Reattach to the DOM in case the caller animated a Fly
                // in which case the dom reference will have changed by now.
                animFly.attach(dom);

                // eslint-disable-next-line vars-on-top
                var width = animFly.getWidth(),
                    height = animFly.getHeight(),
                    xy = animFly.getXY(),
                    position = animFly.getPositioning(),
                    to = {
                        opacity: 0
                    };

                switch (anchor) {
                    case 't':
                        to.y = xy[1] - height;
                        break;

                    case 'l':
                        to.x = xy[0] - width;
                        break;

                    case 'r':
                        to.x = xy[0] + width;
                        break;

                    case 'b':
                        to.y = xy[1] + height;
                        break;

                    case 'tl':
                        to.x = xy[0] - width;
                        to.y = xy[1] - height;
                        break;

                    case 'bl':
                        to.x = xy[0] - width;
                        to.y = xy[1] + height;
                        break;

                    case 'br':
                        to.x = xy[0] + width;
                        to.y = xy[1] + height;
                        break;

                    case 'tr':
                        to.x = xy[0] + width;
                        to.y = xy[1] - height;
                        break;
                }

                this.to = to;
                this.on('afteranimate', function() {
                    // Reattach to the DOM in case the caller animated a Fly
                    // in which case the dom reference will have changed by now.
                    animFly.attach(dom);

                    if (animFly) {
                        animFly.hide();
                        animFly.clearOpacity();
                        animFly.setPositioning(position);
                    }
                });
            };

            me.animate(Ext.applyIf(options || {}, {
                duration: 500,
                easing: 'ease-out',
                listeners: {
                    beforeanimate: beforeAnim
                }
            }));

            return me;
        },

        //<feature legacyBrowser>
        getTextSelection: function() {
            var ret, dom, doc, range, textRange;

            ret = this.callParent();

            if (typeof ret[0] !== 'number') {
                dom = this.dom;
                doc = dom.ownerDocument;
                range = doc.selection.createRange();
                textRange = dom.createTextRange();

                textRange.setEndPoint('EndToStart', range);

                ret[0] = textRange.text.length;
                ret[1] = ret[0] + range.text.length;
            }

            return ret;
        },
        //</feature>

        /**
         * Hide this element - Uses display mode to determine whether to use "display",
         * "visibility", "offsets", or "clip". See {@link #setVisible}.
         * @param {Boolean/Object} [animate] true for the default animation or a standard
         * Element animation config object
         * @return {Ext.dom.Element} this
         */
        hide: function(animate) {
            // hideMode override
            if (typeof animate === 'string') {
                this.setVisible(false, animate);

                return this;
            }

            this.setVisible(false, this.anim(animate));

            return this;
        },

        /**
         * Highlights the Element by setting a color (applies to the background-color by default,
         * but can be changed using the "attr" config option) and then fading back to the original
         * color. If no original color is available, you should provide the "endColor" config option
         * which will be cleared after the animation. Usage:
         *
         *     // default: highlight background to yellow
         *     el.highlight();
         *
         *     // custom: highlight foreground text to blue for 2 seconds
         *     el.highlight("0000ff", { attr: 'color', duration: 2000 });
         *
         *     // common config options shown with default values
         *     el.highlight("ffff9c", {
         *         // can be any valid CSS property (attribute) that supports a color value
         *         attr: "backgroundColor",
         *         endColor: (current color) or "ffffff",
         *         easing: 'easeIn',
         *         duration: 1000
         *     });
         *
         * @param {String} color (optional) The highlight color. Should be a 6 char hex color
         * without the leading # (defaults to yellow: 'ffff9c')
         * @param {Object} options (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        highlight: function(color, options) {
            var me = this,
                dom = me.dom,
                from = {},
                animFly = new Ext.dom.Fly(),
                restore, to, attr, lns, event, fn;

            options = options || {};
            lns = options.listeners || {};
            attr = options.attr || 'backgroundColor';
            from[attr] = color || 'ffff9c';

            if (!options.to) {
                to = {};
                to[attr] = options.endColor || me.getColor(attr, 'ffffff', '');
            }
            else {
                to = options.to;
            }

            // Don't apply directly on lns, since we reference it in our own callbacks below
            options.listeners = Ext.apply(Ext.apply({}, lns), {
                beforeanimate: function() {
                    // Reattach to the DOM in case the caller animated a Fly
                    // in which case the dom reference will have changed by now.
                    animFly.attach(dom);

                    restore = dom.style[attr];
                    animFly.clearOpacity();
                    animFly.show();

                    event = lns.beforeanimate;

                    if (event) {
                        fn = event.fn || event;

                        return fn.apply(event.scope || lns.scope || WIN, arguments);
                    }
                },
                afteranimate: function() {
                    if (dom) {
                        dom.style[attr] = restore;
                    }

                    event = lns.afteranimate;

                    if (event) {
                        fn = event.fn || event;
                        fn.apply(event.scope || lns.scope || WIN, arguments);
                    }
                }
            });

            me.animate(Ext.apply({}, options, {
                duration: 1000,
                easing: 'ease-in',
                from: from,
                to: to
            }));

            return me;
        },

        /**
         * Initializes a {@link Ext.dd.DD} drag drop object for this element.
         * @param {String} group The group the DD object is member of
         * @param {Object} config The DD config object
         * @param {Object} overrides An object containing methods to override/implement
         * on the DD object
         * @return {Ext.dd.DD} The DD object
         */
        initDD: function(group, config, overrides) {
            var dd = new Ext.dd.DD(Ext.id(this.dom), group, config);

            return Ext.apply(dd, overrides);
        },

        /**
         * Initializes a {@link Ext.dd.DDProxy} object for this element.
         * @param {String} group The group the DDProxy object is member of
         * @param {Object} config The DDProxy config object
         * @param {Object} overrides An object containing methods to override/implement
         * on the DDProxy object
         * @return {Ext.dd.DDProxy} The DDProxy object
         */
        initDDProxy: function(group, config, overrides) {
            var dd = new Ext.dd.DDProxy(Ext.id(this.dom), group, config);

            return Ext.apply(dd, overrides);
        },

        /**
         * Initializes a {@link Ext.dd.DDTarget} object for this element.
         * @param {String} group The group the DDTarget object is member of
         * @param {Object} config The DDTarget config object
         * @param {Object} overrides An object containing methods to override/implement
         * on the DDTarget object
         * @return {Ext.dd.DDTarget} The DDTarget object
         */
        initDDTarget: function(group, config, overrides) {
            var dd = new Ext.dd.DDTarget(Ext.id(this.dom), group, config);

            return Ext.apply(dd, overrides);
        },

        /**
         * Returns true if this element is masked. Also re-centers any displayed message
         * within the mask.
         *
         * @param {Boolean} [deep] Go up the DOM hierarchy to determine if any parent
         * element is masked.
         *
         * @return {Boolean}
         */
        isMasked: function(deep) {
            var me = this,
                data = me.getData(),
                maskEl = data.maskEl,
                maskMsg = data.maskMsg,
                hasMask = false,
                parent;

            if (maskEl && maskEl.isVisible()) {
                if (maskMsg) {
                    maskMsg.center(me);
                }

                hasMask = true;
            }
            else if (deep) {
                parent = me.findParentNode();

                if (parent) {
                    return Ext.fly(parent).isMasked(deep);
                }
            }

            return hasMask;
        },

        /**
         * Direct access to the Ext.ElementLoader {@link Ext.ElementLoader#method-load} method.
         * The method takes the same object parameter as {@link Ext.ElementLoader#method-load}
         * @param {Object} options a options object for Ext.ElementLoader
         * {@link Ext.ElementLoader#method-load}
         * @return {Ext.dom.Element} this
         */
        load: function(options) {
            this.getLoader().load(options);

            return this;
        },

        /**
         * Puts a mask over this element to disable user interaction.
         * This method can only be applied to elements which accept child nodes. Use
         * {@link #unmask} to remove the mask.
         *
         * @param {String} [msg] A message to display in the mask
         * @param {String} [msgCls] A css class to apply to the msg element
         * @param {Number} elHeight (private) Passed by AbstractComponent.mask to avoid the need
         * to interrogate the DOM to get the height
         * @return {Ext.dom.Element} The mask element
         */
        mask: function(msg, msgCls, elHeight) {
            var me = this,
                dom = me.dom,
                data = me.getData(),
                maskEl = data.maskEl,
                maskMsg;

            if (!(bodyRe.test(dom.tagName) && me.getStyle('position') === 'static')) {
                me.addCls(XMASKEDRELATIVE);
            }

            // We always needs to recreate the mask since the DOM element may have been re-created
            if (maskEl) {
                maskEl.destroy();
            }

            maskEl = Ext.DomHelper.append(dom, {
                role: 'presentation',
                cls: Ext.baseCSSPrefix + "mask " + Ext.baseCSSPrefix + "border-box",
                children: {
                    role: 'presentation',
                    cls: msgCls ? EXTELMASKMSG + " " + msgCls : EXTELMASKMSG,
                    cn: {
                        tag: 'div',
                        role: 'presentation',
                        cls: Ext.baseCSSPrefix + 'mask-msg-inner',
                        cn: {
                            tag: 'div',
                            role: 'presentation',
                            cls: Ext.baseCSSPrefix + 'mask-msg-text',
                            html: msg || ''
                        }
                    }
                }
            }, true);

            maskMsg = Ext.fly(maskEl.dom.firstChild);

            data.maskEl = maskEl;

            me.addCls(XMASKED);
            maskEl.setDisplayed(true);

            if (typeof msg === 'string') {
                maskMsg.setDisplayed(true);
                maskMsg.center(me);
            }
            else {
                maskMsg.setDisplayed(false);
            }

            if (dom === DOC.body) {
                maskEl.addCls(Ext.baseCSSPrefix + 'mask-fixed');
            }

            // When masking the body, don't touch its tabbable state
            me.saveTabbableState({
                skipSelf: dom === DOC.body
            });

            // ie will not expand full height automatically
            if (Ext.isIE9m && dom !== DOC.body && me.isStyle('height', 'auto')) {
                maskEl.setSize(undefined, elHeight || me.getHeight());
            }

            return maskEl;
        },

        /**
         * Fades the element out while slowly expanding it in all directions. When the effect
         * is completed, the element will be hidden (visibility = 'hidden') but block elements
         * will still take up space in the document. Usage:
         *
         *     // default
         *     el.puff();
         *
         *     // common config options shown with default values
         *     el.puff({
         *         easing: 'easeOut',
         *         duration: 500,
         *         useDisplay: false
         *     });
         *
         * @param {Object} obj (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        puff: function(obj) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly(),
                beforeAnim,
                box = me.getBox(),
                originalStyles;

            originalStyles = me.getStyle(['width', 'height', 'left', 'right', 'top', 'bottom',
                                          'position', 'z-index', 'font-size', 'opacity'], true);

            obj = Ext.applyIf(obj || {}, {
                easing: 'ease-out',
                duration: 500,
                useDisplay: false
            });

            beforeAnim = function() {
                // Reattach to the DOM in case the caller animated a Fly
                // in which case the dom reference will have changed by now.
                animFly.attach(dom);

                animFly.clearOpacity();
                animFly.show();

                this.to = {
                    width: box.width * 2,
                    height: box.height * 2,
                    x: box.x - (box.width / 2),
                    y: box.y - (box.height / 2),
                    opacity: 0,
                    fontSize: '200%'
                };

                this.on('afteranimate', function() {
                    // Reattach to the DOM in case the caller animated a Fly
                    // in which case the dom reference will have changed by now.
                    animFly.attach(dom);

                    if (obj.useDisplay) {
                        animFly.setDisplayed(false);
                    }
                    else {
                        animFly.hide();
                    }

                    animFly.setStyle(originalStyles);
                    Ext.callback(obj.callback, obj.scope);
                });
            };

            me.animate({
                duration: obj.duration,
                easing: obj.easing,
                listeners: {
                    beforeanimate: {
                        fn: beforeAnim
                    }
                }
            });

            return me;
        },

        //<feature legacyBrowser>
        // private
        // used to ensure the mouseup event is captured if it occurs outside of the
        // window in IE9m.  The only reason this method exists, (vs just calling
        // el.dom.setCapture() directly) is so that we can override it to emptyFn
        // during testing because setCapture() can wreak havoc on emulated mouse events
        // http://msdn.microsoft.com/en-us/library/windows/desktop/ms646262(v=vs.85).aspx
        setCapture: function() {
            var dom = this.dom;

            if (Ext.isIE9m && dom.setCapture) {
                dom.setCapture();
            }
        },
        //</feature>

        /**
         * Set the height of this Element.
         * 
         *     // change the height to 200px and animate with default configuration
         *     Ext.fly('elementId').setHeight(200, true);
         *
         *     // change the height to 150px and animate with a custom configuration
         *     Ext.fly('elId').setHeight(150, {
         *         duration : 500, // animation will have a duration of .5 seconds
         *         // will change the content to "finished"
         *         callback: function(){ this.setHtml("finished"); }
         *     });
         *     
         * @param {Number/String} height The new height. This may be one of:
         *
         * - A Number specifying the new height in pixels.
         * - A String used to set the CSS height style. Animation may **not** be used.
         *     
         * @param {Boolean/Object} [animate] a standard Element animation config object or `true`
         * for the default animation (`{duration: 350, easing: 'ease-in'}`)
         * @return {Ext.dom.Element} this
         */
        setHeight: function(height, animate) {
            var me = this;

            if (!animate || !me.anim) {
                me.callParent(arguments);
            }
            else {
                if (!Ext.isObject(animate)) {
                    animate = {};
                }

                me.animate(Ext.applyIf({
                    to: {
                        height: height
                    }
                }, animate));
            }

            return me;
        },

        /**
         * Removes "vertical" state from this element (reverses everything done
         * by {@link #setVertical}).
         * @private
         */
        setHorizontal: function() {
            var me = this,
                cls = me.verticalCls;

            delete me.vertical;

            if (cls) {
                delete me.verticalCls;
                me.removeCls(cls);
            }

            // delete the inverted methods and revert to inheriting from the prototype 
            delete me.setWidth;
            delete me.setHeight;

            if (!Ext.isIE8) {
                delete me.getWidth;
                delete me.getHeight;
            }

            // revert to inheriting styleHooks from the prototype
            delete me.styleHooks;
        },

        /**
         * Updates the *text* value of this element.
         * Replaces the content of this element with a *single text node* containing
         * the passed text.
         * @param {String} text The text to display in this Element.
         */
        updateText: function(text) {
            var me = this,
                dom,
                textNode;

            if (dom) {
                textNode = dom.firstChild;

                if (!textNode || (textNode.nodeType !== 3 || textNode.nextSibling)) {
                    textNode = DOC.createTextNode();
                    me.empty();
                    dom.appendChild(textNode);
                }

                if (text) {
                    textNode.data = text;
                }
            }
        },

        /**
         * Updates the innerHTML of this element, optionally searching for and processing scripts.
         * @param {String} html The new HTML
         * @param {Boolean} [loadScripts] Pass `true` to look for and process scripts.
         * @param {Function} [callback] For async script loading you can be notified
         * when the update completes.
         * @param {Object} [scope=`this`] The scope (`this` reference) in which to execute
         * the callback.
         * 
         * Also used as the scope for any *inline* script source if the `loadScripts` parameter
         * is `true`. Scripts with a `src` attribute cannot be executed in this scope.
         *
         * Defaults to this Element.
         * @return {Ext.dom.Element} this
         */
        setHtml: function(html, loadScripts, callback, scope) {
            var me = this,
                id,
                dom,
                interval;

            if (!me.dom) {
                return me;
            }

            html = html || '';
            dom = me.dom;

            if (loadScripts !== true) {

                // Setting innerHtml changes the DOM and replace all dom nodes
                // with the new html. For IE specifically, all dom child nodes get 
                // destroyed when removed from DOM tree even if DOM is referenced 
                // within some JS file. Thus, before setting innerHTML, remove the 
                // children so that they are not destroyed/removed from DOM tree.

                if (Ext.isIE) {
                    while (dom.firstChild) {
                        dom.removeChild(dom.firstChild);
                    }
                }

                dom.innerHTML = html;
                Ext.callback(callback, me);

                return me;
            }

            id = Ext.id();
            html += '<span id="' + id + '" role="presentation"></span>';

            interval = Ext.interval(function() {
                var hd, match, attrs, srcMatch, typeMatch, el, s;

                if (!(el = DOC.getElementById(id))) {
                    return false;
                }

                Ext.uninterval(interval);
                Ext.removeNode(el);
                hd = Ext.getHead().dom;

                while ((match = scriptTagRe.exec(html))) {
                    attrs = match[1];
                    srcMatch = attrs ? attrs.match(srcRe) : false;

                    if (srcMatch && srcMatch[2]) {
                        s = DOC.createElement("script");
                        s.src = srcMatch[2];
                        typeMatch = attrs.match(typeRe);

                        if (typeMatch && typeMatch[2]) {
                            s.type = typeMatch[2];
                        }

                        hd.appendChild(s);
                    }
                    else if (match[2] && match[2].length > 0) {
                        if (scope) {
                            Ext.functionFactory(match[2]).call(scope);
                        }
                        else {
                            Ext.globalEval(match[2]);
                        }
                    }
                }

                Ext.callback(callback, scope || me);
            }, 20);

            dom.innerHTML = html.replace(replaceScriptTagRe, '');

            return me;
        },

        /**
         * Set the opacity of the element
         * @param {Number} opacity The new opacity. 0 = transparent, .5 = 50% visible,
         * 1 = fully visible, etc
         * @param {Boolean/Object} [animate] a standard Element animation config object or `true`
         * for the default animation (`{duration: 350, easing: 'ease-in'}`)
         * @return {Ext.dom.Element} this
         */
        setOpacity: function(opacity, animate) {
            var me = this;

            if (!me.dom) {
                return me;
            }

            if (!animate || !me.anim) {
                me.setStyle('opacity', opacity);
            }
            else {
                if (typeof animate !== 'object') {
                    animate = {
                        duration: 350,
                        easing: 'ease-in'
                    };
                }

                me.animate(Ext.applyIf({
                    to: {
                        opacity: opacity
                    }
                }, animate));
            }

            return me;
        },

        /**
         * Set positioning with an object returned by `getPositioning`.
         * @param {Object} pc
         * @return {Ext.dom.Element} this
         */
        setPositioning: function(pc) {
            return this.setStyle(pc);
        },

        /**
         * Changes this Element's state to "vertical" (rotated 90 or 270 degrees).
         * This involves inverting the getters and setters for height and width,
         * and applying hooks for rotating getters and setters for border/margin/padding.
         * (getWidth becomes getHeight and vice versa), setStyle and getStyle will
         * also return the inverse when height or width are being operated on.
         * 
         * @param {Number} angle the angle of rotation - either 90 or 270
         * @param {String} cls an optional css class that contains the required
         * styles for switching the element to vertical orientation. Omit this if
         * the element already contains vertical styling.  If cls is provided,
         * it will be removed from the element when {@link #setHorizontal} is called.
         * @private
         */
        setVertical: function(angle, cls) {
            var me = this,
                proto = Element.prototype;

            me.vertical = true;

            if (cls) {
                me.addCls(me.verticalCls = cls);
            }

            me.setWidth = proto.setHeight;
            me.setHeight = proto.setWidth;

            if (!Ext.isIE8) {
                // In browsers that use CSS3 transforms we must invert getHeight and
                // get Width. In IE8 no adjustment is needed because we use
                // a BasicImage filter to rotate the element and the element's
                // offsetWidth and offsetHeight are automatically inverted.
                me.getWidth = proto.getHeight;
                me.getHeight = proto.getWidth;
            }

            // Switch to using the appropriate vertical style hooks
            me.styleHooks =
                (angle === 270) ? proto.verticalStyleHooks270 : proto.verticalStyleHooks90;
        },

        /**
         * Set the size of this Element. If animation is true, both width and height will be
         * animated concurrently.
         * @param {Number/String} width The new width. This may be one of:
         *
         * - A Number specifying the new width in pixels.
         * - A String used to set the CSS width style. Animation may **not** be used.
         * - A size object in the format `{width: widthValue, height: heightValue}`.
         *
         * @param {Number/String} height The new height. This may be one of:
         *
         * - A Number specifying the new height in  pixels.
         * - A String used to set the CSS height style. Animation may **not** be used.
         *
         * @param {Boolean/Object} [animate] a standard Element animation config object or `true`
         * for the default animation (`{duration: 350, easing: 'ease-in'}`)
         *
         * @return {Ext.dom.Element} this
         */
        setSize: function(width, height, animate) {
            var me = this;

            if (Ext.isObject(width)) { // in case of object from getSize()
                animate = height;
                height = width.height;
                width = width.width;
            }

            if (!animate || !me.anim) {
                me.dom.style.width = Element.addUnits(width);
                me.dom.style.height = Element.addUnits(height);

                if (me.shadow || me.shim) {
                    me.syncUnderlays();
                }
            }
            else {
                if (animate === true) {
                    animate = {};
                }

                me.animate(Ext.applyIf({
                    to: {
                        width: width,
                        height: height
                    }
                }, animate));
            }

            return me;
        },

        /**
         * Sets the visibility of the element (see details). If the visibilityMode is set
         * to Element.DISPLAY, it will use the display property to hide the element,
         * otherwise it uses visibility. The default is to hide and show using the
         * visibility property.
         *
         * @param {Boolean} visible Whether the element is visible
         * @param {Boolean/Object} [animate] True for the default animation,
         * or a standard Element animation config object.
         *
         * @return {Ext.dom.Element} this
         */
        setVisible: function(visible, animate) {
            var me = this,
                dom = me.dom,
                animFly,
                visMode = getVisMode(me);

            // hideMode string override
            if (typeof animate === 'string') {
                switch (animate) {
                    case DISPLAY:
                        visMode = Element.DISPLAY;
                        break;

                    case VISIBILITY:
                        visMode = Element.VISIBILITY;
                        break;

                    case OFFSETS:
                        visMode = Element.OFFSETS;
                        break;

                    case CLIP:
                        visMode = Element.CLIP;
                        break;
                }

                me.setVisibilityMode(visMode);
                animate = false;
            }

            if (!animate || !me.anim) {
                if (visMode === Element.DISPLAY) {
                    return me.setDisplayed(visible);
                }
                else if (visMode === Element.OFFSETS) {
                    me[visible ? 'removeCls' : 'addCls'](OFFSETCLASS);
                }
                else if (visMode === Element.CLIP) {
                    me[visible ? 'removeCls' : 'addCls'](CLIPCLASS);
                }
                else if (visMode === Element.VISIBILITY) {
                    me.fixDisplay();
                    // Show by clearing visibility style.
                    // Explicitly setting to "visible" overrides parent visibility setting
                    dom.style.visibility = visible ? '' : HIDDEN;
                }
            }
            else {
                // closure for composites
                if (visible) {
                    me.setOpacity(0.01);
                    me.setVisible(true);
                }

                if (!Ext.isObject(animate)) {
                    animate = {
                        duration: 350,
                        easing: 'ease-in'
                    };
                }

                animFly = new Ext.dom.Fly();

                me.animate(Ext.applyIf({
                    callback: function() {
                        if (!visible) {
                            // Grab the dom again, since the reference may have changed
                            // if we use fly
                            animFly.attach(dom).setVisible(false).setOpacity(1);
                        }
                    },
                    to: {
                        opacity: (visible) ? 1 : 0
                    }
                }, animate));
            }

            me.getData()[ISVISIBLE] = visible;

            if (me.shadow || me.shim) {
                me.setUnderlaysVisible(visible);
            }

            return me;
        },

        /**
         * Set the width of this Element.
         * 
         *     // change the width to 200px and animate with default configuration
         *     Ext.fly('elementId').setWidth(200, true);
         *
         *     // change the width to 150px and animate with a custom configuration
         *     Ext.fly('elId').setWidth(150, {
         *         duration : 500, // animation will have a duration of .5 seconds
         *         // will change the content to "finished"
         *         callback: function(){ this.setHtml("finished"); }
         *     });
         *     
         * @param {Number/String} width The new width. This may be one of:
         *
         * - A Number specifying the new width in pixels.
         * - A String used to set the CSS width style. Animation may **not** be used.
         * 
         * @param {Boolean/Object} [animate] a standard Element animation config object or `true`
         * for the default animation (`{duration: 350, easing: 'ease-in'}`)
         * @return {Ext.dom.Element} this
         */
        setWidth: function(width, animate) {
            var me = this;

            if (!animate || !me.anim) {
                me.callParent(arguments);
            }
            else {
                if (!Ext.isObject(animate)) {
                    animate = {};
                }

                me.animate(Ext.applyIf({
                    to: {
                        width: width
                    }
                }, animate));
            }

            return me;
        },

        setX: function(x, animate) {
            return this.setXY([x, this.getY()], animate);
        },

        setXY: function(xy, animate) {
            var me = this;

            if (!animate || !me.anim) {
                me.callParent([xy]);
            }
            else {
                if (!Ext.isObject(animate)) {
                    animate = {};
                }

                me.animate(Ext.applyIf({ to: { x: xy[0], y: xy[1] } }, animate));
            }

            return this;
        },

        setY: function(y, animate) {
            return this.setXY([this.getX(), y], animate);
        },

        /**
         * Show this element - Uses display mode to determine whether to use "display",
         * "visibility", "offsets", or "clip". See {@link #setVisible}.
         *
         * @param {Boolean/Object} [animate] true for the default animation or a standard
         * Element animation config object.
         *
         * @return {Ext.dom.Element} this
         */
        show: function(animate) {
            // hideMode override
            if (typeof animate === 'string') {
                this.setVisible(true, animate);

                return this;
            }

            this.setVisible(true, this.anim(animate));

            return this;
        },

        /**
         * Slides the element into view. An anchor point can be optionally passed to set the point
         * of origin for the slide effect. This function automatically handles wrapping the element
         * with a fixed-size container if needed. See the {@link Ext.fx.Anim} class overview
         * for valid anchor point options. Usage:
         *
         *     // default: slide the element in from the top
         *     el.slideIn();
         *
         *     // custom: slide the element in from the right with a 2-second duration
         *     el.slideIn('r', { duration: 2000 });
         *
         *     // common config options shown with default values
         *     el.slideIn('t', {
         *         easing: 'easeOut',
         *         duration: 500
         *     });
         *
         * @param {String} [anchor] One of the valid {@link Ext.fx.Anim} anchor positions
         * (defaults to top: 't')
         * @param {Object} [options] Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @param {Boolean} options.preserveScroll Set to true if preservation of any descendant
         * elements' `scrollTop` values is required. By default the DOM wrapping operation
         * performed by `slideIn` and `slideOut` causes the browser to lose all scroll positions.
         * @param {Boolean} slideOut
         * @return {Ext.dom.Element} The Element
         */
        slideIn: function(anchor, options, slideOut) {
            var me = this,
                dom = me.dom,
                elStyle = dom.style,
                animFly = new Ext.dom.Fly(),
                beforeAnim,
                wrapAnim,
                restoreScroll,
                wrapDomParentNode;

            anchor = anchor || "t";
            options = options || {};

            beforeAnim = function() {
                var animScope = this,
                    listeners = options.listeners,
                    box, originalStyles, anim, wrap;

                // Reattach to the DOM in case the caller animated a Fly
                // in which case the dom reference will have changed by now.
                animFly.attach(dom);

                if (!slideOut) {
                    animFly.fixDisplay();
                }

                box = animFly.getBox();

                if ((anchor === 't' || anchor === 'b') && box.height === 0) {
                    box.height = dom.scrollHeight;
                }
                else if ((anchor === 'l' || anchor === 'r') && box.width === 0) {
                    box.width = dom.scrollWidth;
                }

                originalStyles = animFly.getStyle(['width', 'height', 'left', 'right', 'top',
                                                   'bottom', 'position', 'z-index'], true);
                animFly.setSize(box.width, box.height);

                // Cache all descendants' scrollTop & scrollLeft values
                // if configured to preserve scroll.
                if (options.preserveScroll) {
                    restoreScroll = animFly.cacheScrollValues();
                }

                wrap = animFly.wrap({
                    role: 'presentation',
                    id: Ext.id() + '-anim-wrap-for-' + dom.id,
                    style: {
                        visibility: slideOut ? 'visible' : 'hidden'
                    }
                });

                wrapDomParentNode = wrap.dom.parentNode;
                wrap.setPositioning(animFly.getPositioning());

                if (wrap.isStyle('position', 'static')) {
                    wrap.position('relative');
                }

                animFly.clearPositioning('auto');
                wrap.clip();

                // The wrap will have reset all descendant scrollTops.
                // Restore them if we cached them.
                if (restoreScroll) {
                    restoreScroll();
                }

                // This element is temporarily positioned absolute within its wrapper.
                // Restore to its default, CSS-inherited visibility setting.
                // We cannot explicitly poke visibility:visible into its style
                // because that overrides the visibility of the wrap.
                animFly.setStyle({
                    visibility: '',
                    position: 'absolute'
                });

                if (slideOut) {
                    wrap.setSize(box.width, box.height);
                }

                switch (anchor) {
                    case 't':
                        anim = {
                            from: {
                                width: box.width + 'px',
                                height: '0px'
                            },
                            to: {
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        elStyle.bottom = '0px';

                        break;

                    case 'l':
                        anim = {
                            from: {
                                width: '0px',
                                height: box.height + 'px'
                            },
                            to: {
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        me.anchorAnimX(anchor);

                        break;

                    case 'r':
                        anim = {
                            from: {
                                x: box.x + box.width,
                                width: '0px',
                                height: box.height + 'px'
                            },
                            to: {
                                x: box.x,
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        me.anchorAnimX(anchor);

                        break;

                    case 'b':
                        anim = {
                            from: {
                                y: box.y + box.height,
                                width: box.width + 'px',
                                height: '0px'
                            },
                            to: {
                                y: box.y,
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        break;

                    case 'tl':
                        anim = {
                            from: {
                                x: box.x,
                                y: box.y,
                                width: '0px',
                                height: '0px'
                            },
                            to: {
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        elStyle.bottom = '0px';
                        me.anchorAnimX('l');

                        break;

                    case 'bl':
                        anim = {
                            from: {
                                y: box.y + box.height,
                                width: '0px',
                                height: '0px'
                            },
                            to: {
                                y: box.y,
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        me.anchorAnimX('l');

                        break;

                    case 'br':
                        anim = {
                            from: {
                                x: box.x + box.width,
                                y: box.y + box.height,
                                width: '0px',
                                height: '0px'
                            },
                            to: {
                                x: box.x,
                                y: box.y,
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        me.anchorAnimX('r');

                        break;

                    case 'tr':
                        anim = {
                            from: {
                                x: box.x + box.width,
                                width: '0px',
                                height: '0px'
                            },
                            to: {
                                x: box.x,
                                width: box.width + 'px',
                                height: box.height + 'px'
                            }
                        };

                        elStyle.bottom = '0px';
                        me.anchorAnimX('r');

                        break;
                }

                wrap.show();

                wrapAnim = Ext.apply({}, options);
                delete wrapAnim.listeners;

                wrapAnim = new Ext.fx.Anim(Ext.applyIf(wrapAnim, {
                    target: wrap,
                    duration: 500,
                    easing: 'ease-out',
                    from: slideOut ? anim.to : anim.from,
                    to: slideOut ? anim.from : anim.to
                }));

                // In the absence of a callback, this listener MUST be added first
                wrapAnim.on('afteranimate', function() {
                    // Reattach to the DOM in case the caller animated a Fly
                    // in which case the dom reference will have changed by now.
                    animFly.attach(dom);

                    animFly.setStyle(originalStyles);

                    if (slideOut) {
                        if (options.useDisplay) {
                            animFly.setDisplayed(false);
                        }
                        else {
                            animFly.hide();
                        }
                    }

                    if (wrap.dom) {
                        if (wrap.dom.parentNode) {
                            wrap.dom.parentNode.insertBefore(dom, wrap.dom);
                        }
                        else {
                            wrapDomParentNode.appendChild(dom);
                        }

                        wrap.destroy();
                    }

                    // The unwrap will have reset all descendant scrollTops.
                    // Restore them if we cached them.
                    if (restoreScroll) {
                        restoreScroll();
                    }

                    // kill the no-op element animation created below
                    animScope.end();
                });

                // Add configured listeners after
                if (listeners) {
                    wrapAnim.on(listeners);
                }
            };

            me.animate({
                // See "A Note About Wrapped Animations" at the top of this class:
                duration: options.duration ? Math.max(options.duration, 500) * 2 : 1000,
                listeners: {
                    beforeanimate: beforeAnim // kick off the wrap animation
                }
            });

            return me;
        },

        /**
         * Slides the element out of view. An anchor point can be optionally passed to set the end
         * point for the slide effect. When the effect is completed, the element will be hidden
         * (visibility = 'hidden') but block elements will still take up space in the document.
         * The element must be removed from the DOM using the 'remove' config option if
         * desired. This function automatically handles wrapping the element with a fixed-size
         * container if needed. See the {@link Ext.fx.Anim} class overview for valid anchor point
         * options. Usage:
         *
         *     // default: slide the element out to the top
         *     el.slideOut();
         *
         *     // custom: slide the element out to the right with a 2-second duration
         *     el.slideOut('r', { duration: 2000 });
         *
         *     // common config options shown with default values
         *     el.slideOut('t', {
         *         easing: 'easeOut',
         *         duration: 500,
         *         remove: false,
         *         useDisplay: false
         *     });
         *
         * @param {String} anchor (optional) One of the valid {@link Ext.fx.Anim} anchor positions
         * (defaults to top: 't')
         * @param {Object} options (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        slideOut: function(anchor, options) {
            return this.slideIn(anchor, options, true);
        },

        /**
         * Blinks the element as if it was clicked and then collapses on its center (similar to
         * switching off a television). When the effect is completed, the element will be hidden
         * (visibility = 'hidden') but block elements will still take up space in the document.
         * The element must be removed from the DOM using the 'remove' config option if desired.
         * Usage:
         *
         *     // default
         *     el.switchOff();
         *
         *     // all config options shown with default values
         *     el.switchOff({
         *         easing: 'easeIn',
         *         duration: .3,
         *         remove: false,
         *         useDisplay: false
         *     });
         *
         * @param {Object} options (optional) Object literal with any of the {@link Ext.fx.Anim}
         * config options
         * @return {Ext.dom.Element} The Element
         */
        switchOff: function(options) {
            var me = this,
                dom = me.dom,
                animFly = new Ext.dom.Fly(),
                beforeAnim;

            options = Ext.applyIf(options || {}, {
                easing: 'ease-in',
                duration: 500,
                remove: false,
                useDisplay: false
            });

            beforeAnim = function() {
                // Reattach to the DOM in case the caller animated a Fly
                // in which case the dom reference will have changed by now.
                animFly.attach(dom);

                // eslint-disable-next-line vars-on-top
                var animScope = this,
                    size = animFly.getSize(),
                    xy = animFly.getXY(),
                    keyframe, position;

                animFly.clearOpacity();
                animFly.clip();
                position = animFly.getPositioning();

                keyframe = new Ext.fx.Animator({
                    target: dom,
                    duration: options.duration,
                    easing: options.easing,
                    keyframes: {
                        33: {
                            opacity: 0.3
                        },
                        66: {
                            height: 1,
                            y: xy[1] + size.height / 2
                        },
                        100: {
                            width: 1,
                            x: xy[0] + size.width / 2
                        }
                    }
                });

                keyframe.on('afteranimate', function() {
                    // Reattach to the DOM in case the caller animated a Fly
                    // in which case the dom reference will have changed by now.
                    animFly.attach(dom);

                    if (options.useDisplay) {
                        animFly.setDisplayed(false);
                    }
                    else {
                        animFly.hide();
                    }

                    animFly.clearOpacity();
                    animFly.setPositioning(position);
                    animFly.setSize(size);

                    // kill the no-op element animation created below
                    animScope.end();
                });
            };

            me.animate({
                // See "A Note About Wrapped Animations" at the top of this class:
                duration: (Math.max(options.duration, 500) * 2),
                listeners: {
                    beforeanimate: {
                        fn: beforeAnim
                    }
                },
                callback: options.callback,
                scope: options.scope
            });

            return me;
        },

        /**
         * @private
         * Currently used for updating grid cells without modifying DOM structure
         *
         * Synchronizes content of this Element with the content of the passed element.
         * 
         * Style and CSS class are copied from source into this Element, and contents are synced
         * recursively. If a child node is a text node, the textual data is copied.
         */
        syncContent: function(source) {
            source = Ext.getDom(source);

            // eslint-disable-next-line vars-on-top
            var sourceNodes = source.childNodes,
                sourceLen = sourceNodes.length,
                dest = this.dom,
                destNodes = dest.childNodes,
                destLen = destNodes.length,
                i, destNode, sourceNode, sourceStyle,
                nodeType, newAttrs, attLen, attName, value,
                elData = dest._extData;

            if (!syncContentFly) {
                syncContentFly = new Ext.dom.Fly();
            }

            // Update any attributes who's values have changed..
            newAttrs = source.attributes;
            attLen = newAttrs.length;

            for (i = 0; i < attLen; i++) {
                attName = newAttrs[i].name;
                value = newAttrs[i].value;

                if (attName !== 'id' && dest.getAttribute(attName) !== value) {
                    dest.setAttribute(attName, newAttrs[i].value);
                }
            }

            // The element's data is no longer synchronized. We just overwrite it in the DOM
            if (elData) {
                elData.isSynchronized = false;
            }

            // If the number of child nodes does not match, fall back to replacing innerHTML
            if (sourceLen !== destLen) {
                dest.innerHTML = source.innerHTML;

                return;
            }

            // Loop through source nodes.
            // If there are fewer, we must remove excess
            for (i = 0; i < sourceLen; i++) {
                sourceNode = sourceNodes[i];
                destNode = destNodes[i];
                nodeType = sourceNode.nodeType;
                sourceStyle = sourceNode.style;

                // If node structure is out of sync, just drop innerHTML in and return
                if (nodeType !== destNode.nodeType ||
                    (nodeType === 1 && sourceNode.tagName !== destNode.tagName)) {
                    dest.innerHTML = source.innerHTML;

                    return;
                }

                // Update non-Element node (text, comment)
                if (!sourceStyle) {
                    destNode.data = sourceNode.data;
                }
                // Sync element content
                else {
                    if (sourceNode.id && destNode.id !== sourceNode.id) {
                        destNode.id = sourceNode.id;
                    }

                    destNode.style.cssText = sourceStyle.cssText;
                    destNode.className = sourceNode.className;
                    syncContentFly.attach(destNode).syncContent(sourceNode);
                }
            }
        },

        /**
         * Toggles the element's visibility, depending on visibility mode.
         * @param {Boolean/Object} [animate] True for the default animation, or a standard Element
         * animation config object
         * @return {Ext.dom.Element} this
         */
        toggle: function(animate) {
            var me = this;

            me.setVisible(!me.isVisible(), me.anim(animate));

            return me;
        },

        /**
         * Hides a previously applied mask.
         */
        unmask: function() {
            var me = this,
                data = me.getData(),
                maskEl = data.maskEl,
                style;

            if (maskEl) {
                style = maskEl.dom.style;

                // Remove resource-intensive CSS expressions as soon as they are not required.
                if (style.clearExpression) {
                    style.clearExpression('width');
                    style.clearExpression('height');
                }

                if (maskEl) {
                    maskEl.destroy();
                    delete data.maskEl;
                }

                me.removeCls([XMASKED, XMASKEDRELATIVE]);
            }

            me.restoreTabbableState(me.dom === DOC.body);
        },

        /**
         * Return clipping (overflow) to original clipping before {@link #clip} was called
         * @return {Ext.dom.Element} this
         */
        unclip: function() {
            var me = this,
                data = me.getData(),
                clip;

            if (data[ISCLIPPED]) {
                data[ISCLIPPED] = false;
                clip = data[ORIGINALCLIP];

                if (clip.o) {
                    me.setStyle(OVERFLOW, clip.o);
                }

                if (clip.x) {
                    me.setStyle(OVERFLOWX, clip.x);
                }

                if (clip.y) {
                    me.setStyle(OVERFLOWY, clip.y);
                }
            }

            return me;
        },

        translate: function(x, y, z) {
            if (Ext.supports.CssTransforms && !Ext.isIE9m) {
                this.callParent(arguments);
            }
            else {
                if (x != null) {
                    this.dom.style.left = x + 'px';
                }

                if (y != null) {
                    this.dom.style.top = y + 'px';
                }
            }
        },

        deprecated: {
            '4.0': {
                methods: {
                    /**
                     * @method pause
                     * Creates a pause before any subsequent queued effects begin. If there are
                     * no effects queued after the pause it will have no effect. Usage:
                     *
                     *     el.pause(1);
                     *
                     * @deprecated 4.0 Use the `delay` config to {@link #animate} instead.
                     * @param {Number} ms The length of time to pause (in milliseconds)
                     * @return {Ext.dom.Element} The Element
                     */
                    pause: function(ms) {
                        var me = this;

                        Ext.fx.Manager.setFxDefaults(me.id, {
                            delay: ms
                        });

                        return me;
                    },

                    /**
                     * @method scale
                     * Animates the transition of an element's dimensions from a starting
                     * height/width to an ending height/width. This method is a convenience
                     * implementation of {@link #shift}. Usage:
                     *
                     *     // change height and width to 100x100 pixels
                     *     el.scale(100, 100);
                     *
                     *     // common config options shown with default values.
                     *     // The height and width will default to the element's existing values
                     *     // if passed as null.
                     *     el.scale(
                     *         [element's width],
                     *         [element's height], {
                     *             easing: 'easeOut',
                     *             duration: 350
                     *         }
                     *     );
                     *
                     * @deprecated 4.0 Just use {@link #animate} instead.
                     * @param {Number} width The new width (pass undefined to keep the original
                     * width)
                     * @param {Number} height The new height (pass undefined to keep the original
                     * height)
                     * @param {Object} options (optional) Object literal with any of the
                     * {@link Ext.fx.Anim} config options
                     * @return {Ext.dom.Element} The Element
                     */
                    scale: function(width, height, options) {
                        this.animate(Ext.apply({}, options, {
                            width: width,
                            height: height
                        }));

                        return this;
                    },

                    /**
                     * @method shift
                     * Animates the transition of any combination of an element's dimensions,
                     * xy position and/or opacity. Any of these properties not specified in the
                     * config object will not be changed. This effect requires that at least one new
                     * dimension, position or opacity setting must be passed in on the config object
                     * in order for the function to have any effect. Usage:
                     *
                     *     // slide the element horizontally to x position 200
                     *     // while changing the height and opacity
                     *     el.shift({ x: 200, height: 50, opacity: .8 });
                     *
                     *     // common config options shown with default values.
                     *     el.shift({
                     *         width: [element's width],
                     *         height: [element's height],
                     *         x: [element's x position],
                     *         y: [element's y position],
                     *         opacity: [element's opacity],
                     *         easing: 'easeOut',
                     *         duration: 350
                     *     });
                     *
                     * @deprecated 4.0 Just use {@link #animate} instead.
                     * @param {Object} options Object literal with any of the {@link Ext.fx.Anim}
                     * config options
                     * @return {Ext.dom.Element} The Element
                     */
                    shift: function(options) {
                        this.animate(options);

                        return this;
                    }
                }
            },

            '4.2': {
                methods: {
                    /**
                     * @method moveTo
                     * Sets the position of the element in page coordinates.
                     * @param {Number} x X value for new position (coordinates are page-based)
                     * @param {Number} y Y value for new position (coordinates are page-based)
                     * @param {Boolean/Object} [animate] True for the default animation,
                     * or a standard Element animation config object
                     * @return {Ext.dom.Element} this
                     * @deprecated 4.2.0 Use {@link Ext.dom.Element#setXY} instead.
                     */
                    moveTo: function(x, y, animate) {
                        return this.setXY([x, y], animate);
                    },

                    /**
                     * @method setBounds
                     * Sets the element's position and size in one shot. If animation is true then
                     * width, height, x and y will be animated concurrently.
                     *
                     * @param {Number} x X value for new position (coordinates are page-based)
                     * @param {Number} y Y value for new position (coordinates are page-based)
                     * @param {Number/String} width The new width. This may be one of:
                     *
                     * - A Number specifying the new width in pixels
                     * - A String used to set the CSS width style. Animation may **not** be used.
                     *
                     * @param {Number/String} height The new height. This may be one of:
                     *
                     * - A Number specifying the new height in pixels
                     * - A String used to set the CSS height style. Animation may **not** be used.
                     *
                     * @param {Boolean/Object} [animate] true for the default animation or
                     * a standard Element animation config object
                     *
                     * @return {Ext.dom.Element} this
                     * @deprecated 4.2.0 Use {@link Ext.util.Positionable#setBox} instead.
                     */
                    setBounds: function(x, y, width, height, animate) {
                        return this.setBox({
                            x: x,
                            y: y,
                            width: width,
                            height: height
                        }, animate);
                    },

                    /**
                     * @method setLeftTop
                     * Sets the element's left and top positions directly using CSS style
                     * @param {Number/String} left Number of pixels or CSS string value to
                     * set as the left CSS property value
                     * @param {Number/String} top Number of pixels or CSS string value to
                     * set as the top CSS property value
                     * @return {Ext.dom.Element} this
                     * @deprecated 4.2.0 Use {@link Ext.dom.Element#setLocalXY} instead
                     */
                    setLeftTop: function(left, top) {
                        var me = this,
                            style = me.dom.style;

                        style.left = Element.addUnits(left);
                        style.top = Element.addUnits(top);

                        if (me.shadow || me.shim) {
                            me.syncUnderlays();
                        }

                        return me;
                    },

                    /**
                     * @method setLocation
                     * Sets the position of the element in page coordinates.
                     * @param {Number} x X value for new position
                     * @param {Number} y Y value for new position
                     * @param {Boolean/Object} [animate] True for the default animation,
                     * or a standard Element animation config object
                     * @return {Ext.dom.Element} this
                     * @deprecated 4.2.0 Use {@link Ext.dom.Element#setXY} instead.
                     */
                    setLocation: function(x, y, animate) {
                        return this.setXY([x, y], animate);
                    }
                }
            },

            '5.0': {
                methods: {
                    /**
                     * @method getAttributeNS
                     * Returns the value of a namespaced attribute from the element's underlying
                     * DOM node.
                     * @param {String} namespace The namespace in which to look for the attribute
                     * @param {String} name The attribute name
                     * @return {String} The attribute value
                     * @deprecated 5.0.0 Please use {@link Ext.dom.Element#getAttribute} instead.
                     */
                    getAttributeNS: function(namespace, name) {
                        return this.getAttribute(name, namespace);
                    },

                    /**
                     * @method getCenterXY
                     * Calculates the x, y to center this element on the screen
                     * @return {Number[]} The x, y values [x, y]
                     * @deprecated 5.0.0 Use {@link Ext.dom.Element#getAlignToXY} instead.
                     *     el.getAlignToXY(document, 'c-c');
                     */
                    getCenterXY: function() {
                        return this.getAlignToXY(DOC, 'c-c');
                    },

                    /**
                     * @method getComputedHeight
                     * Returns either the offsetHeight or the height of this element based on CSS
                     * height adjusted by padding or borders when needed to simulate offsetHeight
                     * when offsets aren't available. This may not work on display:none elements
                     * if a height has not been set using CSS.
                     * @return {Number}
                     * @deprecated 5.0.0 use {@link Ext.dom.Element#getHeight} instead
                     */
                    getComputedHeight: function() {
                        return Math.max(this.dom.offsetHeight, this.dom.clientHeight) ||
                               parseFloat(this.getStyle(HEIGHT)) || 0;
                    },

                    /**
                     * @method getComputedWidth
                     * Returns either the offsetWidth or the width of this element based on CSS
                     * width adjusted by padding or borders when needed to simulate offsetWidth
                     * when offsets aren't available. This may not work on display:none elements
                     * if a width has not been set using CSS.
                     * @return {Number}
                     * @deprecated 5.0.0 use {@link Ext.dom.Element#getWidth} instead.
                     */
                    getComputedWidth: function() {
                        return Math.max(this.dom.offsetWidth, this.dom.clientWidth) ||
                               parseFloat(this.getStyle(WIDTH)) || 0;
                    },

                    /**
                     * @method getStyleSize
                     * Returns the dimensions of the element available to lay content out in.
                     *
                     * getStyleSize utilizes prefers style sizing if present, otherwise it chooses
                     * the larger of offsetHeight/clientHeight and offsetWidth/clientWidth.
                     * To obtain the size excluding scrollbars, use getViewSize.
                     *
                     * Sizing of the document body is handled at the adapter level which handles
                     * special cases for IE and strict modes, etc.
                     *
                     * @return {Object} Object describing width and height.
                     * @return {Number} return.width
                     * @return {Number} return.height
                     * @deprecated 5.0.0 Use {@link Ext.dom.Element#getSize} instead.
                     */
                    getStyleSize: function() {
                        var me = this,
                            d = this.dom,
                            isDoc = (d === DOC || d === DOC.body),
                            s,
                            w, h;

                        // If the body, use static methods
                        if (isDoc) {
                            return {
                                width: Element.getViewportWidth(),
                                height: Element.getViewportHeight()
                            };
                        }

                        s = me.getStyle(['height', 'width'], true);  // seek inline

                        // Use Styles if they are set
                        if (s.width && s.width !== 'auto') {
                            w = parseFloat(s.width);
                        }

                        // Use Styles if they are set
                        if (s.height && s.height !== 'auto') {
                            h = parseFloat(s.height);
                        }

                        // Use getWidth/getHeight if style not set.
                        return { width: w || me.getWidth(true), height: h || me.getHeight(true) };
                    },

                    /**
                     * @method isBorderBox
                     * Returns true if this element uses the border-box-sizing model.
                     * This method is deprecated as of version 5.0 because border-box sizing
                     * is forced upon all elements via a style sheet rule, and the browsers
                     * that do not support border-box (IE6/7 strict mode) are no longer supported.
                     * @deprecated 5.0.0 This method is deprecated.  Browsers that do not
                     * support border-box (IE6/7 strict mode) are no longer supported.
                     * @return {Boolean}
                     */
                    isBorderBox: function() {
                        return true;
                    },

                    /**
                     * @method isDisplayed
                     * Returns true if display is not "none"
                     * @return {Boolean}
                     * @deprecated 5.0.0 use element.isStyle('display', 'none');
                     */
                    isDisplayed: function() {
                        return !this.isStyle('display', 'none');
                    },

                    /**
                     * @method focusable
                     * Checks whether this element can be focused.
                     * @return {Boolean} True if the element is focusable
                     * @deprecated 5.0.0 use {@link #isFocusable} instead
                     */
                    focusable: 'isFocusable'
                }
            }
        }
    };
})(), function() {
    var Element = Ext.dom.Element,
        proto = Element.prototype,
        useDocForId = !Ext.isIE8,
        DOC = document,
        view = DOC.defaultView,
        opacityRe = /alpha\(opacity=(.*)\)/i,
        trimRe = /^\s+|\s+$/g,
        styleHooks = proto.styleHooks,
        supports = Ext.supports,
        verticalStyleHooks90, verticalStyleHooks270, edges, k,
        edge, borderWidth, getBorderWidth;

    proto._init(Element);
    delete proto._init;

    Ext.plainTableCls = Ext.baseCSSPrefix + 'table-plain';
    Ext.plainListCls = Ext.baseCSSPrefix + 'list-plain';

    // ensure that any methods added by this override are also added to Ext.CompositeElementLite
    if (Ext.CompositeElementLite) {
        Ext.CompositeElementLite.importElementMethods();
    }

    if (!supports.Opacity && Ext.isIE) {
        Ext.apply(styleHooks.opacity, {
            get: function(dom) {
                var filter = dom.style.filter,
                    match, opacity;

                if (filter.match) {
                    match = filter.match(opacityRe);

                    if (match) {
                        opacity = parseFloat(match[1]);

                        if (!isNaN(opacity)) {
                            return opacity ? opacity / 100 : 0;
                        }
                    }
                }

                return 1;
            },
            set: function(dom, value) {
                var style = dom.style,
                    val = style.filter.replace(opacityRe, '').replace(trimRe, '');

                style.zoom = 1; // ensure dom.hasLayout

                // value can be a number or '' or null... so treat falsey as no opacity
                if (typeof(value) === 'number' && value >= 0 && value < 1) {
                    value *= 100;
                    style.filter = val + (val.length ? ' ' : '') + 'alpha(opacity=' + value + ')';
                }
                else {
                    style.filter = val;
                }
            }
        });
    }

    if (!supports.matchesSelector) {
        // Match basic tagName.ClassName selector syntax for is implementation
        // eslint-disable-next-line vars-on-top
        var simpleSelectorRe = /^([a-z]+|\*)?(?:\.([a-z][a-z\-_0-9]*))?$/i,
            dashRe = /-/g,
            fragment,
            classMatcher = function(tag, cls) {
                var classRe = new RegExp('(?:^|\\s+)' + cls.replace(dashRe, '\\-') + '(?:\\s+|$)');

                if (tag && tag !== '*') {
                    tag = tag.toUpperCase();

                    return function(el) {
                        return el.tagName === tag && classRe.test(el.className);
                    };
                }

                return function(el) {
                    return classRe.test(el.className);
                };
            },
            tagMatcher = function(tag) {
                tag = tag.toUpperCase();

                return function(el) {
                    return el.tagName === tag;
                };
            },
            cache = {};

        proto.matcherCache = cache;

        proto.is = function(selector) {
            var dom = this.dom,
                cls, match, testFn, root, isOrphan, is, tag;

            // Empty selector always matches
            if (!selector) {
                return true;
            }

            // Only Element node types can be matched.
            if (dom.nodeType !== 1) {
                return false;
            }

            // eslint-disable-next-line no-cond-assign
            if (!(testFn = Ext.isFunction(selector) ? selector : cache[selector])) {
                // eslint-disable-next-line no-cond-assign
                if (!(match = selector.match(simpleSelectorRe))) {
                    // Not a simple tagName.className selector, do it the hard way
                    root = dom.parentNode;

                    if (!root) {
                        isOrphan = true;
                        root = fragment || (fragment = DOC.createDocumentFragment());
                        fragment.appendChild(dom);
                    }

                    is = Ext.Array.indexOf(Ext.fly(root, '_is').query(selector), dom) !== -1;

                    if (isOrphan) {
                        fragment.removeChild(dom);
                    }

                    return is;
                }

                tag = match[1];
                cls = match[2];
                cache[selector] = testFn = cls ? classMatcher(tag, cls) : tagMatcher(tag);
            }

            return testFn(dom);
        };
    }

    // IE8 needs its own implementation of getStyle because it doesn't support getComputedStyle
    if (!view || !view.getComputedStyle) {
        proto.getStyle = function(property, inline) {
            var me = this,
                dom = me.dom,
                multiple = typeof property !== 'string',
                prop = property,
                props = prop,
                len = 1,
                isInline = inline,
                styleHooks = me.styleHooks,
                camel, domStyle, values, hook, out, style, i;

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
                style = dom.currentStyle;

                // fallback to inline style if rendering context not available
                if (!style) {
                    isInline = true;
                    style = domStyle;
                }
            }

            do {
                hook = styleHooks[prop];

                if (!hook) {
                    styleHooks[prop] = hook = { name: Element.normalize(prop) };
                }

                if (hook.get) {
                    out = hook.get(dom, me, isInline, style);
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
        };
    }

    // override getStyle for border-*-width
    if (Ext.isIE8) {
        getBorderWidth = function(dom, el, inline, style) {
            if (style[this.styleName] === 'none') {
                return '0px';
            }

            return style[this.name];
        };

        edges = ['Top', 'Right', 'Bottom', 'Left'];
        k = edges.length;

        while (k--) {
            edge = edges[k];
            borderWidth = 'border' + edge + 'Width';

            styleHooks['border-' + edge.toLowerCase() + '-width'] = styleHooks[borderWidth] = {
                name: borderWidth,
                styleName: 'border' + edge + 'Style',
                get: getBorderWidth
            };
        }

        // IE8 has an odd bug with handling font icons in pseudo elements;
        // it will render the icon once and not update it when something
        // like text color is changed via style addition or removal.
        // We have to force icon repaint by adding a style with forced empty
        // pseudo element content, (x-sync-repaint) and removing it back to work
        // around this issue.
        // See this: https://github.com/FortAwesome/Font-Awesome/issues/954
        // and this: https://github.com/twbs/bootstrap/issues/13863
        // eslint-disable-next-line vars-on-top
        var syncRepaintCls = Ext.baseCSSPrefix + 'sync-repaint';

        proto.syncRepaint = function() {
            this.addCls(syncRepaintCls);

            // Measuring element width will make the browser to repaint it
            this.getWidth();

            // Removing empty content makes the icon to appear again and be redrawn
            this.removeCls(syncRepaintCls);
        };
    }

    if (Ext.isIE10m) {
        Ext.override(Element, {
            focus: function(defer, dom) {
                var me = this,
                    ex;

                dom = dom || me.dom;

                if (me.deferredFocusTimer) {
                    Ext.undefer(me.deferredFocusTimer);
                }

                me.deferredFocusTimer = null;

                if (Number(defer)) {
                    me.deferredFocusTimer = Ext.defer(me.focus, defer, me, [null, dom]);
                }
                else {
                    Ext.GlobalEvents.fireEvent('beforefocus', dom);

                    // IE10m has an acute problem with focusing input elements;
                    // when the element was just shown and did not have enough
                    // time to initialize, focusing it might fail. The problem
                    // is somewhat random in nature; most of the time focusing
                    // an input element will succeed, failing only occasionally.
                    // When it fails, the focus will be thrown to the document
                    // body element, with subsequent focusout/focusin event pair
                    // on the body, which throws off our focusenter/focusleave
                    // processing.
                    // Fortunately for us, when this focus failure happens, the
                    // resulting focusout event will happen *synchronously*
                    // unlike the normal focusing events which IE will fire
                    // asynchronously. Also fortunately for us, in most cases
                    // trying to focus the given element the second time
                    // immediately after it failed to focus the first time
                    // seems to do the trick; however when second focus attempt
                    // succeeds, it will result in focusout on the body and
                    // focusin on the given element, which again wreaks havoc
                    // on our focusenter/focusleave handling.
                    // The only workable solution we have is to pretend that
                    // focus never went to the document body and ignore the
                    // focusout and focusin caused by failed first focus attempt.
                    // To this end, we fudge the event stream in Focus publisher
                    // override.
                    if (dom && (dom.tagName === 'INPUT' || dom.tagname === 'TEXTAREA')) {
                        Ext.synchronouslyFocusing = document.activeElement;
                    }

                    // Also note that trying to focus an unfocusable element
                    // might throw an exception in IE8. What a cute idea, MS. :(
                    try {
                        dom.focus();
                    }
                    catch (xcpt) {
                        ex = xcpt;
                    }

                    // Ok so now we have this situation when we tried to focus
                    // the first time but did not succeed. Let's try again but
                    // not if there was an exception the first time - when the
                    // "focus failure" happens it does so silently. :(
                    if (Ext.synchronouslyFocusing && document.activeElement !== dom && !ex) {
                        dom.focus();
                    }

                    Ext.synchronouslyFocusing = null;
                }

                return me;
            }
        });
    }

    Ext.apply(Ext, {
        /**
         * `true` to automatically uncache orphaned Ext.Elements periodically. If set to
         * `false`, the application will be required to clean up orphaned Ext.Elements and
         * it's listeners as to not cause memory leakage.
         * @member Ext
         */
        enableGarbageCollector: true,

        // In sencha v5 isBorderBox is no longer needed since all supported browsers
        // support border-box, but it is hard coded to true for backward compatibility
        isBorderBox: true,

        /**
         * @property {Boolean} useShims
         * @member Ext
         * Set to `true` to use a {@link Ext.util.Floating#shim shim} on all floating Components
         * and {@link Ext.LoadMask LoadMasks}
         */
        useShims: false,

        getElementById: function(id) {
            var el = DOC.getElementById(id),
                detachedBodyEl;

            if (!el && (detachedBodyEl = Ext.detachedBodyEl)) {
                el = detachedBodyEl.dom.querySelector(Ext.makeIdSelector(id));
            }

            return el;
        },

        /**
         * Applies event listeners to elements by selectors when the document is ready.
         * The event name is specified with an `@` suffix.
         *
         *     Ext.addBehaviors({
         *         // add a listener for click on all anchors in element with id foo
         *         '#foo a@click': function(e, t){
         *             // do something
         *         },
         *
         *         // add the same listener to multiple selectors (separated by comma BEFORE the @)
         *         '#foo a, #bar span.some-class@mouseover': function(){
         *             // do something
         *         }
         *     });
         *
         * @param {Object} obj The list of behaviors to apply
         * @member Ext
         */
        addBehaviors: function(obj) {
            // simple cache for applying multiple behaviors to same selector
            // does query multiple times
            var cache = {},
                parts, b, s;

            if (!Ext.isReady) {
                Ext.onInternalReady(function() {
                    Ext.addBehaviors(obj);
                });
            }
            else {
                for (b in obj) {
                    if ((parts = b.split('@'))[1]) { // for Object prototype breakers
                        s = parts[0];

                        if (!cache[s]) {
                            cache[s] = Ext.fly(document).select(s, true);
                        }

                        cache[s].on(parts[1], obj[b]);
                    }
                }

                cache = null;
            }
        }
    });

    if (Ext.isIE9m) {
        Ext.getElementById = function(id) {
            var el = DOC.getElementById(id),
                detachedBodyEl;

            if (!el && (detachedBodyEl = Ext.detachedBodyEl)) {
                el = detachedBodyEl.dom.all[id];
            }

            return el;
        };

        proto.getById = function(id, asDom) {
            var dom = this.dom,
                ret = null,
                entry, el;

            if (dom) {
                // for normal elements getElementById is the best solution, but if the el is
                // not part of the document.body, we need to use all[]
                el = (useDocForId && DOC.getElementById(id)) || dom.all[id];

                if (el) {
                    if (asDom) {
                        ret = el;
                    }
                    else {
                        // calling Element.get here is a real hit (2x slower) because it has to
                        // redetermine that we are giving it a dom el.
                        entry = Ext.cache[id];

                        if (entry) {
                            if (entry.skipGarbageCollection || !Ext.isGarbage(entry.dom)) {
                                ret = entry;
                            }
                            else {
                                //<debug>
                                Ext.raise("Stale Element with id '" + el.id +
                                    "' found in Element cache. " +
                                    "Make sure to clean up Element instances using destroy()");
                                //</debug>

                                entry.destroy();
                            }
                        }

                        ret = ret || new Ext.Element(el);
                    }
                }
            }

            return ret;
        };
    }
    else if (!DOC.querySelector) {
        Ext.getDetachedBody = Ext.getBody;

        Ext.getElementById = function(id) {
            return DOC.getElementById(id);
        };

        proto.getById = function(id, asDom) {
            var dom = DOC.getElementById(id);

            return asDom ? dom : (dom ? Ext.get(dom) : null);
        };
    }

    if (Ext.isIE && !(Ext.isIE9p && DOC.documentMode >= 9)) {
        // Essentially all web browsers (Firefox, Internet Explorer, recent versions of Opera,
        // Safari, Konqueror, and iCab, as a non-exhaustive list) return null when the specified
        // attribute does not exist on the specified element.
        // The DOM specification says that the correct return value in this case is actually
        // the empty string, and some DOM implementations implement this behavior.
        // The implementation of getAttribute in XUL (Gecko) actually follows the specification
        // and returns an empty string. Consequently, you should use hasAttribute to check
        // for an attribute's existence prior to calling getAttribute() if it is possible that
        // the requested attribute does not exist on the specified element.
        //
        // https://developer.mozilla.org/en-US/docs/DOM/element.getAttribute
        // http://www.w3.org/TR/DOM-Level-2-Core/core.html#ID-745549614
        proto.getAttribute = function(name, ns) {
            var d = this.dom,
                type;

            if (ns) {
                type = typeof d[ns + ":" + name];

                if (type !== 'undefined' && type !== 'unknown') {
                    return d[ns + ":" + name] || null;
                }

                return null;
            }

            if (name === "for") {
                name = "htmlFor";
            }

            return d[name] || null;
        };
    }

    Ext.onInternalReady(function() {
        var transparentRe = /^(?:transparent|(?:rgba[(](?:\s*\d+\s*[,]){3}\s*0\s*[)]))$/i,
            origSetWidth = proto.setWidth,
            origSetHeight = proto.setHeight,
            origSetSize = proto.setSize,
            origUnselectable = proto.unselectable,
            pxRe = /^\d+(?:\.\d*)?px$/i,
            colorStyles, i, name, camel;

        if (supports.FixedTableWidthBug) {
            // EXTJSIV-12665
            // https://bugs.webkit.org/show_bug.cgi?id=130239
            // Webkit browsers fail to layout correctly when a form field's width is less
            // than the min-width of the body element.  The only way to fix it seems to be
            // to toggle the display style of the field's element before and after setting
            // the width. Note: once the bug has been corrected by toggling the element's
            // display, successive calls to setWidth will work without the hack.  It's only
            // when going from naturally widthed to having an explicit width that the bug
            // occurs.
            styleHooks.width = {
                name: 'width',
                set: function(dom, value, el) {
                    var style = dom.style,
                        needsFix = el._needsTableWidthFix,
                        origDisplay = style.display;

                    if (needsFix) {
                        style.display = 'none';
                    }

                    style.width = value;

                    if (needsFix) {
                        // repaint
                        // eslint-disable-next-line no-unused-expressions
                        dom.scrollWidth;
                        style.display = origDisplay;
                    }
                }
            };

            proto.setWidth = function(width, animate) {
                var me = this,
                    dom = me.dom,
                    style = dom.style,
                    needsFix = me._needsTableWidthFix,
                    origDisplay = style.display;

                if (needsFix && !animate) {
                    style.display = 'none';
                }

                origSetWidth.call(me, width, animate);

                if (needsFix && !animate) {
                    // repaint
                    // eslint-disable-next-line no-unused-expressions
                    dom.scrollWidth;
                    style.display = origDisplay;
                }

                return me;
            };

            proto.setSize = function(width, height, animate) {
                var me = this,
                    dom = me.dom,
                    style = dom.style,
                    needsFix = me._needsTableWidthFix,
                    origDisplay = style.display;

                if (needsFix && !animate) {
                    style.display = 'none';
                }

                origSetSize.call(me, width, height, animate);

                if (needsFix && !animate) {
                    // repaint
                    // eslint-disable-next-line no-unused-expressions
                    dom.scrollWidth;
                    style.display = origDisplay;
                }

                return me;
            };
        }

        //<feature legacyBrowser>
        if (Ext.isIE8) {
            styleHooks.height = {
                name: 'height',
                set: function(dom, value, el) {
                    var component = el.component,
                        frameInfo, frameBodyStyle;

                    if (component && component._syncFrameHeight && el === component.el) {
                        frameBodyStyle = component.frameBody.dom.style;

                        if (pxRe.test(value)) {
                            frameInfo = component.getFrameInfo();

                            if (frameInfo) {
                                frameBodyStyle.height = (parseInt(value, 10) - frameInfo.height) +
                                                        'px';
                            }
                        }
                        else if (!value || value === 'auto') {
                            frameBodyStyle.height = '';
                        }
                    }

                    dom.style.height = value;
                }
            };

            proto.setHeight = function(height, animate) {
                var component = this.component,
                    frameInfo, frameBodyStyle;

                if (component && component._syncFrameHeight && this === component.el) {
                    frameBodyStyle = component.frameBody.dom.style;

                    if (!height || height === 'auto') {
                        frameBodyStyle.height = '';
                    }
                    else {
                        frameInfo = component.getFrameInfo();

                        if (frameInfo) {
                            frameBodyStyle.height = (height - frameInfo.height) + 'px';
                        }
                    }
                }

                return origSetHeight.call(this, height, animate);
            };

            proto.setSize = function(width, height, animate) {
                var component = this.component,
                    frameInfo, frameBodyStyle;

                if (component && component._syncFrameHeight && this === component.el) {
                    frameBodyStyle = component.frameBody.dom.style;

                    if (!height || height === 'auto') {
                        frameBodyStyle.height = '';
                    }
                    else {
                        frameInfo = component.getFrameInfo();

                        if (frameInfo) {
                            frameBodyStyle.height = (height - frameInfo.height) + 'px';
                        }
                    }
                }

                return origSetSize.call(this, width, height, animate);
            };

            // Override for IE8 which throws an error setting innerHTML when inside
            // an event handler invoked from that element.
            proto.setText = function(text) {
                var dom = this.dom;

                // Remove all child nodes, leave only a single textNode
                if (!(dom.childNodes.length === 1 && dom.firstChild.nodeType === 3)) {
                    while (dom.lastChild && dom.lastChild.nodeType !== 3) {
                        dom.removeChild(dom.lastChild);
                    }

                    dom.appendChild(document.createTextNode());
                }

                // Set the data of the textNode
                dom.firstChild.data = text;
            };

            proto.unselectable = function() {
                origUnselectable.call(this);

                this.dom.onselectstart = function() {
                    return false;
                };
            };
        }
        //</feature>

        function fixTransparent(dom, el, inline, style) {
            var value = style[this.name] || '';

            return transparentRe.test(value) ? 'transparent' : value;
        }

        /*
         * Helper function to create the function that will restore the selection.
         */
        function makeSelectionRestoreFn(activeEl, start, end) {
            return function() {
                activeEl.selectionStart = start;
                activeEl.selectionEnd = end;
            };
        }

        /*
         * Creates a function to call to clean up problems with the work-around for the
         * WebKit RightMargin bug. The work-around is to add "display: 'inline-block'" to
         * the element before calling getComputedStyle and then to restore its original
         * display value. The problem with this is that it corrupts the selection of an
         * INPUT or TEXTAREA element (as in the "I-beam" goes away but the focus remains).
         * To cleanup after this, we need to capture the selection of any such element and
         * then restore it after we have restored the display style.
         *
         * @param {HTMLElement} target The top-most element being adjusted.
         * @private
         */
        function getRightMarginFixCleaner(target) {
            var hasInputBug = supports.DisplayChangeInputSelectionBug,
                hasTextAreaBug = supports.DisplayChangeTextAreaSelectionBug,
                activeEl, tag, start, end;

            if (hasInputBug || hasTextAreaBug) {
                activeEl = Element.getActiveElement();
                tag = activeEl && activeEl.tagName;

                if ((hasTextAreaBug && tag === 'TEXTAREA') ||
                    (hasInputBug && tag === 'INPUT' && activeEl.type === 'text')) {
                    if (Ext.fly(target).isAncestor(activeEl)) {
                        start = activeEl.selectionStart;
                        end = activeEl.selectionEnd;

                        if (Ext.isNumber(start) && Ext.isNumber(end)) { // to be safe...
                            // We don't create the raw closure here inline because that
                            // will be costly even if we don't want to return it (nested
                            // function decls and exprs are often instantiated on entry
                            // regardless of whether execution ever reaches them):
                            return makeSelectionRestoreFn(activeEl, start, end);
                        }
                    }
                }
            }

            return Ext.emptyFn; // avoid special cases, just return a nop
        }

        function fixRightMargin(dom, el, inline, style) {
            var result = style.marginRight,
                domStyle, display;

            // Ignore cases when the margin is correctly reported as 0, the bug only shows
            // numbers larger.
            if (result !== '0px') {
                domStyle = dom.style;
                display = domStyle.display;
                domStyle.display = 'inline-block';
                // eslint-disable-next-line max-len
                result = (inline ? style : dom.ownerDocument.defaultView.getComputedStyle(dom, null)).marginRight;
                domStyle.display = display;
            }

            return result;
        }

        function fixRightMarginAndInputFocus(dom, el, inline, style) {
            var result = style.marginRight,
                domStyle, cleaner, display;

            if (result !== '0px') {
                domStyle = dom.style;
                cleaner = getRightMarginFixCleaner(dom);
                display = domStyle.display;
                domStyle.display = 'inline-block';
                // eslint-disable-next-line max-len
                result = (inline ? style : dom.ownerDocument.defaultView.getComputedStyle(dom, '')).marginRight;
                domStyle.display = display;
                cleaner();
            }

            return result;
        }

        // TODO - this was fixed in Safari 3 - verify if this is still an issue
        // Fix bug caused by this: https://bugs.webkit.org/show_bug.cgi?id=13343
        if (!supports.RightMargin) {
            styleHooks.marginRight = styleHooks['margin-right'] = {
                name: 'marginRight',
                // TODO - Touch should use conditional compilation here or ensure that the
                //      underlying Ext.supports flags are set correctly...
                // eslint-disable-next-line max-len
                get: (supports.DisplayChangeInputSelectionBug || supports.DisplayChangeTextAreaSelectionBug)
                    ? fixRightMarginAndInputFocus
                    : fixRightMargin
            };
        }

        if (!supports.TransparentColor) {
            colorStyles = ['background-color', 'border-color', 'color', 'outline-color'];

            for (i = colorStyles.length; i--;) {
                name = colorStyles[i];
                camel = Element.normalize(name);

                styleHooks[name] = styleHooks[camel] = {
                    name: camel,
                    get: fixTransparent
                };
            }
        }

        // When elements are rotated 80 or 270 degrees, their border, margin and padding hooks
        // need to be rotated as well.
        proto.verticalStyleHooks90 = verticalStyleHooks90 = Ext.Object.chain(styleHooks);
        proto.verticalStyleHooks270 = verticalStyleHooks270 = Ext.Object.chain(styleHooks);

        verticalStyleHooks90.width = styleHooks.height || { name: 'height' };
        verticalStyleHooks90.height = styleHooks.width || { name: 'width' };
        verticalStyleHooks90['margin-top'] = { name: 'marginLeft' };
        verticalStyleHooks90['margin-right'] = { name: 'marginTop' };
        verticalStyleHooks90['margin-bottom'] = { name: 'marginRight' };
        verticalStyleHooks90['margin-left'] = { name: 'marginBottom' };
        verticalStyleHooks90['padding-top'] = { name: 'paddingLeft' };
        verticalStyleHooks90['padding-right'] = { name: 'paddingTop' };
        verticalStyleHooks90['padding-bottom'] = { name: 'paddingRight' };
        verticalStyleHooks90['padding-left'] = { name: 'paddingBottom' };
        verticalStyleHooks90['border-top'] = { name: 'borderLeft' };
        verticalStyleHooks90['border-right'] = { name: 'borderTop' };
        verticalStyleHooks90['border-bottom'] = { name: 'borderRight' };
        verticalStyleHooks90['border-left'] = { name: 'borderBottom' };

        verticalStyleHooks270.width = styleHooks.height || { name: 'height' };
        verticalStyleHooks270.height = styleHooks.width || { name: 'width' };
        verticalStyleHooks270['margin-top'] = { name: 'marginRight' };
        verticalStyleHooks270['margin-right'] = { name: 'marginBottom' };
        verticalStyleHooks270['margin-bottom'] = { name: 'marginLeft' };
        verticalStyleHooks270['margin-left'] = { name: 'marginTop' };
        verticalStyleHooks270['padding-top'] = { name: 'paddingRight' };
        verticalStyleHooks270['padding-right'] = { name: 'paddingBottom' };
        verticalStyleHooks270['padding-bottom'] = { name: 'paddingLeft' };
        verticalStyleHooks270['padding-left'] = { name: 'paddingTop' };
        verticalStyleHooks270['border-top'] = { name: 'borderRight' };
        verticalStyleHooks270['border-right'] = { name: 'borderBottom' };
        verticalStyleHooks270['border-bottom'] = { name: 'borderLeft' };
        verticalStyleHooks270['border-left'] = { name: 'borderTop' };

        /**
         * @property {Boolean} scopeCss
         * @member Ext
         * Set this to true before onReady to prevent any styling from being added to
         * the body element.  By default a few styles such as font-family, and color
         * are added to the body element via a "x-body" class.  When this is set to
         * `true` the "x-body" class is not added to the body element, but is added
         * to the elements of root-level containers instead.
         */
        if (!Ext.scopeCss) {
            Ext.getBody().addCls(Ext.baseCSSPrefix + 'body');
        }

    }, null, { priority: 1500 }); // onReady
});
