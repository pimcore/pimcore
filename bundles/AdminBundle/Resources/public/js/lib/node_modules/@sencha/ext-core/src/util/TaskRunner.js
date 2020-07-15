// @tag core
/**
 * Provides the ability to execute one or more arbitrary tasks in an asynchronous manner.
 *
 * Generally, you can use the singleton {@link Ext.TaskManager}.  Or you can create 
 * separate TaskRunner instances to start and stop unique tasks independent of one 
 * another.
 * 
 * Example usage:
 *
 *     @example
 *     var runner = new Ext.util.TaskRunner(),
 *         clock, updateClock, task;
 *     
 *     clock = Ext.getBody().appendChild({
 *         id: 'clock'
 *     });
 *     
 *     // Start a simple clock task that updates a div once per second
 *     updateClock = function() {
 *         clock.setHtml(Ext.Date.format(new Date(), 'g:i:s A'));
 *     };
 *     
 *     task = runner.start({
 *         run: updateClock,
 *         interval: 1000
 *     });
 *
 * The equivalent using TaskManager:
 *
 *     @example
 *     var clock, updateClock, task;
 *     
 *     clock = Ext.getBody().appendChild({
 *         id: 'clock'
 *     });
 *     
 *     // Start a simple clock task that updates a div once per second
 *     updateClock = function() {
 *         clock.setHtml(Ext.Date.format(new Date(), 'g:i:s A'));
 *     };
 *     
 *     var task = Ext.TaskManager.start({
 *         run: updateClock,
 *         interval: 1000
 *     });
 *
 * To end a running task:
 * 
 *      task.destroy();
 *
 * If a task needs to be started and stopped repeated over time, you can create a
 * {@link Ext.util.TaskRunner.Task Task} instance.
 *
 *     var runner = new Ext.util.TaskRunner(),
 *         task;
 *     
 *     task = runner.newTask({
 *         run: function() {
 *             // useful code
 *         },
 *         interval: 1000
 *     });
 *     
 *     task.start();
 *     
 *     // ...
 *     
 *     task.stop();
 *     
 *     // ...
 *     
 *     task.start();
 *
 * A re-usable, single-run task can be managed similar to the above:
 *
 *     var runner = new Ext.util.TaskRunner(),
 *         task;
 *     
 *     task = runner.newTask({
 *         run: function() {
 *             // useful code
 *         },
 *         interval: 1000,
 *         repeat: 1
 *     });
 *     
 *     task.start();
 *     
 *     // ...
 *     
 *     task.stop();
 *     
 *     // ...
 *     
 *     task.start();
 *
 * See the {@link #start} method for details about how to configure a Task.
 *
 * Also see {@link Ext.util.DelayedTask}.
 * 
 * @constructor
 * @param {Number/Object} [interval=10] The minimum precision in milliseconds supported by 
 * this TaskRunner instance. Alternatively, a config object to apply to the new instance.
 */
