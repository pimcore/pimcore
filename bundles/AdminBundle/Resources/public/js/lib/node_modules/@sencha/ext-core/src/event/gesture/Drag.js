/**
 *
 */
Ext.define('Ext.event.gesture.Drag', {
    extend: 'Ext.event.gesture.SingleTouch',

    priority: 100,

    startPoint: null,

    previousPoint: null,

    lastPoint: null,

    handledEvents: ['dragstart', 'drag', 'dragend', 'dragcancel'],

    config: {
        /**
         * @cfg {Number} minDistance
         * The minimum distance of pixels before a touch event becomes a drag event.
         */
        minDistance: 8
    },

    constructor: function() {
        this.callParent(arguments);

        this.initInfo();
    },

    initInfo: function() {
        this.info = {
            touch: null,
            previous: {
                x: 0,
                y: 0
            },
            x: 0,
            y: 0,
            delta: {
                x: 0,
                y: 0
            },
            absDelta: {
                x: 0,
                y: 0
            },
            flick: {
                velocity: {
                    x: 0,
                    y: 0
                }
            },
            direction: {
                x: 0,
                y: 0
            },
            time: 0,
            previousTime: {
                x: 0,
                y: 0
            },
            longpress: false
        };
    },

    onTouchStart: function(e) {
        var me = this,
            ret = me.callParent([e]);

        if (ret !== false) {
            me.startTime = e.time;
            me.startPoint = e.changedTouches[0].point;
        }

        return ret;
    },

    tryDragStart: function(e) {
        var me = this,
            point = e.changedTouches[0].point,
            minDistance = me.getMinDistance(),
            scale = Ext.Element.getViewportScale(),
            // account for scale so that move distance is actual screen pixels, not page pixels
            distance = Math.round(Math.abs(point.getDistanceTo(me.startPoint) * scale));

        if (distance >= minDistance) {
            me.doDragStart(e);
        }
    },

    doDragStart: function(e, isLongPress) {
        var me = this,
            touch = e.changedTouches[0],
            point = touch.point,
            info = me.info,
            time;

        if (isLongPress) {
            time = Ext.now();
            me.startTime = time;
            me.startPoint = point;
            info.longpress = true;
        }
        else {
            time = e.time;
        }

        me.isStarted = true;

        me.previousPoint = me.lastPoint = point;

        me.resetInfo('x', e, touch);
        me.resetInfo('y', e, touch);

        info.time = time;

        me.fire('dragstart', e, info);
    },

    onTouchMove: function(e) {
        var me = this,
            touch, point;

        if (!me.startPoint) {
            return;
        }

        if (!me.isStarted) {
            me.tryDragStart(e);
        }

        if (!me.isStarted) {
            return;
        }

        touch = e.changedTouches[0];
        point = touch.point;

        if (me.lastPoint) {
            me.previousPoint = me.lastPoint;
        }

        me.lastPoint = point;
        me.lastMoveEvent = e;

        me.updateInfo('x', e, touch);
        me.updateInfo('y', e, touch);

        me.info.time = e.time;

        me.fire('drag', e, me.info);
    },

    onAxisDragEnd: function(axis, info) {
        var duration = info.time - info.previousTime[axis];

        if (duration > 0) {
            info.flick.velocity[axis] = (info[axis] - info.previous[axis]) / duration;
        }
    },

    resetInfo: function(axis, e, touch) {
        var me = this,
            value = me.lastPoint[axis],
            startValue = me.startPoint[axis],
            delta = value - startValue,
            capAxis = axis.toUpperCase(),
            info = me.info;

        info.touch = touch;

        info.delta[axis] = delta;
        info.absDelta[axis] = Math.abs(delta);

        info.previousTime[axis] = me.startTime;
        info.previous[axis] = startValue;
        info[axis] = value;
        info.direction[axis] = 0;

        info['start' + capAxis] = me.startPoint[axis];
        info['previous' + capAxis] = info.previous[axis];
        info['page' + capAxis] = info[axis];
        info['delta' + capAxis] = info.delta[axis];
        info['absDelta' + capAxis] = info.absDelta[axis];
        info['previousDelta' + capAxis] = 0;
        info.startTime = me.startTime;
    },

    updateInfo: function(axis, e, touch) {
        var me = this,
            value = me.lastPoint[axis],
            previousValue = me.previousPoint[axis],
            startValue = me.startPoint[axis],
            delta = value - startValue,
            info = me.info,
            direction = info.direction,
            capAxis = axis.toUpperCase(),
            previousFlick = info.previous[axis];

        info.touch = touch;
        info.delta[axis] = delta;
        info.absDelta[axis] = Math.abs(delta);

        if (value !== previousFlick && value !== info[axis]) {
            info.previous[axis] = info[axis];
            info.previousTime[axis] = info.time;
        }

        info[axis] = value;

        if (value > previousValue) {
            direction[axis] = 1;
        }
        else if (value < previousValue) {
            direction[axis] = -1;
        }

        info['start' + capAxis] = startValue;
        info['previous' + capAxis] = info.previous[axis];
        info['page' + capAxis] = info[axis];
        info['delta' + capAxis] = info.delta[axis];
        info['absDelta' + capAxis] = info.absDelta[axis];
        info['previousDelta' + capAxis] = info.previous[axis] - startValue;
        info.startTime = me.startTime;
    },

    onTouchEnd: function(e) {
        var me = this,
            touch, point, info;

        if (me.isStarted) {
            touch = e.changedTouches[0];
            point = touch.point;
            info = me.info;

            me.lastPoint = point;

            me.updateInfo('x', e, touch);
            me.updateInfo('y', e, touch);

            info.time = e.time;

            me.onAxisDragEnd('x', info);
            me.onAxisDragEnd('y', info);

            me.fire('dragend', e, info);
        }

        return this.callParent([e]);
    },

    onCancel: function(e) {
        var me = this,
            touch = e.changedTouches[0],
            info = me.info;

        // if "e" is a true cancellation event (touchcancel, pointercancel) e.touches.length
        // will be 0.  If length is anything else we can safely assume that this was called
        // because an additional touch was added (see SingleTouch#onTouchStart).  If that
        // is the case we do not want to update the lastPoint because the coordinates should
        // be those of the last single-touch drag, not the new touch.
        if (!e.touches.length) {
            me.lastPoint = touch.point;
        }

        me.updateInfo('x', e, touch);
        me.updateInfo('y', e, touch);

        info.time = e.time;

        me.fire('dragcancel', e, info, true);
    },

    reset: function() {
        var me = this;

        me.lastPoint = me.startPoint = me.previousPoint = me.lastPoint = me.lastMoveEvent = null;

        me.initInfo();

        return me.callParent();
    }
}, function(Drag) {
    var gestures = Ext.manifest.gestures;

    Drag.instance = new Drag(gestures && gestures.drag);
});
