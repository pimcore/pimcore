/**
 * A split button that provides a built-in dropdown arrow that can fire an event separately
 * from the default click event of the button. Typically this would be used to display a dropdown
 * menu that provides additional options to the primary button action, but any custom handler
 * can provide the arrowclick implementation.
 * Example usage:
 *
 *     @example
 *     // display a dropdown menu:
 *     Ext.create('Ext.button.Split', {
 *         renderTo: Ext.getBody(),
 *         text: 'Options',
 *         // handle a click on the button itself
 *         handler: function() {
 *             alert("The button was clicked");
 *         },
 *         menu: new Ext.menu.Menu({
 *             items: [
 *                 // these will render as dropdown menu items when the arrow is clicked:
 *                 {text: 'Item 1', handler: function(){ alert("Item 1 clicked"); }},
 *                 {text: 'Item 2', handler: function(){ alert("Item 2 clicked"); }}
 *             ]
 *         })
 *     });
 *
 * Instead of showing a menu, you can provide any type of custom functionality you want when
 * the dropdown arrow is clicked:
 *
 *     Ext.create('Ext.button.Split', {
 *         renderTo: 'button-ct',
 *         text: 'Options',
 *         handler: optionsHandler,
 *         arrowHandler: myCustomHandler
 *     });
 *
 */
