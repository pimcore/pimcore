/**
 * @class Ext.chart.interactions.Abstract
 *
 * Defines a common abstract parent class for all interactions.
 *
 */
Ext.define('Ext.chart.interactions.Abstract', {

    xtype: 'interaction',

    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    config: {

        /**
         * @cfg {Object} gesture
         * Maps gestures that should be used for starting/maintaining/ending the interaction
         * to corresponding class methods.
         * @private
         */
        gestures: {
            tap: 'onGesture'
        },

        /**
         * @cfg {Ext.chart.AbstractChart} chart The chart that the interaction is bound.
         */
        chart: null,

        /**
         * @cfg {Boolean} enabled 'true' if the interaction is enabled.
         */
        enabled: true
    },

    /**
     * Android device is emerging too many events so if we re-render every frame it will
     * take forever to finish a frame.
     * This throttle technique will limit the timespan between two frames.
     */
    throttleGap: 0,

    stopAnimationBeforeSync: false,

    constructor: function(config) {
        var me = this,
            id;

        config = config || {};

        if ('id' in config) {
            id = config.id;
        }
        else if ('id' in me.config) {
            id = me.config.id;
        }
        else {
            id = me.getId();
        }

        me.setId(id);

        me.mixins.observable.constructor.call(me, config);
    },

    updateChart: function(newChart, oldChart) {
        var me = this;

        if (oldChart === newChart) {
            return;
        }

        if (oldChart) {
            oldChart.unregister(me);
            me.removeChartListener(oldChart);
        }

        if (newChart) {
            newChart.register(me);
            me.addChartListener();
        }
    },

    updateEnabled: function(enabled) {
        var me = this,
            chart = me.getChart();

        if (chart) {
            if (enabled) {
                me.addChartListener();
            }
            else {
                me.removeChartListener(chart);
            }
        }
    },

    /**
     * @method
     * @protected
     * Placeholder method.
     */
    onGesture: Ext.emptyFn,

    /**
     * @protected
     * Find and return a single series item corresponding to the given event,
     * or null if no matching item is found.
     * @param {Event} e
     * @return {Object} the item object or null if none found.
     */
    getItemForEvent: function(e) {
        var me = this,
            chart = me.getChart(),
            chartXY = chart.getEventXY(e);

        return chart.getItemForPoint(chartXY[0], chartXY[1]);
    },

    /**
     * Find and return all series items corresponding to the given event.
     * @param {Event} e
     * @return {Array} array of matching item objects
     * @private
     * @deprecated 6.5.2 This method is deprecated
     */
    getItemsForEvent: function(e) {
        var me = this,
            chart = me.getChart(),
            chartXY = chart.getEventXY(e);

        return chart.getItemsForPoint(chartXY[0], chartXY[1]);
    },

    /**
     * @private
     */
    addChartListener: function() {
        var me = this,
            chart = me.getChart(),
            gestures = me.getGestures(),
            gesture;

        if (!me.getEnabled()) {
            return;
        }

        function insertGesture(name, fn) {
            chart.addElementListener(
                name,
                // wrap the handler so it does not fire if the event is locked
                // by another interaction
                me.listeners[name] = function(e) {
                    var locks = me.getLocks(),
                        result;

                    if (me.getEnabled() && (!(name in locks) || locks[name] === me)) {
                        result = (Ext.isFunction(fn) ? fn : me[fn]).apply(this, arguments);

                        if (result === false && e && e.stopPropagation) {
                            e.stopPropagation();
                        }

                        return result;
                    }
                },
                me
            );
        }

        me.listeners = me.listeners || {};

        for (gesture in gestures) {
            insertGesture(gesture, gestures[gesture]);
        }
    },

    removeChartListener: function(chart) {
        var me = this,
            gestures = me.getGestures(),
            gesture;

        function removeGesture(name) {
            var fn = me.listeners[name];

            if (fn) {
                chart.removeElementListener(name, fn);
                delete me.listeners[name];
            }
        }

        if (me.listeners) {
            for (gesture in gestures) {
                removeGesture(gesture);
            }
        }
    },

    lockEvents: function() {
        var me = this,
            locks = me.getLocks(),
            args = Array.prototype.slice.call(arguments),
            i = args.length;

        while (i--) {
            locks[args[i]] = me;
        }
    },

    unlockEvents: function() {
        var locks = this.getLocks(),
            args = Array.prototype.slice.call(arguments),
            i = args.length;

        while (i--) {
            delete locks[args[i]];
        }
    },

    getLocks: function() {
        var chart = this.getChart();

        return chart.lockedEvents || (chart.lockedEvents = {});
    },

    doSync: function() {
        var me = this,
            chart = me.getChart();

        if (me.syncTimer) {
            Ext.undefer(me.syncTimer);
            me.syncTimer = null;
        }

        if (me.stopAnimationBeforeSync) {
            chart.animationSuspendCount++;
        }

        chart.redraw();

        if (me.stopAnimationBeforeSync) {
            chart.animationSuspendCount--;
        }

        me.syncThrottle = Date.now() + me.throttleGap;
    },

    sync: function() {
        var me = this;

        if (me.throttleGap && Ext.frameStartTime < me.syncThrottle) {
            if (me.syncTimer) {
                return;
            }

            me.syncTimer = Ext.defer(function() {
                me.doSync();
            }, me.throttleGap);
        }
        else {
            me.doSync();
        }
    },

    getItemId: function() {
        return this.getId();
    },

    isXType: function(xtype) {
        return xtype === 'interaction';
    },

    destroy: function() {
        var me = this;

        me.setChart(null);
        delete me.listeners;
        me.callParent();
    }

}, function() {
    if (Ext.os.is.Android4) {
        this.prototype.throttleGap = 40;
    }
});
