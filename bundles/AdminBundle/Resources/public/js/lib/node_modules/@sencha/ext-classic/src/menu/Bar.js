/**
 * Horizontal Menu bar widget, a specialized version of the {@link Ext.menu.Menu}.
 *
 *      @example
 *      new Ext.menu.Bar({
 *          renderTo: Ext.getBody(),
 *          width: 200,
 *          items: [{
 *              text: 'File',
 *              menu: [
 *                  { text: 'Open...' },
 *                  '-',
 *                  { text: 'Close' }
 *              ]
 *          }, {
 *              text: 'Edit',
 *              menu: [
 *                  { text: 'Cut' },
 *                  { text: 'Copy' }
 *                  { text: 'Paste' }
 *              ]
 *          }]
 *      });
 */
Ext.define('Ext.menu.Bar', {
    extend: 'Ext.menu.Menu',
    xtype: 'menubar',

    isMenuBar: true,

    /**
     * @config {String} defaultMenuAlign The default {@link Ext.menu.Item#menuAlign} config
     * for direct child items of this Menu bar.
     */
    defaultMenuAlign: 'tl-bl?',

    floating: false,
    constrain: false,
    showSeparator: false,
    allowOtherMenus: true,

    ariaRole: 'menubar',

    ui: 'default-menubar',

    layout: {
        type: 'hbox',
        align: 'stretchmax',
        pack: 'start',
        overflowHandler: 'menu'
    },

    lookupComponent: function(comp) {
        comp = this.callParent([comp]);

        if (comp.isMenuItem) {
            comp.menuAlign = this.defaultMenuAlign;
        }

        return comp;
    },

    privates: {
        onFocusableContainerLeftKey: function(e) {
            // The default action is to scroll the nearest horizontally scrollable container
            e.preventDefault();

            this.mixins.focusablecontainer.onFocusableContainerLeftKey.call(this, e);
        },

        onFocusableContainerRightKey: function(e) {
            // Ditto
            e.preventDefault();

            this.mixins.focusablecontainer.onFocusableContainerRightKey.call(this, e);
        },

        onFocusableContainerUpKey: function(e) {
            var focusItem = this.lastFocusedChild;

            e.preventDefault();

            // As per WAI-ARIA, both Up and Down arrow keys open submenu
            if (focusItem && focusItem.expandMenu) {
                focusItem.expandMenu(e, 0);
            }
        },

        onFocusableContainerDownKey: function(e) {
            var focusItem = this.lastFocusedChild;

            e.preventDefault();

            if (focusItem && focusItem.expandMenu) {
                focusItem.expandMenu(e, 0);
            }
        }
    }
});
