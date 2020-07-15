/**
 * This mixin implements focus management functionality in Widgets and Components
 */
Ext.define('Ext.mixin.Focusable', {
    mixinId: 'focusable',

    $isFocusableEntity: true,

    // tabIndex config is now defined in Ext.Component

    /**
     * @property {Boolean} focusable
     * @readonly
     *
     * `true` for keyboard interactive Components or Widgets, `false` otherwise.
     * For Containers, this property reflects interactiveness of the
     * Container itself, not its children. See {@link #isFocusable}.
     *
     * **Note:** It is not enough to set this property to `true` to make
     * a component keyboard interactive. You also need to make sure that
     * the component's {@link #focusEl} is reachable via Tab key (tabbable).
     * See also {@link #tabIndex}.
     */
    focusable: false,

    /**
     * @property {Boolean} hasFocus `true` if this component's {@link #focusEl} is focused.
     * See also {@link #containsFocus}.
     *
     * @readonly
     */
    hasFocus: false,

    /**
     * @property {Boolean} containsFocus `true` if this currently focused element
     * is within this Component's or Container's hierarchy. This property is set separately
     * from {@link #hasFocus}, and can be `true` when `hasFocus` is `false`.
     *
     * Examples:
     *
     * + Text field with input element focused would be:
     *      focusable: true,
     *      hasFocus: true,
     *      containsFocus: true
     *
     * + Date field with drop-down picker currently focused would be:
     *      focusable: true,
     *      hasFocus: false,
     *      containsFocus: true
     *
     * + Form Panel with a child input field currently focused would be:
     *      focusable: false,
     *      hasFocus: false,
     *      containsFocus: true
     *
     * See also {@link #hasFocus}.
     *
     * @readonly
     */
    containsFocus: false,

    /**
     * @cfg {String} [focusCls='x-focused'] CSS class that will be added to focused
     * component's {@link #focusClsEl}, and removed when component blurs.
     */
    focusCls: Ext.baseCSSPrefix + 'focused',

    /**
     * @property {Ext.dom.Element} focusEl The element that will be focused
     * when {@link #method!focus} method is called on this component. Usually this is
     * the same element that receives focus via mouse clicks, taps, and pressing
     * Tab key.
     */
    focusEl: 'el',

    /**
     * @property {Ext.dom.Element} focusClsEl The element that will have the
     * {@link #focusCls} applied when component's {@link #focusEl} is focused.
     */

    /**
     * @event focus
     * Fires when this Component's {@link #focusEl} receives focus.
     * @param {Ext.Component/Ext.Widget} this
     * @param {Ext.event.Event} event The focus event.
     */

    /**
     * @event blur
     * Fires when this Component's {@link #focusEl} loses focus.
     * @param {Ext.Component} this
     * @param {Ext.event.Event} event The blur event.
     */

    /**
     * @event focusenter
     * Fires when focus enters this Component's hierarchy.
     * @param {Ext.Component} this
     * @param {Ext.event.Event} event The focusenter event.
     */

    /**
     * @event focusleave
     * Fires when focus leaves this Component's hierarchy.
     * @param {Ext.Component} this
     * @param {Ext.event.Event} event The focusleave event.
     */

    /**
     * Returns the main focus holder element associated with this Focusable, i.e.
     * the element that will be focused when Focusable's {@link #method!focus} method is
     * called. For most Focusables, this will be the {@link #focusEl}.
     *
     * @return {Ext.dom.Element}
     * @protected
     */
    getFocusEl: function(/* private e */) {
        var focusEl = this.focusEl;

        return focusEl && focusEl.dom ? focusEl : null;
    },

    /**
     * Returns the element used to apply focus styling CSS class when Focusable's
     * {@link #focusEl} becomes focused. By default it is {@link #focusEl}.
     *
     * @param {Ext.dom.Element} [focusEl] Return focus styling element for the given
     * focused element. This is used by Components implementing multiple focusable
     * elements.
     *
     * @return {Ext.dom.Element} The focus styling element.
     * @protected
     */
    getFocusClsEl: function() {
        return this.getFocusEl();
    },

    /**
     * Template method to do any Focusable related initialization that
     * does not involve event listeners creation.
     * @protected
     */
    initFocusable: Ext.emptyFn,

    /**
     * Template method to do any event listener initialization for a Focusable.
     * This generally happens after the focusEl is available.
     * @protected
     */
    initFocusableEvents: function(force) {
        // If *not* naturally focusable, then we look for the tabIndex property
        // to be defined which indicates that the element should be made focusable.
        this.initFocusableElement(force);
    },

    enableFocusable: Ext.emptyFn,

    disableFocusable: function() {
        var me = this;

        // If this is disabled while focused, by default, focus would return to document.body.
        // This must be avoided, both for the convenience of keyboard users, and also
        // for when focus is tracked within a tree, such as below an expanded ComboBox.
        if (me.hasFocus) {
            me.revertFocus();
        }

        me.removeFocusCls();
    },

    destroyFocusable: function() {
        var me = this;

        Ext.destroy(me.focusListeners);

        me.focusListeners = me.focusEnterEvent = me.focusTask = null;
        me.focusEl = me.ariaEl = null;
    },

    /**
     * Determine if this Focusable can receive focus at this time.
     *
     * Note that Containers can be non-focusable themselves while delegating
     * focus treatment to a child Component; see {@link Ext.Container #defaultFocus}
     * for more information.
     *
     * @param {Boolean} [deep=false] Optionally determine if the container itself
     * is focusable, or if container's focus is delegated to a child component
     * and that child is focusable.
     *
     * @return {Boolean} True if component is focusable, false if not.
     */
    isFocusable: function(deep) {
        var me = this,
            focusEl;

        if (!me.focusable && (!me.isContainer || !deep)) {
            return false;
        }

        focusEl = me.getFocusEl();

        if (focusEl && me.canFocus()) {

            // getFocusEl might return a Component if a Container wishes to
            // delegate focus to a descendant. Both Component and Element
            // implement isFocusable, so always ask that.
            return focusEl && !focusEl.destroyed && focusEl.isFocusable(deep);
        }

        return false;
    },

    /**
     * Determines if this Component is inside a Component tree which is destroyed, *or
     * is being destroyed*.
     * @return {boolean} `true` if this Component, or any ancestor is destroyed, or
     * is being destroyed.
     * @private
     */
    isDestructing: function() {
        var c;

        for (c = this; c; c = c.getRefOwner()) {
            if (c.destroying || c.destroyed) {
                return true;
            }
        }

        return false;
    },

    canFocus: function(skipVisibility, includeFocusTarget) {
        var me = this,
            ownerFC = me.ownerFocusableContainer,
            focusableIfDisabled = ownerFC && ownerFC.allowFocusingDisabledChildren,
            canFocus;

        // Containers may have focusable children while being non-focusable
        // themselves; this is why we only account for me.focusable for
        // ordinary Components here and below.
        // MenuItems must accept focus when disabled.
        canFocus = !me.destroyed && me.rendered && !me.isDestructing() &&
                (me.isContainer || me.focusable) &&
                (!me.isDisabled() || focusableIfDisabled) &&
                (skipVisibility || me.isVisible(true));

        return canFocus || (includeFocusTarget && !!me.findFocusTarget());
    },

    /**
     * Try to focus this component.
     *
     * If this component is disabled or otherwise not focusable, a close relation
     * will be targeted for focus instead to keep focus localized for keyboard users.
     *
     * @param {Boolean/Number[]} [selectText] If applicable, `true` to also select all the text
     * in this component, or an array consisting of start and end (defaults to start)
     * position of selection.
     *
     * @return {Boolean} `true` if focus target was found and focusing was attempted,
     * `false` if no focusing attempt was made.
     */
    focus: function(selectText) {
        var me = this,
            focusTarget, focusElDom;

        if ((!me.focusable && !me.isContainer) || me.destroyed || me.destroying) {
            return false;
        }

        // Assignment in conditional here to fall through to else block
        // if me.canFocus() returns true but there is no focus target
        if (me.canFocus() && (focusTarget = me.getFocusEl())) {
            // getFocusEl might return a child Widget or Component if a Container wishes
            // to delegate focus to a descendant via its defaultFocus configuration.
            if (focusTarget.$isFocusableEntity) {
                return focusTarget.focus.apply(focusTarget, arguments);
            }

            focusElDom = focusTarget.dom;

            // This is an Element instance
            if (focusElDom) {
                // NOT focusElDom.focus() here! Gotta run through possible Element overrides!
                focusTarget.focus();

                if (selectText && (me.selectText || focusElDom.select)) {
                    if (me.selectText) {
                        if (Ext.isArray(selectText)) {
                            me.selectText.apply(me, selectText);
                        }
                        else {
                            me.selectText();
                        }
                    }
                    else {
                        focusElDom.select();
                    }
                }
            }
            // Could be a Widget or something else with no dom property but focusable
            else if (focusTarget.focus) {
                focusTarget.focus();
            }
            else {
                return false;
            }
        }
        else {
            // If we are asked to focus while not able to focus though disablement/invisibility etc,
            // focus may revert to document.body if the current focus is being hidden or destroyed.
            // This must be avoided, both for the convenience of keyboard users, and also
            // for when focus is tracked within a tree, such as below an expanded ComboBox.
            focusTarget = me.findFocusTarget();

            if (focusTarget && focusTarget !== me) {
                return focusTarget.focus.apply(focusTarget, arguments);
            }
            else {
                return false;
            }
        }

        return true;
    },

    /**
     * @private
     */
    onBlur: function(e) {
        var me = this,
            container = me.ownerFocusableContainer;

        me.hasFocus = false;

        if (me.beforeBlur && !me.beforeBlur.$emptyFn) {
            me.beforeBlur(e);
        }

        if (container) {
            container.beforeFocusableChildBlur(me, e);
        }

        me.removeFocusCls(e);

        if (me.hasListeners.blur) {
            me.fireEvent('blur', me, e);
        }

        if (me.postBlur && !me.postBlur.$emptyFn) {
            me.postBlur(e);
        }

        if (container) {
            container.afterFocusableChildBlur(me, e);
        }
    },

    /**
     * @private
     */
    onFocus: function(e) {
        var me = this,
            container = me.ownerFocusableContainer;

        if (me.canFocus()) {
            if (me.beforeFocus && !me.beforeFocus.$emptyFn) {
                me.beforeFocus(e);
            }

            if (container) {
                container.beforeFocusableChildFocus(me, e);
            }

            me.addFocusCls(e);

            if (!me.hasFocus) {
                me.hasFocus = true;
                me.fireEvent('focus', me, e);
            }

            if (me.postFocus && !me.postFocus.$emptyFn) {
                me.postFocus(e);
            }

            if (container) {
                container.afterFocusableChildFocus(me, e);
            }
        }
    },

    /**
     * Return the actual tabIndex for this Focusable.
     *
     * @return {Number} tabIndex attribute value
     */
    getTabIndex: function() {
        var me = this,
            el, index;

        if (!me.focusable) {
            return;
        }

        el = me.getFocusEl();

        if (el) {
            // getFocusEl may return a child Widget or Component
            if (el.$isFocusableEntity) {
                index = el.getTabIndex();
            }

            else if (el.isElement && el.dom) {
                // We can't query el.dom.tabIndex because IE8 will return 0
                // when tabIndex attribute is not present, and Chrome will
                // return -1. Can't trust a browser to do a simplest thing. :/
                index = el.dom.getAttribute('tabIndex');

                // This contraption is here because we can't simply coerce
                // the returned attribute value to a number. If the attribute
                // is not present, the value returned will be null, and coercing
                // null gives 0. Wondrous JavaScript. :/
                if (index !== null) {
                    index -= 0;
                }
            }

            // A component can be configured with el: '#id' to look up
            // its main element from the DOM rather than render it; in
            // such case getTabIndex() may happen to be called before
            // said lookup has happened; indeterminate result follows.
            else {
                return;
            }
        }

        if (typeof index !== 'number') {
            index = me.tabIndex;
        }

        return index;
    },

    /**
     * Set the tabIndex property for this Focusable. If the focusEl
     * is available, set tabIndex attribute on it, too.
     *
     * @param {Number} newTabIndex new tabIndex to set
     * @param {HTMLElement} [focusEl] (private)
     */
    setTabIndex: function(newTabIndex, focusEl) {
        var me = this,
            ownerFC = me.ownerFocusableContainer,
            focusableIfDisabled = ownerFC && ownerFC.allowFocusingDisabledChildren,
            wasFocusable = me.focusable,
            el;

        // See comments for definition of forceTabIndex as to why this is needed
        // Return early if not focusable unless we are either forcing the tabIndex to
        // be set, or we are removing the tabIndex attribute.
        if (!wasFocusable && !(me.forceTabIndex || newTabIndex == null)) {
            return;
        }

        me.tabIndex = newTabIndex;

        // We must not do this if we are destroyed, or if we are incapable of being focused.
        // Handle our owning FocusableContainer having allowFocusingDisabledChildren
        if (me.destroying || me.destroyed || (me.isDisabled() && !focusableIfDisabled)) {
            return;
        }

        // getFocusEl does not return the element if focusable is false.
        // If we are forcing the tabIndex, or setting it to null, we
        // need it to return the focusEl.
        me.focusable = true;
        el = focusEl || me.getFocusEl();
        me.focusable = wasFocusable;

        if (el) {
            // getFocusEl may return a child Widget or Component
            if (el.$isFocusableEntity) {
                el.setTabIndex(newTabIndex);
            }

            // Or if a component is configured with el: '#id', it may
            // still be a string by the time setTabIndex is called from
            // owner FocusableContainer.
            else if (el.isElement && el.dom) {
                // setTabIndex is aware of saved tabbable state
                el.setTabIndex(newTabIndex);
            }
        }
    },

    /**
     * @template
     * @protected
     * Called when focus enters this Component's hierarchy
     * @param {Object} e
     * @param {Ext.event.Event} e.event The underlying DOM event.
     * @param {HTMLElement} e.target The element gaining focus.
     * @param {HTMLElement} e.relatedTarget The element losing focus.
     * @param {Ext.Component} e.toComponent The Component gaining focus.
     * @param {Ext.Component} e.fromComponent The Component losing focus.
     * @param {Boolean} e.backwards `true` if the `fromComponent` is *after* the `toComponent*
     * in the DOM tree, indicating that the user used `SHIFT+TAB` to move focus. Note that setting
     * `tabIndex` values to affect tabbing order can cause this to be incorrect. Setting
     * `tabIndex` values is not advised.
     */
    onFocusEnter: function(e) {
        var me = this;

        // We DO NOT check if `me` is focusable here. The reason is that
        // non-focusable containers need to track focus entering their
        // children so that revertFocus would work if these children
        // become unavailable.
        if (me.destroying || me.destroyed) {
            return;
        }

        // Save all information about how we received focus so that
        // we can do appropriate things when asked to revertFocus
        me.focusEnterEvent = e;
        me.containsFocus = true;

        if (me.hasListeners.focusenter) {
            me.fireEvent('focusenter', me, e);
        }
    },

    /**
     * @template
     * @protected
     * Called when focus exits from this Component's hierarchy
     * @param {Ext.event.Event} e
     * @param {Ext.event.Event} e.event The underlying DOM event.
     * @param {HTMLElement} e.target The element gaining focus.
     * @param {HTMLElement} e.relatedTarget The element losing focus.
     * @param {Ext.Component} e.toComponent The Component gaining focus.
     * @param {Ext.Component} e.fromComponent The Component losing focus.
     */
    onFocusLeave: function(e) {
        var me = this;

        // Same as in onFocusEnter
        if (me.destroying || me.destroyed) {
            return;
        }

        me.focusEnterEvent = null;
        me.containsFocus = false;

        if (me.hasListeners.focusleave) {
            me.fireEvent('focusleave', me, e);
        }
    },

    /**
     * @template
     * @method
     * @protected
     * Called when focus moves *within* this Component's hierarchy
     * @param {Object} info
     * @param {Ext.event.Event} info.event The underlying Event object.
     * @param {HTMLElement} info.toElement The element gaining focus.
     * @param {HTMLElement} info.fromElement The element losing focus.
     * @param {Ext.Component} info.toComponent The Component gaining focus.
     * @param {Ext.Component} info.fromComponent The Component losing focus.
     * @param {Boolean} info.backwards `true` if the focus movement is backward in DOM order
     */
    onFocusMove: Ext.emptyFn,

    privates: {
        // This private flag was introduced to work around an issue where
        // the tab index would not be stamped onto the component.
        // Consider a tool, which is focusable by default and has tabIndex: 0
        // on the class definition. If a non-focusable tool is required, setting
        // focusable: false is not enough, the tabIndex also needs to be considered.
        // However, the default behaviour is to not modify the tabIndex on non
        // focusable items. This config can go away if that behaviour is changed.
        // Arguably, a non-focusable widget probably shouldn't retain a tab index
        // if it's explicitly configured.
        forceTabIndex: false,

        /**
         * Returns focus to the Component or element found in the cached
         * focusEnterEvent.
         *
         * @private
         */
        revertFocus: function() {
            var me = this,
                focusEvent = me.focusEnterEvent,
                activeElement = Ext.Element.getActiveElement(),
                focusTarget, fromComponent, reverted;

            // If we have a record of where focus arrived from,
            //  and have not been told to avoid refocusing,
            //  and we contain the activeElement.
            // Then, before hiding, restore focus to what was focused before we were focused.
            if (focusEvent && !me.preventRefocus && me.el.contains(activeElement)) {

                fromComponent = focusEvent.fromComponent;

                // If the default focus reversion target is in shifting ground, fall back
                // to document.body
                if (fromComponent && (fromComponent.destroyed || fromComponent.isDestructing())) {
                    focusTarget = document.body;
                }

                // Preferred focus target is the actual element from which focus entered
                // this component. It will be up to its encapsulating component to handle this
                // in an appropriate way. For example, a grid, upon having focus pushed
                // to a certain cell will set its navigation position to that cell and highlight it
                // as focused. Likewise an input field must handle its field acquiring focus.
                else {
                    focusTarget = focusEvent.relatedTarget;
                }

                // If focus was from the body, try to keep it closer than that
                if (focusTarget === document.body) {
                    fromComponent = me.findFocusTarget();

                    if (fromComponent) {
                        focusTarget = fromComponent.getFocusEl();
                    }
                }

                if (focusTarget && focusTarget.$isFocusableEntity) {
                    if (!focusTarget.destroyed && focusTarget.isFocusable()) {
                        focusTarget.focus();
                    }
                }

                // If the element is in the document and focusable, then we're good. The owning
                // component will handle it.
                else if (Ext.getDoc().contains(focusTarget) && Ext.fly(focusTarget).isFocusable()) {
                    fromComponent = Ext.Component.from(focusTarget);

                    // Allow the focus recieving component to modify the focus sequence.
                    if (fromComponent) {
                        fromComponent.revertFocusTo(focusTarget);
                    }
                    else {
                        focusTarget.focus();
                    }
                }

                // If the element has gone, or is hidden, we will have to rely on the intelligent
                // focus diversion of components to send focus back to somewhere that is least
                // surprising for the user.
                else if (focusEvent.fromComponent && focusEvent.fromComponent.focus) {
                    reverted = focusEvent.fromComponent.focus();

                    // The component was not able to find a suitable target.
                    // Important: Touch platforms do not blur programmatically focused elements
                    // when they become hidden, so we must force the issue in order to maintain
                    // focus tracking.
                    if (!reverted) {
                        activeElement.blur();
                    }
                }
            }
        },

        /**
         * This field is on the recieving end of a call from {@link #method!revertFocus}.
         *
         * It is called when focus is being pushed back into this Component from a Component
         * that is focused and is being hidden or disabled.
         *
         * We must focus the passed element.
         *
         * Subclasses may perform some extra processing to prepare for refocusing.
         * @param target
         */
        revertFocusTo: function(target) {
            target.focus();
        },

        /**
         * Finds an alternate Component to focus if this Component is disabled while focused, or
         * focused while disabled, or otherwise unable to focus.
         * 
         * In both cases, focus must not be lost to document.body, but must move to an intuitively
         * connectible Component, either a sibling, or uncle or nephew.
         *
         * This is both for the convenience of keyboard users, and also for when focus is tracked
         * within a Component tree such as for ComboBoxes and their dropdowns.
         *
         * For example, a ComboBox with a PagingToolbar in is BoundList. If the "Next Page"
         * button is hit, the LoadMask shows and focuses, the next page is the last page, so
         * the "Next Page" button is disabled. When the LoadMask hides, it attempt to focus the
         * last focused Component which is the disabled "Next Page" button. In this situation,
         * focus should move to a sibling within the PagingToolbar.
         * 
         * @return {Ext.Component} A closely related focusable Component to which focus can move.
         * @private
         */
        findFocusTarget: function() {
            var me = this,
                parentAxis, candidate, len, i, focusTargets, focusIndex;

            if (me.preventRefocus) {
                return null;
            }

            // Create an axis of visible, enabled, stable parent components which we
            // will walk up looking for ancestors to revert focus to.
            //
            // First, find all enabled parents.
            // eslint-disable-next-line max-len
            for (parentAxis = [], candidate = me.getRefOwner(); candidate; candidate = candidate.getRefOwner()) {
                if (!candidate.isDisabled()) {
                    parentAxis.unshift(candidate);
                }
            }

            // Then walk downwards until we encounter a non-targetable parent
            // which means hidden of destroying. All candidates *above* that
            // are potential sources of focus targets.
            for (i = 0, len = parentAxis.length; i < len; i++) {
                candidate = parentAxis[i];

                if (candidate.destroying || !candidate.isVisible()) {
                    parentAxis.length = i;
                    break;
                }
            }

            // Walk up the parent axis checking each parent for focus targets.
            for (i = parentAxis.length - 1; i >= 0; i--) {
                candidate = parentAxis[i];
                // Use CQ to find a target that is fully focusable (:canfocus, NOT the theoretical
                // :focusable). Cannot use :focusable(true) because that consults findFocusTarget
                // and would cause infinite recursion.
                // Exclude the component which currently has focus.
                // Cannot use candidate.child() because the parent might not be a Container.
                // Non-Container Components may still have ownership relationships with
                // other Components. eg: BoundList with PagingToolbar
                focusTargets = Ext.ComponentQuery.query(':canfocus()', candidate);

                if (focusTargets.length) {
                    // eslint-disable-next-line max-len
                    focusIndex = Ext.Array.indexOf(focusTargets, Ext.ComponentManager.getActiveComponent());

                    // Return the next focusable, or the previous focusable, or the first focusable
                    return focusTargets[focusIndex + 1] || focusTargets[focusIndex - 1] ||
                           focusTargets[0];
                }

                // We found no focusable siblings in our candidate, but the candidate may itself
                // be focusable, it is not always a Container - could be the owning Field
                // of a BoundList.
                if (candidate.isFocusable && candidate.isFocusable()) {
                    return candidate;
                }
            }
        },

        /**
         * Sets up the focus listener on this Component's {@link #getFocusEl focusEl} if it has one.
         *
         * Form Components which must implicitly participate in tabbing order usually have
         * a naturally focusable element as their {@link #getFocusEl focusEl}, and it is
         * the DOM event of that receiving focus which drives the Component's `onFocus` handling,
         * and the DOM event of it being blurred which drives the `onBlur` handling.
         * @private
         */
        initFocusableElement: function(force) {
            var me = this,
                tabIndex = me.getTabIndex(),
                focusEl = me.getFocusEl();

            if (focusEl && !focusEl.$isFocusableEntity) {
                // focusEl is not available until after rendering, and rendering tabIndex
                // into focusEl is not always convenient. So we apply it here if Component's
                // tabIndex property is set and Component is otherwise focusable.
                // Note that for Widgets and Modern Components we might not have the rendered
                // flag set yet but can force setting the property.
                if (tabIndex != null && (force || me.canFocus(true))) {
                    me.setTabIndex(tabIndex, focusEl);
                }

                // This attribute is a shortcut to look up a Component by its Elements
                // It only makes sense on focusable elements, so we set it here unless
                // our focusEl is delegated to the focusEl of an owned Component and it
                // already has ownership stamped into it.
                if (!focusEl.dom.hasAttribute('data-componentid')) {
                    focusEl.dom.setAttribute('data-componentid', me.id);
                }
            }
        },

        addFocusCls: function(e) {
            var focusCls = this.focusCls,
                el;

            el = this.getFocusClsEl();

            if (focusCls) {
                el = this.getFocusClsEl(e);

                if (el) {
                    el.addCls(focusCls);
                }
            }
        },

        removeFocusCls: function(e) {
            var focusCls = this.focusCls,
                el;

            if (focusCls) {
                el = this.getFocusClsEl(e);

                if (el) {
                    el.removeCls(focusCls);
                }
            }
        },

        /**
         * @private
         */
        handleFocusEvent: function(info) {
            var me = this,
                event;

            if (!me.focusable || me.destroying || me.destroyed) {
                return;
            }

            // handleFocusEvent and handleBlurEvent are called by ComponentManager
            // passing the normalized element event that might or might not cause
            // component focus or blur. The component itself makes the decision
            // whether focus/blur happens or not. This is necessary for components
            // that might have more than one focusable element within the component's
            // DOM structure, like Ext.button.Split.
            if (me.isFocusing(info)) {
                event = new Ext.event.Event(info.event);
                event.type = 'focus';
                event.relatedTarget = info.fromElement;
                event.target = info.toElement;

                me.onFocus(event);
            }
        },

        /**
         * @private
         */
        handleBlurEvent: function(info) {
            var me = this,
                event;

            if (!me.focusable || me.destroying || me.destroyed) {
                return;
            }

            // Must process blur if toElement is document.body
            // Blurs caused by the app losing focus are processed synchronously
            // for obvious reasons, so the activeElement will still be the
            // focusEl, so isBlurring will return false, and reject this op.
            if (info.toElement === document.body || me.isBlurring(info)) {
                event = new Ext.event.Event(info.event);
                event.type = 'blur';
                event.target = info.fromElement;
                event.relatedTarget = info.toElement;

                me.onBlur(event);
            }
        },

        /**
         * @private
         */
        isFocusing: function(e) {
            var focusEl = this.getFocusEl();

            if (focusEl) {
                if (focusEl.isFocusing) {
                    return focusEl.isFocusing(e);
                }
                else {
                    // Sometimes focusing an element may cause reaction from other entities
                    // that will focus something else instead. So before unraveling the
                    // event chain we better make sure our focusEl is *indeed* focused.
                    return focusEl.dom === document.activeElement &&
                           e.toElement === focusEl.dom && e.fromElement !== e.toElement;
                }
            }

            return false;
        },

        /**
         * @private
         */
        isBlurring: function(e) {
            var focusEl = this.getFocusEl();

            if (focusEl) {
                if (focusEl.isFocusing) {
                    return focusEl.isBlurring(e);
                }
                else {
                    // Ditto for blurring: in some cases freshly blurred element can be
                    // refocused by external forces so we need to check if our focusEl
                    // *indeed* is not focused before we can call it blurring.
                    return focusEl.dom !== document.activeElement &&
                           e.fromElement === focusEl.dom && e.fromElement !== e.toElement;
                }
            }

            return false;
        },

        /**
         * @private
         */
        blur: function() {
            var me = this,
                focusEl;

            if (!me.focusable || !me.canFocus()) {
                return;
            }

            focusEl = me.getFocusEl();

            if (focusEl) {
                me.blurring = true;
                focusEl.blur();
                delete me.blurring;
            }
        },

        isTabbable: function() {
            var me = this,
                focusEl;

            if (me.focusable) {
                focusEl = me.getFocusEl();

                if (focusEl && focusEl.isTabbable()) {
                    return focusEl.isTabbable();
                }
            }

            return false;
        },

        disableTabbing: function() {
            var me = this,
                el = me.el,
                focusEl;

            // We DO NOT check for me.focusable here, because this should work
            // for containers with focus delegates, too!
            if (me.destroying || me.destroyed) {
                return;
            }

            // We're disabling tabbability for all elements of a given Component;
            // focusEl might be outside of the hierarchy which is checked below.
            if (el) {
                el.saveTabbableState();
            }

            focusEl = me.getFocusEl();

            if (focusEl) {
                // focusEl may happen to be a focus delegate for a container
                if (focusEl.$isFocusableEntity) {
                    focusEl.disableTabbing();
                }

                // Alternatively focusEl may happen to be outside of the main el,
                // or else it can be a string reference to an element that
                // has not been resolved yet
                else if (focusEl.isElement && el && !el.contains(focusEl)) {
                    focusEl.saveTabbableState();
                }
            }
        },

        enableTabbing: function(reset) {
            var me = this,
                el = me.el,
                focusEl;

            // We DO NOT check for me.focusable here, because this should work
            // for containers with focus delegates, too!
            if (me.destroying || me.destroyed) {
                return;
            }

            focusEl = me.getFocusEl();

            if (focusEl) {
                if (focusEl.$isFocusableEntity) {
                    focusEl.enableTabbing();
                }
                else if (focusEl.isElement && el && !el.contains(focusEl)) {
                    focusEl.restoreTabbableState();
                }
            }

            if (el) {
                el.restoreTabbableState({ reset: reset });
            }
        }
    }
}, function() {
    var keyboardModeCls = Ext.baseCSSPrefix + 'keyboard-mode',
        keyboardMode = false;

    /**
     * @cfg {Boolean} enableKeyboardMode
     * When set to `true`, focus styling will be applied to focused elements based on the
     * user interaction mode: when keyboard was used to focus an element, focus styling
     * will be visible but not when element was focused otherwise (e.g. with mouse, touch,
     * or programmatically). The {@link #keyboardMode} property will then reflect the last
     * user interaction mode.
     * Setting this option to `false` disables keyboard mode tracking and results in focus
     * styling always being applied to focused elements, which is pre-Ext JS 6.5 behavior.
     *
     * Defaults to `false` in desktop environments, `true` on mobile devices.
     * @member Ext
     * @bindable
     * @since 6.6.0
     */
    Ext.enableKeyboardMode = Ext.isModern || !Ext.os.is.Desktop;

    /**
     * @property {Boolean} keyboardMode
     * @member Ext
     * A flag which indicates that the last UI interaction from the user was a keyboard gesture
     * @since 6.5.0
     */

    /**
     * @property {Boolean} touchMode
     * @member Ext
     * A flag which indicates that the last UI interaction from the user was a touch gesture
     * @since 6.5.0
     */

    Ext.setKeyboardMode = Ext.setKeyboardMode || function(keyboardMode) {
        Ext.keyboardMode = keyboardMode;
        Ext.getBody().toggleCls(keyboardModeCls, keyboardMode);
    };

    Ext.isTouchMode = function() {
        return (Ext.now() - Ext.lastTouchTime) < 500;
    };

    /**
     * @private
     */
    Ext.syncKeyboardMode = function(e) {
        var type;

        if (!Ext.enableKeyboardMode) {
            return;
        }

        type = e.type;

        if (type === 'pointermove') {
            // On pointer move we want to track that the user has switched from keyboard
            // to mouse, but we do not remove the visual focus style until a pointerdown
            // occurs or the focus changes.  This accomodates menus which change focus
            // based on mouseenter of a menu item
            keyboardMode = false;
        }
        else {
            keyboardMode = (type === 'keydown');
            Ext.lastTouchTime = e.pointerType === 'touch' && Ext.now();
            Ext.setKeyboardMode(keyboardMode);
        }
    };

    function keyboardModeFocusHandler() {
        // NOT Ext.keyboardMode here; closing over variable local to class callback fn
        if (keyboardMode !== Ext.getBody().hasCls(keyboardModeCls)) {
            Ext.setKeyboardMode(keyboardMode);
        }
    }

    Ext.getEnableKeyboardMode = function() {
        return Ext.enableKeyboardMode;
    };

    Ext.setEnableKeyboardMode = function(enable) {
        var listeners = {
            pointerdown: Ext.syncKeyboardMode,
            pointermove: Ext.syncKeyboardMode,
            keydown: Ext.syncKeyboardMode,
            capture: true,
            delegated: false
        };

        Ext.enableKeyboardMode = !!enable;

        if (Ext.enableKeyboardMode) {
            Ext.getWin().on(listeners);
            Ext.on('focus', keyboardModeFocusHandler);
        }
        else {
            Ext.getWin().un(listeners);
            Ext.un('focus', keyboardModeFocusHandler);
        }
    };

    Ext.onReady(function() {
        // Add the CSS class once upfront if enableKeyboardMode is disabled
        if (!Ext.enableKeyboardMode) {
            Ext.getBody().addCls(keyboardModeCls);
        }

        Ext.setEnableKeyboardMode(Ext.enableKeyboardMode);
    });
});
