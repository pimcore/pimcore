/**
 * Provides a common registry groups of {@link Ext.menu.CheckItem}s.
 *
 * Since 5.1.0, this class no longer registers all menus in your applications.
 * To access all menus, use {@link Ext.ComponentQuery ComponentQuery}.
 * @singleton
 */
Ext.define('Ext.menu.Manager', {
    singleton: true,

    alternateClassName: 'Ext.menu.MenuMgr',

    uses: ['Ext.menu.Menu'],

    groups: {},

    visible: [],

    /**
     * @private
     */
    constructor: function() {
        var me = this;

        // Lazily create the mousedown listener on first menu show
        me.onShow = function() {
            // This is a separate method to allow calling eagerly in unit tests
            me.registerGlobalListeners();

            return me.onShow.apply(me, arguments); // do the real thing
        };
    },

    onGlobalScroll: function(scroller) {
        var allMenus = this.visible,
            len = allMenus.length,
            i, menu,
            scrollerEl = scroller.getElement();

        // Scrolling document should not hide menus.
        // The will move along with the document.
        if (len && scroller !== Ext.scroll.Scroller.viewport) {
            // Clone here, we may modify this collection while the loop is active
            allMenus = allMenus.slice();

            for (i = 0; i < len; ++i) {
                menu = allMenus[i];

                // Hide the menu if:
                //      The menu does not own scrolling element
                if (!menu.alignOnScroll && menu.hideOnScroll !== false && !menu.owns(scrollerEl)) {
                    menu.hide();
                }
            }
        }
    },

    checkActiveMenus: function(e) {
        var allMenus = this.visible,
            len = allMenus.length,
            i, menu,
            mousedownCmp = Ext.Component.from(e);

        if (len) {
            // Clone here, we may modify this collection while the loop is active
            allMenus = allMenus.slice();

            for (i = 0; i < len; ++i) {
                menu = allMenus[i];

                // Hide the menu if:
                //      The menu does not own the clicked upon element AND
                //      The menu is not the child menu of a clicked upon MenuItem
                // eslint-disable-next-line max-len
                if (!(menu.owns(e) || (mousedownCmp && mousedownCmp.isMenuItem && mousedownCmp.getMenu() === menu))) {
                    menu.hide();
                }
            }
        }
    },

    /**
     * {@link Ext.menu.Menu#afterShow} adds itself to the visible list here.
     * @private
     */
    onShow: function(menu) {
        if (menu.floating) {
            Ext.Array.include(this.visible, menu);
        }
    },

    /**
     * {@link Ext.menu.Menu#onHide} removes itself from the visible list here.
     * @private
     */
    onHide: function(menu) {
        if (menu.floating) {
            Ext.Array.remove(this.visible, menu);
        }
    },

    /**
     * Hides all floating menus that are currently visible
     * @return {Boolean} success True if any active menus were hidden.
     */
    hideAll: function() {
        var allMenus = this.visible,
            len = allMenus.length,
            result = false,
            i;

        if (len) {
            // Clone here, we may modify this collection while the loop is active
            allMenus = allMenus.slice();

            for (i = 0; i < len; i++) {
                allMenus[i].hide();
                result = true;
            }
        }

        return result;
    },

    /**
     * Returns a {@link Ext.menu.Menu} object
     * @param {String/Object} menu The string menu id, an existing menu object reference,
     * or a Menu config that will be used to generate and return a new Menu this.
     * @param {Object} [config] A configuration to use when creating the menu.
     * @return {Ext.menu.Menu} The specified menu, or null if none are found
     */
    get: function(menu, config) {
        var result;

        if (typeof menu === 'string') { // menu id
            result = Ext.getCmp(menu);

            if (result instanceof Ext.menu.Menu) {
                menu = result;
            }
        }
        else if (Ext.isArray(menu)) { // array of menu items
            config = Ext.apply({ items: menu }, config);
            menu = new Ext.menu.Menu(config);
        }
        else if (!menu.isComponent) { // otherwise, must be a config
            config = Ext.apply({}, menu, config);
            menu = Ext.ComponentManager.create(config, 'menu');
        }

        return menu;
    },

    /**
     * @private
     */
    registerCheckable: function(menuItem) {
        var groups = this.groups,
            groupId = menuItem.group;

        if (groupId) {
            if (!groups[groupId]) {
                groups[groupId] = [];
            }

            groups[groupId].push(menuItem);
        }
    },

    /**
     * @private
     */
    unregisterCheckable: function(menuItem) {
        var groups = this.groups,
            groupId = menuItem.group;

        if (groupId) {
            Ext.Array.remove(groups[groupId], menuItem);
        }
    },

    onCheckChange: function(menuItem, state) {
        var groups = this.groups,
            groupId = menuItem.group,
            i = 0,
            group, ln, curr;

        if (groupId && state) {
            group = groups[groupId];
            ln = group.length;

            for (; i < ln; i++) {
                curr = group[i];

                if (curr !== menuItem) {
                    curr.setChecked(false);
                }
            }
        }
    },

    /**
     * @private
     */
    registerGlobalListeners: function() {
        var me = this;

        delete me.onShow; // remove the lazy-init hook

        // Use the global mousedown event that gets fired even if propagation is stopped
        Ext.on({
            mousedown: me.checkActiveMenus,
            scroll: me.onGlobalScroll,
            scope: me
        });
    }
});