Ext.define('Ext.util.TaskRunner', {
// @require Ext.Function

    /**
     * @cfg {Boolean} fireIdleEvent
     * This may be configured `false` to inhibit firing of the {@link
     * Ext.GlobalEvents#idle idle event} after task invocation. By default the tasks
     * run in a given tick determine whether `idle` events fire.
     */
    fireIdleEvent: null,

    /**
     * @cfg {Number} interval
     * How often to run the task in milliseconds. Defaults to every 10ms.
     */
    interval: 10,

    /**
     * @property timerId
     * The id of the current timer.
     * @private
     */
    timerId: null,

    constructor: function(interval) {
        var me = this;

        if (typeof interval === 'number') {
            me.interval = interval;
        }
        else if (interval) {
            Ext.apply(me, interval);
        }

        me.tasks = [];
        me.timerFn = me.onTick.bind(me);
    },

    /**
     * Creates a new {@link Ext.util.TaskRunner.Task Task} instance. These instances can
     * be easily started and stopped.
     * @param {Object} config The config object. For details on the supported properties,
     * see {@link #start}.
     *
     * @return {Ext.util.TaskRunner.Task} 
     * Ext.util.TaskRunner.Task instance, which can be useful for method chaining.
     */
    newTask: function(config) {
        var task = new Ext.util.TaskRunner.Task(config);

        task.manager = this;

        //<debug>
        if (Ext.Timer.track) {
            task.creator = new Error().stack;
        }
        //</debug>

        return task;
    },

    /**
     * Starts a new task.
     *
     * Before each invocation, Ext injects the property `taskRunCount` into the task object
     * so that calculations based on the repeat count can be performed.
     * 
     * The returned task will contain a `destroy` method that can be used to destroy the
     * task and cancel further calls. This is equivalent to the {@link #stop} method.
     *
     * @param {Object} task A config object that supports the following properties:
     * @param {Function} task.run The function to execute each time the task is invoked. The
     * function will be called at each interval and passed the `args` argument if specified,
     * and the current invocation count if not.
     * 
     * If a particular scope (`this` reference) is required, be sure to specify it using
     * the `scope` argument.
     * 
     * @param {Function} task.onError The function to execute in case of unhandled
     * error on task.run.
     *
     * @param {Boolean} task.run.return `false` from this function to terminate the task.
     *
     * @param {Number} task.interval The frequency in milliseconds with which the task
     * should be invoked.
     *
     * @param {Object[]} [task.args] An array of arguments to be passed to the function
     * specified by `run`. If not specified, the current invocation count is passed.
     *
     * @param {Boolean} [task.addCountToArgs=false] True to add the current invocation count as 
     * one of the arguments of args. 
     * Note: This only takes effect when args is specified.
     *
     * @param {Object} [task.scope] The scope (`this` reference) in which to execute the
     * `run` function. Defaults to the task config object.
     *
     * @param {Number} [task.duration] The length of time in milliseconds to invoke the task
     * before stopping automatically (defaults to indefinite).
     *
     * @param {Number} [task.repeat] The number of times to invoke the task before stopping
     * automatically (defaults to indefinite).
     *
     * @param {Number} [task.fireIdleEvent=true] If all tasks in a TaskRunner's execution 
     * sweep are configured with `fireIdleEvent: false`, then the 
     * {@link Ext.GlobalEvents#idle idle event} is not fired when the TaskRunner's execution
     * sweep finishes.
     *
     * @param {Boolean} [task.fireOnStart=false] True to run the task immediately instead of 
     * waiting for the _interval's_ initial pass to call the _run_ function.
     */
    start: function(task) {
        var me = this,
            now = Ext.Date.now();

        if (!task.pending) {
            me.tasks.push(task);
            task.pending = true; // don't allow the task to be added to me.tasks again
        }

        task.stopped = false; // might have been previously stopped...
        task.taskStartTime = now;
        task.taskRunTime = task.fireOnStart !== false ? 0 : task.taskStartTime;
        task.taskRunCount = 0;

        if (!me.firing) {
            if (task.fireOnStart !== false) {
                me.startTimer(0, now);
            }
            else {
                me.startTimer(task.interval, now);
            }
        }

        return task;
    },

    /**
     * Stops an existing running task.
     * @param {Object} task The task to stop.
     * @param {Boolean} andRemove Pass `true` to also remove the task from the queue.
     * @return {Object} The task
     */
    stop: function(task, andRemove) {
        var me = this,
            tasks = me.tasks,
            pendingCount = 0,
            i;

        // NOTE: we don't attempt to remove the task from me.tasks at this point because
        // this could be called from inside a task which would then corrupt the state of
        // the loop in onTick
        if (!task.stopped) {
            task.stopped = true;
            task.pending = false;

            if (task.onStop) {
                task.onStop.call(task.scope || task, task);
            }
        }

        if (andRemove) {
            Ext.Array.remove(tasks, task);
        }

        // If there are now no pending tasks
        // we shhuld stop the timer.
        for (i = 0; !pendingCount && i < tasks.length; i++) {
            if (!tasks[i].stopped) {
                pendingCount++;
            }
        }

        if (!pendingCount) {
            Ext.undefer(me.timerId);
            me.timerId = null;
        }

        return task;
    },

    /**
     * Stops all tasks that are currently running.
     * @param {Boolean} andRemove Pass `true` to also remove the tasks from the queue.
     */
    stopAll: function(andRemove) {
        var me = this;

        // onTick will take care of cleaning up the mess after this point...
        // must use reverse in case a task is removed.
        Ext.each(this.tasks, function(task) {
            me.stop(task, andRemove);
        }, null, true);
    },

    //-------------------------------------------------------------------------

    firing: false,

    nextExpires: 1e99,

    /**
     * @private
     */
    onTick: function() {
        var me = this,
            tasks = me.tasks,
            fireIdleEvent = me.fireIdleEvent, // null by default
            now = Ext.Date.now(),
            nextExpires = 1e99,
            len = tasks.length,
            expires, newTasks, i, task, rt, remove, args;

        //<debug>
        var timer = Ext.Timer.get(me.timerId); // eslint-disable-line vars-on-top, one-var

        if (timer) {
            timer.tasks = [];
        }
        //</debug>

        me.timerId = null;
        me.firing = true; // ensure we don't startTimer during this loop...

        // tasks.length can be > len if start is called during a task.run call... so we
        // first check len to avoid tasks.length reference but eventually we need to also
        // check tasks.length. we avoid repeating use of tasks.length by setting len at
        // that time (to help the next loop)
        for (i = 0; i < len || i < (len = tasks.length); ++i) {
            task = tasks[i];

            if (!(remove = task.stopped)) {
                expires = task.taskRunTime + task.interval;

                if (expires <= now) {
                    rt = 1; // otherwise we have a stale "rt"

                    // If all tasks left specify fireIdleEvent as false, then don't do it
                    if (fireIdleEvent === null && task.fireIdleEvent !== false) {
                        fireIdleEvent = true;
                    }

                    task.taskRunCount++;

                    if (task.args) {
                        args =
                            task.addCountToArgs ? task.args.concat([task.taskRunCount]) : task.args;
                    }
                    else {
                        args = [task.taskRunCount];
                    }

                    // We want the exceptions not to get caught while unit testing
                    //<debug>
                    if (timer) {
                        timer.tasks.push(task);
                    }

                    if (me.disableTryCatch) {
                        rt = task.run.apply(task.scope || task, args);
                    }
                    else {
                    //</debug>
                        try {
                            rt = task.run.apply(task.scope || task, args);
                        }
                        catch (taskError) {
                            try {
                                //<debug>
                                Ext.log({
                                    fn: task.run,
                                    prefix: 'Error while running task',
                                    stack: taskError.stack,
                                    msg: taskError,
                                    level: 'error'
                                });

                                //</debug>
                                if (task.onError) {
                                    rt = task.onError.call(task.scope || task, task, taskError);
                                }
                            }
                            catch (e) {
                                // ignore
                            }
                        }
                    //<debug>
                    }
                    //</debug>

                    task.taskRunTime = now;

                    if (rt === false || task.taskRunCount === task.repeat) {
                        me.stop(task);
                        remove = true;
                    }
                    else {
                        remove = task.stopped; // in case stop was called by run
                        expires = now + task.interval;
                    }
                }

                if (!remove && task.duration && task.duration <= (now - task.taskStartTime)) {
                    me.stop(task);
                    remove = true;
                }
            }

            if (remove) {
                task.pending = false; // allow the task to be added to me.tasks again

                // once we detect that a task needs to be removed, we copy the tasks that
                // will carry forward into newTasks... this way we avoid O(N*N) to remove
                // each task from the tasks array (and ripple the array down) and also the
                // potentially wasted effort of making a new tasks[] even if all tasks are
                // going into the next wave.
                if (!newTasks) {
                    newTasks = tasks.slice(0, i);
                    // we don't set me.tasks here because callbacks can also start tasks,
                    // which get added to me.tasks... so we will visit them in this loop
                    // and account for their expirations in nextExpires...
                }
            }
            else {
                if (newTasks) {
                    newTasks.push(task); // we've cloned the tasks[], so keep this one...
                }

                if (nextExpires > expires) {
                    nextExpires = expires; // track the nearest expiration time
                }
            }
        }

        if (newTasks) {
            // only now can we copy the newTasks to me.tasks since no user callbacks can
            // take place
            me.tasks = newTasks;
        }

        me.firing = false; // we're done, so allow startTimer afterwards

        if (me.tasks.length) {
            // we create a new Date here because all the callbacks could have taken a long
            // time... we want to base the next timeout on the current time (after the
            // callback storm):
            me.startTimer(nextExpires - now, Ext.Date.now());
        }

        // If all tasks fired and had fireIdleEvent=false then our fireIdleEvent var
        // will still be null. This is to allow any task that does not suppress idle
        // to override those that do. The only other reason our var will be null is if
        // no tasks fired. In which case, no need for idle either.
        if (fireIdleEvent === null) {
            fireIdleEvent = false;
        }

        Ext._suppressIdle = !fireIdleEvent;
    },

    /**
    * @private
    */
    startTimer: function(timeout, now) {
        var me = this,
            expires = now + timeout,
            timerId = me.timerId;

        // Check to see if this request is enough in advance of the current timer. If so,
        // we reschedule the timer based on this new expiration.
        if (timerId && me.nextExpires - expires > me.interval) {
            timerId = Ext.undefer(timerId);
        }

        if (!timerId) {
            if (timeout < me.interval) {
                timeout = me.interval;
            }

            me.timerId = Ext.defer(me.timerFn, timeout);
            me.nextExpires = expires;

            //<debug>
            var timer = Ext.Timer.get(me.timerId); // eslint-disable-line vars-on-top

            if (timer) {
                timer.runner = me;
            }
            //</debug>
        }
    }
}, function() {
    var me = this,
        proto = me.prototype;

    /**
     * Destroys this instance, stopping all tasks that are currently running.
     * @method destroy
     */
    proto.destroy = proto.stopAll;

    /**
     * Instances of this class are created by {@link Ext.util.TaskRunner#newTask} method.
     * 
     * For details on config properties, see {@link Ext.util.TaskRunner#start}.
     * @class Ext.util.TaskRunner.Task
     */
    me.Task = new Ext.Class({
        isTask: true,

        /**
         * This flag is set to `true` by {@link #stop}.
         * @private
         */
        stopped: true, // this avoids the odd combination of !stopped && !pending

        fireOnStart: false,

        constructor: function(config) {
            Ext.apply(this, config);
        },

        /**
         * Restarts this task, clearing it duration, expiration and run count.
         * @param {Number} [interval] Optionally reset this task's interval.
         */
        restart: function(interval) {
            if (interval !== undefined) {
                this.interval = interval;
            }

            this.manager.start(this);
        },

        /**
         * Starts this task if it is not already started.
         * @param {Number} [interval] Optionally reset this task's interval.
         */
        start: function(interval) {
            if (this.stopped) {
                this.restart(interval);
            }
        },

        /**
         * Stops this task.
         */
        stop: function(andRemove) {
            this.manager.stop(this, andRemove);
        },

        destroy: function() {
            this.stop(true);
        }
    });

    proto = me.Task.prototype;

    /**
     * Destroys this instance, stopping this task's execution.
     * @method destroy
     * @member Ext.util.TaskRunner.Task
     */
    proto.destroy = proto.stop;
});
