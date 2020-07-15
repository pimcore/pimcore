/**
 * A menu object. This is the container to which you may add {@link Ext.menu.Item menu items}.
 *
 * Menus may contain either {@link Ext.menu.Item menu items}, or general
 * {@link Ext.Component Components}. Menus may also contain
 * {@link Ext.panel.Panel#dockedItems docked items} because it extends {@link Ext.panel.Panel}.
 *
 * By default, non {@link Ext.menu.Item menu items} are indented so that they line up with the text
 * of menu items, clearing the icon column. To make a contained general
 * {@link Ext.Component Component} left aligned configure the child Component with `indent: false`.
 *
 * By default, Menus are absolutely positioned, floating Components. By configuring a 
 * Menu with `{@link #cfg-floating}: false`, a Menu may be used as a child of a 
 * {@link Ext.container.Container Container}.
 *
 *     @example
 *     Ext.create('Ext.menu.Menu', {
 *         width: 100,
 *         margin: '0 0 10 0',
 *         floating: false,  // usually you want this set to True (default)
 *         renderTo: Ext.getBody(),  // usually rendered by it's containing component
 *         items: [{
 *             text: 'regular item 1'
 *         }, {
 *             text: 'regular item 2'
 *         }, {
 *             text: 'regular item 3'
 *         }]
 *     });
 *
 *     Ext.create('Ext.menu.Menu', {
 *         width: 100,
 *         plain: true,
 *         floating: false,  // usually you want this set to True (default)
 *         renderTo: Ext.getBody(),  // usually rendered by it's containing component
 *         items: [{
 *             text: 'plain item 1'
 *         }, {
 *             text: 'plain item 2'
 *         }, {
 *             text: 'plain item 3'
 *         }]
 *     });
 */
