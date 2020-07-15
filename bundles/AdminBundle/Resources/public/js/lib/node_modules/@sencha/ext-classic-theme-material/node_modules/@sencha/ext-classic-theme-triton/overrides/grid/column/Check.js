Ext.define('Ext.theme.triton.grid.column.Check', {
    override: 'Ext.grid.column.Check',

    compatibility: Ext.isIE8,

    setRecordCheck: function(record, index, checked, cell) {
        this.callParent(arguments);
        Ext.fly(cell).syncRepaint();
    }
});
