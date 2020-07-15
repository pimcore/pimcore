//
// Ext.Deferred adapted from:
// [DeftJS](https://github.com/deftjs/deftjs5)
// Copyright (c) 2012-2013 [DeftJS Framework Contributors](http://deftjs.org)
// Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
//
// when(), all(), any(), some(), map(), reduce(), delay() and timeout()
// sequence(), parallel(), pipeline()
// methods adapted from: [when.js](https://github.com/cujojs/when)
// Copyright (c) B Cavalier & J Hann
// Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
//

/**
 * Deferreds are the mechanism used to create new Promises. A Deferred has a single
 * associated Promise that can be safely returned to external consumers to ensure they do
 * not interfere with the resolution or rejection of the deferred operation.
 *
 * This implementation of Promises is an extension of the ECMAScript 6 Promises API as
 * detailed [here][1]. For a compatible, though less full featured, API see `{@link Ext.Promise}`.
 *
 * A Deferred is typically used within the body of a function that performs an asynchronous
 * operation. When that operation succeeds, the Deferred should be resolved; if that
 * operation fails, the Deferred should be rejected.
 *
 * Each Deferred has an associated Promise. A Promise delegates `then` calls to its
 * Deferred's `then` method. In this way, access to Deferred operations are divided between
 * producer (Deferred) and consumer (Promise) roles.
 *
 * ## Basic Usage
 *
 * In it's most common form, a method will create and return a Promise like this:
 *
 *      // A method in a service class which uses a Store and returns a Promise
 *      //
 *      loadCompanies: function () {
 *          var deferred = new Ext.Deferred(); // create the Ext.Deferred object
 *
 *          this.companyStore.load({
 *              callback: function (records, operation, success) {
 *                  if (success) {
 *                      // Use "deferred" to drive the promise:
 *                      deferred.resolve(records);
 *                  }
 *                  else {
 *                      // Use "deferred" to drive the promise:
 *                      deferred.reject("Error loading Companies.");
 *                  }
 *              }
 *          });
 *
 *          return deferred.promise;  // return the Promise to the caller
 *      }
 *
 * You can see this method first creates a `{@link Ext.Deferred Deferred}` object. It then
 * returns its `Promise` object for use by the caller. Finally, in the asynchronous
 * callback, it resolves the `deferred` object if the call was successful, and rejects the
 * `deferred` if the call failed.
 *
 * When a Deferred's `resolve` method is called, it fulfills with the optionally specified
 * value. If `resolve` is called with a then-able (i.e.a Function or Object with a `then`
 * function, such as another Promise) it assimilates the then-able's result; the Deferred
 * provides its own `resolve` and `reject` methods as the onFulfilled or onRejected
 * arguments in a call to that then-able's `then` function. If an error is thrown while
 * calling the then-able's `then` function (prior to any call back to the specified
 * `resolve` or `reject` methods), the Deferred rejects with that error. If a Deferred's
 * `resolve` method is called with its own Promise, it rejects with a TypeError.
 *
 * When a Deferred's `reject` method is called, it rejects with the optionally specified
 * reason.
 *
 * Each time a Deferred's `then` method is called, it captures a pair of optional
 * onFulfilled and onRejected callbacks and returns a Promise of the Deferred's future
 * value as transformed by those callbacks.
 *
 * See `{@link Ext.promise.Promise}` for an example of using the returned Promise.
 *
 * @since 6.0.0
 */
