/**
 * A mixin for groups of Focusable things (Components, Widgets, etc) that
 * should respond to arrow keys to navigate among the peers, but keep only
 * one of the peers tabbable by default (tabIndex=0)
 *
 * Some examples: Toolbars, Tab bars, Panel headers, Menus
 */
Ext.define('Ext.mixin.FocusableContainer', {
    extend: 'Ext.Mixin',

    requires: [
        'Ext.util.KeyNav'
    ],

    mixinConfig: {
        id: 'focusablecontainer'
    },

    isFocusableContainer: true,

    /**
     * @cfg {Boolean} [focusableContainer=false] Enable or disable navigation
     * with arrow keys for this FocusableContainer. This option may be useful
     * with nested FocusableContainers, when only the root container should
     * handle keyboard events.
     */
    focusableContainer: false,

    /**
     * @cfg {Boolean} [resetFocusPosition=false] When `true`, FocusableContainer
     * will reset last focused position whenever focus leaves the container.
     * Subsequent tabbing into the container will always focus the first eligible
     * child item.
     *
     * When `false`, subsequent tabbing into the container will focus the child
     * item that was last focused before.
     */
    resetFocusPosition: false,

    /**
     * @cfg {Number} [activeChildTabIndex=0] DOM tabIndex attribute to set on the
     * active Focusable child of this container when using the "Roaming tabindex"
     * technique.
     */
    activeChildTabIndex: 0,

    /**
     * @cfg {Number} [inactiveChildTabIndex=-1] DOM tabIndex attribute to set on
     * inactive Focusable children of this container when using the "Roaming tabindex"
     * technique. This value rarely needs to be changed from its default.
     */
    inactiveChildTabIndex: -1,

    /**
     * @cfg {Boolean} [allowFocusingDisabledChildren=false] Set this to `true`
     * to enable focusing disabled child items via keyboard.
     */
    allowFocusingDisabledChildren: false,

    /**
     * @property {String/Ext.dom.Element} [focusableContainerEl="el"] The name of the element
     * that FocusableContainer should bind its keyboard handler to. Similar to {@link #ariaEl},
     * this name is resolved to the {@link Ext.dom.Element} instance after rendering.
     */
    focusableContainerEl: 'el',

    privates: {
        initFocusableContainer: function(clearChildren) {
            var items, i, len;

            // Allow nested containers to optionally disable
            // children containers' behavior
            if (this.focusableContainer) {
                clearChildren = clearChildren != null ? clearChildren : true;
                this.doInitFocusableContainer(clearChildren);
            }

            // A FocusableContainer instance such as a toolbar could have decided
            // to opt out of FC behavior for some reason; it could have happened
            // after all or almost all child items have been initialized with
            // ownerFocusableContainer reference. We need to clean this up
            // if we're not going to behave like a FocusableContainer after all.
            else {
                items = this.getFocusables();

                for (i = 0, len = items.length; i < len; i++) {
                    items[i].ownerFocusableContainer = null;
                }
            }
        },

        doInitFocusableContainer: function(clearChildren) {
            var me = this,
                el = me.focusableContainerEl,
                child;

            // Resolve property name to the actual Element instance if it hasn't been
            // resolved already; doInitFocusableContainer can be called more than once
            if (!el.isElement) {
                el = me.focusableContainerEl = me[el];
            }

            if (me.initFocusableContainerKeyNav) {
                me.initFocusableContainerKeyNav(el);
            }

            // This flag allows post factum initialization of the focusable container,
            // i.e. when container was empty initially and then some tabbable children
            // were added and we need to clear their tabIndices.
            // This is useful for Panel and Window headers that might have tools
            // added dynamically.
            if (clearChildren) {
                me.clearFocusables();

                if (!me.isDisabled()) {
                    child = me.findNextFocusableChild({ step: 1 }) ||
                            me.findNextFocusableChild({ beforeRender: true });

                    if (child) {
                        me.activateFocusable(child);
                    }
                }
            }

            child = me.findNextFocusableChild({ firstTabbable: true });

            // If we have no potentially focusable children we need to disable arrow key
            // handlers so that they don't interfere with event bubbling.
            me.activateFocusableContainer(!!child && !me.isDisabled());
        },

        initFocusableContainerKeyNav: function(el) {
            var me = this;

            if (!me.focusableKeyNav) {
                el = el || me.focusableContainerEl;

                me.focusableKeyNav = new Ext.util.KeyNav({
                    target: el,
                    disabled: true,
                    eventName: 'keydown',

                    // Our event processing filters out events from input fields.
                    processEvent: me.processFocusableContainerKeyEvent,
                    processEventScope: me,
                    scope: me,

                    tab: me.onFocusableContainerTabKey,
                    enter: {
                        handler: me.onFocusableContainerEnterKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    },
                    space: {
                        handler: me.onFocusableContainerSpaceKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    },
                    up: {
                        handler: me.onFocusableContainerUpKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    },
                    down: {
                        handler: me.onFocusableContainerDownKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    },
                    left: {
                        handler: me.onFocusableContainerLeftKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    },
                    right: {
                        handler: me.onFocusableContainerRightKey,
                        ctrl: false,
                        shift: false,
                        alt: false
                    }
                });
            }
        },

        destroyFocusableContainer: function() {
            this.focusableKeyNav = Ext.destroy(this.focusableKeyNav);
        },

        activateFocusableContainer: function(enable) {
            var keyNav = this.focusableKeyNav;

            if (keyNav) {
                keyNav.setDisabled(!enable);
            }
        },

        isFocusableContainerActive: function() {
            var keyNav = this.focusableKeyNav;

            return keyNav ? !keyNav.disabled : false;
        },

        // Default FocusableContainer implies a flat list of focusable children
        getFocusables: function() {
            return this.items.items;
        },

        initDefaultFocusable: function() {
            var me = this,
                haveFocusable = false,
                items, item, i, len;

            items = me.getFocusables();
            len = items.length;

            if (!len) {
                return;
            }

            // Check if any child Focusable is already active.
            // Note that we're not determining *which* focusable child
            // to focus here, ONLY that we have some focusables.
            for (i = 0; i < len; i++) {
                item = items[i];

                if (!item.isDisabled() && item.isFocusable()) {
                    haveFocusable = true;

                    // DO NOT return an item here! We want to fall through
                    // to findNextFocusableChild below.
                    break;
                }
            }

            if (!haveFocusable) {
                return;
            }

            item = me.findNextFocusableChild({
                items: items,
                step: true
            });

            if (item) {
                me.activateFocusable(item);
            }

            return item;
        },

        clearFocusables: function(skipFocused) {
            var me = this,
                items = me.getFocusables(),
                len = items.length,
                item, i;

            for (i = 0; i < len; i++) {
                item = items[i];

                if (!item.destroyed && item.focusable && item.isTabbable()) {
                    me.deactivateFocusable(item);
                }
            }
        },

        /**
         * @template
         * An overrideable function which preprocesses all key events from within this
         * FocusableContainer. The base implementation vetoes processing of key events from input
         * fields by returning `undefined`. This may be overridden in subclasses with special
         * requirements.
         * @param {Ext.util.Event} e A keyboard event that is significant to the operation
         * of a FocusableContainer.
         * @returns {Ext.util.Event} The event if it is to be processed as a FocusableContainer
         * navigation keystroke, or `undefined` if it is to be ignore by the FocusableContainer
         * navigation machinery.
         */
        processFocusableContainerKeyEvent: function(e) {
            if (!Ext.fly(e.target).isInputField()) {
                return e;
            }
        },

        activateFocusable: function(child) {
            child.setTabIndex(this.activeChildTabIndex);
        },

        deactivateFocusable: function(child) {
            child.setTabIndex(this.inactiveChildTabIndex);
        },

        onFocusableContainerTabKey: function() {
            return true;
        },

        onFocusableContainerEnterKey: function() {
            return true;
        },

        onFocusableContainerSpaceKey: function() {
            return true;
        },

        onFocusableContainerUpKey: function(e) {
            // Default action is to scroll the nearest vertically scrollable container
            e.preventDefault();

            return this.moveChildFocus(e, false);
        },

        onFocusableContainerDownKey: function(e) {
            // Ditto
            e.preventDefault();

            return this.moveChildFocus(e, true);
        },

        onFocusableContainerLeftKey: function(e) {
            // Default action is to scroll the nearest horizontally scrollable container
            e.preventDefault();

            return this.moveChildFocus(e, false);
        },

        onFocusableContainerRightKey: function(e) {
            // Ditto
            e.preventDefault();

            return this.moveChildFocus(e, true);
        },

        getFocusableFromEvent: function(e) {
            var child = Ext.Component.from(e);

            //<debug>
            if (!child) {
                Ext.raise("No focusable child found for keyboard event!");
            }
            //</debug>

            return child;
        },

        moveChildFocus: function(e, forward) {
            var child = this.getFocusableFromEvent(e);

            return this.focusChild(child, forward, e);
        },

        focusChild: function(child, forward) {
            var nextChild = this.findNextFocusableChild({
                child: child,
                step: forward
            });

            if (nextChild) {
                nextChild.focus();
            }

            return nextChild;
        },

        findNextFocusableChild: function(options) {
            // This method is private, so options should always be provided
            var beforeRender = options.beforeRender,
                firstTabbable = options.firstTabbable,
                items, item, child, step, idx, i, len, allowDisabled;

            items = options.items || this.getFocusables();
            step = options.step != null ? options.step : 1;
            child = options.child;

            // Some containers such as Menus need to support arrowing over disabled children
            allowDisabled = !!this.allowFocusingDisabledChildren;

            // If the child is null or undefined, idx will be -1.
            // The loop below will account for that, trying to find
            // the first focusable child from either end (depending on step)
            idx = Ext.Array.indexOf(items, child);

            // It's often easier to pass a boolean for 1/-1
            step = step === true ? 1 : step === false ? -1 : step;

            len = items.length;
            i = step > 0 ? (idx < len ? idx + step : 0) : (idx > 0 ? idx + step : len - 1);

            for (;; i += step) {
                // We're looking for the first or last focusable child
                // and we've reached the end of the items, so punt
                if (idx < 0 && (i >= len || i < 0)) {
                    return null;
                }

                // Loop over forward
                else if (i >= len) {
                    i = -1; // Iterator will increase it once more
                    continue;
                }

                // Loop over backward
                else if (i < 0) {
                    i = len;
                    continue;
                }

                // Looped to the same item, give up
                else if (i === idx) {
                    return null;
                }

                item = items[i];

                if (!item || !item.focusable || (item.isDisabled() && !allowDisabled)) {
                    continue;
                }

                if (firstTabbable) {
                    if (item.isTabbable && item.isTabbable()) {
                        return item;
                    }
                }
                // This loop can be run either at FocusableContainer init time,
                // or later when we need to navigate upon pressing an arrow key.
                // When we're navigating, we have to know exactly if the child is
                // focusable or not, hence only rendered children will make the cut.
                // At the init time item.isFocusable() may return false incorrectly
                // just because the item has not been rendered yet and its focusEl
                // is not defined, so we don't bother to call isFocusable and return
                // the first potentially focusable child.
                else if (beforeRender || (item.isFocusable && item.isFocusable())) {
                    return item;
                }
            }

            return null; // eslint-disable-line no-unreachable
        },

        onFocusEnter: function(e) {
            var me = this,
                target = e.toComponent,
                child;

            // Some FocusableContainers such as Menus are focusable by themselves;
            // when a container becomes focused we need to switch focus to a child
            // item instead, if there are eligible items.
            // Note that when the target is an actual child item, we will activate
            // it in beforeFocusableChildFocus().
            if (target === me) {
                child = me.initDefaultFocusable();

                if (child) {
                    child.focus();
                }
            }

            // Make sure arrow key handlers are enabled.
            me.activateFocusableContainer(true);
        },

        onFocusLeave: function(e) {
            var me = this;

            if (me.resetFocusPosition) {
                me.clearFocusables();
                me.initDefaultFocusable();
            }
        },

        beforeFocusableChildBlur: Ext.privateFn,
        afterFocusableChildBlur: Ext.privateFn,

        beforeFocusableChildFocus: function(child) {
            var me = this;

            if (!me.focusableContainer || me.destroying || me.destroyed) {
                return;
            }

            // Ensure this child is the only one tabbable
            me.clearFocusables();
            me.activateFocusable(child);
        },

        afterFocusableChildFocus: function(child) {
            var me = this;

            if (!me.focusableContainer || me.destroying || me.destroyed) {
                return;
            }

            me.lastFocusedChild = child;
        },

        onFocusableChildAdd: function(child) {
            var me = this;

            if (child.focusable) {
                child.ownerFocusableContainer = me;
            }
        },

        onFocusableChildRemove: function(child) {
            var me = this,
                next;

            child.ownerFocusableContainer = null;

            // If the focused child is being removed, we need to find another item
            // to make it default focusable.
            if (child === me.lastFocusedChild) {
                me.lastFocusedChild = null;

                next = me.initDefaultFocusable();

                // When currently focused child is about to be removed, it can be
                // hidden or destroyed as well and that could cause focus to be
                // lost to the document body. We don't want that.
                if (child.hasFocus) {
                    next = next || child.findFocusTarget();

                    if (next) {
                        next.focus();
                    }
                }
            }

            child = next || me.findNextFocusableChild({ step: 1, beforeRender: true });

            if (!child) {
                me.activateFocusableContainer(false);
            }
        },

        beforeFocusableChildEnable: Ext.privateFn,

        onFocusableChildEnable: function(child) {
            var me = this,
                active;

            if (!me.focusableContainer || me.destroying || me.destroyed) {
                return;
            }

            // Having currently focused child trumps any config
            if (me.containsFocus) {
                active = Ext.ComponentManager.getActiveComponent();

                me.clearFocusables();
                me.activateFocusable(active);
            }
            else if (me.resetFocusPosition || me.lastFocusedChild == null) {
                me.clearFocusables();

                if (child.hasFocus) {
                    me.activateFocusable(child);
                    active = child;
                }
            }
            else {
                me.deactivateFocusable(child);

                // Some Components like Buttons do not render tabIndex attribute
                // when they start their lifecycle disabled, or remove tabIndex
                // if they get disabled later. Subsequently, such Components will
                // reset their tabIndex to default configured value upon enabling.
                // We don't want these children to be tabbable so we reset their
                // tabIndex yet again, unless this child is the last focused one.
                if (child === me.lastFocusedChild) {
                    me.clearFocusables();
                    me.activateFocusable(child);
                }

                active = me.findNextFocusableChild({ firstTabbable: true });
            }

            if (!active) {
                me.initDefaultFocusable();
            }

            // Also make sure arrow key handlers are enabled
            me.activateFocusableContainer(true);
        },

        beforeFocusableChildDisable: function(child) {
            var me = this,
                next;

            if (!me.focusableContainer || me.destroying || me.destroyed) {
                return;
            }

            // When currently focused child is about to be disabled,
            // it may lose the focus as well. For example, Classic buttons
            // will remove tabIndex attribute upon disabling, which
            // in turn will throw focus to the document body and cause
            // onFocusLeave to fire on the FocusableContainer.
            // We're focusing the next sibling to prevent that.
            if (child.hasFocus) {
                next = me.findNextFocusableChild({ child: child }) || child.findFocusTarget();

                // Note that it is entirely possible not to find the nextTarget,
                // e.g. when we're disabling the last button in a toolbar rendered
                // directly into document body. We don't have a good way to handle
                // such cases at present.
                if (next) {
                    next.focus();
                }
            }
        },

        onFocusableChildDisable: function(child) {
            var me = this,
                next;

            if (!me.focusableContainer || me.destroying || me.destroyed) {
                return;
            }

            // If the disabled child was focused before disabling, we should have
            // switched focus to another child and activated it already.
            next = me.findNextFocusableChild({ firstTabbable: true });

            // If not then activate first found child.
            if (!next) {
                next = me.initDefaultFocusable();
            }

            // It is possible that the disabled child was the last focusable child
            // of this container, in which case we need to deactivate keyboard
            // event handlers.
            if (!next) {
                me.activateFocusableContainer(false);
            }
        },

        beforeFocusableChildHide: function(child) {
            return this.beforeFocusableChildDisable(child);
        },

        onFocusableChildHide: function(child) {
            return this.onFocusableChildDisable(child);
        },

        beforeFocusableChildShow: function(child) {
            return this.beforeFocusableChildEnable(child);
        },

        onFocusableChildShow: function(child) {
            return this.onFocusableChildEnable(child);
        },

        // TODO
        onFocusableChildMasked: Ext.privateFn,
        onFocusableChildDestroy: Ext.privateFn,
        onFocusableChildUpdate: Ext.privateFn
    },

    deprecated: {
        7: {
            configs: {
                enableFocusableContainer: 'focusableContainer'
            }
        }
    }
});
