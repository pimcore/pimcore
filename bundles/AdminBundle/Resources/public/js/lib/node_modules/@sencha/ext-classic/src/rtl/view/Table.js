Ext.define('Ext.rtl.view.Table', {
    override: 'Ext.view.Table',

    /* eslint-disable indent, max-len */
    rtlCellTpl: [
        '<td class="' + Ext.baseCSSPrefix + 'rtl {tdCls}" {tdAttr} {[Ext.aria ? "id=\\"" + Ext.id() + "\\"" : ""]} style="width:{column.cellWidth}px;<tpl if="tdStyle">{tdStyle}</tpl>" tabindex="-1" {ariaCellAttr} data-columnid="{[values.column.getItemId()]}">',
            '<div {unselectableAttr} class="' + Ext.baseCSSPrefix + 'rtl ' + Ext.baseCSSPrefix + 'grid-cell-inner {innerCls}" ',
        'style="text-align:{align};<tpl if="style">{style}</tpl>" {ariaCellInnerAttr}>{value}</div>',
        '</td>', {
            priority: 0
        }
    ],
    /* eslint-enable indent, max-len */

    beforeRender: function() {
        var me = this;

        me.callParent();

        if (me.getInherited().rtl) {
            me.addCellTpl(me.lookupTpl('rtlCellTpl'));
        }
    },

    getCellPaddingAfter: function(cell) {
        return Ext.fly(cell).getPadding(this.getInherited().rtl ? 'l' : 'r');
    }
});
