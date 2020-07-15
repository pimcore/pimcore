Ext.define('Ext.rtl.grid.locking.Lockable', {
    override: 'Ext.grid.locking.Lockable',

    getScrollExtraCls: function() {
        return this.getInherited().rtl ? this._rtlCls : '';
    },

    initScrollers: function() {
        var me = this,
            normalView = me.normalGrid.view;

        if (normalView.el._rtlScrollbarOnRight && me.getInherited().rtl) {
            me.verticalScrollbar = me.scrollContainer.appendChild({
                cls: me.scrollbarCls,
                style: {
                    top: 0,
                    left: 0,
                    bottom: 0,
                    width: Ext.scrollbar.width() + 'px'
                }
            });

            me.verticalScrollbarScroller = new Ext.scroll.Scroller({
                element: me.verticalScrollbar,
                x: false,
                y: true
            });

            me.verticalScrollbarScroller.addPartner(me.scrollable, 'y');
        }
    },

    onSyncLockableLayout: function(hasVerticalScrollbar, viewWidth) {
        var me = this,
            verticalScrollbar = me.verticalScrollbar,
            scrollbarWidth, normalGrid, style;

        // Account for the scrollbar being stuck at the right in RTL mode
        // This is a bug which affects Safari. All our layouts assume that
        // scrollbar always goes at the locale end of content. We will only have
        // a verticalScrollbar if we're in RTL, so no need to check
        if (verticalScrollbar) {
            if (hasVerticalScrollbar) {
                normalGrid = me.normalGrid;
                scrollbarWidth = Ext.scrollbar.width();
                style = me.scrollBody.dom.style;

                style.width = (viewWidth + scrollbarWidth) + 'px';
                style.right = -scrollbarWidth + 'px';

                normalGrid.headerCt.layout.innerCt.setWidth(
                    normalGrid.headerCt.layout.innerCt.getWidth() + scrollbarWidth
                );

                me.verticalScrollbarScroller.setSize({ y: me.scrollable.getSize().y });
                me.verticalScrollbar.show();
            }
            else {
                me.verticalScrollbar.hide();
            }
        }
    },

    setNormalScrollerX: function(x) {
        var me = this;

        if (me.getInherited().rtl) {
            me.normalScrollbar.rtlSetLocalX(x);
            me.normalScrollbarClipper.rtlSetLocalX(x);
        }
        else {
            me.callParent([x]);
        }
    }
});
