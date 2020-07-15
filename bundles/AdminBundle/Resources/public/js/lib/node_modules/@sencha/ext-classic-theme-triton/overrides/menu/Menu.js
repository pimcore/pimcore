Ext.define('Ext.theme.triton.menu.Menu', {
    override: 'Ext.menu.Menu',

    compatibility: Ext.isIE8,

    afterShow: function() {
        var me = this,
            items, item, i, len;

        me.callParent(arguments);

        items = me.items.getRange();

        for (i = 0, len = items.length; i < len; i++) {
            item = items[i];

            // Just in case if it happens to be a non-menu Item 
            if (item && item.repaintIcons) {
                item.repaintIcons();
            }
        }
    }
});
