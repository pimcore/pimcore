Ext.define('Ext.rtl.scroll.Scroller', {
    override: 'Ext.scroll.Scroller',

    config: {
        /**
         * @cfg {Boolean} [rtl=false]
         * `true` to enable scrolling of "right-to-left" content.  This is typically
         * configured automatically by an {@link Ext.Component} based on its inherited
         * {@link Ext.Component#rtl rtl} state
         * @member Ext.scroll.Scroller
         */
        rtl: null
    },

    // Empty updater - workaround for https://sencha.jira.com/browse/EXTJS-14574
    updateRtl: Ext.emptyFn,

    privates: {
        convertX: function(x) {
            var element;

            if (this.getRtl()) {
                element = this.getElement();

                if (element) {
                    x = element.rtlNormalizeScrollLeft(x);
                }
            }

            return x;
        },

        getElementScroll: function(element) {
            return this.getRtl() ? element.rtlGetScroll() : element.getScroll();
        },

        // rtl hook
        translateSpacer: function(x, y) {
            if (this.getRtl()) {
                this.getSpacer().dom.style.right = (x - 1) + 'px';
                this.callParent([null, y]);
            }
            else {
                this.callParent([x, y]);
            }
        }
    }
});
