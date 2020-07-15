/**
 * @class Ext.Function
 *
 * A collection of useful static methods to deal with function callbacks.
 * @singleton
 */

/* eslint-disable indent */
Ext.Function = (function() {
// @define Ext.lang.Function
// @define Ext.Function
// @require Ext
// @require Ext.lang.Array
    var lastTime = 0,
        animFrameId,
        animFrameHandlers = [],
        animFrameNoArgs = [],
        idSource = 0,
        animFrameMap = {},
        slice = Array.prototype.slice,
        win = window,
        global = Ext.global,
        // We disable setImmediate in unit tests because it derails internal Jasmine queue
        hasImmediate = !Ext.disableImmediate && !!(global.setImmediate && global.clearImmediate),
        requestAnimFrame = win.requestAnimationFrame || win.webkitRequestAnimationFrame ||
            win.mozRequestAnimationFrame || win.oRequestAnimationFrame ||
            function(callback) {
                var currTime = Ext.now(),
                    timeToCall = Math.max(0, 16 - (currTime - lastTime)),
                    timerFn = function() {
                        callback(currTime + timeToCall);
                    },
                    id;

                //<debug>
                timerFn.$origFn = callback.$origFn || callback;
                timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;
                //</debug>

                id = win.setTimeout(timerFn, timeToCall);

                lastTime = currTime + timeToCall;

                return id;
            },
        fireHandlers = function() {
            var len = animFrameHandlers.length,
                id, i, handler;

            animFrameId = null;

            //<debug>
            var timer; // eslint-disable-line vars-on-top
            //</debug>

            // Fire all animation frame handlers in one go
            for (i = 0; i < len; i++) {
                handler = animFrameHandlers[i];
                id = handler[3];

                // Check if this timer has been canceled; its map entry is going to be removed
                if (animFrameMap[id]) {
                    delete animFrameMap[id];

                    //<debug>
                    timer = Ext.Timer.get(id, 'raf');

                    if (timer) {
                        timer.tick();
                    }
                    //</debug>

                    handler[0].apply(handler[1] || global, handler[2] || animFrameNoArgs);

                    //<debug>
                    if (timer) {
                        timer.tock();
                    }
                    //</debug>
                }
            }

            // Clear all fired animation frame handlers, don't forget that new handlers
            // could have been created in user handler functions called in the loop above
            animFrameHandlers = animFrameHandlers.slice(len);
        },
        fireElevatedHandlers = function() {
            Ext.elevate(fireHandlers);
        },

    ExtFunction = {
        /**
         * A very commonly used method throughout the framework. It acts as a wrapper around
         * another method which originally accepts 2 arguments for `name` and `value`.
         * The wrapped function then allows "flexible" value setting of either:
         *
         * - `name` and `value` as 2 arguments
         * - one single object argument with multiple key - value pairs
         *
         * For example:
         *
         *     var setValue = Ext.Function.flexSetter(function(name, value) {
         *         this[name] = value;
         *     });
         *
         *     // Afterwards
         *     // Setting a single name - value
         *     setValue('name1', 'value1');
         *
         *     // Settings multiple name - value pairs
         *     setValue({
         *         name1: 'value1',
         *         name2: 'value2',
         *         name3: 'value3'
         *     });
         *
         * @param {Function} setter The single value setter method.
         * @param {String} setter.name The name of the value being set.
         * @param {Object} setter.value The value being set.
         * @return {Function}
         */
        flexSetter: function(setter) {
            return function(name, value) {
                var k, i;

                if (name !== null) {
                    if (typeof name !== 'string') {
                        for (k in name) {
                            if (name.hasOwnProperty(k)) {
                                setter.call(this, k, name[k]);
                            }
                        }

                        if (Ext.enumerables) {
                            for (i = Ext.enumerables.length; i--;) {
                                k = Ext.enumerables[i];

                                if (name.hasOwnProperty(k)) {
                                    setter.call(this, k, name[k]);
                                }
                            }
                        }
                    }
                    else {
                        setter.call(this, name, value);
                    }
                }

                return this;
            };
        },

        /**
         * Create a new function from the provided `fn`, change `this` to the provided scope,
         * optionally overrides arguments for the call. Defaults to the arguments passed by
         * the caller.
         *
         * {@link Ext#bind Ext.bind} is alias for {@link Ext.Function#bind Ext.Function.bind}
         * 
         * **NOTE:** This method is similar to the native `bind()` method. The major difference
         * is in the way the parameters are passed. This method expects an array of parameters,
         * and if supplied, it does not automatically pass forward parameters from the bound
         * function:
         * 
         *      function foo (a, b, c) {
         *          console.log(a, b, c);
         *      }
         *      
         *      var nativeFn = foo.bind(this, 1, 2);
         *      var extFn = Ext.Function.bind(foo, this, [1, 2]);
         *
         *      nativeFn(3); // 1, 2, 3
         *      extFn(3); // 1, 2, undefined
         *
         * This method is unavailable natively on IE8 and IE/Quirks but Ext JS provides a
         * "polyfill" to emulate the important features of the standard `bind` method. In
         * particular, the polyfill only provides binding of "this" and optional arguments.
         * 
         * @param {Function} fn The function to delegate.
         * @param {Object} [scope] The scope (`this` reference) in which the function
         * is executed.
         * **If omitted, defaults to the global environment object (usually the browser `window`).**
         * @param {Array} [args] Overrides arguments for the call. (Defaults to
         * the arguments passed by the caller).
         * @param {Boolean/Number} [appendArgs] if `true` the `args` are appended to the
         * arguments passed to the returned wrapper (by default these arguments are ignored).
         * If a number then the `args` are inserted at the specified position.
         * @return {Function} The bound wrapper function.
         */
        bind: function(fn, scope, args, appendArgs) {
            // Function.prototype.bind is polyfilled in IE8, otherwise native
            if (arguments.length <= 2) {
                return fn.bind(scope);
            }

            var method = fn; // eslint-disable-line vars-on-top

            return function() {
                var callArgs = args || arguments;

                if (appendArgs === true) {
                    callArgs = slice.call(arguments, 0);
                    callArgs = callArgs.concat(args);
                }
                else if (typeof appendArgs === 'number') {
                    callArgs = slice.call(arguments, 0); // copy arguments first
                    Ext.Array.insert(callArgs, appendArgs, args);
                }

                return method.apply(scope || global, callArgs);
            };
        },

        /**
         * Captures the given parameters for a later call to `Ext.callback`. This binding is
         * most useful for resolving scopes for example to an `Ext.app.ViewController`.
         *
         * The arguments match that of `Ext.callback` except for the `args` which, if provided
         * to this method, are prepended to any arguments supplied by the eventual caller of
         * the returned function.
         *
         * @return {Function} A function that, when called, uses `Ext.callback` to call the
         * captured `callback`.
         * @since 5.0.0
         */
        bindCallback: function(callback, scope, args, delay, caller) {
            return function() {
                var a = slice.call(arguments);

                return Ext.callback(callback, scope, args ? args.concat(a) : a, delay, caller);
            };
        },

        /**
         * Create a new function from the provided `fn`, the arguments of which are pre-set
         * to `args`. New arguments passed to the newly created callback when it's invoked
         * are appended after the pre-set ones.
         * This is especially useful when creating callbacks.
         *
         * For example:
         *
         *     var originalFunction = function(){
         *         alert(Ext.Array.from(arguments).join(' '));
         *     };
         *
         *     var callback = Ext.Function.pass(originalFunction, ['Hello', 'World']);
         *
         *     callback(); // alerts 'Hello World'
         *     callback('by Me'); // alerts 'Hello World by Me'
         *
         * {@link Ext#pass Ext.pass} is alias for {@link Ext.Function#pass Ext.Function.pass}
         *
         * @param {Function} fn The original function.
         * @param {Array} args The arguments to pass to new callback.
         * @param {Object} scope (optional) The scope (`this` reference) in which the function
         * is executed.
         * @return {Function} The new callback function.
         */
        pass: function(fn, args, scope) {
            if (!Ext.isArray(args)) {
                if (Ext.isIterable(args)) {
                    args = Ext.Array.clone(args);
                }
                else {
                    args = args !== undefined ? [args] : [];
                }
            }

            return function() {
                var fnArgs = args.slice();

                fnArgs.push.apply(fnArgs, arguments);

                return fn.apply(scope || this, fnArgs);
            };
        },

        /**
         * Create an alias to the provided method property with name `methodName` of `object`.
         * Note that the execution scope will still be bound to the provided `object` itself.
         *
         * @param {Object/Function} object
         * @param {String} methodName
         * @return {Function} aliasFn
         */
        alias: function(object, methodName) {
            return function() {
                return object[methodName].apply(object, arguments);
            };
        },

        /**
         * Create a "clone" of the provided method. The returned method will call the given
         * method passing along all arguments and the "this" pointer and return its result.
         *
         * @param {Function} method
         * @return {Function} cloneFn
         */
        clone: function(method) {
            var newMethod, prop;

            newMethod = function() {
                return method.apply(this, arguments);
            };

            for (prop in method) {
                if (method.hasOwnProperty(prop)) {
                    newMethod[prop] = method[prop];
                }
            }

            return newMethod;
        },

        /**
         * Creates an interceptor function. The passed function is called before the original one.
         * If it returns false, the original one is not called. The resulting function returns
         * the results of the original function. The passed function is called with the parameters
         * of the original function. Example usage:
         *
         *     var sayHi = function(name){
         *         alert('Hi, ' + name);
         *     };
         *
         *     sayHi('Fred'); // alerts "Hi, Fred"
         *
         *     // create a new function that validates input without
         *     // directly modifying the original function:
         *     var sayHiToFriend = Ext.Function.createInterceptor(sayHi, function(name){
         *         return name === 'Brian';
         *     });
         *
         *     sayHiToFriend('Fred');  // no alert
         *     sayHiToFriend('Brian'); // alerts "Hi, Brian"
         *
         * @param {Function} origFn The original function.
         * @param {Function} newFn The function to call before the original.
         * @param {Object} [scope] The scope (`this` reference) in which the passed function
         * is executed. **If omitted, defaults to the scope in which the original function
         * is called or the browser window.**
         * @param {Object} [returnValue=null] The value to return if the passed function return
         * `false`.
         * @return {Function} The new function.
         */
        createInterceptor: function(origFn, newFn, scope, returnValue) {
            if (!Ext.isFunction(newFn)) {
                return origFn;
            }
            else {
                returnValue = Ext.isDefined(returnValue) ? returnValue : null;

                return function() {
                    var me = this,
                        args = arguments;

                    return (newFn.apply(scope || me || global, args) !== false)
                        ? origFn.apply(me || global, args)
                        : returnValue;
                };
            }
        },

        /**
         * Creates a delegate (callback) which, when called, executes after a specific delay.
         *
         * @param {Function} fn The function which will be called on a delay when the returned
         * function is called. Optionally, a replacement (or additional) argument list
         * may be specified.
         * @param {Number} delay The number of milliseconds to defer execution by whenever called.
         * @param {Object} scope (optional) The scope (`this` reference) used by the function
         * at execution time.
         * @param {Array} args (optional) Override arguments for the call.
         * (Defaults to the arguments passed by the caller)
         * @param {Boolean/Number} appendArgs (optional) if True args are appended to call args
         * instead of overriding, if a number the args are inserted at the specified position.
         * @return {Function} A function which, when called, executes the original function
         * after the specified delay.
         */
        createDelayed: function(fn, delay, scope, args, appendArgs) {
            var boundFn = fn;

            if (scope || args) {
                boundFn = Ext.Function.bind(fn, scope, args, appendArgs);
            }

            return function() {
                var me = this,
                    args = slice.call(arguments),
                    timerFn, timerId;

                //<debug>
                var timer; // eslint-disable-line vars-on-top, one-var
                //</debug>

                timerFn = function() {
                    Ext.elevate(boundFn, me, args
                                //<debug>
                                , timer // eslint-disable-line comma-style
                                //</debug>
                    );
                };

                timerId = setTimeout(timerFn, delay);

                //<debug>
                timerFn.$origFn = fn.$origFn || fn;
                timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

                timer = Ext.Timer.created('timeout', timerId, {
                    type: 'createDelayed',
                    fn: fn,
                    timerFn: timerFn
                });
                //</debug>
            };
        },

        /**
         * Calls function `fn` after the number of milliseconds specified, optionally with
         * a specific `scope` (`this` pointer).
         *
         * Example usage:
         *
         *     var sayHi = function(name) {
         *         alert('Hi, ' + name);
         *     }
         *
         *     // executes immediately:
         *     sayHi('Fred');
         *
         *     // executes after 2 seconds:
         *     Ext.defer(sayHi, 2000, this, ['Fred']);
         *
         * The following syntax is useful for scheduling anonymous functions:
         *
         *     Ext.defer(function() {
         *         alert('Anonymous');
         *     }, 100);
         *
         * NOTE: The `Ext.Function.defer()` method is an alias for `Ext.defer()`.
         *
         * @param {Function} fn The function to defer.
         * @param {Number} millis The number of milliseconds for the `setTimeout` call
         * (if less than or equal to 0 the function is executed immediately).
         * @param {Object} scope (optional) The scope (`this` reference) in which the function
         * is executed. **If omitted, defaults to the browser window.**
         * @param {Array} [args] Overrides arguments for the call. Defaults to the arguments passed
         * by the caller.
         * @param {Boolean/Number} [appendArgs=false] If `true` args are appended to call args
         * instead of overriding, or, if a number, then the args are inserted at the specified
         * position.
         * @return {Number} The timeout id that can be used with `Ext.undefer`.
         */
        defer: function(fn, millis, scope, args, appendArgs) {
            var timerId = 0,
                timerFn, boundFn;

            //<debug>
            var timer; // eslint-disable-line vars-on-top, one-var
            //</debug>

            if (!scope && !args && !appendArgs) {
                boundFn = fn;
            }
            else {
                boundFn = Ext.Function.bind(fn, scope, args, appendArgs);
            }

            if (millis > 0) {
                timerFn = function() {
                    Ext.elevate(boundFn
                                //<debug>
                                , null, null, timer // eslint-disable-line comma-style
                                //</debug>
                    );
                };

                timerId = setTimeout(timerFn, millis);

                //<debug>
                timerFn.$origFn = fn.$origFn || fn;
                timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

                timer = Ext.Timer.created('timeout', timerId, {
                    type: 'defer',
                    fn: fn,
                    timerFn: timerFn
                });
                //</debug>
            }
            else {
                boundFn();
            }

            return timerId;
        },

        /**
         * Calls the function `fn` repeatedly at a given interval, optionally with a
         * specific `scope` (`this` pointer).
         *
         *     var sayHi = function(name) {
         *         console.log('Hi, ' + name);
         *     }
         *
         *     // executes every 2 seconds:
         *     var timerId = Ext.interval(sayHi, 2000, this, ['Fred']);
         *
         * The timer is stopped by:
         *
         *     Ext.uninterval(timerId);
         *
         * NOTE: The `Ext.Function.interval()` method is an alias for `Ext.interval()`.
         *
         * @param {Function} fn The function to defer.
         * @param {Number} millis The number of milliseconds for the `setInterval` call
         * @param {Object} scope (optional) The scope (`this` reference) in which the function
         * is executed. **If omitted, defaults to the browser window.**
         * @param {Array} [args] Overrides arguments for the call. Defaults to the arguments
         * passed by the caller.
         * @param {Boolean/Number} [appendArgs=false] If `true` args are appended to call args
         * instead of overriding, or, if a number, then the args are inserted at the specified
         * position.
         * @return {Number} The interval id that can be used with `Ext.uninterval`.
         */
        interval: function(fn, millis, scope, args, appendArgs) {
            var timerFn, timerId, boundFn;

            //<debug>
            var timer; // eslint-disable-line vars-on-top, one-var
            //</debug>

            boundFn = Ext.Function.bind(fn, scope, args, appendArgs);

            timerFn = function() {
                Ext.elevate(boundFn
                            //<debug>
                            , null, null, timer // eslint-disable-line comma-style
                            //</debug>
                );
            };

            timerId = setInterval(timerFn, millis);

            //<debug>
            timerFn.$origFn = boundFn.$origFn || fn;
            timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

            timer = Ext.Timer.created('interval', timerId, {
                type: 'interval',
                fn: fn,
                timerFn: timerFn
            });
            //</debug>

            return timerId;
        },

        /**
         * Create a combined function call sequence of the original function + the passed function.
         * The resulting function returns the results of the original function.
         * The passed function is called with the parameters of the original function.
         * Example usage:
         *
         *     var sayHi = function(name){
         *         alert('Hi, ' + name);
         *     };
         *
         *     sayHi('Fred'); // alerts "Hi, Fred"
         *
         *     var sayGoodbye = Ext.Function.createSequence(sayHi, function(name){
         *         alert('Bye, ' + name);
         *     });
         *
         *     sayGoodbye('Fred'); // both alerts show
         *
         * @param {Function} originalFn The original function.
         * @param {Function} newFn The function to sequence.
         * @param {Object} [scope] The scope (`this` reference) in which the passed function
         * is executed. If omitted, defaults to the scope in which the original function is called
         * or the default global environment object (usually the browser window).
         * @return {Function} The new function.
         */
        createSequence: function(originalFn, newFn, scope) {
            if (!newFn) {
                return originalFn;
            }
            else {
                return function() {
                    var result = originalFn.apply(this, arguments);

                    newFn.apply(scope || this, arguments);

                    return result;
                };
            }
        },

        /**
         * Creates a delegate function, optionally with a bound scope which, when called, buffers
         * the execution of the passed function for the configured number of milliseconds.
         * If called again within that period, the impending invocation will be canceled, and the
         * timeout period will begin again.
         *
         * @param {Function} fn The function to invoke on a buffered timer.
         * @param {Number} buffer The number of milliseconds by which to buffer the invocation
         * of the function.
         * @param {Object} [scope] The scope (`this` reference) in which.
         * the passed function is executed. If omitted, defaults to the scope specified
         * by the caller.
         * @param {Array} [args] Override arguments for the call. Defaults to the arguments
         * passed by the caller.
         * @return {Function} A function which invokes the passed function after buffering
         * for the specified time.
         */
        createBuffered: function(fn, buffer, scope, args) {
            var timerId,
                result = function() {
                    var callArgs = args || slice.call(arguments, 0),
                        me = scope || this,
                        timerFn;

                    //<debug>
                    var timer; // eslint-disable-line vars-on-top, one-var
                    //</debug>

                    if (timerId) {
                        Ext.undefer(timerId);
                    }

                    timerFn = function() {
                        Ext.elevate(fn, me, callArgs
                                    //<debug>
                                    , timer // eslint-disable-line comma-style
                                    //</debug>
                        );
                    };

                    result.timer = timerId = setTimeout(timerFn, buffer);

                    //<debug>
                    timerFn.$origFn = fn.$origFn || fn;
                    timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

                    timer = Ext.Timer.created('timeout', timerId, {
                        type: 'createBuffered',
                        fn: fn,
                        timerFn: timerFn
                    });
                    //</debug>
                };

            return result;
        },

        /**
        * Creates a wrapped function that, when invoked, defers execution until the next
        * animation frame
         * @private
         * @param {Function} fn The function to call.
         * @param {Object} [scope] The scope (`this` reference) in which the function is executed.
         * Defaults to the window object.
         * @param {Array} [args] The argument list to pass to the function.
         * @param {Number} [queueStrategy=3] A bit flag that indicates how multiple calls to
         * the returned function within the same animation frame should be handled.
         *
         * - 1: All calls will be queued - FIFO order
         * - 2: Only the first call will be queued
         * - 3: The last call will replace all previous calls
         *
         * @return {Function}
         */
        createAnimationFrame: function(fn, scope, args, queueStrategy) {
            var boundFn, timerId;

            queueStrategy = queueStrategy || 3;

            boundFn = function() {
                var timerFn,
                    callArgs = args || slice.call(arguments, 0);

                scope = scope || this;

                if (queueStrategy === 3 && timerId) {
                    ExtFunction.cancelAnimationFrame(timerId);
                }

                if ((queueStrategy & 1) || !timerId) {
                    timerFn = function() {
                        timerId = boundFn.timerId = null;
                        fn.apply(scope, callArgs);
                    };

                    //<debug>
                    timerFn.$origFn = fn.$origFn || fn;
                    timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;
                    //</debug>

                    timerId = boundFn.timerId = ExtFunction.requestAnimationFrame(timerFn);
                }
            };

            return boundFn;
        },

        /**
         * @private
         * Schedules the passed function to be called on the next animation frame.
         * @param {Function} fn The function to call.
         * @param {Object} [scope] The scope (`this` reference) in which the function is executed.
         * Defaults to the window object.
         * @param {Mixed[]} [args] The argument list to pass to the function.
         *
         * @return {Number} Timer id for the new animation frame to use when canceling it.
         */
        requestAnimationFrame: function(fn, scope, args) {
            var id = ++idSource,  // Ids start at 1
                handler = slice.call(arguments, 0);

            handler[3] = id;
            animFrameMap[id] = 1; // A flag to indicate that the timer exists

            //<debug>
            Ext.Timer.created('raf', id, {
                type: 'raf',
                fn: fn
            });
            //</debug>

            // We might be in fireHandlers at this moment but this new entry will not
            // be executed until the next frame
            animFrameHandlers.push(handler);

            if (!animFrameId) {
                animFrameId = requestAnimFrame(fireElevatedHandlers);
            }

            return id;
        },

        cancelAnimationFrame: function(id) {
            // Don't remove any handlers from animFrameHandlers array, because
            // the might be in use at the moment (when cancelAnimationFrame is called).
            // Just remove the handler id from the map so it will not be executed
            delete animFrameMap[id];

            //<debug>
            Ext.Timer.cancel('raf', id);
            //</debug>
        },

        /**
         * Creates a throttled version of the passed function which, when called repeatedly and
         * rapidly, invokes the passed function only after a certain interval has elapsed since the
         * previous invocation.
         *
         * This is useful for wrapping functions which may be called repeatedly, such as
         * a handler of a mouse move event when the processing is expensive.
         *
         * @param {Function} fn The function to execute at a regular time interval.
         * @param {Number} interval The interval in milliseconds on which the passed function
         * is executed.
         * @param {Object} [scope] The scope (`this` reference) in which
         * the passed function is executed. If omitted, defaults to the scope specified
         * by the caller.
         * @return {Function} A function which invokes the passed function at the specified
         * interval.
         */
        createThrottled: function(fn, interval, scope) {
            var lastCallTime = 0,
                elapsed,
                lastArgs,
                timerId,
                execute = function() {
                    fn.apply(scope, lastArgs);

                    lastCallTime = Ext.now();
                    lastArgs = timerId = null;
                };

            //<debug>
            execute.$origFn = fn.$origFn || fn;
            execute.$skipTimerCheck = execute.$origFn.$skipTimerCheck;
            //</debug>

            return function() {
                // Use scope of last call unless the creator specified a scope
                if (!scope) {
                    scope = this;
                }

                elapsed = Ext.now() - lastCallTime;
                lastArgs = Ext.Array.slice(arguments);

                // If this is the first invocation, or the throttle interval has been reached,
                // clear any pending invocation, and call the target function now.
                if (elapsed >= interval) {
                    Ext.undefer(timerId);
                    execute();
                }
                // Throttle interval has not yet been reached. Only set the timer to fire
                // if not already set.
                else if (!timerId) {
                    timerId = Ext.defer(execute, interval - elapsed);
                }
            };
        },

        /**
         * Wraps the passed function in a barrier function which will call the passed function
         * after the passed number of invocations.
         * @param {Number} count The number of invocations which will result in the calling
         * of the passed function.
         * @param {Function} fn The function to call after the required number of invocations.
         * @param {Object} scope The scope (`this` reference) in which the function will be called.
         */    
        createBarrier: function(count, fn, scope) {
            var barrierFn = function() {
                if (!--count) {
                    fn.apply(scope, arguments);
                }
            };

            //<debug>
            barrierFn.$origFn = fn.$origFn || fn;
            barrierFn.$skipTimerCheck = barrierFn.$origFn.$skipTimerCheck;
            //</debug>

            return barrierFn;
        },

        /**
         * Adds behavior to an existing method that is executed before the
         * original behavior of the function.  For example:
         * 
         *     var soup = {
         *         contents: [],
         *         add: function(ingredient) {
         *             this.contents.push(ingredient);
         *         }
         *     };
         *     Ext.Function.interceptBefore(soup, "add", function(ingredient){
         *         if (!this.contents.length && ingredient !== "water") {
         *             // Always add water to start with
         *             this.contents.push("water");
         *         }
         *     });
         *     soup.add("onions");
         *     soup.add("salt");
         *     soup.contents; // will contain: water, onions, salt
         * 
         * @param {Object} object The target object
         * @param {String} methodName Name of the method to override
         * @param {Function} fn Function with the new behavior.  It will
         * be called with the same arguments as the original method.  The
         * return value of this function will be the return value of the
         * new method.
         * @param {Object} [scope] The scope to execute the interceptor function.
         * Defaults to the object.
         * @return {Function} The new function just created.
         */
        interceptBefore: function(object, methodName, fn, scope) {
            var method = object[methodName] || Ext.emptyFn;

            return (object[methodName] = function() {
                var ret = fn.apply(scope || this, arguments);

                method.apply(this, arguments);

                return ret;
            });
        },

        /**
         * Adds behavior to an existing method that is executed after the
         * original behavior of the function.  For example:
         * 
         *     var soup = {
         *         contents: [],
         *         add: function(ingredient) {
         *             this.contents.push(ingredient);
         *         }
         *     };
         *     Ext.Function.interceptAfter(soup, "add", function(ingredient){
         *         // Always add a bit of extra salt
         *         this.contents.push("salt");
         *     });
         *     soup.add("water");
         *     soup.add("onions");
         *     soup.contents; // will contain: water, salt, onions, salt
         * 
         * @param {Object} object The target object
         * @param {String} methodName Name of the method to override
         * @param {Function} fn Function with the new behavior.  It will
         * be called with the same arguments as the original method.  The
         * return value of this function will be the return value of the
         * new method.
         * @param {Object} [scope] The scope to execute the interceptor function.
         * Defaults to the object.
         * @return {Function} The new function just created.
         */
        interceptAfter: function(object, methodName, fn, scope) {
            var method = object[methodName] || Ext.emptyFn;

            return (object[methodName] = function() {
                method.apply(this, arguments);

                return fn.apply(scope || this, arguments);
            });
        },

        interceptAfterOnce: function(object, methodName, fn, scope) {
            var origMethod = object[methodName],
                newMethod;

            newMethod = function() {
                var ret;

                if (origMethod) {
                    origMethod.apply(this, arguments);
                }

                ret = fn.apply(scope || this, arguments);

                object[methodName] = origMethod;
                object = methodName = fn = scope = origMethod = newMethod = null;

                return ret;
            };

            object[methodName] = newMethod;

            return newMethod;
        },

        makeCallback: function(callback, scope) {
            //<debug>
            if (!scope[callback]) {
                if (scope.$className) {
                    Ext.raise('No method "' + callback + '" on ' + scope.$className);
                }

                Ext.raise('No method "' + callback + '"');
            }
            //</debug>

            return function() {
                return scope[callback].apply(scope, arguments);
            };
        },

        /**
         * Returns a wrapper function that caches the return value for previously
         * processed function argument(s).
         *
         * For example:
         *
         *      function factorial (value) {
         *          var ret = value;
         *
         *          while (--value > 1) {
         *              ret *= value;
         *          }
         *
         *          return ret;
         *      }
         *
         * Each call to `factorial` will loop and multiply to produce the answer. Using
         * this function we can wrap the above and cache its answers:
         *
         *      factorial = Ext.Function.memoize(factorial);
         *
         * The returned function operates in the same manner as before, but results are
         * stored in a cache to avoid calling the wrapped function when given the same
         * arguments.
         *
         *      var x = factorial(20);  // first time; call real factorial()
         *      var y = factorial(20);  // second time; return value from first call
         *
         * To support multi-argument methods, you will need to provide a `hashFn`.
         *
         *      function permutation (n, k) {
         *          return factorial(n) / factorial(n - k);
         *      }
         *
         *      permutation = Ext.Function.memoize(permutation, null, function(n, k) {
         *          n + '-' + k;
         *      });
         *
         * In this case, the `memoize` of `factorial` is sufficient optimization, but the
         * example is simply to illustrate how to generate a unique key for an expensive,
         * multi-argument method.
         *
         * **IMPORTANT**: This cache is unbounded so be cautious of memory leaks if the
         * `memoize`d function is kept indefinitely or is given an unbounded set of
         * possible arguments.
         *
         * @param {Function} fn Function to wrap.
         * @param {Object} scope Optional scope in which to execute the wrapped function.
         * @param {Function} hashFn Optional function used to compute a hash key for
         * storing the result, based on the arguments to the original function.
         * @return {Function} The caching wrapper function.
         * @since 6.0.0
         */
        memoize: function(fn, scope, hashFn) {
            var memo = {},
                isFunc = hashFn && Ext.isFunction(hashFn);

            return function(value) {
                var key = isFunc ? hashFn.apply(scope, arguments) : value;

                if (!(key in memo)) {
                    memo[key] = fn.apply(scope, arguments);
                }

                return memo[key];
            };
        },

        //<debug>
        _stripCommentRe: /(\/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+\/)|(\/\/.*)/g,
        //</debug>

        toCode: function(fn) {
            var s = fn ? fn.toString() : '';

            //<debug>
            s = s.replace(ExtFunction._stripCommentRe, '');
            //</debug>

            return s;
        }

        //<debug>
        // This is useful for unit testing so we can force handlers which have been deferred
        // to the next animation frame to run immediately
        , fireElevatedHandlers: function() { // eslint-disable-line comma-style
            fireElevatedHandlers();
        }
        //</debug>
    }; // ExtFunction

    /**
     * @member Ext
     * @method asap
     * Schedules the specified callback function to be executed on the next turn of the
     * event loop. Where available, this method uses the browser's `setImmediate` API. If
     * not available, this method substitutes `setTimeout(0)`. Though not a perfect
     * replacement for `setImmediate` it is sufficient for many use cases.
     *
     * For more details see [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Window/setImmediate).
     *
     * @param {Function} fn Callback function.
     * @param {Object} [scope] The scope for the callback (`this` pointer).
     * @param {Mixed[]} [parameters] Additional parameters to pass to `fn`.
     * @return {Number} A cancellation id for `{@link Ext#unasap}`.
     */
    Ext.asap = hasImmediate
        ? function(fn, scope, parameters) {
            var boundFn = fn,
                timerFn, timerId;

            //<debug>
            var timer; // eslint-disable-line vars-on-top, one-var
            //</debug>

            if (scope != null || parameters != null) {
                boundFn = ExtFunction.bind(fn, scope, parameters);
            }

            timerFn = function() {
                Ext.elevate(boundFn
                            //<debug>
                            , null, null, timer // eslint-disable-line comma-style
                            //</debug>
                );
            };

            timerId = setImmediate(timerFn);

            //<debug>
            timerFn.$origFn = fn.$origFn || fn;
            timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

            timer = Ext.Timer.created('asap', timerId, {
                type: 'asap',
                fn: fn,
                timerFn: timerFn
            });
            //</debug>

            return timerId;
        }
        : function(fn, scope, parameters) {
            var boundFn = fn,
                timerFn, timerId;

            //<debug>
            var timer; // eslint-disable-line vars-on-top, one-var
            //</debug>

            if (scope != null || parameters != null) {
                boundFn = ExtFunction.bind(fn, scope, parameters);
            }

            timerFn = function() {
                Ext.elevate(boundFn
                            //<debug>
                            , null, null, timer // eslint-disable-line comma-style
                            //</debug>
                );
            };

            timerId = setTimeout(timerFn, 0, true);

            //<debug>
            timerFn.$origFn = fn.$origFn || fn;
            timerFn.$skipTimerCheck = timerFn.$origFn.$skipTimerCheck;

            timer = Ext.Timer.created('timeout', timerId, {
                type: 'asap',
                fn: fn,
                timerFn: timerFn
            });
            //</debug>

            return timerId;
        };

    /**
     * @member Ext
     * @method unasap
     * Cancels a previously scheduled call to `{@link Ext#asap}`.
     *
     *      var timerId = Ext.asap(me.method, me);
     *      ...
     *
     *      if (nevermind) {
     *          Ext.unasap(timerId);
     *      }
     *
     * This method always returns `null` to enable simple cleanup:
     *
     *      timerId = Ext.unasap(timerId);  // safe even if !timerId
     *
     * @param {Number} id The id returned by `{@link Ext#asap}`.
     * @return {Object} Always returns `null`.
     */
    Ext.unasap = hasImmediate
        ? function(id) {
            if (id) {
                clearImmediate(id);
                //<debug>
                Ext.Timer.cancel('asap', id);
                //</debug>
            }

            return null;
        }
        : function(id) {
            return Ext.undefer(id);
        };

    /**
     * @member Ext
     * @method asapCancel
     * Cancels a previously scheduled call to `{@link Ext#asap}`.
     * @param {Number} id The id returned by `{@link Ext#asap}`.
     * @deprecated 6.5.1 Use `Ext.unasap` instead.
     */
    Ext.asapCancel = function(id) {
        return Ext.unasap(id);
    };

    /**
     * @method defer
     * @member Ext
     * @inheritdoc Ext.Function#defer
     */
    Ext.defer = ExtFunction.defer;

    /**
     * @member Ext
     * @method undefer
     * Cancels a previously scheduled call to `{@link Ext#defer}`.
     *
     *      var timerId = Ext.defer(me.method, me);
     *      ...
     *
     *      if (nevermind) {
     *          Ext.undefer(timerId);
     *      }
     *
     * This method always returns `null` to enable simple cleanup:
     *
     *      timerId = Ext.undefer(timerId);  // safe even if !timerId
     *
     * @param {Number} id The id returned by `{@link Ext#defer}`.
     */
    Ext.undefer = function(id) {
        if (id) {
            clearTimeout(id);

            //<debug>
            Ext.Timer.cancel('timeout', id);
            //</debug>
        }

        return null;
    };

    /**
     * @method interval
     * @member Ext
     * @inheritdoc Ext.Function#interval
     */
    Ext.interval = ExtFunction.interval;

    /**
     * @member Ext
     * @method uninterval
     * Cancels a previously scheduled call to `{@link Ext#interval}`.
     *
     *      var timerId = Ext.interval(me.method, me);
     *      ...
     *
     *      if (nevermind) {
     *          Ext.uninterval(timerId);
     *      }
     *
     * This method always returns `null` to enable simple cleanup:
     *
     *      timerId = Ext.uninterval(timerId);  // safe even if !timerId
     *
     * @param {Number} id The id returned by `{@link Ext#interval}`.
     */
    Ext.uninterval = function(id) {
        if (id) {
            clearInterval(id);

            //<debug>
            Ext.Timer.cancel('interval', id);
            //</debug>
        }

        return null;
    };

    /**
     * @method pass
     * @member Ext
     * @inheritdoc Ext.Function#pass
     */
    Ext.pass = ExtFunction.pass;

    /**
     * @method bind
     * @member Ext
     * @inheritdoc Ext.Function#bind
     */
    Ext.bind = ExtFunction.bind;

    Ext.raf = function() {
        return ExtFunction.requestAnimationFrame.apply(ExtFunction, arguments);
    };

    Ext.unraf = function(id) {
        ExtFunction.cancelAnimationFrame(id);
    };

    return ExtFunction;
})();
