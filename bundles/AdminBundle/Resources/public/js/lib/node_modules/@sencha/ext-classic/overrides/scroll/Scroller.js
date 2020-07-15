/**
 * @class Ext.scroll.Scroller
 */
Ext.define(null, {
    override: 'Ext.scroll.Scroller',

    compatibility: Ext.isIE8,

    privates: {
        // Important note: this code had to be copied as a whole
        // because the scrollLeft assignment trickery only works
        // reliably when it is done within the same function context.
        doScrollTo: function(x, y, animate) {
            var me = this,
                element = me.getScrollElement(),
                maxPosition, dom, to, xInf, yInf,
                ret, deferred, callback;

            if (element && !element.destroyed) {
                dom = element.dom;

                xInf = (x === Infinity);
                yInf = (y === Infinity);

                if (xInf || yInf) {
                    maxPosition = me.getMaxPosition();

                    if (xInf) {
                        x = maxPosition.x;
                    }

                    if (yInf) {
                        y = maxPosition.y;
                    }
                }

                if (x !== null) {
                    x = me.convertX(x);
                }

                if (animate) {
                    to = {};

                    if (y != null) {
                        to.scrollTop = y;
                    }

                    if (x != null) {
                        to.scrollLeft = x;
                    }

                    animate = Ext.mergeIf({
                        to: {
                            scrollTop: y,
                            scrollLeft: x
                        }
                    }, animate);

                    deferred = new Ext.Deferred();
                    callback = animate.callback;

                    animate.callback = function() {
                        if (callback) {
                            callback.call(animate.scope || Ext.global, arguments);
                        }

                        // The callback will be called if the element is destroyed
                        if (me.destroyed) {
                            deferred.reject();
                        }
                        else {
                            deferred.resolve();
                        }
                    };

                    element.animate(animate);
                    ret = deferred.promise;
                }
                else {
                    // When we need to assign both scrollTop and scrollLeft,
                    // IE8 might fire scroll event on the first assignment
                    // but not on the second; that behavior is unlike the other
                    // browsers which will wait for the second assignment
                    // to happen before firing the event. This leads to our
                    // scrollstart event firing prematurely, when the scrolling
                    // has not actually finished yet.
                    // To work around that, we ignore the first event and then
                    // force another one by assigning scrollLeft the second time.
                    if ((x != null && x !== 0) && y != null) {
                        me.deferDomScroll = true;
                    }

                    if (y != null) {
                        dom.scrollTop = y;
                    }

                    if (x != null) {
                        dom.scrollLeft = x;
                    }

                    if (me.deferDomScroll) {
                        me.deferDomScroll = false;

                        // Reading the DOM makes sure the second assignment will fire the event.
                        // eslint-disable-next-line no-unused-expressions
                        +dom.scrollLeft;
                        dom.scrollLeft = x;
                        // eslint-disable-next-line no-unused-expressions
                        +dom.scrollTop;
                        dom.scrollTop = y;
                    }

                    ret = Ext.Deferred.getCachedResolved();
                }

                // Our position object will need refreshing before returning.
                me.positionDirty = true;
            }
            else {
                ret = Ext.Deferred.getCachedRejected();
            }

            return ret;
        },

        onDomScroll: function() {
            var me = this;

            if (me.deferDomScroll) {
                return;
            }

            me.callParent();
        }
    }
});
