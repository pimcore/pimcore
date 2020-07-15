Ext.define('Ext.theme.material.view.Table', {
    override: 'Ext.view.Table',

    mixins: [
        'Ext.mixin.ItemRippler'
    ],

    config: {
        itemRipple: {
            color: 'default'
        }
    },

    processItemEvent: function(record, item, rowIndex, e) {
        var me = this,
            eventPosition, result, rowElement, cellElement,
            selModel;

        result = me.callParent([record, item, rowIndex, e]);

        if (e.type === 'mousedown') {
            eventPosition = me.eventPosition;
            rowElement = eventPosition && me.eventPosition.rowElement;
            cellElement = eventPosition && me.eventPosition.cellElement;
            selModel = me.getSelectionModel().type;

            // for ripple on row click
            if (rowElement && (selModel === 'rowmodel')) {
                me.rippleItem(Ext.fly(rowElement), e);
            }
            // for ripple on cell click
            else if (cellElement && (selModel === 'cellmodel')) {
                me.rippleItem(Ext.fly(cellElement), e);
            }
        }

        return result;
    }
});
