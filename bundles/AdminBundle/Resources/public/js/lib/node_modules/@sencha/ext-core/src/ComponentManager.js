/**
 * Provides a registry of all Components (instances of {@link Ext.Component} or any subclass
 * thereof) on a page so that they can be easily accessed by {@link Ext.Component component}
 * {@link Ext.Component#id id} (see {@link #get}, or the convenience method
 * {@link Ext#getCmp Ext.getCmp}).
 *
 * This object also provides a registry of available Component *classes* indexed by a
 * mnemonic code known as the Component's {@link Ext.Component#xtype xtype}. The `xtype`
 * provides a way to avoid instantiating child Components when creating a full, nested
 * config object for a complete Ext page.
 *
 * A child Component may be specified simply as a *config object* as long as the correct
 * `{@link Ext.Component#xtype xtype}` is specified so that if and when the Component
 * needs rendering, the correct type can be looked up for lazy instantiation.
 * 
 * @singleton
 */
Ext.define('Ext.ComponentManager', {
    alternateClassName: 'Ext.ComponentMgr',

    singleton: true,

    mixins: [
        'Ext.mixin.Bufferable'
    ],

    count: 0,

    fixReferencesTimer: null,

    referenceRepairs: 0,

    typeName: 'xtype',

    bufferableMethods: {
        handleDocumentMouseDown: 'asap'
    },

    /**
     * @private
     */
    constructor: function(config) {
        var me = this;

        Ext.apply(me, config);

        me.all = {};
        me.byInstanceId = {};
        me.holders = {};
        me.onAvailableCallbacks = {};
    },

    /**
     * Creates a new Component from the specified config object using the config object's
     * `xtype` to determine the class to instantiate.
     *
     * @param {Object} config A configuration object for the Component you wish to create.
     * @param {String} [defaultType] The `xtype` to use if the config object does not
     * contain a `xtype`. (Optional if the config contains a `xtype`).
     * @return {Ext.Component} The newly instantiated Component.
     */
    create: function(config, defaultType) {
        if (typeof config === 'string') {
            return Ext.widget(config);
        }

        if (config.isComponent) {
            return config;
        }

        if ('xclass' in config) {
            return Ext.create(config.xclass, config);
        }

        return Ext.widget(config.xtype || defaultType, config);
    },

    /**
     * Returns an item by id.
     * @param {String} id The id of the item
     * @return {Object} The item, undefined if not found.
     */
    get: function(id) {
        return this.all[id];
    },

    register: function(component) {
        var me = this,
            id = component.getId(),
            onAvailableCallbacks = me.onAvailableCallbacks;

        //<debug>
        if (id === undefined) {
            Ext.raise('Component id is undefined. Please ensure the component has an id.');
        }

        if (id in me.all) {
            Ext.raise('Duplicate component id "' + id + '"');
        }

        if (component.$iid in me.byInstanceId) {
            Ext.raise('Duplicate component instance id "' + component.$iid + '"');
        }
        //</debug>

        me.all[id] = component;
        me.byInstanceId[component.$iid] = component;

        if (component.nameHolder || component.referenceHolder) {
            me.holders[id] = component;
        }

        ++me.count;

        if (!me.hasFocusListener) {
            me.installFocusListener();
        }

        onAvailableCallbacks = onAvailableCallbacks && onAvailableCallbacks[id];

        if (onAvailableCallbacks && onAvailableCallbacks.length) {
            me.notifyAvailable(component);
        }
    },

    unregister: function(component) {
        var me = this,
            all = me.all,
            byInstanceId = me.byInstanceId,
            holders = me.holders,
            id = component.getId();

        if (id in holders) {
            // Helps IE since delete may just mark the entry as "free" and not
            // release the object by clearing the entry value.
            // TODO find out when IE fixed this
            holders[id] = null;
            delete holders[id];
        }

        all[id] = null;
        delete all[id];

        id = component.$iid;
        byInstanceId[id] = null;
        delete byInstanceId[id];

        --me.count;
    },

    markReferencesDirty: function() {
        var me = this,
            holders = me.holders,
            holder, id;

        if (!Ext.referencesDirty) {
            // Clear all collections (no stale entries)
            for (id in holders) {
                holder = holders[id];

                holder.refs = holder.nameRefs = null;

                if (holder.invalidateChildDirty) {
                    holder.invalidateChildDirty();
                }
            }

            Ext.referencesDirty = true;

            me.fixReferencesTimer = Ext.asap(function() {
                me.fixReferencesTimer = null;
                me.fixReferences();
            });
        }
    },

    fixReferences: function() {
        var me = this,
            all = me.all,
            holders = me.holders,
            holder, id;

        if (Ext.referencesDirty) {
            me.fixReferencesTimer = Ext.unasap(me.fixReferencesTimer);
            // Falsy value but also !== false so we can tell we're fixing the refs
            Ext.referencesDirty = 0;
            ++me.referenceRepairs;

            for (id in holders) {
                holder = holders[id];

                if (holder.beginSyncChildDirty) {
                    holder.beginSyncChildDirty();
                }
            }

            for (id in all) {
                all[id]._fixReference();
            }

            for (id in holders) {
                holder = holders[id];

                if (holder.finishSyncChildDirty) {
                    holder.finishSyncChildDirty();
                }
            }

            Ext.referencesDirty = false;
        }
    },

    /**
     * Registers a function that will be called (a single time) when an item with the specified
     * id is added to the manager. This will happen on instantiation.
     * @param {String} id The item id
     * @param {Function} fn The callback function. Called with a single parameter, the item.
     * @param {Object} scope The scope ('this' reference) in which the callback is executed.
     * Defaults to the item.
     */
    onAvailable: function(id, fn, scope) {
        var me = this,
            callbacks = me.onAvailableCallbacks,
            all = me.all,
            item;

        if (id in all) { // if already an instance, callback immediately
            item = all[id];
            fn.call(scope || item, item);
        }
        else if (id) { // otherwise, queue for dispatch
            if (!Ext.isArray(callbacks[id])) {
                callbacks[id] = [ ];
            }

            callbacks[id].push(function(item) {
                fn.call(scope || item, item);
            });
        }
    },

    /**
    * @private
    */
    notifyAvailable: function(item) {
        var callbacks = this.onAvailableCallbacks[item && item.getId()] || [];

        while (callbacks.length) {
            (callbacks.shift())(item);
        }
    },

    /**
     * Executes the specified function once for each item in the collection.
     * @param {Function} fn The function to execute.
     * @param {String} fn.key The key of the item
     * @param {Number} fn.value The value of the item
     * @param {Number} fn.length The total number of items in the collection ** Removed
     * in 5.0 **
     * @param {Boolean} fn.return False to cease iteration.
     * @param {Object} scope The scope to execute in. Defaults to `this`.
     */
    each: function(fn, scope) {
        Ext.Object.each(this.all, fn, scope);
    },

    /**
     * Gets the number of items in the collection.
     * @return {Number} The number of items in the collection.
     */
    getCount: function() {
        return this.count;
    },

    /**
     * Returns an array of all components
     * @return {Array}
     */
    getAll: function() {
        return Ext.Object.getValues(this.all);
    },

    /**
     * Return the currently active (focused) Component
     *
     * @return {Ext.Component/null} Active Component, or null
     * @private
     */
    getActiveComponent: function() {
        return Ext.Component.from(Ext.dom.Element.getActiveElement());
    },

    // Deliver focus events to Component
    onGlobalFocus: function(info) {
        var me = this,
            event = info.event.chain(),
            infoCopy = Ext.applyIf({ event: event }, info),
            to, from, ancestor, target;

        to = event.toComponent = infoCopy.toComponent = Ext.Component.from(info.toElement);
        from = event.fromComponent = infoCopy.fromComponent = Ext.Component.from(info.fromElement);
        ancestor = me.getCommonAncestor(from, to);

        // Focus moves *within* a component should not cause component focus leave/enter
        if (to !== from) {
            if (from && !from.destroyed && !from.isDestructing()) {
                if (from.handleBlurEvent) {
                    from.handleBlurEvent(infoCopy);
                }

                // Call onFocusLeave on the component axis from which focus is exiting
                for (target = from; target && target !== ancestor; target = target.getRefOwner()) {
                    if (!(target.destroyed || target.destroying)) {
                        event.type = 'focusleave';
                        target.onFocusLeave(event);
                    }
                }
            }

            if (to && !to.destroyed && !to.isDestructing()) {
                if (to.handleFocusEvent) {
                    to.handleFocusEvent(infoCopy);
                }

                // Call onFocusEnter on the component axis to which focus is entering
                for (target = to; target && target !== ancestor; target = target.getRefOwner()) {
                    event.type = 'focusenter';
                    target.onFocusEnter(event);
                }
            }
        }

        for (target = ancestor; target; target = target.getRefOwner()) {
            if (!(target.destroying || target.destroyed)) {
                target.onFocusMove(infoCopy);
            }
        }
    },

    getCommonAncestor: function(compA, compB) {
        if (compA === compB) {
            return compA;
        }

        while (compA && !(compA.isAncestor(compB) || compA === compB)) {
            compA = compA.getRefOwner();
        }

        return compA;
    },

    privates: {
        /**
         * This method reorders the DOM structure of floated components to arrange that the
         * clicked element is last of its siblings, and therefore on the visual "top" of
         * the floated component stack.
         *
         * This is a Bufferable ASAP method invoked directly from Ext.GlobalEvents.
         *
         * We need to use ASAP, not a low priority listener because we need it
         * to run *after* the browser's default response to the event has been
         * processed, ie focus consequences.
         * For example, a Dialog contains a picker field, and the picker field has
         * its floated picker open and focused.
         * The user mousedowns on another field in the dialog. The resulting
         * immediate DOM shuffle to bring the dialog above the picker results
         * in focus being silently lost.
         * @param {type} e The mousedown event
         * @private
         */
        doHandleDocumentMouseDown: function(e) {
            var floatedSelector = Ext.Widget.prototype.floatedSelector,
                targetFloated;

            // If mousedown/pointerdown/touchstart is on a floated Component, ask it to sync
            // its place in the hierarchy.
            if (floatedSelector) {
                targetFloated = Ext.Component.from(e.getTarget(floatedSelector, Ext.getBody()));

                // If the mousedown is in a floated, move it to top.
                if (targetFloated) {
                    targetFloated.toFront(true);
                }
            }
        },

        installFocusListener: function() {
            var me = this;

            Ext.on('focus', me.onGlobalFocus, me);
            me.hasFocusListener = true;
        },

        clearAll: function() {
            var me = this;

            me.all = {};
            me.byInstanceId = {};
            me.holders = {};
            me.onAvailableCallbacks = {};
        },

        /**
         * Find the Widget or Component to which the given Element belongs.
         *
         * @param {Ext.dom.Element/HTMLElement} el The element from which to start to find
         * an owning Component.
         * @param {Ext.dom.Element/HTMLElement} [limit] The element at which to stop upward
         * searching for an owning Component, or the number of Components to traverse before
         * giving up. Defaults to the document's HTML element.
         * @param {String} [selector] An optional {@link Ext.ComponentQuery} selector to
         * filter the target.
         * @return {Ext.Widget/Ext.Component} The widget, component or `null`.
         *
         * @private
         * @since 6.5.0
         */
        from: function(el, limit, selector) {
            var cache = this.all,
                depth = 0,
                target, topmost, cmpId, cmp;

            if (el && el.isEvent) {
                el = el.target;
            }

            target = Ext.getDom(el);

            if (typeof limit !== 'number') {
                topmost = Ext.getDom(limit);
                limit = Number.MAX_VALUE;
            }

            while (target && target.nodeType === 1 && depth < limit && target !== topmost) {
                cmpId = target.getAttribute('data-componentid') || target.id;

                if (cmpId) {
                    cmp = cache[cmpId];

                    if (cmp && (!selector || Ext.ComponentQuery.is(cmp, selector))) {
                        return cmp;
                    }

                    // Increment depth on every *Component* found, not Element
                    depth++;
                }

                target = target.parentNode;
            }

            return null;
        }
    }
}, function(ComponentManager) {
    // Backwards compat:
    ComponentManager.fromElement = ComponentManager.from;

    // No components yet, so nothing is dirty. We need this to be false when the first
    // component is created so that it sets our fixup timer.
    Ext.referencesDirty = false;

    Ext.fixReferences = function() {
        ComponentManager.fixReferences();
    };

    Ext.markReferencesDirty = function() {
        ComponentManager.markReferencesDirty();
    };

    /**
     * This is shorthand reference to {@link Ext.ComponentManager#get}.
     * Looks up an existing {@link Ext.Component Component} by {@link Ext.Component#id id}
     *
     * @method getCmp
     * @param {String} id The component {@link Ext.Component#id id}
     * @return {Ext.Component} The Component, `undefined` if not found, or `null` if a
     * Class was found.
     * @member Ext
     */
    Ext.getCmp = function(id) {
        return ComponentManager.get(id);
    };

    Ext.iidToCmp = function(iid) {
        return ComponentManager.byInstanceId[iid] || null;
    };

    /**
     * @private
     * @deprecated 6.6.0 Inline event handlers are deprecated
     */
    Ext.doEv = function(node, e) {
        var cmp, method, event;

        // The event here is a raw browser event, so don't pass the event directly
        // since from expects an Ext.event.Event
        cmp = Ext.Component.from(e.target);

        if (cmp && !cmp.destroying && !cmp.destroyed && cmp.getEventHandlers) {
            method = cmp.getEventHandlers()[e.type];

            if (method && cmp[method]) {
                event = new Ext.event.Event(e);

                return cmp[method](event);
            }
        }

        return true;
    };
});