Ext.define('Ext.menu.Menu', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.menu',

    requires: [
        'Ext.layout.container.VBox',
        'Ext.menu.CheckItem',
        'Ext.menu.Item',
        'Ext.menu.Manager',
        'Ext.menu.Separator'
    ],

    defaultType: 'menuitem',

    /**
     * @property {Ext.menu.Menu} parentMenu
     * The parent Menu of this Menu.
     */

    /**
     * @cfg {Boolean} [enableKeyNav=true]
     * @deprecated 5.1.0 Intra-menu key navigation is always enabled.
     */
    enableKeyNav: true,

    /**
     * @cfg {Boolean} [allowOtherMenus=false]
     * True to allow multiple menus to be displayed at the same time.
     */
    allowOtherMenus: false,

    /**
     * @cfg {String} ariaRole
     * @private
     */
    ariaRole: 'menu',

    /**
     * @cfg {Boolean} autoRender
     * Floating is true, so autoRender always happens.
     * @private
     */

    /**
     * @cfg {Boolean} [floating=true]
     * A Menu configured as `floating: true` (the default) will be rendered as an 
     * absolutely positioned,
     * {@link Ext.Component#cfg-floating floating} {@link Ext.Component Component}. If 
     * configured as `floating: false`, the Menu may be used as a child item of another 
     * {@link Ext.container.Container Container}.
     */
    floating: true,

    /**
     * @cfg {Boolean} constrain
     * Menus are constrained to the document body by default.
     * @private
     */
    constrain: true,

    /**
     * @cfg {Boolean} [hidden]
     * True to initially render the Menu as hidden, requiring to be shown manually.
     *
     * Defaults to `true` when `floating: true`, and defaults to `false` when `floating: false`.
     */
    hidden: true,

    hideMode: 'visibility',

    /**
     * @cfg {Boolean} [ignoreParentClicks=false]
     * True to ignore clicks on any item in this menu that is a parent item (displays a submenu)
     * so that the submenu is not dismissed when clicking the parent item.
     */
    ignoreParentClicks: false,

    /**
     * @cfg {Number} [mouseLeaveDelay]
     * The delay in ms as to how long the framework should wait before firing a mouseleave event.
     * This allows submenus not to be collapsed while hovering other menu items.
     *
     * Defaults to 50
     */
    mouseLeaveDelay: 50,

    /**
     * @property {Boolean} isMenu
     * `true` in this class to identify an object as an instantiated Menu, or subclass thereof.
     */
    isMenu: true,

    /**
     * @cfg {Ext.enums.Layout/Object} layout
     * @private
     */

    /**
     * @cfg {Boolean} [showSeparator=true]
     * True to show the icon separator.
     */
    showSeparator: true,

    /**
     * @cfg {Number} [minWidth=120]
     * The minimum width of the Menu. The default minWidth only applies when the 
     * {@link #cfg-floating} config is true.
     */
    minWidth: undefined,

    defaultMinWidth: 120,

    /**
     * @cfg {String} [defaultAlign="tl-bl?"]
     * The default {@link Ext.util.Positionable#getAlignToXY Ext.dom.Element#getAlignToXY}
     * anchor position value for this menu relative to its owner. Used in conjunction with
     * {@link #showBy}.
     */
    defaultAlign: 'tl-bl?',

    /**
     * @cfg {Boolean} [plain=false]
     * True to remove the incised line down the left side of the menu and to not indent general
     * Component items.
     * 
     * {@link Ext.menu.Item MenuItem}s will *always* have space at their start for an icon.
     * With the `plain` setting, non {@link Ext.menu.Item MenuItem} child components will not
     * be indented to line up.
     * 
     * Basically, `plain:true` makes a Menu behave more like a regular
     * {@link Ext.layout.container.HBox HBox layout} {@link Ext.panel.Panel Panel}
     * which just has the same background as a Menu.
     * 
     * See also the {@link #showSeparator} config.
     */

    /**
     * @cfg focusOnToFront
     * @inheritdoc
     */
    focusOnToFront: false,

    bringParentToFront: false,
    alignOnScroll: false,

    // Menus are focusable
    focusable: true,
    tabIndex: -1,
    focusableContainer: true,

    // When a Menu is used as a carrier to float some focusable Component such as a DatePicker
    // or ColorPicker
    // This will be used to delegate focus to its focusable child.
    // In normal usage, a Menu is a FocusableContainer, and this will not be consulted.
    defaultFocus: ':focusable',

    // We need to focus disabled menu items when arrowing as per WAI-ARIA:
    // http://www.w3.org/TR/wai-aria-practices/#menu
    allowFocusingDisabledChildren: true,

    /**
     * @private
     */
    menuClickBuffer: 0,
    baseCls: Ext.baseCSSPrefix + 'menu',
    _iconSeparatorCls: Ext.baseCSSPrefix + 'menu-icon-separator',
    _itemCmpCls: Ext.baseCSSPrefix + 'menu-item-cmp',

    /**
     * @event click
     * Fires when this menu is clicked
     * @param {Ext.menu.Menu} menu The menu which has been clicked
     * @param {Ext.Component} item The menu item that was clicked. `undefined` if not applicable.
     * @param {Ext.event.Event} e The underlying {@link Ext.event.Event}.
     */

    /**
     * @event mouseenter
     * Fires when the mouse enters this menu
     * @param {Ext.menu.Menu} menu The menu
     * @param {Ext.event.Event} e The underlying {@link Ext.event.Event}
     */

    /**
     * @event mouseleave
     * Fires when the mouse leaves this menu
     * @param {Ext.menu.Menu} menu The menu
     * @param {Ext.event.Event} e The underlying {@link Ext.event.Event}
     */

    /**
     * @event mouseover
     * Fires when the mouse is hovering over this menu
     * @param {Ext.menu.Menu} menu The menu
     * @param {Ext.Component} item The menu item that the mouse is over. `undefined`
     * if not applicable.
     * @param {Ext.event.Event} e The underlying {@link Ext.event.Event}
     */

    layout: {
        type: 'vbox',
        align: 'stretchmax',
        overflowHandler: 'Scroller'
    },

    initComponent: function() {
        var me = this,
            cls = [Ext.baseCSSPrefix + 'menu'],
            bodyCls = me.bodyCls ? [me.bodyCls] : [],
            isFloating = me.floating !== false,
            listeners = {
                element: 'el',
                click: me.onClick,
                mouseover: me.onMouseOver,
                scope: me
            };

        if (Ext.supports.Touch) {
            listeners.pointerdown = me.onMouseOver;
        }

        me.on(listeners);
        me.on({
            beforeshow: me.onBeforeShow,
            scope: me
        });

        // Menu classes
        if (me.plain) {
            cls.push(Ext.baseCSSPrefix + 'menu-plain');
        }

        me.cls = cls.join(' ');

        // Menu body classes
        bodyCls.push(Ext.baseCSSPrefix + 'menu-body', Ext.dom.Element.unselectableCls);
        me.bodyCls = bodyCls.join(' ');

        if (isFloating) {
            // only apply the minWidth when we're floating & one hasn't already been set
            if (me.minWidth === undefined) {
                me.minWidth = me.defaultMinWidth;
            }
        }
        else {
            // hidden defaults to false if floating is configured as false
            me.hidden = !!me.initialConfig.hidden;
            me.constrain = false;
        }

        me.callParent();

        // Configure items prior to render with special classes to align
        // non MenuItem child components with their MenuItem siblings.
        Ext.override(me.getLayout(), {
            configureItem: me.configureItem
        });

        me.itemOverTask = new Ext.util.DelayedTask(me.handleItemOver, me);
    },

    // Private implementation for Menus. They are a special case, in that in the vast majority
    // (nearly all?) of use cases they shouldn't be constrained to anything other than the viewport.
    // See EXTJS-13596.
    /**
     * @method
     * @private
     */
    initFloatConstrain: Ext.emptyFn,

    getInherited: function() {
        // As floating menus are never contained, a floating Menu's visibility only ever depends
        // upon its own hidden state.
        // Ignore hiddenness from the ancestor hierarchy, override it with local hidden state.
        var result = this.callParent();

        if (this.floating) {
            result.hidden = this.hidden;
        }

        return result;
    },

    beforeRender: function() {
        var me = this;

        me.callParent();

        // Menus are usually floating: true, which means they shrink wrap their items.
        // However, when they are contained, and not auto sized, we must stretch the items.
        if (!me.getSizeModel().width.shrinkWrap) {
            me.layout.align = 'stretch';
        }

        if (me.floating) {
            me.ariaRenderAttributes = me.ariaRenderAttributes || {};
            me.ariaRenderAttributes['aria-expanded'] = !!me.autoShow;
        }
    },

    onBoxReady: function(width, height) {
        var me = this,
            iconSeparatorCls = me._iconSeparatorCls,
            keyNav = me.focusableKeyNav;

        // Keyboard handling can be disabled, e.g. by the DatePicker menu
        // or the Date filter menu constructed by the Grid
        if (keyNav) {
            // Handle ESC key
            keyNav.map.addBinding([
                {
                    key: Ext.event.Event.ESC,
                    handler: me.onEscapeKey,
                    scope: me
                },
                // Handle character shortcuts
                {
                    key: /[\w]/,
                    handler: me.onShortcutKey,
                    scope: me,
                    shift: false,
                    ctrl: false,
                    alt: false
                }]
            );
        }
        else {
            // Even when FocusableContainer key event processing is disabled,
            // we still need to handle the Escape key!
            me.escapeKeyNav = new Ext.util.KeyNav({
                target: me.el,
                eventName: 'keydown',
                scope: me,
                esc: me.onEscapeKey
            });
        }

        me.callParent([width, height]);

        // TODO: Move this to a subTemplate When we support them in the future
        if (me.showSeparator) {
            me.iconSepEl = me.body.insertFirst({
                role: 'presentation',
                cls: iconSeparatorCls + ' ' + iconSeparatorCls + '-' + me.ui,
                html: '&#160;'
            });
        }

        // Modern IE browsers have click events translated to PointerEvents, and b/c of this the
        // event isn't being canceled like it needs to be. So, we need to add an extra listener.
        // For devices that have touch support, the default click event may be a gesture that
        // runs asynchronously, so by the time we try and prevent it, it's already happened
        if (Ext.supports.Touch || Ext.supports.MSPointerEvents || Ext.supports.PointerEvents) {
            me.el.on({
                scope: me,
                click: me.preventClick,
                translate: false
            });
        }

        me.mouseMonitor = me.el.monitorMouseLeave(me.mouseLeaveDelay, me.onMouseLeave, me);
    },

    onFocusEnter: function(e) {
        var me = this,
            hierarchyState;

        me.callParent([e]);
        me.mixins.focusablecontainer.onFocusEnter.call(me, e);

        if (me.floating) {
            hierarchyState = me.getInherited();

            // The topmost focusEnter event upon entry into a floating menu stack
            // is recorded in the hierarchy state.
            //
            // Focusing upwards from descendant menus in a stack will NOT trigger onFocusEnter
            // on the parent menu because focus is already in its component tree.
            // For focusing downwards we check for presence of the topmostFocusEvent
            // already being present in the hierarchy.
            //
            // If we need to explicitly access a focus reversion point, we can use that.
            // This is only ever needed if tabbing forwards from the menu. We explicitly
            // push focus to the topmost focusEnter component, and then allow natural
            // tabbing to proceed from there.
            //
            // In all other focus reversion scenarios we use the immediate focusEnter event
            if (!hierarchyState.topmostFocusEvent) {
                hierarchyState.topmostFocusEvent = e;
            }
        }
    },

    onFocusLeave: function(e) {
        var me = this;

        me.callParent([e]);

        // We need to make sure that menus do not "remember" the last focused item
        // so that the first menu item is always activated when the menu is shown.
        // This is the expected behavior according to WAI-ARIA spec.
        me.lastFocusedChild = null;

        me.mixins.focusablecontainer.onFocusLeave.call(me, e);

        if (me.floating) {
            me.hide();
        }
    },

    handleItemOver: function(e, item) {
        // Only focus non-menuitem on real mouseover events.
        if (!item.containsFocus && (e.pointerType === 'mouse' || item.isMenuItem)) {
            item.focus();
        }

        if (item.expandMenu) {
            item.expandMenu(e);
        }
    },

    /**
     * @param {Ext.Component} item The child item to test for focusability.
     * Returns whether a menu item can be activated or not.
     * @return {Boolean} `true` if the passed item is focusable.
     */
    canActivateItem: function(item) {
        return item && item.isFocusable();
    },

    /**
     * Deactivates the current active item on the menu, if one exists.
     */
    deactivateActiveItem: function() {
        var me = this,
            activeItem = me.lastFocusedChild;

        if (activeItem) {
            activeItem.blur();
        }
    },

    /**
     * @private
     */
    getItemFromEvent: function(e) {
        var me = this,
            renderTarget = me.layout.getRenderTarget().dom,
            toEl = e.getTarget();

        // See which top level element the event is in and find its owning Component.
        while (toEl.parentNode !== renderTarget) {
            toEl = toEl.parentNode;

            if (!toEl) {
                return;
            }
        }

        return Ext.getCmp(toEl.id);
    },

    lookupComponent: function(cmp) {
        var me = this;

        if (typeof cmp === 'string') {
            if (cmp[0] === '@') {
                cmp = this.callParent([cmp]);
            }
            else {
                cmp = me.lookupItemFromString(cmp);
            }
        }
        else if (Ext.isObject(cmp)) {
            cmp = me.lookupItemFromObject(cmp);
        }

        // Apply our minWidth to all of our non-docked child components (Menu extends Panel)
        // so it's accounted for in our VBox layout
        if (!cmp.dock) {
            cmp.minWidth = cmp.minWidth || me.minWidth;
        }

        return cmp;
    },

    /**
     * @private
     */
    lookupItemFromObject: function(cmp) {
        var type = this.defaultType;

        if (!cmp.isComponent) {
            if (!cmp.xtype && Ext.isBoolean(cmp.checked)) {
                type = 'menucheckitem';
            }

            cmp = Ext.ComponentManager.create(cmp, type);
        }

        if (cmp.isMenuItem) {
            cmp.parentMenu = this;
        }

        return cmp;
    },

    /**
     * @private
     */
    lookupItemFromString: function(cmp) {
        return (cmp === 'separator' || cmp === '-')
            ? new Ext.menu.Separator()
            : new Ext.menu.Item({
                canActivate: false,
                hideOnClick: false,
                plain: true,
                text: cmp
            });
    },

    // Override applied to the Menu's layout. Runs in the context of the layout.
    // Add special classes to allow non MenuItem components to coexist with MenuItems.
    // If there is only *one* child, then this Menu is just a vehicle for floating
    // and aligning the component, so do not do this.
    configureItem: function(cmp) {
        var me = this.owner,
            prefix = Ext.baseCSSPrefix,
            ui = me.ui,
            cls, cmpCls;

        if (cmp.isMenuItem) {
            cmp.setUI(ui);
        }
        else if (me.items.getCount() > 1 && !cmp.rendered && !cmp.dock) {
            cmpCls = me._itemCmpCls;
            cls = [cmpCls, cmpCls + '-' + ui];

            // The "plain" setting means that the menu does not look so much like a menu.
            // It's more like a grey Panel. So it has no vertical separator.
            // Plain menus also will not indent non MenuItem components;
            // there is nothing to indent them to the right of.
            if (!me.plain && (cmp.indent !== false || cmp.iconCls === 'no-icon')) {
                cls.push(prefix + 'menu-item-indent-' + ui);
            }

            if (cmp.rendered) {
                cmp.el.addCls(cls);
            }
            else {
                cmp.cls = (cmp.cls || '') + ' ' + cls.join(' ');
            }

            // So we can clean the item if it gets removed.
            cmp.$extraMenuCls = cls;
        }

        // @noOptimize.callParent
        this.callParent(arguments);
    },

    onRemove: function(cmp) {
        this.callParent([cmp]);

        // Remove any extra classes we added to non-MenuItem child items
        if (!cmp.destroyed && cmp.$extraMenuCls) {
            cmp.el.removeCls(cmp.$extraMenuCls);
        }
    },

    onClick: function(e) {
        var me = this,
            type = e.type,
            item,
            clickResult,
            iskeyEvent = type === 'keydown';

        if (me.disabled) {
            e.stopEvent();

            return;
        }

        item = me.getItemFromEvent(e);

        if (item && item.isMenuItem) {
            if (!item.menu || !me.ignoreParentClicks) {
                clickResult = item.onClick(e);
            }
            else {
                e.stopEvent();
            }

            // Click handler on the item could have destroyed the menu
            if (me.destroyed) {
                return;
            }

            // SPACE and ENTER invokes the menu
            if (item.menu && clickResult !== false && iskeyEvent) {
                item.expandMenu(e, 0);
            }
        }

        // Click event may be fired without an item, so we need a second check
        if (!item || item.disabled) {
            item = undefined;
        }

        me.fireEvent('click', me, item, e);
    },

    doDestroy: function() {
        var me = this;

        if (me.escapeKeyNav) {
            me.escapeKeyNav.destroy();
        }

        me.itemOverTask.cancel();
        me.parentMenu = me.ownerCmp = me.escapeKeyNav = null;

        if (me.rendered) {
            me.el.un(me.mouseMonitor);
            Ext.destroy(me.iconSepEl);
        }

        // Menu can be destroyed while shown;
        // we should notify the Manager
        Ext.menu.Manager.onHide(me);

        me.callParent();
    },

    onMouseLeave: function(e) {
        var me = this;

        if (me.itemOverTask) {
            me.itemOverTask.cancel();
        }

        if (me.disabled) {
            return;
        }

        me.fireEvent('mouseleave', me, e);
    },

    onMouseOver: function(e) {
        var me = this,
            fromEl = e.getRelatedTarget(),
            mouseEnter = !me.el.contains(fromEl),
            item = me.getItemFromEvent(e),
            parentMenu = me.parentMenu,
            ownerCmp = me.ownerCmp;

        if (mouseEnter && parentMenu) {
            parentMenu.setActiveItem(ownerCmp);
            ownerCmp.cancelDeferHide();
            parentMenu.mouseMonitor.mouseenter();
            parentMenu.itemOverTask.cancel();
        }

        if (me.disabled) {
            return;
        }

        // Do not activate the item if the mouseover was within the item, and it's already active
        if (item) {
            // Activate the item in time specified by mouseLeaveDelay.
            // If we mouseout, or move to another item this invocation will be canceled.
            if (e.pointerType === 'touch') {
                me.handleItemOver(e, item);
            }
            else {
                me.itemOverTask.delay(me.expanded ? me.mouseLeaveDelay : 0, null, null, [e, item]);
            }
        }

        if (mouseEnter) {
            me.fireEvent('mouseenter', me, e);
        }

        me.fireEvent('mouseover', me, item, e);
    },

    setActiveItem: function(item) {
        var me = this;

        if (item && (item !== me.lastFocusedChild)) {
            me.focusChild(item, 1);
            // Focusing will scroll the item into view.
        }
    },

    onEscapeKey: function() {
        if (this.floating) {
            this.hide();
        }
    },

    onShortcutKey: function(keyCode, e) {
        var shortcutChar = String.fromCharCode(e.getCharCode()),
            items = this.query('>[text]'),
            len = items.length,
            item = this.lastFocusedChild,
            focusIndex = Ext.Array.indexOf(items, item),
            i = focusIndex;

        if (len === 0) {
            return;
        }

        // Loop through all items which have a text property
        // starting at the one after the current focus.
        for (;;) {
            if (++i === len) {
                i = 0;
            }

            item = items[i];

            // Looped back to start - no matches
            if (i === focusIndex) {
                return;
            }

            // Found a text match
            if (item.text && item.text[0].toUpperCase() === shortcutChar) {
                item.focus();

                return;
            }
        }
    },

    onBeforeShow: function() {
        // Do not allow show immediately after a hide
        if (Ext.Date.getElapsed(this.lastHide) < this.menuClickBuffer) {
            return false;
        }
    },

    beforeShow: function() {
        var me = this,
            parent;

        // Constrain the height to the containing element's viewable area
        if (me.floating) {
            parent = me.hasFloatMenuParent();

            if (!parent && !me.allowOtherMenus) {
                Ext.menu.Manager.hideAll();
            }
        }

        me.callParent();
    },

    afterShow: function(animateTarget, callback, scope) {
        var me = this,
            ariaDom = me.ariaEl.dom;

        me.callParent([animateTarget, callback, scope]);
        Ext.menu.Manager.onShow(me);

        if (me.parentMenu) {
            me.parentMenu.expanded = true;
        }

        if (me.floating && ariaDom) {
            ariaDom.setAttribute('aria-expanded', true);
        }

        // Restore configured maxHeight
        if (me.floating) {
            me.maxHeight = me.savedMaxHeight;
        }

        if (me.autoFocus) {
            me.focus();
        }
    },

    onHide: function(animateTarget, cb, scope) {
        var me = this,
            ariaDom = me.ariaEl.dom;

        me.callParent([animateTarget, cb, scope]);
        me.lastHide = Ext.Date.now();
        Ext.menu.Manager.onHide(me);

        if (me.parentMenu) {
            me.parentMenu.expanded = false;
        }

        if (me.floating && ariaDom) {
            ariaDom.setAttribute('aria-expanded', false);
        }
    },

    afterHide: function(cb, scope) {
        this.callParent([cb, scope]);

        // Top level focusEnter is only valid when focused
        delete this.getInherited().topmostFocusEvent;
    },

    preventClick: function(e) {
        var item = this.getItemFromEvent(e);

        if (item && item.isMenuItem && !item.href) {
            e.preventDefault();
        }
    },

    privates: {
        /**
         * @private
         */
        applyDefaults: function(config) {
            if (!Ext.isString(config)) {
                config = this.callParent([config]);
            }

            return config;
        },

        initFocusableElement: function() {
            var me = this,
                tabIndex = me.tabIndex,
                el = me.el;

            // Floating menus always need to have focusable main el
            // so that mouse clicks within the menu would not close it.
            // We're not checking focusable property here, Component
            // will do that before we can reach this method.
            if (me.floating && tabIndex != null && el && el.dom) {
                el.dom.setAttribute('tabIndex', tabIndex);
                el.dom.setAttribute('data-componentid', me.id);
            }
        },

        processFocusableContainerKeyEvent: function(e) {
            // ESC may be from input fields, and FocusableContainers ignore keys from
            // input fields. We do not want to ignore ESC. ESC hide menus.
            if (e.keyCode === e.ESC) {
                e.target = this.el.dom;
            }
            // TAB from textual input fields is converted into UP or DOWN.
            else if (e.keyCode === e.TAB && Ext.fly(e.target).is('input[type=text],textarea')) {
                e.preventDefault();
                e.target = this.getItemFromEvent(e).el.dom;

                if (e.shiftKey) {
                    e.shiftKey = false;
                    e.keyCode = e.UP;
                }
                else {
                    e.keyCode = e.DOWN;
                }
            }
            else {
                return this.callParent([e]);
            }

            return e;
        },

        // Tabbing in a floating menu must hide, but not move focus.
        // onHide takes care of moving focus back to an owner Component.
        onFocusableContainerTabKey: function(e) {
            var me = this;

            if (me.floating) {
                if (e.shiftKey) {
                    // We do not want TAB behaviour to proceed.
                    // SHIFT+TAB reverts "backwards" to the menu's invoker
                    // which is the automatic behaviour.
                    e.preventDefault();
                }
                else {
                    // If we want to navigate forwards, we cannot allow the
                    // automatic focus reversion to go to the parent menu.
                    // It must behave as if it were the topmost menu in the
                    // floating stack, revert to there, and then TAB onwards.
                    me.focusEnterEvent = me.getInherited().topmostFocusEvent;
                }

                me.hide();
            }
        },

        onFocusableContainerEnterKey: function(e) {
            this.onClick(e);
        },

        onFocusableContainerSpaceKey: function(e) {
            this.onClick(e);
        },

        onFocusableContainerLeftKey: function(e) {
            // The default action is to scroll the nearest horizontally scrollable container
            e.preventDefault();

            // If we are a submenu, then left arrow focuses the owning MenuItem
            if (this.parentMenu) {
                this.ownerCmp.focus();
                this.hide();
            }
        },

        onFocusableContainerRightKey: function(e) {
            var me = this,
                focusItem = me.lastFocusedChild;

            // See above
            e.preventDefault();

            if (focusItem && focusItem.expandMenu) {
                focusItem.expandMenu(e, 0);
            }
        },

        hasFloatMenuParent: function() {
            return this.parentMenu || this.up('menu[floating=true]');
        },

        setOwnerCmp: function(comp, instanced) {
            var me = this;

            me.parentMenu = comp.isMenuItem ? comp : null;
            me.ownerCmp = comp;
            me.registerWithOwnerCt();

            delete me.hierarchicallyHidden;
            me.onInheritedAdd(comp, instanced);
            me.containerOnAdded(comp, instanced);
        }
    }
});
