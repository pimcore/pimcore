/**
 * A simple event recogniser which knows when you tap.
 */
Ext.define('Ext.event.gesture.Tap', {
    extend: 'Ext.event.gesture.SingleTouch',

    priority: 200,

    handledEvents: ['tap', 'tapcancel'],

    config: {
        /**
         * @cfg {Number} moveDistance
         * The maximimum distance in pixels a touchstart event can travel and still be considered
         * a tap event.
         */

        moveDistance: 8
    },

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]);

        if (ret !== false) {
            me.isStarted = true;
            me.startPoint = e.changedTouches[0].point;
        }

        return ret;
    },

    onTouchMove: function(e) {
        var me = this,
            point = e.changedTouches[0].point,
            scale = Ext.Element.getViewportScale(),
            // account for scale so that move distance is actual screen pixels, not page pixels
            distance = Math.round(Math.abs(point.getDistanceTo(me.startPoint) * scale));

        if (distance >= me.getMoveDistance()) {
            return me.cancel(e);
        }
    },

    onTouchEnd: function(e) {
        this.fire('tap', e, {
            touch: e.changedTouches[0]
        });

        return this.callParent([e]);
    },

    onCancel: function(e) {
        this.fire('tapcancel', e, {
            touch: e.changedTouches[0]
        }, true);
    },

    reset: function() {
        this.startPoint = null;

        return this.callParent();
    }
}, function(Tap) {
    var gestures = Ext.manifest.gestures;

    Tap.instance = new Tap(gestures && gestures.tap);
});
