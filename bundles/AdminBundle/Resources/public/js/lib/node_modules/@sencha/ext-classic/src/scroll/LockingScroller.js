Ext.define('Ext.scroll.LockingScroller', {
    extend: 'Ext.scroll.Scroller',
    alias: 'scroller.locking',

    config: {
        lockedScroller: null,
        normalScroller: null
    },

    scrollTo: function(x, y, animate) {
        var lockedX, lockedPromise, ret;

        if (Ext.isObject(x)) {
            lockedX = x.lockedX;

            if (lockedX) {
                lockedPromise = this.getLockedScroller().scrollTo(lockedX, null, animate);
            }
        }

        ret = this.callParent([x, y, animate]);

        if (lockedPromise) {
            ret = Ext.Promise.all([ret, lockedPromise]);
        }

        return ret;
    },

    updateLockedScroller: function(lockedScroller) {
        lockedScroller.on('scroll', 'onLockedScroll', this);
        lockedScroller.setLockingScroller(this);
    },

    updateNormalScroller: function(normalScroller) {
        normalScroller.on('scroll', 'onNormalScroll', this);
        normalScroller.setLockingScroller(this);
    },

    updateTouchAction: function(touchAction, oldTouchAction) {
        this.callParent([touchAction, oldTouchAction]);

        this.getLockedScroller().setTouchAction(touchAction);
        this.getNormalScroller().setTouchAction(touchAction);
    },

    getPosition: function() {
        var position = this.callParent();

        position.x = this.getNormalScroller().getPosition().x;
        position.lockedX = this.getLockedScroller().getPosition().x;

        return position;
    },

    privates: {
        updateSpacerXY: function(pos) {
            var me = this,
                lockedScroller = me.getLockedScroller(),
                normalScroller = me.getNormalScroller(),
                lockedView = lockedScroller.component,
                normalView = normalScroller.component,
                height;

            height = pos.y +
                ((normalView.headerCt.tooNarrow || lockedView.headerCt.tooNarrow)
                    ? Ext.scrollbar.height()
                    : 0);

            normalView.stretchHeight(height);
            lockedView.stretchHeight(height);
            me.callParent([pos]);
        },

        doScrollTo: function(x, y, animate) {
            var ret,
                normalPromise;

            if (x != null) {
                normalPromise = this.getNormalScroller().scrollTo(x, null, animate);
                x = null;
            }

            ret = this.callParent([x, y, animate]);

            if (normalPromise) {
                ret = Ext.Promise.all([ret, normalPromise]);
            }

            return ret;
        },

        onLockedScroll: function(lockedScroller, x, y) {
            this.position.lockedX = x;
        },

        onNormalScroll: function(normalScroller, x, y) {
            this.position.x = x;
        },

        readPosition: function(position) {
            var me = this;

            position = me.callParent([position]);
            position = position || {};

            // read position should consider the normal view for the x axis
            position.x = me.getNormalScroller().getPosition().x;

            return position;
        }
    }
});
