Ext.define('Ext.theme.triton.grid.column.Column', {
    override: 'Ext.grid.column.Column',

    compatibility: Ext.isIE8,

    onTitleMouseOver: function() {
        var triggerEl = this.triggerEl;

        this.callParent(arguments);

        if (triggerEl) {
            triggerEl.syncRepaint();
        }
    }
});
