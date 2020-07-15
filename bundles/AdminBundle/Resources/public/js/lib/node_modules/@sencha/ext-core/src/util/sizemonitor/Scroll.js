/**
 * @private
 */
Ext.define('Ext.util.sizemonitor.Scroll', {

    extend: 'Ext.util.sizemonitor.Abstract',

    getElementConfig: function() {
        return {
            reference: 'detectorsContainer',
            classList: [Ext.baseCSSPrefix + 'size-monitors', 'scroll'],
            children: [
                {
                    reference: 'expandMonitor',
                    className: 'expand'
                },
                {
                    reference: 'shrinkMonitor',
                    className: 'shrink'
                }
            ]
        };
    },

    constructor: function(config) {
        this.onScroll = this.onScroll.bind(this);

        this.callParent(arguments);
    },

    bindListeners: function(bind) {
        var method = bind ? 'addEventListener' : 'removeEventListener';

        this.expandMonitor[method]('scroll', this.onScroll, true);
        this.shrinkMonitor[method]('scroll', this.onScroll, true);
    },

    onScroll: function() {
        if (!this.destroyed) {
            Ext.TaskQueue.requestRead('refresh', this);
        }
    },

    refreshMonitors: function() {
        var expandMonitor = this.expandMonitor,
            shrinkMonitor = this.shrinkMonitor,
            end = 1000000;

        if (expandMonitor && !expandMonitor.destroyed) {
            expandMonitor.scrollLeft = end;
            expandMonitor.scrollTop = end;
        }

        if (shrinkMonitor && !shrinkMonitor.destroyed) {
            shrinkMonitor.scrollLeft = end;
            shrinkMonitor.scrollTop = end;
        }
    },

    destroy: function() {
        // This is a closure so Base destructor won't null it
        this.onScroll = null;

        this.callParent();
    }
});
