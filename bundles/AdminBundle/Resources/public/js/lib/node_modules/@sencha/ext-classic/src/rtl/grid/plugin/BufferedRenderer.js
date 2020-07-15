Ext.define('Ext.rtl.grid.plugin.BufferedRenderer', {
    override: 'Ext.grid.plugin.BufferedRenderer',

    translateBody: function(body, bodyTop) {
        var scroller = this.view.getScrollable();

        if (this.isRTL && Ext.supports.xOriginBug && scroller && scroller.getY()) {
            body.translate(Ext.scrollbar.width(), this.bodyTop = bodyTop);
        }
        else {
            this.callParent([body, bodyTop]);
        }
    }
});
