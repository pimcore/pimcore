Ext.define('Ext.theme.triton.selection.CheckboxModel', {
    override: 'Ext.selection.CheckboxModel',

    headerWidth: 32,

    onHeaderClick: function(headerCt, header, e) {
        this.callParent([headerCt, header, e]);

        // Every checkbox needs repainting.
        if (Ext.isIE8) {
            header.getView().ownerGrid.el.syncRepaint();
        }
    }
});
