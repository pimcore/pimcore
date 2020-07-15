/**
 * A menu item that contains a togglable checkbox by default, but that can also be a part
 * of a radio group.
 *
 *     @example
 *     Ext.create('Ext.menu.Menu', {
 *         width: 100,
 *         height: 110,
 *         floating: false,  // usually you want this set to True (default)
 *         renderTo: Ext.getBody(),  // usually rendered by it's containing component
 *         items: [{
 *             xtype: 'menucheckitem',
 *             text: 'select all'
 *         }, {
 *             xtype: 'menucheckitem',
 *             text: 'select specific'
 *         }, {
 *             iconCls: 'add16',
 *             text: 'icon item'
 *         }, {
 *             text: 'regular item'
 *         }]
 *     });
 */
Ext.define('Ext.menu.CheckItem', {
    extend: 'Ext.menu.Item',
    alias: 'widget.menucheckitem',

    /**
     * @cfg {Boolean} [checked=false]
     * True to render the menuitem initially checked.
     */

    /**
     * @cfg {Function/String} checkHandler
     * Alternative for the {@link #checkchange} event.  Gets called with the same parameters.
     * @controllable
     */

    /**
     * @cfg {Object} scope
     * Scope for the {@link #checkHandler} callback.
     */

    /**
     * @cfg {String} group
     * Name of a radio group that the item belongs.
     *
     * Specifying this option will turn check item into a radio item.
     *
     * Note that the group name must be globally unique.
     */

    /**
     * @cfg {String} checkedCls
     * The CSS class used by {@link #cls} to show the checked state.
     * Defaults to `Ext.baseCSSPrefix + 'menu-item-checked'`.
     */
    checkedCls: Ext.baseCSSPrefix + 'menu-item-checked',

    /**
     * @cfg {String} uncheckedCls
     * The CSS class used by {@link #cls} to show the unchecked state.
     * Defaults to `Ext.baseCSSPrefix + 'menu-item-unchecked'`.
     */
    uncheckedCls: Ext.baseCSSPrefix + 'menu-item-unchecked',

    /**
     * @cfg {String} groupCls
     * The CSS class applied to this item's icon image to denote being a part of a radio group.
     * Defaults to `Ext.baseCSSClass + 'menu-group-icon'`.
     * Any specified {@link #iconCls} overrides this.
     */
    groupCls: Ext.baseCSSPrefix + 'menu-group-icon',

    /**
     * @cfg {Boolean} [hideOnClick=false]
     * Whether to not to hide the owning menu when this item is clicked.
     * Defaults to `false` for checkbox items, and to `true` for radio group items.
     */
    hideOnClick: false,

    /**
     * @cfg {Boolean} [checkChangeDisabled=false]
     * True to prevent the checked item from being toggled. Any submenu will still be accessible.
     */
    checkChangeDisabled: false,

    /**
     * @cfg {String} submenuText Text to be announced by screen readers when a check item
     * submenu is focused.
     * @locale
     */
    submenuText: '{0} submenu',

    ariaRole: 'menuitemcheckbox',

    childEls: [
        'checkEl'
    ],

    defaultBindProperty: 'checked',

    showCheckbox: true,

    isMenuCheckItem: true,

    checkboxCls: Ext.baseCSSPrefix + 'menu-item-checkbox',

    /**
     * @event beforecheckchange
     * Fires before a change event. Return false to cancel.
     * @param {Ext.menu.CheckItem} this
     * @param {Boolean} checked
     */

    /**
     * @event checkchange
     * Fires after a change event.
     * @param {Ext.menu.CheckItem} this
     * @param {Boolean} checked
     */

    initComponent: function() {
        var me = this,
            checked = me.checked;

        me.checkedConfigure = checked;

        // coerce to bool straight away
        me.checked = !!checked;

        me.callParent();

        if (me.group) {
            Ext.menu.Manager.registerCheckable(me);

            if (me.initialConfig.hideOnClick !== false) {
                me.hideOnClick = true;
            }
        }
    },

    beforeRender: function() {
        var me = this,
            ariaAttr;

        me.callParent();
        Ext.apply(me.renderData, {
            checkboxCls: me.checkboxCls,
            showCheckbox: me.showCheckbox
        });

        ariaAttr = (me.ariaRenderAttributes || (me.ariaRenderAttributes = {}));
        ariaAttr['aria-checked'] = me.menu ? 'mixed' : me.checked;

        // For some reason JAWS will not announce that a check item has a submenu
        // so users will get no indication whatsoever, unless we set the label.
        if (me.menu) {
            ariaAttr['aria-label'] = Ext.String.formatEncode(me.submenuText, me.text);
        }
    },

    afterRender: function() {
        var me = this;

        me.callParent();

        me.checked = !me.checked;
        me.initial = true;
        me.setChecked(!me.checked, true);
        me.initial = false;

        if (me.checkChangeDisabled) {
            me.disableCheckChange();
        }

        // For reasons unknown, clicking a div inside anchor element might cause
        // the anchor to be blurred in Firefox. We can't allow this to happen
        // because blurring will cause focusleave which will hide the menu
        // before click event fires. See https://sencha.jira.com/browse/EXTJS-18882
        if (Ext.isGecko && me.checkEl) {
            me.checkEl.on('mousedown', me.onMouseDownCheck);
        }
    },

    /**
     * Disables just the checkbox functionality of this menu Item. If this menu item has
     * a submenu, that submenu will still be accessible
     */
    disableCheckChange: function() {
        var me = this,
            checkEl = me.checkEl;

        if (checkEl) {
            checkEl.addCls(me.disabledCls);
        }

        // In some cases the checkbox will disappear until repainted, see: EXTJSIV-6412
        if (Ext.isIE8 && me.rendered) {
            me.el.repaint();
        }

        me.checkChangeDisabled = true;
    },

    /**
     * Re-enables the checkbox functionality of this menu item after having been 
     * disabled by {@link #disableCheckChange}
     */
    enableCheckChange: function() {
        var me = this,
            checkEl = me.checkEl;

        if (checkEl) {
            checkEl.removeCls(me.disabledCls);
        }

        me.checkChangeDisabled = false;
    },

    onMouseDownCheck: function(e) {
        e.preventDefault();
    },

    onClick: function(e) {
        var me = this;

        // If pointer type is touch, we should only toggle check status if there's no submenu
        // or they tapped in the checkEl. This is because there's no hover to invoke the submenu
        // on touch devices, so a tap is needed to show it. That tap should not toggle
        // unless it's on the checkbox.
        if (!(me.disabled || me.checkChangeDisabled || me.checked && me.group ||
            me.menu && "touch" === e.pointerType && !me.checkEl.contains(e.target))) {
            me.setChecked(!me.checked);

            // Clicked using SPACE or ENTER just un-checks.
            // RightArrow to invoke any submenu
            if (e.type === 'keydown' && me.menu) {
                return false;
            }
        }

        return me.callParent([e]);
    },

    doDestroy: function() {
        Ext.menu.Manager.unregisterCheckable(this);

        this.callParent();
    },

    setText: function(text) {
        var me = this,
            ariaDom = me.ariaEl.dom;

        me.callParent([text]);

        if (ariaDom && me.menu) {
            ariaDom.setAttribute('aria-label', Ext.String.formatEncode(me.submenuText, text));
        }
    },

    /**
     * @cfg [publishes='checked']
     * @inheritdoc
     */

    /**
     * Sets the checked state of the item
     * @param {Boolean} checked True to check, false to un-check
     * @param {Boolean} [suppressEvents=false] True to prevent firing the checkchange events.
     */
    setChecked: function(checked, suppressEvents) {
        var me = this,
            checkedCls = me.checkedCls,
            uncheckedCls = me.uncheckedCls,
            el = me.el,
            ariaDom = me.ariaEl.dom,
            checkedConfigure = me.checkedConfigure;

        if (me.checked !== checked &&
            (suppressEvents || me.fireEvent('beforecheckchange', me, checked) !== false)) {
            if (el) {
                if (checked) {
                    el.addCls(checkedCls);
                    el.removeCls(uncheckedCls);
                }
                else {
                    el.addCls(uncheckedCls);
                    el.removeCls(checkedCls);
                }
            }

            if (ariaDom) {
                ariaDom.setAttribute('aria-checked', me.menu ? 'mixed' : !!checked);
            }

            me.checked = checked;
            me.checkedConfigure = checked;
            Ext.menu.Manager.onCheckChange(me, checked);

            // Don't publish the state if we're initially setting the
            // checked state and we didn't get configured with a value
            if (!(me.initial && checkedConfigure == null)) {
                me.publishState('checked', checked);
            }

            if (!suppressEvents) {
                Ext.callback(me.checkHandler, me.scope, [me, checked], 0, me);
                me.fireEvent('checkchange', me, checked);
            }
        }
    }
});