Ext.define('Ext.Deferred', function(Deferred) {
/* eslint indent: "off" */
var ExtPromise,
    rejected,
    resolved,
    when; // eslint-disable-line

return {
    extend: 'Ext.promise.Deferred',

    requires: [
        'Ext.Promise'
    ],

    statics: {
        _ready: function() {
            // Our requires are met, so we can cache Ext.promise.Deferred
            ExtPromise = Ext.promise.Promise;
            when = Ext.Promise.resolve;
        },

        /**
         * Returns a new Promise that will only resolve once all the specified
         * `promisesOrValues` have resolved.
         *
         * The resolution value will be an Array containing the resolution value of each
         * of the `promisesOrValues`.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} promisesOrValues An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @return {Ext.promise.Promise} A Promise of an Array of the resolved values.
         * @static
         */
        all: function() {
            return ExtPromise.all.apply(ExtPromise, arguments);
        },

        /**
         * Initiates a competitive race, returning a new Promise that will resolve when
         * any one of the specified `promisesOrValues` have resolved, or will reject when
         * all `promisesOrValues` have rejected or cancelled.
         *
         * The resolution value will the first value of `promisesOrValues` to resolve.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} promisesOrValues An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @return {Ext.promise.Promise} A Promise of the first resolved value.
         * @static
         */
        any: function(promisesOrValues) {
            //<debug>
            if (!(Ext.isArray(promisesOrValues) || ExtPromise.is(promisesOrValues))) {
                Ext.raise('Invalid parameter: expected an Array or Promise of an Array.');
            }
            //</debug>

            return Deferred.some(promisesOrValues, 1).then(function(array) {
                return array[0];
            }, function(error) {
                if (error instanceof Error &&
                    error.message === 'Too few Promises were resolved.') {
                    Ext.raise('No Promises were resolved.');
                }
                else {
                    throw error;
                }
            });
        },

        /**
         * Returns a new Promise that will automatically resolve with the specified
         * Promise or value after the specified delay (in milliseconds).
         *
         * @param {Mixed} promiseOrValue A Promise or value.
         * @param {Number} milliseconds A delay duration (in milliseconds).
         * @return {Ext.promise.Promise} A Promise of the specified Promise or value that
         * will resolve after the specified delay.
         * @static
         */
        delay: function(promiseOrValue, milliseconds) {
            var deferred;

            if (arguments.length === 1) {
                milliseconds = promiseOrValue;
                promiseOrValue = undefined;
            }

            milliseconds = Math.max(milliseconds, 1);

            deferred = new Deferred();

            deferred.timeoutId = Ext.defer(function() {
                delete deferred.timeoutId;
                deferred.resolve(promiseOrValue);
            }, milliseconds);

            return deferred.promise;
        },

        /**
         * Get a shared cached rejected promise. Assumes Promises
         * have been required.
         * @return {Ext.Promise}
         *
         * @private
         * @since 6.5.0
         */
        getCachedRejected: function() {
            if (!rejected) {
                // Prevent Cmd from requiring
                rejected = Ext.Promise.reject();
            }

            return rejected;
        },

        /**
         * Get a shared cached resolved promise. Assumes Promises
         * have been required.
         * @return {Ext.Promise}
         *
         * @private
         * @since 6.5.0
         */
        getCachedResolved: function() {
            if (!resolved) {
                // Prevent Cmd from requiring
                resolved = Ext.Promise.resolve();
            }

            return resolved;
        },

        /**
         * Traditional map function, similar to `Array.prototype.map()`, that allows
         * input to contain promises and/or values.
         *
         * The specified map function may return either a value or a promise.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} promisesOrValues An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @param {Function} mapFn A Function to call to transform each resolved value in
         * the Array.
         * @return {Ext.promise.Promise} A Promise of an Array of the mapped resolved
         * values.
         * @static
         */
        map: function(promisesOrValues, mapFn) {
            //<debug>
            if (!(Ext.isArray(promisesOrValues) || ExtPromise.is(promisesOrValues))) {
                Ext.raise('Invalid parameter: expected an Array or Promise of an Array.');
            }

            if (!Ext.isFunction(mapFn)) {
                Ext.raise('Invalid parameter: expected a function.');
            }
            //</debug>

            return Deferred.resolved(promisesOrValues).then(function(promisesOrValues) {
                var deferred, index, promiseOrValue, remainingToResolve, resolve, results, i, len;

                remainingToResolve = promisesOrValues.length;
                results = new Array(promisesOrValues.length);
                deferred = new Deferred();

                if (!remainingToResolve) {
                    deferred.resolve(results);
                }
                else {
                    resolve = function(item, index) {
                        return Deferred.resolved(item).then(function(value) {
                            return mapFn(value, index, results);
                        })
                        .then(function(value) {
                            results[index] = value;

                            if (!--remainingToResolve) {
                                deferred.resolve(results);
                            }

                            return value;
                        }, function(reason) {
                            return deferred.reject(reason);
                        });
                    };

                    for (index = i = 0, len = promisesOrValues.length; i < len; index = ++i) {
                        promiseOrValue = promisesOrValues[index];

                        if (index in promisesOrValues) {
                            resolve(promiseOrValue, index);
                        }
                        else {
                            remainingToResolve--;
                        }
                    }
                }

                return deferred.promise;
            });
        },

        /**
         * Returns a new function that wraps the specified function and caches the
         * results for previously processed inputs.
         *
         * Similar to {@link Ext.Function#memoize Ext.Function.memoize()}, except it
         * allows for parameters that are Promises and/or values.
         *
         * @param {Function} fn A Function to wrap.
         * @param {Object} scope An optional scope in which to execute the wrapped function.
         * @param {Function} hashFn An optional function used to compute a hash key for
         * storing the result, based on the arguments to the original function.
         * @return {Function} The new wrapper function.
         * @static
         */
        memoize: function(fn, scope, hashFn) {
            var memoizedFn = Ext.Function.memoize(fn, scope, hashFn);

            return function() {
                return Deferred.all(Ext.Array.slice(arguments)).then(function(values) {
                    return memoizedFn.apply(scope, values);
                });
            };
        },

        /**
         * Execute an Array (or {@link Ext.promise.Promise Promise} of an Array) of
         * functions in parallel.
         *
         * The specified functions may optionally return their results as
         * {@link Ext.promise.Promise Promises}.
         *
         * @param {Function[]/Ext.promise.Promise} fns The Array (or Promise of an Array)
         * of functions to execute.
         * @param {Object} scope Optional scope in which to execute the specified functions.
         * @return {Ext.promise.Promise} Promise of an Array of results for each function
         * call (in the same order).
         * @static
         */
        parallel: function(fns, scope) {
            var args;

            if (scope == null) {
                scope = null;
            }

            args = Ext.Array.slice(arguments, 2);

            return Deferred.map(fns, function(fn) {
                if (!Ext.isFunction(fn)) {
                    throw new Error('Invalid parameter: expected a function.');
                }

                return fn.apply(scope, args);
            });
        },

        /**
         * Execute an Array (or {@link Ext.promise.Promise Promise} of an Array) of
         * functions as a pipeline, where each function's result is passed to the
         * subsequent function as input.
         *
         * The specified functions may optionally return their results as
         * {@link Ext.promise.Promise Promises}.
         *
         * @param {Function[]/Ext.promise.Promise} fns The Array (or Promise of an Array)
         * of functions to execute.
         * @param {Object} initialValue Initial value to be passed to the first function
         * in the pipeline.
         * @param {Object} scope Optional scope in which to execute the specified functions.
         * @return {Ext.promise.Promise} Promise of the result value for the final
         * function in the pipeline.
         * @static
         */
        pipeline: function(fns, initialValue, scope) {
            if (scope == null) {
                scope = null;
            }

            return Deferred.reduce(fns, function(value, fn) {
                if (!Ext.isFunction(fn)) {
                    throw new Error('Invalid parameter: expected a function.');
                }

                return fn.call(scope, value);
            }, initialValue);
        },

        /**
         * Returns a promise that resolves or rejects as soon as one of the promises
         * in the array resolves or rejects, with the value or reason from that promise.
         * @param {Ext.promise.Promise[]} promises The promises.
         * @return {Ext.promise.Promise} The promise to be resolved when the race completes.
         *
         * @static
         * @since 6.5.0
         */
        race: function() {
            return ExtPromise.race.apply(ExtPromise, arguments);
        },

        /**
         * Traditional reduce function, similar to `Array.reduce()`, that allows input to
         * contain promises and/or values.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} values An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @param {Function} reduceFn A Function to call to transform each successive
         * item in the Array into the final reduced value.
         * @param {Mixed} initialValue An initial Promise or value.
         * @return {Ext.promise.Promise} A Promise of the reduced value.
         * @static
         */
        reduce: function(values, reduceFn, initialValue) {
            var initialValueSpecified;

            //<debug>
            if (!(Ext.isArray(values) || ExtPromise.is(values))) {
                Ext.raise('Invalid parameter: expected an Array or Promise of an Array.');
            }

            if (!Ext.isFunction(reduceFn)) {
                Ext.raise('Invalid parameter: expected a function.');
            }
            //</debug>

            initialValueSpecified = arguments.length === 3;

            return Deferred.resolved(values).then(function(promisesOrValues) {
                var reduceArguments = [
                    promisesOrValues,
                    function(previousValueOrPromise, currentValueOrPromise, currentIndex) {
                        return Deferred.resolved(previousValueOrPromise).then(
                            function(previousValue) {
                                return Deferred.resolved(currentValueOrPromise).then(
                                    function(currentValue) {
                                        return reduceFn(
                                            previousValue, currentValue, currentIndex,
                                            promisesOrValues
                                        );
                                });
                        });
                    }
                ];

                if (initialValueSpecified) {
                    reduceArguments.push(initialValue);
                }

                return Ext.Array.reduce.apply(Ext.Array, reduceArguments);
            });
        },

        /**
         * Convenience method that returns a new Promise rejected with the specified
         * reason.
         *
         * @param {Error} reason Rejection reason.
         * @return {Ext.promise.Promise} The rejected Promise.
         * @static
         */
        rejected: function(reason) {
            var deferred = new Ext.Deferred();

            deferred.reject(reason);

            return deferred.promise;
        },

        /**
         * Returns a new Promise that either
         *
         *  * Resolves immediately for the specified value, or
         *  * Resolves or rejects when the specified promise (or third-party Promise or
         *    then()-able) is resolved or rejected.
         *
         * @param {Mixed} promiseOrValue A Promise (or third-party Promise or then()-able)
         * or value.
         * @return {Ext.promise.Promise} A Promise of the specified Promise or value.
         * @static
         */
        resolved: function(promiseOrValue) {
            var deferred = new Ext.Deferred();

            deferred.resolve(promiseOrValue);

            return deferred.promise;
        },

        /**
         * Execute an Array (or {@link Ext.promise.Promise Promise} of an Array) of
         * functions sequentially.
         *
         * The specified functions may optionally return their results as {@link
         * Ext.promise.Promise Promises}.
         *
         * @param {Function[]/Ext.promise.Promise} fns The Array (or Promise of an Array)
         * of functions to execute.
         * @param {Object} scope Optional scope in which to execute the specified functions.
         * @return {Ext.promise.Promise} Promise of an Array of results for each function
         * call (in the same order).
         * @static
         */
        sequence: function(fns, scope) {
            var args;

            if (scope == null) {
                scope = null;
            }

            args = Ext.Array.slice(arguments, 2);

            return Deferred.reduce(fns, function(results, fn) {
                if (!Ext.isFunction(fn)) {
                    throw new Error('Invalid parameter: expected a function.');
                }

                return Deferred.resolved(fn.apply(scope, args)).then(function(result) {
                    results.push(result);

                    return results;
                });
            }, []);
        },

        /**
         * Initiates a competitive race, returning a new Promise that will resolve when
         * `howMany` of the specified `promisesOrValues` have resolved, or will reject
         * when it becomes impossible for `howMany` to resolve.
         *
         * The resolution value will be an Array of the first `howMany` values of
         * `promisesOrValues` to resolve.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} promisesOrValues An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @param {Number} howMany The expected number of resolved values.
         * @return {Ext.promise.Promise} A Promise of the expected number of resolved
         * values.
         * @static
         */
        some: function(promisesOrValues, howMany) {
            //<debug>
            if (!(Ext.isArray(promisesOrValues) || ExtPromise.is(promisesOrValues))) {
                Ext.raise('Invalid parameter: expected an Array or Promise of an Array.');
            }

            if (!Ext.isNumeric(howMany) || howMany <= 0) {
                Ext.raise('Invalid parameter: expected a positive integer.');
            }
            //</debug>

            return Deferred.resolved(promisesOrValues).then(function(promisesOrValues) {
                var deferred, index, onReject, onResolve, promiseOrValue,
                    remainingToReject, remainingToResolve, values, i, len;

                values = [];
                remainingToResolve = howMany;
                remainingToReject = (promisesOrValues.length - remainingToResolve) + 1;
                deferred = new Deferred();

                if (promisesOrValues.length < howMany) {
                    deferred.reject(new Error('Too few Promises were resolved.'));
                }
                else {
                    onResolve = function(value) {
                        if (remainingToResolve > 0) {
                            values.push(value);
                        }

                        remainingToResolve--;

                        if (remainingToResolve === 0) {
                            deferred.resolve(values);
                        }

                        return value;
                    };

                    onReject = function(reason) {
                        remainingToReject--;

                        if (remainingToReject === 0) {
                            deferred.reject(new Error('Too few Promises were resolved.'));
                        }

                        return reason;
                    };

                    for (index = i = 0, len = promisesOrValues.length; i < len; index = ++i) {
                        promiseOrValue = promisesOrValues[index];

                        if (index in promisesOrValues) {
                            Deferred.resolved(promiseOrValue).then(onResolve, onReject);
                        }
                    }
                }

                return deferred.promise;
            });
        },

        /**
         * Returns a new Promise that will automatically reject after the specified
         * timeout (in milliseconds) if the specified promise has not resolved or
         * rejected.
         *
         * @param {Mixed} promiseOrValue A Promise or value.
         * @param {Number} milliseconds A timeout duration (in milliseconds).
         * @return {Ext.promise.Promise} A Promise of the specified Promise or value that
         * enforces the specified timeout.
         * @static
         */
        timeout: function(promiseOrValue, milliseconds) {
            var deferred = new Deferred(),
                timeoutId;

            timeoutId = Ext.defer(function() {
                if (timeoutId) {
                    deferred.reject(new Error('Promise timed out.'));
                }
            }, milliseconds);

            Deferred.resolved(promiseOrValue).then(function(value) {
                Ext.undefer(timeoutId);
                timeoutId = null;
                deferred.resolve(value);
            }, function(reason) {
                Ext.undefer(timeoutId);
                timeoutId = null;
                deferred.reject(reason);
            });

            return deferred.promise;
        }
    }
};
},
function(Deferred) {
    Deferred._ready();
});
