Ext.define('Ext.rtl.layout.container.Absolute', {
    override: 'Ext.layout.container.Absolute',

    adjustWidthAnchor: function(width, childContext) {
        if (this.owner.getInherited().rtl) {
            // eslint-disable-next-line vars-on-top
            var padding = this.targetPadding,
                x = childContext.getStyle('right');

            return width - x + padding.right;
        }
        else {
            return this.callParent([width, childContext]);
        }
    }
});
