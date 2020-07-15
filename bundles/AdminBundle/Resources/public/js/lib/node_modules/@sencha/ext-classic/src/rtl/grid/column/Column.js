Ext.define('Ext.rtl.grid.column.Column', {
    override: 'Ext.grid.column.Column',

    beforeRender: function() {
        var me = this;

        if (me.getInherited().rtl) {
            me._alignMap = me._rtlAlignMap;
        }

        me.callParent();
    },

    isAtStartEdge: function(e, margin) {
        var me = this,
            offset;

        if (!me.getInherited().rtl !== !Ext.rootInheritedState.rtl) {
            offset = me.getX() + me.getWidth() - e.getXY()[0];

            // To the right of the first column, not over
            if (offset < 0 && this.getIndex() === 0) {
                return false;
            }

            return (offset <= me.getHandleWidth(e));
        }
        else {
            return me.callParent([e, margin]);
        }
    },

    isAtEndEdge: function(e, margin) {
        var me = this;

        return (!me.getInherited().rtl !== !Ext.rootInheritedState.rtl)
            ? (e.getXY()[0] - me.getX() <= me.getHandleWidth(e))
            : me.callParent([e, margin]);
    },

    privates: {
        _rtlAlignMap: {
            start: 'right',
            end: 'left'
        }
    }
});