Ext.define('Ext.button.Split', {
    extend: 'Ext.button.Button',
    alternateClassName: 'Ext.SplitButton',
    alias: 'widget.splitbutton',

    isSplitButton: true,

    /**
     * @cfg {Function/String} arrowHandler
     * A function called when the arrow button is clicked (can be used instead of click event)
     * @cfg {Ext.button.Split} arrowHandler.this
     * @cfg {Event} arrowHandler.e The click event.
     * @controllable
     */

    /**
     * @cfg {String} arrowTooltip
     * The title attribute of the arrow.
     */

    /**
     * @cfg {Boolean} [separateArrowStyling=false] If enabled, arrow element mouseover,
     * click, and focus events will be handled separately from main element and
     * corresponding hover, pressed, and focused states will be added separately
     * to main element and arrow element, respectively.
     *
     * This requires theme support for extended states (see Graphite theme).
     *
     * @since 6.6.0
     * @private
     */
    separateArrowStyling: false,

    /**
     * @private
     */
    arrowCls: 'split',
    split: true,

    componentCls: Ext.baseCSSPrefix + 'split-button',

    /**
     * @event arrowclick
     * Fires when this button's arrow is clicked.
     * @param {Ext.button.Split} this
     * @param {Event} e The click event.
     */

    // It is possible to use both menu and arrowHandler with a Split button, which is confusing
    // and will clash with WAI-ARIA requirements. So we check that and warn if need be.
    // Unfortunately this won't work with arrowclick event that can be subscribed to
    // dynamically but we don't want to run these checks at run time so there's a limit
    // to what we can do about it.
    //<debug>
    initComponent: function() {
        var me = this;

        // Don't warn if we're under the slicer. Only check hasListeners of the component
        // instance; there could be listeners on the EventBus inherited via prototype.
        if (me.menu && (me.arrowHandler || me.hasListeners.hasOwnProperty('arrowclick'))) {
            Ext.ariaWarn(
                me,
                "Using both menu and arrowHandler config options in Split buttons " +
                "leads to confusing user experience and conflicts with accessibility " +
                "best practices. See WAI-ARIA 1.0 Authoring guide: " +
                "http://www.w3.org/TR/wai-aria-practices/#menubutton"
            );
        }

        me.callParent();
    },
    //</debug>

    getTemplateArgs: function() {
        var me = this,
            ariaAttr, data;

        data = me.callParent();

        if (me.disabled) {
            data.tabIndex = null;
        }

        ariaAttr = me.ariaArrowElAttributes || {};

        ariaAttr['aria-hidden'] = !!me.hidden;
        ariaAttr['aria-disabled'] = !!me.disabled;

        if (me.arrowTooltip) {
            ariaAttr['aria-label'] = me.arrowTooltip;
        }
        else {
            ariaAttr['aria-labelledby'] = me.id;
        }

        data.arrowElAttributes = ariaAttr;

        return data;
    },

    onRender: function() {
        var me = this,
            el;

        me.callParent();

        el = me.getFocusEl();

        if (el) {
            el.on({
                scope: me,
                focus: me.onMainElFocus,
                blur: me.onMainElBlur
            });
        }

        el = me.arrowEl;

        if (el) {
            el.dom.setAttribute('data-componentid', me.id);
            el.setVisibilityMode(Ext.dom.Element.DISPLAY);

            el.on({
                scope: me,
                focus: me.onArrowElFocus,
                blur: me.onArrowElBlur
            });
        }
    },

    /**
     * Sets this button's arrow click handler.
     * @param {Function} handler The function to call when the arrow is clicked.
     * @param {Object} scope (optional) Scope for the function passed above.
     */
    setArrowHandler: function(handler, scope) {
        this.arrowHandler = handler;
        this.scope = scope;
    },

    onMouseDown: function(e) {
        var me = this;

        if (me.separateArrowStyling && !me.disabled && e.button === 0 &&
            me.isWithinTrigger(e)) {
            e.preventDefault();
            me.arrowEl.focus();

            Ext.button.Manager.onButtonMousedown(me, e);
            me.addCls(me._arrowPressedCls);
        }
        else {
            me.callParent([e]);
        }
    },

    onMouseUp: function(e) {
        var me = this;

        if (me.separateArrowStyling && !me.destroyed && e.button === 0 &&
            me.isWithinTrigger(e)) {
            me.removeCls(me._arrowPressedCls);
        }
        else {
            me.callParent([e]);
        }
    },

    onMenuTriggerOver: function(e) {
        var me = this;

        if (me.separateArrowStyling && !me.disabled) {
            me.addCls(me._arrowOverCls);
        }

        me.callParent([e]);
    },

    onMenuTriggerOut: function(e) {
        var me = this;

        if (me.separateArrowStyling && !me.disabled) {
            me.removeCls(me._arrowOverCls);
        }

        me.callParent([e]);
    },

    /**
     * @private
     */
    onClick: function(e) {
        var me = this,
            arrowKeydown = e.type === 'keydown' && e.target === me.arrowEl.dom;

        me.doPreventDefault(e);

        if (!me.disabled) {
            if (arrowKeydown || me.isWithinTrigger(e)) {
                // Force prevent default here, if we click on the arrow part
                // we want to trigger the menu, not any link if we have it
                e.preventDefault();
                me.maybeShowMenu(e);
                me.fireEvent("arrowclick", me, e);

                if (me.arrowHandler) {
                    me.arrowHandler.call(me.scope || me, me, e);
                }
            }
            else {
                me.doToggle();
                me.fireHandler(e);
            }
        }
    },

    enable: function(silent) {
        var me = this,
            arrowEl = me.arrowEl;

        me.callParent([silent]);

        // May not be rendered yet
        if (arrowEl) {
            arrowEl.dom.setAttribute('tabIndex', me.tabIndex);
            arrowEl.dom.setAttribute('aria-disabled', 'false');
        }
    },

    disable: function(silent) {
        var me = this,
            arrowEl = me.arrowEl;

        me.callParent([silent]);

        // May not be rendered yet
        if (arrowEl) {
            arrowEl.dom.removeAttribute('tabIndex');
            arrowEl.dom.setAttribute('aria-disabled', 'true');
        }
    },

    afterHide: function(cb, scope) {
        this.callParent([cb, scope]);
        this.arrowEl.dom.setAttribute('aria-hidden', 'true');
    },

    afterShow: function(animateTarget, cb, scope) {
        this.callParent([animateTarget, cb, scope]);
        this.arrowEl.dom.setAttribute('aria-hidden', 'false');
    },

    privates: {
        isFocusing: function(e) {
            var me = this,
                from = e.fromElement,
                to = e.toElement,
                focusEl = me.focusEl && me.focusEl.dom,
                arrowEl = me.arrowEl && me.arrowEl.dom;

            if (me.focusable) {
                if (to === focusEl) {
                    return from === arrowEl ? false : true;
                }
                else if (to === arrowEl) {
                    return from === focusEl ? false : true;
                }

                return true;
            }

            return false;
        },

        isBlurring: function(e) {
            var me = this,
                from = e.fromElement,
                to = e.toElement,
                focusEl = me.focusEl && me.focusEl.dom,
                arrowEl = me.arrowEl && me.arrowEl.dom;

            if (me.focusable) {
                if (from === focusEl) {
                    return to === arrowEl ? false : true;
                }
                else if (from === arrowEl) {
                    return to === focusEl ? false : true;
                }

                return true;
            }

            return false;
        },

        // We roll our own focus style handling for Split button, see below
        getFocusClsEl: Ext.privateFn,

        onMainElFocus: function(e) {
            this.el.addCls(this._focusCls);
        },

        onMainElBlur: function(e) {
            this.el.removeCls(this._focusCls);
        },

        onArrowElFocus: function(e) {
            this.el.addCls(this._arrowFocusCls);
        },

        onArrowElBlur: function() {
            this.el.removeCls(this._arrowFocusCls);
        },

        setTabIndex: function(newTabIndex) {
            this.callParent([newTabIndex]);

            // May not be rendered yet
            if (this.arrowEl) {
                this.arrowEl.set({ tabIndex: newTabIndex });
            }
        },

        // This and below are called by the setMenu method in the parent class.
        _addSplitCls: function() {
            var arrowEl = this.arrowEl;

            this.callParent();

            arrowEl.dom.setAttribute('tabIndex', this.tabIndex);
            arrowEl.setVisible(true);
        },

        _removeSplitCls: function() {
            var arrowEl = this.arrowEl;

            this.callParent();

            arrowEl.dom.removeAttribute('tabIndex');
            arrowEl.setVisible(false);
        }
    }
});
