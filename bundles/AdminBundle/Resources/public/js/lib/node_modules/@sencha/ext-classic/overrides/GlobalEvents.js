// @tag core

/**
 * @class Ext.GlobalEvents
 */
Ext.define('Ext.overrides.GlobalEvents', {
    override: 'Ext.GlobalEvents',

    /**
     * @event resumelayouts
     * Fires after global layout processing has been resumed in {@link
     * Ext.Component#resumeLayouts}.
     */

    attachListeners: function() {
        var me = this,
            docElement, bufferedFn;

        // In IE9- when using legacy onresize event via attachEvent or onresize property,
        // the event may fire for *content size changes* as well as actual document view
        // size changes. See this: https://msdn.microsoft.com/en-us/library/ms536959(v=vs.85).aspx
        // and this: http://stackoverflow.com/questions/1852751/window-resize-event-firing-in-internet-explorer
        // The amount of these events firing all at once can be entirely staggering, and they
        // often happen during layouts so we have to be über careful to execute as few JavaScript
        // statements as possible to improve overall framework performance.
        if (Ext.isIE8) {
            docElement = Ext.getDoc().dom.documentElement;
            bufferedFn = Ext.Function.createBuffered(me.fireResize, me.resizeBuffer, me);

            Ext.getWin().dom.attachEvent('onresize', function() {
                if (docElement.clientWidth !== Ext.GlobalEvents.curWidth ||
                    docElement.clientHeight !== Ext.GlobalEvents.curHeight) {
                    bufferedFn();
                }
            });
        }

        me.callParent();
    },

    deprecated: {
        5: {
            methods: {
                addListener: function(ename, fn, scope, options, order, caller, eventOptions) {
                    var name,
                        readyFn;

                    // The "ready" event was removed from Ext.globalEvents in 5.0 in favor of
                    // Ext.onReady().  This function adds compatibility for the ready event

                    if (ename === 'ready') {
                        readyFn = fn;
                    }
                    else if (typeof ename !== 'string') {
                        for (name in ename) {
                            if (name === 'ready') {
                                readyFn = ename[name];
                            }
                        }
                    }

                    if (readyFn) {
                        //<debug>
                        Ext.log.warn("Ext.on('ready', fn) is deprecated.  " +
                                     "Please use Ext.onReady(fn) instead.");
                        //</debug>

                        Ext.onReady(readyFn);
                    }

                    this.callParent([ename, fn, scope, options, order, caller, eventOptions]);
                }
            }
        }
    }
});
