Ext.define('Ext.layout.component.field.Text', {
    extend: 'Ext.layout.component.Auto',
    alias: 'layout.textfield',

    beginLayoutCycle: function(ownerContext, firstCycle) {
        var target = ownerContext.target;

        ownerContext.el.toggleCls(
            target.heightedCls,
            !ownerContext.heightModel.shrinkWrap || target.minHeight != null
        );

        this.callParent([ownerContext, firstCycle]);
    }
});
