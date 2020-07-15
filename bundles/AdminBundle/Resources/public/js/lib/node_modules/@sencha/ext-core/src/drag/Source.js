/**
 * A wrapper around a DOM element that allows it to be dragged.
 *
 * ## Constraining
 *
 * The {@link #constrain} config gives various options for limiting drag, for example:
 * - Vertical or horizontal only
 * - Minimum/maximum x/y values.
 * - Snap to grid
 * - Constrain to an element or region.
 *
 * See {@link Ext.drag.Constraint} for detailed options.
 *
 *
 *      new Ext.drag.Source({
 *          element: dragEl,
 *          constrain: {
 *              // Drag only vertically in 30px increments
 *              vertical: true,
 *              snap: {
 *                  y: 30
 *              }
 *          }
 *      });
 *
 * ## Data
 *
 * Data representing the underlying drag is driven by the {@link #method!describe} method. This
 * method is called once at the beginning of the drag. It should populate the info object with data
 * using the {@link Ext.drag.Info#setData setData} method. It accepts 2 arguments. 
 * 
 * - The `type` is used to indicate to {@link Ext.drag.Target targets} the type(s) of data being
 * provided.  This allows the {@link Ext.drag.Target target} to decide whether it is able to
 * interact with the source.  All types added are available in {@link Ext.drag.Info#types types}.
 * - The value can be a static value, or a function reference. In the latter case, the function
 * is evaluated when the data is requested.
 *
 * The {@link Ext.drag.Info#getData} method may be called once the drop completes. The data for the
 * relevant type is retrieved. All values from this method return a {@link Ext.Promise} to allow
 * for consistency when dealing with synchronous and asynchronous data.
 *
 * ## Proxy
 *
 * A {@link #proxy} is an element that follows the mouse cursor during a drag. This may be the
 * {@link #element}, a newly created element, or none at all (if the purpose is to just track
 * the cursor).
 *
 * See {@link Ext.drag.proxy.None for details}.
 *
 *      var data = [{
 *          id: 1,
 *          name: 'Adam'
 *      }, {
 *          id: 2,
 *          name: 'Barbara'
 *      }, {
 *          id: 3,
 *          name: 'Charlie'
 *      }];
 *
 *      var tpl = new Ext.XTemplate(
 *          '<div class="container">',
 *              '<tpl for=".">',
 *                  '<div class="child" data-id="{id}">{name}</div>',
 *              '</tpl>',
 *          '</div>'
 *      );
 *
 *      var el = tpl.append(Ext.getBody(), data);
 *
 *      new Ext.drag.Source({
 *          element: el,
 *          handle: '.child',
 *          proxy: {
 *              type: 'placeholder',
 *              getElement: function(info) {
 *                  return Ext.getBody().createChild({
 *                      cls: 'foo',
 *                      html: info.eventTarget.innerHTML
 *                  });
 *              }
 *          }
 *      });
 *       
 *
 * ## Handle
 *
 * A {@link #handle} is a CSS selector that allows certain child elements of the {@link #element}
 * to begin a drag. This is useful in 2 case:
 * - Where only a certain part of the element should trigger a drag, but the whole element should
 * move.
 * - When there are several repeated elements that may represent objects. 
 * 
 * In the example below, each child element becomes draggable and
 * the describe method is used to extract the id from the DOM element.
 *
 *
 *      var data = [{
 *          id: 1,
 *          name: 'Adam'
 *      }, {
 *          id: 2,
 *          name: 'Barbara'
 *      }, {
 *          id: 3,
 *          name: 'Charlie'
 *      }];
 *
 *      var tpl = new Ext.XTemplate(
 *          '<div class="container">',
 *              '<tpl for=".">',
 *                  '<div class="child" data-id="{id}">{name}</div>',
 *              '</tpl>',
 *          '</div>'
 *      );
 *
 *      var el = tpl.append(Ext.getBody(), data);
 *
 *      new Ext.drag.Source({
 *          element: el,
 *          handle: '.child',
 *          describe: function(info) {
 *              info.setData('item', Ext.fly(info.eventTarget).getAttribute('data-id'));
 *          }
 *      });
 *  
 */
