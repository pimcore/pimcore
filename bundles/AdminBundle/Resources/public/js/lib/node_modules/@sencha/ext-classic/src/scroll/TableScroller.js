Ext.define('Ext.scroll.TableScroller', {
    extend: 'Ext.scroll.Scroller',
    alias: 'scroller.table',

    config: {
        lockingScroller: null
    },

    privates: {
        getEnsureVisibleXY: function(el, options) {
            var lockingScroller = this.getLockingScroller(),
                position = this.getPosition(),
                newPosition;

            if (el && el.element && !el.isElement) {
                options = el;
                el = options.element;
            }

            options = options || {};

            if (lockingScroller) {
                position.y = lockingScroller.position.y;
            }

            newPosition =
                Ext.fly(el).getScrollIntoViewXY(this.getElement(), position.x, position.y);

            newPosition.x = (options.x === false) ? position.x : newPosition.x;

            if (lockingScroller) {
                newPosition.y = (options.y === false)
                    ? position.y
                    : Ext.fly(el).getScrollIntoViewXY(
                        lockingScroller.getElement(), position.x, position.y
                    ).y;
            }

            return newPosition;
        },

        doScrollTo: function(x, y, animate) {
            var lockingScroller,
                lockedPromise,
                ret;

            if (y != null) {
                lockingScroller = this.getLockingScroller();

                if (lockingScroller) {
                    lockedPromise = lockingScroller.doScrollTo(null, y, animate);
                    y = null;
                }
            }

            ret = this.callParent([x, y, animate]);

            if (lockedPromise) {
                ret = Ext.Promise.all([ret, lockedPromise]);
            }

            return ret;
        },

        restoreState: function() {
            var me = this,
                el = me.getScrollElement(),
                lockingScroller = me.getLockingScroller(),
                trackingScrollTop;

            if (el) {
                // scrollTop is managed by the LockingScroller if there is one.
                trackingScrollTop = lockingScroller
                    ? lockingScroller.trackingScrollTop
                    : me.trackingScrollTop;

                // Only restore state if has been previously captured! For example,
                // floaters probably have not been hidden before initially shown.
                if (trackingScrollTop !== undefined) {
                    // If we're restoring the scroll position, we don't want to publish
                    // scroll events since the scroll position should not have changed
                    // at all as far as the user is concerned, so just do it silently
                    // while ensuring we maintain the correct internal state. 50ms is
                    // enough to capture the async scroll events, anything after that
                    // we re-enable.
                    if (!me.restoreTimer) {
                        me.restoreTimer = Ext.defer(function() {
                            me.restoreTimer = null;
                        }, 50);
                    }

                    me.doScrollTo(me.trackingScrollLeft, trackingScrollTop, false);

                    // Do not discard the state.
                    // It may need to be restored again.
                }
            }
        }
    }
});
