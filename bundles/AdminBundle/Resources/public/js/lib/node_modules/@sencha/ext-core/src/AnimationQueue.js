/**
 * @private
 */
Ext.define('Ext.AnimationQueue', {
    singleton: true,

    constructor: function() {
        var me = this;

        me.queue = [];
        me.taskQueue = [];
        me.runningQueue = [];
        me.idleQueue = [];
        me.isRunning = false;
        me.isIdle = true;

        me.run = me.run.bind(me);

        // iOS has a nasty bug which causes pending requestAnimationFrame to not release
        // the callback when the WebView is switched back and forth from / to being background
        // process. We use a watchdog timer to workaround this, and restore the pending state
        // correctly if this happens.
        // This timer has to be set as an interval from the very beginning and we have to keep
        // it running for as long as the app lives, setting it later doesn't seem to work.
        // The watchdog timer must be accessible for environments to cancel.
        if (Ext.os.is.iOS) {
            //<debug>
            me.watch.$skipTimerCheck = true;
            //</debug>
            me.watchdogTimer = Ext.interval(me.watch, 500, me);
        }
    },

    /**
     *
     * @param {Function} fn
     * @param {Object} [scope]
     * @param {Object} [args]
     */
    start: function(fn, scope, args) {
        var me = this;

        me.queue.push(arguments);

        if (!me.isRunning) {
            if (me.hasOwnProperty('idleTimer')) {
                Ext.undefer(me.idleTimer);
                delete me.idleTimer;
            }

            if (me.hasOwnProperty('idleQueueTimer')) {
                Ext.undefer(me.idleQueueTimer);
                delete me.idleQueueTimer;
            }

            me.isIdle = false;
            me.isRunning = true;
            //<debug>
            me.startCountTime = Ext.now();
            me.count = 0;
            //</debug>
            me.doStart();
        }
    },

    clear: function() {
        var me = this;

        Ext.undefer(me.idleTimer);
        Ext.undefer(me.idleQueueTimer);
        Ext.unraf(me.animationFrameId);

        me.idleTimer = me.idleQueueTimer = me.animationFrameId = null;

        me.queue.length = me.taskQueue.length = me.runningQueue.length = me.idleQueue.length = 0;
        me.isRunning = false;
        me.isIdle = true;

        //<debug>
        me.startCountTime = Ext.now();
        me.count = 0;
        //</debug>
    },

    watch: function() {
        if (this.isRunning && Ext.now() - this.lastRunTime >= 500) {
            this.run();
        }
    },

    run: function() {
        var me = this,
            queue = me.runningQueue,
            now, item, element, i, ln;

        // When asked to start or iterate, it will now create a new one
        me.animationFrameId = null;

        if (!me.isRunning) {
            return;
        }

        now = Ext.now();
        me.lastRunTime = now;
        me.frameStartTime = now;

        // We are doing cleanup here for any destroyed elements
        // this is temporary until we fix CssTransition to properly
        // inform an element that it is being animated
        // then the element, during destruction, will need to cleanup
        // the animation (see Ext.fx.runner.CssTransition#run)
        i = me.queue.length;

        while (i--) {
            item = me.queue[i];
            element = item[1] && item[1].getElement && item[1].getElement();

            if (element && element.destroyed) {
                me.queue.splice(i, 1);
            }
        }

        queue.push.apply(queue, me.queue); // take a snapshot of the current queue and run it

        for (i = 0, ln = queue.length; i < ln; i++) {
            me.invoke(queue[i]);
        }

        queue.length = 0;

        //<debug>
        /* eslint-disable-next-line vars-on-top */
        var elapse = me.frameStartTime - me.startCountTime,
            count = ++me.count;

        if (elapse >= 200) {
            me.onFpsChanged(count * 1000 / elapse, count, elapse);
            me.startCountTime = me.frameStartTime;
            me.count = 0;
        }
        //</debug>

        if (!me.queue.length) {
            me.stop();
        }

        // Could have been stopped while invoking handlers
        if (me.isRunning) {
            me.doIterate();
        }
    },

    //<debug>
    onFpsChanged: Ext.emptyFn,

    onStop: Ext.emptyFn,
    //</debug>

    doStart: function() {
        if (!this.animationFrameId) {
            this.animationFrameId = Ext.raf(this.run);
        }

        this.lastRunTime = Ext.now();
    },

    doIterate: function() {
        if (!this.animationFrameId) {
            this.animationFrameId = Ext.raf(this.run);
        }
    },

    doStop: function() {
        if (this.animationFrameId) {
            Ext.unraf(this.animationFrameId);
        }

        this.animationFrameId = null;
    },

    /**
     *
     * @param {Function} fn
     * @param {Object} [scope]
     * @param {Object} [args]
     */
    stop: function(fn, scope, args) {
        var me = this,
            queue = me.queue,
            ln = queue.length,
            i, item;

        if (!me.isRunning) {
            return;
        }

        for (i = 0; i < ln; i++) {
            item = queue[i];

            if (item[0] === fn && item[1] === scope && item[2] === args) {
                queue.splice(i, 1);
                i--;
                ln--;
            }
        }

        if (ln === 0) {
            me.doStop();
            //<debug>
            me.onStop();
            //</debug>
            me.isRunning = false;

            if (me.idleQueue.length && !me.idleTimer) {
                me.idleTimer = Ext.defer(me.whenIdle, 100, me);
            }
        }
    },

    onIdle: function(fn, scope, args) {
        var me = this,
            listeners = me.idleQueue,
            i, ln, listener;

        for (i = 0, ln = listeners.length; i < ln; i++) {
            listener = listeners[i];

            if (fn === listener[0] && scope === listener[1] && args === listener[2]) {
                return;
            }
        }

        listeners.push(arguments);

        if (me.isIdle) {
            me.processIdleQueue();
        }
        else if (!me.idleTimer) {
            me.idleTimer = Ext.defer(me.whenIdle, 100, me);
        }
    },

    unIdle: function(fn, scope, args) {
        var me = this,
            listeners = me.idleQueue,
            i, ln, listener;

        for (i = 0, ln = listeners.length; i < ln; i++) {
            listener = listeners[i];

            if (fn === listener[0] && scope === listener[1] && args === listener[2]) {
                listeners.splice(i, 1);

                return true;
            }
        }

        if (!listeners.length && me.idleTimer) {
            Ext.undefer(me.idleTimer);
            delete me.idleTimer;
        }

        if (!listeners.length && me.idleQueueTimer) {
            Ext.undefer(me.idleQueueTimer);
            delete me.idleQueueTimer;
        }

        return false;
    },

    queueTask: function(fn, scope, args) {
        this.taskQueue.push(arguments);
        this.processTaskQueue();
    },

    dequeueTask: function(fn, scope, args) {
        var listeners = this.taskQueue,
            i, ln, listener;

        for (i = 0, ln = listeners.length; i < ln; i++) {
            listener = listeners[i];

            if (fn === listener[0] && scope === listener[1] && args === listener[2]) {
                listeners.splice(i, 1);
                i--;
                ln--;
            }
        }
    },

    invoke: function(listener) {
        var fn = listener[0],
            scope = listener[1],
            args = listener[2];

        fn = (typeof fn === 'string' ? scope[fn] : fn);

        if (Ext.isArray(args)) {
            fn.apply(scope, args);
        }
        else {
            fn.call(scope, args);
        }
    },

    whenIdle: function() {
        delete this.idleTimer;
        this.isIdle = true;
        this.processIdleQueue();
    },

    processIdleQueue: function() {
        if (!this.hasOwnProperty('idleQueueTimer')) {
            this.idleQueueTimer = Ext.defer(this.processIdleQueueItem, 1, this);
        }
    },

    processIdleQueueItem: function() {
        var listeners = this.idleQueue,
            listener;

        delete this.idleQueueTimer;

        if (!this.isIdle) {
            return;
        }

        if (listeners.length > 0) {
            listener = listeners.shift();
            this.invoke(listener);
            this.processIdleQueue();
        }
    },

    processTaskQueue: function() {
        if (!this.hasOwnProperty('taskQueueTimer')) {
            this.taskQueueTimer = Ext.defer(this.processTaskQueueItem, 15, this);
        }
    },

    processTaskQueueItem: function() {
        var listeners = this.taskQueue,
            listener;

        delete this.taskQueueTimer;

        if (listeners.length > 0) {
            listener = listeners.shift();
            this.invoke(listener);
            this.processTaskQueue();
        }
    }
    //<debug>
    /* eslint-disable-next-line comma-style */
    ,

    /**
     *
     * @param {Number} fps Frames per second.
     * @param {Number} count Actual number of frames rendered during interval.
     * @param {Number} interval Interval duration.
     */
    showFps: function() {
        var styleTpl = {
            color: 'white',
            'background-color': 'black',
            'text-align': 'center',
            'font-family': 'sans-serif',
            'font-size': '8px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'line-height': '20px',
            '-webkit-font-smoothing': 'antialiased',

            'zIndex': 100000,
            position: 'absolute'
        };

        Ext.getBody().append([
            // --- Average ---
            {
                style: Ext.applyIf({
                    bottom: '50px',
                    left: 0,
                    width: '50px',
                    height: '20px'
                }, styleTpl),
                html: 'Average'
            },
            {
                style: Ext.applyIf({
                    'background-color': 'red',
                    'font-size': '18px',
                    'line-height': '50px',

                    bottom: 0,
                    left: 0,
                    width: '50px',
                    height: '50px'
                }, styleTpl),
                id: '__averageFps',
                html: '0'
            },
            // --- Min ---
            {
                style: Ext.applyIf({
                    bottom: '50px',
                    left: '50px',
                    width: '50px',
                    height: '20px'
                }, styleTpl),
                html: 'Min (Last 1k)'
            },
            {
                style: Ext.applyIf({
                    'background-color': 'orange',
                    'font-size': '18px',
                    'line-height': '50px',

                    bottom: 0,
                    left: '50px',
                    width: '50px',
                    height: '50px'
                }, styleTpl),
                id: '__minFps',
                html: '0'
            },
            // --- Max ---
            {
                style: Ext.applyIf({
                    bottom: '50px',
                    left: '100px',
                    width: '50px',
                    height: '20px'
                }, styleTpl),
                html: 'Max (Last 1k)'
            },
            {
                style: Ext.applyIf({
                    'background-color': 'maroon',
                    'font-size': '18px',
                    'line-height': '50px',

                    bottom: 0,
                    left: '100px',
                    width: '50px',
                    height: '50px'
                }, styleTpl),
                id: '__maxFps',
                html: '0'
            },
            // --- Current ---
            {
                style: Ext.applyIf({
                    bottom: '50px',
                    left: '150px',
                    width: '50px',
                    height: '20px'
                }, styleTpl),
                html: 'Current'
            },
            {
                style: Ext.applyIf({
                    'background-color': 'green',
                    'font-size': '18px',
                    'line-height': '50px',

                    bottom: 0,
                    left: '150px',
                    width: '50px',
                    height: '50px'
                }, styleTpl),
                id: '__currentFps',
                html: '0'
            }
        ]);

        Ext.AnimationQueue.resetFps();
    },

    resetFps: function() {
        var currentFps = Ext.get('__currentFps'),
            averageFps = Ext.get('__averageFps'),
            minFps = Ext.get('__minFps'),
            maxFps = Ext.get('__maxFps'),
            min = 1000,
            max = 0,
            count = 0,
            sum = 0;

        if (!currentFps) {
            return;
        }

        Ext.AnimationQueue.onFpsChanged = function(fps) {
            count++;

            if (!(count % 10)) {
                min = 1000;
                max = 0;
            }

            sum += fps;
            min = Math.min(min, fps);
            max = Math.max(max, fps);
            currentFps.setHtml(Math.round(fps));
            // All-time average since last reset.
            averageFps.setHtml(Math.round(sum / count));
            minFps.setHtml(Math.round(min));
            maxFps.setHtml(Math.round(max));
        };
    }

}, function() {
    /*
        Global FPS indicator. Add ?showfps to use in any application. Note that this REQUIRES
        true requestAnimationFrame to be accurate.
     */
    var paramsString = window.location.search.substr(1),
        paramsArray = paramsString.split("&");

    if (Ext.Array.contains(paramsArray, "showfps")) {
        Ext.onReady(this.showFps.bind(this));
    }
//</debug>
});