Ext.define('Ext.drag.Source', {
    extend: 'Ext.drag.Item',

    defaultIdPrefix: 'source-',

    requires: [
        'Ext.GlobalEvents',
        'Ext.drag.Constraint'
    ],

    config: {
        /**
         * @cfg {Boolean/String/String[]} activeOnLongPress
         * `true` to always begin a drag with longpress. `false` to
         * never drag with longpress. If a string (or strings) are passed, it should
         * correspond to the pointer event type that should initiate a a drag on
         * longpress. See {@link Ext.event.Event#pointerType} for available types.
         */
        activateOnLongPress: false,

        /**
         * @cfg {String} activeCls
         * A css class to add to the {@link #element} while dragging is
         * active.
         */
        activeCls: null,

        /**
         * @cfg {Object/Ext.util.Region/Ext.dom.Element} constrain
         *
         * Adds constraining behavior for this drag source. See {@link Ext.drag.Constraint} for
         * configuration options. As a shortcut, a {@link Ext.util.Region Region} 
         * or {@link Ext.dom.Element} may be passed, which will be mapped to the 
         * appropriate configuration on the constraint.
         */
        constrain: null,

        /**
         * @cfg {String} handle
         * A CSS selector to identify child elements of the {@link #element} that will cause
         * a drag to be activated. If this is not specified, the entire {@link #element} will
         * be draggable.
         */
        handle: null,

        local: null,

        // @cmd-auto-dependency {aliasPrefix: "drag.proxy."}
        /**
         * @cfg {String/Object/Ext.drag.proxy.Base} proxy
         * The proxy to show while this element is dragging. This may be
         * the alias, a config, or instance of a proxy.
         *
         * See {@link Ext.drag.proxy.None None}, {@link Ext.drag.proxy.Original Original}, 
         * {@link Ext.drag.proxy.Placeholder Placeholder}.
         */
        proxy: 'original',

        /**
         * @cfg {Boolean/Object} revert
         * `true` (or an animation configuration) to animate the {@link #proxy} (which may be
         * the {@link #element}) back to the original position after drag.
         */
        revert: false
    },

    /**
     * @cfg {Function} describe
     * See {@link #method-describe}.
     */

    /**
     * @event beforedragstart
     * Fires before drag starts on this source. Return `false` to cancel the drag.
     * 
     * @param {Ext.drag.Source} this This source.
     * @param {Ext.drag.Info} info The drag info.
     * @param {Ext.event.Event} event The event.
     */

    /**
     * @event dragstart
     * Fires when the drag starts on this source.
     * 
     * @param {Ext.drag.Source} this This source.
     * @param {Ext.drag.Info} info The drag info.
     * @param {Ext.event.Event} event The event.
     */

    /**
     * @event dragmove
     * Fires continuously as this source is dragged.
     * 
     * @param {Ext.drag.Source} this This source.
     * @param {Ext.drag.Info} info The drag info.
     * @param {Ext.event.Event} event The event.
     */

    /**
     * @event dragend
     * Fires when the drag ends on this source.
     * 
     * @param {Ext.drag.Source} this This source.
     * @param {Ext.drag.Info} info The drag info.
     * @param {Ext.event.Event} event The event.
     */

    /**
     * @event dragcancel
     * Fires when a drag is cancelled.
     *
     * @param {Ext.drag.Source} this This source.
     * @param {Ext.drag.Info} info The drag info.
     * @param {Ext.event.Event} event The event.
     */

    /**
     * @property {Boolean} dragging
     * `true` if this source is currently dragging.
     *
     * @protected
     */
    dragging: false,

    constructor: function(config) {
        var describe = config && config.describe;

        if (describe) {
            this.describe = describe;

            // Don't mutate the object the user passed. Need to do this
            // here otherwise initConfig will complain about writing over
            // the method.
            config = Ext.apply({}, config);
            delete config.describe;
        }

        this.callParent([config]);

        // Use bracket syntax to prevent Cmd from creating an
        // auto dependency. Will be pulled in by the target if
        // required.
        this.manager = Ext.drag['Manager']; // eslint-disable-line dot-notation
    },

    /**
     * @method
     * Sets up the underlying data that describes the drag. This method
     * is called once at the start of the drag operation.
     *
     * Data should be set on the {@link Ext.drag.Info info} using the 
     * {@link Ext.drag.Info#setData setData} method. See 
     * {@link Ext.drag.Info#setData setData} for more information.
     *
     * This method should not be called by user code.
     * 
     * @param {Ext.drag.Info} info The drag info.
     *
     * @protected
     */
    describe: Ext.emptyFn,

    /**
     * Checks whether this source is actively dragging.
     * @return {Boolean} `true` if this source is dragging.
     */
    isDragging: function() {
        return this.dragging;
    },

    /**
     * @method
     * Called before a drag starts. Return `false` to veto the drag.
     * @param {Ext.drag.Info} The drag info.
     *
     * @return {Boolean} `false` to veto the drag.
     *
     * @protected
     * @template
     */
    beforeDragStart: Ext.emptyFn,

    /**
     * @method
     * Called when a drag is cancelled.
     *
     * @protected
     * @template
     */
    onDragCancel: Ext.emptyFn,

    /**
     * @method
     * Called when a drag ends.
     *
     * @protected
     * @template
     */
    onDragEnd: Ext.emptyFn,

    /**
     * @method
     * Called for each move in a drag.
     *
     * @protected
     * @template
     */
    onDragMove: Ext.emptyFn,

    /**
     * @method
     * Called when a drag starts.
     *
     * @protected
     * @template
     */
    onDragStart: Ext.emptyFn,

    applyActivateOnLongPress: function(activateOnLongPress) {
        if (typeof activateOnLongPress === 'string') {
            activateOnLongPress = [activateOnLongPress];
        }

        return activateOnLongPress;
    },

    updateActivateOnLongPress: function(activateOnLongPress) {
        if (!this.isConfiguring) {
            this.setupListeners();
        }
    },

    updateActiveCls: function(cls, oldCls) {
        var el;

        if (this.dragging) {
            el = this.getElement();

            el.replaceCls(oldCls, cls);
        }
    },

    applyConstrain: function(constrain) {
        if (constrain && !constrain.$isClass) {
            if (constrain.isRegion) {
                constrain = {
                    region: constrain
                };
            }
            else if (constrain.isElement || !Ext.isObject(constrain)) {
                constrain = {
                    element: constrain
                };
            }

            constrain = Ext.apply({
                source: this
            }, constrain);

            constrain = Ext.Factory.dragConstraint(constrain);
        }

        return constrain;
    },

    updateElement: function(element, oldElement) {
        // We can't bind/unbind these listeners with getElListeners because
        // they will conflict with the dragstart gesture event
        if (oldElement && !oldElement.destroyed) {
            oldElement.un('dragstart', 'stopNativeDrag', this);
        }

        if (element && !this.getHandle()) {
            element.setTouchAction({
                panX: false,
                panY: false
            });

            // Suppress translation and delegation for this to avoid event firing on
            // synthetic dragstart published by Gesture from pointermove. We need the
            // native event here.
            element.on('dragstart', 'stopNativeDrag', this, { translate: false, delegated: false });
        }

        this.callParent([ element, oldElement ]);
    },

    updateHandle: function() {
        if (!this.isConfiguring) {
            this.setupListeners();
        }
    },

    applyProxy: function(proxy) {
        if (proxy) {
            proxy = Ext.Factory.dragproxy(proxy);
        }

        return proxy;
    },

    updateProxy: function(proxy, oldProxy) {
        if (oldProxy) {
            oldProxy.destroy();
        }

        if (proxy) {
            proxy.setSource(this);
        }
    },

    resolveListenerScope: function() {
        var ownerCmp = this.ownerCmp,
            a = arguments;

        if (ownerCmp) {
            return ownerCmp.resolveListenerScope.apply(ownerCmp, a);
        }

        return this.callParent(a);
    },

    destroy: function() {
        var me = this;

        me.manager = me.initialEvent = null;
        me.setConstrain(null);
        me.setProxy(null);

        me.callParent();
    },

    privates: {
        /**
         * @property {String} draggingCls
         * A class to add while dragging to give a high z-index and
         * disable pointer events.
         *
         * @private
         */
        draggingCls: Ext.baseCSSPrefix + 'drag-dragging',

        /**
         * @property {Ext.drag.Info} info
         * The info. Only available while a drag is active.
         *
         * @private
         */
        info: null,

        /**
         * @property {String} revertCls
         * A class to add to the proxy element while a revert is active.
         *
         * @private
         */
        revertCls: Ext.baseCSSPrefix + 'drag-revert',

        canActivateOnLongPress: function(e) {
            var activate = this.getActivateOnLongPress();

            /* eslint-disable-next-line max-len */
            return !!(activate && (activate === true || Ext.Array.contains(activate, e.pointerType)));
        },

        /**
         * Perform any cleanup after a drag.
         *
         * @private
         */
        dragCleanup: function(info) {
            var me = this,
                cls = me.getActiveCls(),
                proxy = me.getProxy(),
                el = me.getElement(),
                proxyEl = info ? info.proxy.element : null;

            if (cls) {
                el.removeCls(cls);
            }

            if (proxyEl) {
                proxyEl.removeCls(me.draggingCls);
            }

            proxy.cleanup(info);

            me.dragging = false;
            me.initialEvent = me.info = null;
        },

        /**
         * @method getElListeners
         * @inheritdoc
         */
        getElListeners: function() {
            var handle = this.getHandle(),
                o = {
                    touchstart: 'handleTouchStart',
                    dragstart: 'handleDragStart',
                    drag: 'handleDragMove',
                    dragend: 'handleDragEnd',
                    dragcancel: 'handleDragCancel'
                };

            if (handle) {
                o.dragstart = {
                    fn: o.dragstart,
                    delegate: handle
                };
            }

            if (this.getActivateOnLongPress()) {
                o.longpress = 'handleLongPress';
            }

            return o;
        },

        /**
         * Called when a drag is cancelled.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleDragCancel: function(e) {
            var me = this,
                info = me.info,
                manager = me.manager;

            if (manager) {
                manager.onDragCancel(info, e);
            }

            me.onDragCancel(info);

            if (me.hasListeners.dragcancel) {
                me.fireEvent('dragcancel', me, info, e);
            }

            Ext.fireEvent('dragcancel', me, info, e);

            me.dragCleanup(info);
        },

        /**
         * Called when a drag is ended.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleDragEnd: function(e) {
            if (!this.dragging) {
                return;
            }

            /* eslint-disable-next-line vars-on-top */
            var me = this,
                manager = me.manager,
                revert = me.getRevert(),
                info = me.info,
                proxy = info.proxy;

            info.update(e);

            if (manager) {
                manager.onDragEnd(info, e);
            }

            me.onDragEnd(info);

            if (me.hasListeners.dragend) {
                me.fireEvent('dragend', me, info, e);
            }

            Ext.fireEvent('dragend', me, info, e);

            proxy = proxy.instance;

            if (revert && proxy) {
                proxy.dragRevert(info, me.revertCls, revert, function() {
                    me.dragCleanup(info);
                });
            }
            else {
                me.dragCleanup(info);
            }
        },

        /**
         * Called for each drag movement.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleDragMove: function(e) {
            var me = this,
                info = me.info,
                manager = me.manager;

            if (!me.dragging) {
                return;
            }

            e.stopPropagation();
            e.claimGesture();

            info.update(e);

            if (manager) {
                manager.onDragMove(info, e);
            }

            me.onDragMove(info);

            if (me.hasListeners.dragmove) {
                me.fireEvent('dragmove', me, info, e);
            }
        },

        /**
         * Called when a drag is started.
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleDragStart: function(e) {
            var me = this,
                hasListeners = me.hasListeners,
                manager = me.manager,
                constrain = me.getConstrain(),
                initialEvent = me.initialEvent,
                el, cls, info, cancel, proxyEl;

            if (me.preventStart(e)) {
                return false;
            }

            if (hasListeners.initdragconstraints) {
                // This (private) event allows drag constraints to be adjusted "JIT"
                // (used by modern sliders)
                me.fireEvent('initdragconstraints', me, e);
            }

            me.info = info = new Ext.drag.Info(me, initialEvent);

            me.setup(info);

            if (constrain) {
                constrain.onDragStart(info);
            }

            info.update(e, true);

            cancel = me.beforeDragStart(info) === false;

            if (!cancel && hasListeners.beforedragstart) {
                cancel = me.fireEvent('beforedragstart', me, info, e) === false;
            }

            if (cancel) {
                me.dragCleanup();

                return false;
            }

            e.claimGesture();
            me.dragging = true;

            cls = me.getActiveCls();
            el = me.getElement();

            if (cls) {
                el.addCls(cls);
            }

            proxyEl = info.proxy.element;

            if (proxyEl) {
                proxyEl.addCls(me.draggingCls);
            }

            info.update(e);

            if (manager) {
                manager.onDragStart(info, e);
            }

            me.onDragStart(info);

            if (hasListeners.dragstart) {
                me.fireEvent('dragstart', me, info, e);
            }

            Ext.fireEvent('dragstart', me, info, e);
        },

        /**
         * Called when a longpress is started on this target (which may lead to a drag)
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleLongPress: function(e) {
            if (!this.isDisabled() && this.canActivateOnLongPress(e)) {
                this.initialEvent = e;
                e.startDrag();
            }
        },

        /**
         * Called when a touch starts on this target (which may lead to a drag).
         * @param {Ext.event.Event} e The event.
         *
         * @private
         */
        handleTouchStart: function(e) {
            if (!this.isDisabled()) {
                this.initialEvent = e;
            }
        },

        preventStart: function(e) {
            return this.isDisabled() || (!e.longpress && this.canActivateOnLongPress(e));
        },

        /**
         * Allow for any setup as soon as the info object is created.
         *
         * @private
         */
        setup: Ext.privateFn,

        stopNativeDrag: function(e) {
            e.preventDefault();
        }
    }
});
