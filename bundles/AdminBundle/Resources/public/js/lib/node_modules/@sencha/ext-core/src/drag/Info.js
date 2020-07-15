/**
 * This class is used to unify information for a specific drag instance.
 * This object is passed to template methods and events to obtain
 * details about the current operation.
 *
 * It is not expected that this class will be created by user code.
 */
Ext.define('Ext.drag.Info', {

    requires: ['Ext.Promise'],

    constructor: function(source, e) {
        // Internally we will call the constructor empty when we want to clone.
        if (!source) {
            return;
        }

        /* eslint-disable-next-line vars-on-top */
        var me = this,
            local = source.getLocal(),
            el, proxyEl, proxy, x, xy, y, pageXY, elPageXY;

        me.source = source;
        me.local = local;

        xy = me.getEventXY(e);
        pageXY = e.getXY();

        el = source.getElement();
        elPageXY = el.getXY();
        xy = local ? el.getLocalXY() : elPageXY;

        x = xy[0];
        y = xy[1];

        me.initialEvent = e;
        me.eventTarget = e.target;

        me.cursor = {
            current: {
                x: x,
                y: y
            },
            delta: {
                x: 0,
                y: 0
            },
            initial: {
                x: pageXY[0],
                y: pageXY[1]
            },
            offset: {
                x: pageXY[0] - elPageXY[0],
                y: pageXY[1] - elPageXY[1]
            }
        };

        me.element = {
            current: {
                x: x,
                y: y
            },
            delta: {
                x: 0,
                y: 0
            },
            initial: {
                x: x,
                y: y
            }
        };

        me.proxy = {
            instance: source.getProxy(),
            current: {
                x: x,
                y: y
            },
            delta: {
                x: 0,
                y: 0
            },
            initial: {
                x: x,
                y: y
            },
            element: el,
            isUnderCursor: false,
            isElement: true
        };

        me.types = [];
        me.data = {};

        source.describe(me);

        proxy = me.proxy;
        proxyEl = proxy.instance.setupElement(me);

        proxy.isElement = proxyEl === source.getElement();
        proxy.element = proxyEl;

        if (proxyEl) {
            proxy.width = proxyEl.getWidth();
            proxy.height = proxyEl.getHeight();
        }

        if (proxy.isElement) {
            // If they are the same we don't need to keep track of both
            el = me.element;
            el.current = proxy.current;
            el.delta = proxy.delta;
        }

        me.needsCursorCheck = proxy.element && source.manager && source.manager.pointerBug;
    },

    /**
     * @property {Object} cursor
     * Information about the cursor position. Not available when
     * {@link #isNative} is `true`.
     * 
     *
     * @property {Object} cursor.current
     * The current cursor position.
     *
     * @property {Number} cursor.current.x
     * The current x position.
     *
     * @property {Number} cursor.current.y
     * The current y position.
     *
     *
     * @property {Object} cursor.delta
     * The change in cursor position.
     *
     * @property {Number} cursor.delta.x
     * The change in x position.
     *
     * @property {Number} cursor.delta.y
     * The change in y position.
     * 
     *
     * @property {Object} cursor.initial
     * The intial cursor position.
     *
     * @property {Number} cursor.initial.x
     * The initial x position.
     *
     * @property {Number} cursor.initial.y
     * The initial y position.
     * 
     *
     * @property {Object} cursor.offset
     * The offset from the cursor to the top/left of
     * the {@link Ext.drag.Source#element element}.
     * 
     * @property {Number} cursor.offset.x
     * The x offset.
     *
     * @property {Number} cursor.offset.y
     * The y offset.
     */
    cursor: null,

    /**
     * @property {Object} element
     * Information about the {@link Ext.drag.Source#element} position. 
     * Not available when {@link #isNative} is `true`.
     * 
     *
     * @property {Object} element.current
     * The current element position.
     *
     * @property {Number} element.current.x
     * The current x position.
     *
     * @property {Number} element.current.y
     * The current y position.
     *
     *
     * @property {Object} element.delta
     * The change in element position.
     *
     * @property {Number} element.delta.x
     * The change in x position.
     *
     * @property {Number} element.delta.y
     * The change in y position.
     * 
     *
     * @property {Object} element.initial
     * The intial element position.
     *
     * @property {Number} element.initial.x
     * The initial x position.
     *
     * @property {Number} element.initial.y
     * The initial y position.
     */
    element: null,

    /**
     * @property {HTMLElement} eventTarget
     * The event target that the drag started on.
     *
     * Not available when {@link #isNative} is `true`.
     */
    eventTarget: null,

    /**
     * @property {FileList} files
     * A list of files included for this drag. See:
     * https://developer.mozilla.org/en/docs/Web/API/FileList
     *
     * Only available when {@link #isNative} is `true`.
     */
    files: null,

    /**
     * @property {Boolean} isNative
     * `true` if the drag is a native drag event, for example
     * a file draggedi nto the browser.
     */
    isNative: false,

    /**
     * @property {Object} proxy
     * Information about the {@link Ext.drag.Source#proxy} position.
     * This may be the actual {@link Ext.drag.Source#element}.
     * Not available when {@link #isNative} is `true`.
     * 
     *
     * @property {Object} proxy.current
     * The current proxy position.
     *
     * @property {Number} proxy.current.x
     * The current x position.
     *
     * @property {Number} proxy.current.y
     * The current y position.
     *
     *
     * @property {Object} proxy.delta
     * The change in proxy position.
     *
     * @property {Number} proxy.delta.x
     * The change in x position.
     *
     * @property {Number} proxy.delta.y
     * The change in y position.
     * 
     *
     * @property {Object} proxy.initial
     * The intial proxy position.
     *
     * @property {Number} proxy.initial.x
     * The initial x position.
     *
     * @property {Number} proxy.initial.y
     * The initial y position.
     *
     * @property {Ext.dom.Element} proxy.element
     * The proxy element.
     *
     * @property {Boolean} proxy.isElement
     * `true` if the proxy is the {@link Ext.drag.Source#element}.
     *
     * @property {Boolean} proxy.isUnderCursor
     * `true` if the alignment causes the proxy to be under the cursor.
     */
    proxy: null,

    /**
     * @property {Ext.drag.Source} source
     * The drag source. Not available when {@link #isNative} is `true`.
     */
    source: null,

    /**
     * @property {Ext.drag.Target} target
     * The active target. `null` if not over a target.
     */
    target: null,

    /**
     * @property {String[]} types
     * The data types this drag provides. Added via {@link #setData}.
     */
    types: null,

    /**
     * @property {Boolean} valid
     * `true` if the {@link #target} is valid. See {@link Ext.drag.Target} for
     * information about validity. `false` if there is no target.
     */
    valid: false,

    /**
     * Clear the data for a particular type.
     * @param {String} type The type.
     */
    clearData: function(type) {
        Ext.Array.remove(this.types, type);
        delete this.data[type];
    },

    /**
     * Create a copy of this object with the current state.
     * @return {Ext.drag.Info} A copy of this object.
     */
    clone: function() {
        var me = this,
            ret = new Ext.drag.Info();

        ret.cursor = Ext.merge({}, me.cursor);
        ret.data = Ext.apply({}, me.data);
        ret.element = Ext.merge({}, me.element);
        ret.eventTarget = me.eventTarget;
        ret.proxy = Ext.merge({}, me.proxy);
        ret.source = me.source;
        ret.target = me.target;
        ret.types = Ext.Array.clone(me.types);
        ret.valid = me.valid;

        return ret;
    },

    /**
     * Get data for this drag. This method may only be called once the drop completes.
     * 
     * @param {String} type The type of data to retrieve. Must be in the {@link #types}.
     * See also {@link #setData}.
     * 
     * @return {Ext.Promise} The data. If the produced data is not a {@link Ext.Promise},
     * it will be wrapped in one.
     */
    getData: function(type) {
        var me = this,
            data = me.data,
            dt = me.dataTransfer,
            ret;

        if (dt) {
            ret = dt.getData(type);
        }
        else {
            //<debug>
            if (!me.finalized) {
                Ext.raise('Unable to call getData until the drop is complete');
            }
            //</debug>

            ret = data[type];

            if (typeof ret === 'function') {
                data[type] = ret = ret.call(me.source, me);
            }

            if (!ret && ret !== 0) {
                ret = '';
            }
        }

        return Ext.Promise.resolve(ret);
    },

    /**
     * Set data for this drag. Multiple types may be registered. Each type will be
     * added to {@link #types}.
     * 
     * @param {String} type The type of data being registered.
     * @param {Object/Function} value The value being registered. If a function
     * is provided it will be evaluated if requested when the drop completes. The
     * function should return a value or a {@link Ext.Promise} that will produce a value.
     */
    setData: function(type, value) {
        Ext.Array.include(this.types, type);
        this.data[type] = value;
    },

    destroy: function() {
        var me = this;

        me.eventTarget = me.data = me.proxy = me.targetMap = me.targetMap =
        me.types = me.elementMap = me.possibleTargets = me.target = null;

        me.callParent();
    },

    privates: {
        /**
         * @property {Object} data
         * The underlying data for this drag. Keyed by type, the value
         * can be a value or a function to return a value.
         *
         * @private
         */
        data: null,

        /**
         * @property {DataTransfer} dataTransfer
         * The browser native dataTransfer object, if available.
         *
         * @private
         */
        dataTransfer: null,

        /**
        * @property {Object} elementMap
        * A map of targets that is keyed by the element 
        * id for each drag target. Only provided in point mode.
        *
        * @private
        */
        elementMap: null,

        /**
        * @property {Object} possibleTargets
        * The map of targets that this drag can interact with, meaning
        * those with not matching groups and disabled items are excluded.
        *
        * @private
        */
        possibleTargets: null,

        /**
        * @property {Object} targetMap
        * A map of targets that is keyed by id when the drag began.
        *
        * @private
        */
        targetMap: null,

        copyNativeData: function(target, e) {
            var dt = e.browserEvent.dataTransfer;

            this.target = target;
            this.dataTransfer = dt;
            this.files = dt.files;
        },

        /**
         * Notify that the drag is finished and final processing can occur.
         *
         * @private
         */
        finalize: function() {
            var me = this,
                target = me.target;

            me.finalized = true;

            if (target) {
                target.info = null;
                target.handleDrop(me);
            }
        },

        /**
         * Calculate the current position of the proxy element, taking
         * into account constraints.
         * @param {Number} x The cursor x position.
         * @param {Number} y The cursor y position.
         *
         * @return {Number[]} The position.
         *
         * @private
         */
        getAlignXY: function(x, y) {
            var me = this,
                source = me.source,
                cursorOffset = me.cursor.offset,
                proxy = source.getProxy(),
                proxyEl = me.proxy.element,
                constrain = source.getConstrain(),
                xy = [x, y];

            if (proxyEl) {
                if (me.proxy.isElement) {
                    xy[0] -= cursorOffset.x;
                    xy[1] -= cursorOffset.y;
                }
                else {
                    xy = proxy.adjustCursorOffset(me, xy);
                }

                if (constrain) {
                    xy = constrain.constrain(xy, me);
                }
            }

            return xy;
        },

        getEventXY: function(e) {
            var xy = e.getXY(), // page coordinates
                source = this.source;

            if (this.local) {
                xy = source.convertToLocalXY(xy);
            }

            return xy;
        },

        onNativeDragEnter: function(target, e) {
            var me = this;

            me.valid = target.accepts(me);
            target.info = me;

            me.copyNativeData(target, e);
        },

        onNativeDragLeave: function(target, e) {
            var me = this;

            // With native events, enter fires before leave, so when the leave fires
            // check that we are the current target, another target may have already
            // taken over here
            if (me.target === target) {
                target.info = null;
                me.valid = false;
                me.target = me.dataTransfer = me.files = null;
            }
        },

        onNativeDragMove: function(target, e) {
            this.copyNativeData(target, e);
        },

        onNativeDrop: function(target, e) {
            this.copyNativeData(target, e);
            target.info = null;
        },

        /**
         * Mark the target as active for the drag.
         * @param {Ext.drag.Target} target The target.
         *
         * @private
         */
        setActive: function(target) {
            var me = this,
                source = me.source,
                current = me.target,
                changed = current !== target;

            if (current && changed) {
                current.handleDragLeave(me);
                current.info = null;
            }

            me.target = target;

            if (target) {
                if (changed) {
                    me.valid = !!me.possibleTargets[target.getId()] && target.accepts(me) !== false;
                    target.handleDragEnter(me);
                    target.info = me;
                }

                target.handleDragMove(me);
            }
            else {
                me.valid = false;
            }

            if (changed) {
                source.getProxy().update(me);
            }
        },

        /**
         * Update with the current position information.
         * @param {Ext.event.Event} event The event.
         * @param {Boolean} beforeStart `true` if the update is occurring
         * before the drag starts.
         *
         * @private
         */
        update: function(event, beforeStart) {
            var me = this,
                xy = me.getEventXY(event),
                x = xy[0],
                y = xy[1],
                alignXY = me.getAlignXY(x, y),
                alignX = alignXY[0],
                alignY = alignXY[1],
                proxyData = me.proxy,
                cursor = me.cursor,
                current = cursor.current,
                delta = cursor.delta,
                initial = cursor.initial,
                proxy = proxyData.instance;

            current.x = x;
            current.y = y;

            delta.x = x - initial.x;
            delta.y = y - initial.y;

            current = proxyData.current;
            delta = proxyData.delta;
            initial = proxyData.initial;

            current.x = alignX;
            current.y = alignY;
            delta.x = alignX - initial.x;
            delta.y = alignY - initial.y;

            if (me.needsCursorCheck) {
                proxyData.isUnderCursor = !(x < alignX || y < alignY || x > proxyData.width + alignX || y > proxyData.height + alignY); // eslint-disable-line max-len
            }

            if (!beforeStart && proxy) {
                proxy.setXY(me, alignXY);
            }
        }
    }
});
