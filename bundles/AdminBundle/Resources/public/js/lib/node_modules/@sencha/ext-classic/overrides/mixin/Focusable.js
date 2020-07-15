/**
 * @class Ext.mixin.Focusable
 */
Ext.define('Ext.overrides.mixin.Focusable', {
    override: 'Ext.Component',

    /**
     * @cfg {String} [focusCls='focus'] CSS class suffix that will be used to
     * compose the CSS class name that will be added to Component's {@link #focusClsEl},
     * and removed when Component blurs.
     *
     * **Note** that this is not a full CSS class name; this suffix will be combined
     * with component's UI class via {@link #addClsWithUI} and {@link #removeClsWithUI} methods.
     */
    focusCls: 'focus',

    /**
     * Try to focus this component.
     *
     * If this component is disabled, a close relation will be targeted for focus instead
     * to keep focus localized for keyboard users.
     * @param {Mixed} [selectText] If applicable, `true` to also select all the text in this
     * component, or an array consisting of start and end (defaults to start) position of selection.
     * @param {Boolean/Number} [delay] Delay the focus this number of milliseconds (true for
     * 10 milliseconds).
     * @param {Function} [callback] Only needed if the `delay` parameter is used. A function to call
     * upon focus.
     * @param {Function} [scope] Only needed if the `delay` parameter is used. The scope (`this`
     * reference) in which to execute the callback.
     * @return {Ext.Component} The focused Component. Usually `this` Component. Some Containers may
     * delegate focus to a descendant Component ({@link Ext.window.Window Window}s can do this
     * through their {@link Ext.window.Window#defaultFocus defaultFocus} config option. If this
     * component is disabled, a closely related component will be focused and that will be returned.
     */
    focus: function(selectText, delay, callback, scope) {
        var me = this,
            containerScrollTop;

        if ((!me.focusable && !me.isContainer) || me.destroyed || me.destroying) {
            return me;
        }

        // If delay is wanted, queue a call to this function.
        if (delay) {
            me.getFocusTask().delay(
                Ext.isNumber(delay) ? delay : 10, me.focus, me,
                [selectText, false, callback, scope]
            );

            return me;
        }

        // An immediate focus call must cancel any outstanding delayed focus calls.
        me.cancelFocus();

        if (me.floating && me.container && me.container.dom) {
            containerScrollTop = me.container.dom.scrollTop;
        }

        // Core Focusable method will return true if focusing was attempted
        if (me.mixins.focusable.focus.apply(me, arguments) !== false) {
            if (callback) {
                Ext.callback(callback, scope);
            }

            // Focusing a floating Component brings it to the front of its stack.
            // this is performed by its zIndexManager. Pass preventFocus true to avoid recursion.
            if (me.floating && containerScrollTop !== undefined) {
                me.container.dom.scrollTop = containerScrollTop;
            }
        }

        return me;
    },

    /**
     * Cancel any deferred focus on this component
     * @protected
     */
    cancelFocus: function() {
        var me = this,
            task = me.getFocusTask();

        if (task) {
            task.cancel();
        }
    },

    /**
     * @method
     * Template method to do any pre-blur processing.
     * @protected
     * @param {Ext.event.Event} e The event object
     */
    beforeBlur: Ext.emptyFn,

    /**
     * @method
     * Template method to do any post-blur processing.
     * @protected
     * @param {Ext.event.Event} e The event object
     */
    postBlur: Ext.emptyFn,

    /**
     * @method
     * Template method to do any pre-focus processing.
     * @protected
     * @param {Ext.event.Event} e The event object
     */
    beforeFocus: Ext.emptyFn,

    /**
     * @method
     * Template method to do any post-focus processing.
     * @protected
     * @param {Ext.event.Event} e The event object
     */
    postFocus: Ext.emptyFn,

    onFocusEnter: function(e) {
        var me = this;

        if (me.destroying || me.destroyed) {
            return;
        }

        // Focusing must being a floating component to the front.
        // Only bring to front if this component is not the manager's
        // topmost component (may be a result of focusOnToFront).
        if (me.floating && me !== me.zIndexManager.getActive()) {
            me.toFront(true);
        }

        me.callParent([e]);
    },

    destroyFocusable: function() {
        var me = this;

        // Calling cancelFocus() will assign focusTask property,
        // which we don't want during destruction
        if (me.focusTask) {
            me.focusTask.stop(me.focus, me);
        }

        me.callParent();
    },

    privates: {
        addFocusCls: function(e) {
            var me = this,
                focusCls = me.focusCls,
                el;

            if (focusCls) {
                el = me.getFocusClsEl(e);

                if (el) {
                    el.addCls(me.addClsWithUI(focusCls, true));
                }
            }
        },

        removeFocusCls: function(e) {
            var me = this,
                focusCls = me.focusCls,
                el;

            if (focusCls) {
                el = me.getFocusClsEl(e);

                if (el) {
                    el.removeCls(me.removeClsWithUI(focusCls, true));
                }
            }
        },

        /**
         * @private
         */
        getFocusTask: function() {
            if (!this.focusTask) {
                this.focusTask = Ext.focusTask;
            }

            return this.focusTask;
        },

        updateMaskState: function(state, mask) {
            var me = this,
                ariaEl = me.ariaEl.dom,
                skipMask = me.getInherited().disabled && me.getInherited().disableMask,
                value;

            if (state) {
                me.disableTabbing();

                if (!skipMask) {
                    me.setMasked(true);
                }

                if (ariaEl) {
                    ariaEl.setAttribute('aria-busy', 'true');

                    // It is possible that ariaEl already has aria-describedby attribute;
                    // in that case we need to save it to restore later.
                    value = ariaEl.getAttribute('aria-describedby');

                    if (value) {
                        me._savedAriaDescribedBy = value;
                    }

                    ariaEl.setAttribute('aria-describedby', mask.ariaEl.id);
                }
            }
            else {
                me.enableTabbing();

                if (!skipMask) {
                    me.setMasked(false);
                }

                if (ariaEl) {
                    ariaEl.removeAttribute('aria-busy');

                    value = ariaEl.getAttribute('aria-describedby');
                    ariaEl.removeAttribute('aria-describedby');

                    if (value === mask.ariaEl.id && me._savedAriaDescribedBy) {
                        ariaEl.setAttribute('aria-describedby', me._savedAriaDescribedBy);
                        delete me._savedAriaDescribedBy;
                    }
                }
            }
        }
    }
}, function() {
    // One global DelayedTask to assign focus
    // So that the last focus call wins.
    if (!Ext.focusTask) {
        Ext.focusTask = new Ext.util.DelayedTask();
    }
});
