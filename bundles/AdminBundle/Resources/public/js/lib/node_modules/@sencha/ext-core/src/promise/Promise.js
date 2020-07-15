/*
 Ext.promise.Deferred adapted from:
 [DeftJS](https://github.com/deftjs/deftjs5)
 Copyright (c) 2012-2014 [DeftJS Framework Contributors](http://deftjs.org)
 Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
 */

/**
 * Promises represent a future value; i.e., a value that may not yet be available.
 *
 * Users should **not** create instances of this class directly. Instead user code should
 * use `new {@link Ext.Promise}()` or `new {@link Ext.Deferred}()` to create and manage
 * promises. If the browser supports the standard `Promise` constructor, this class will
 * not be used by `Ext.Promise`. This class will always be used by `Ext.Deferred` in order
 * to provide enhanced capabilities beyond standard promises.
 *
 * A Promise's `{@link #then then()}` method is used to specify onFulfilled and onRejected
 * callbacks that will be notified when the future value becomes available. Those callbacks
 * can subsequently transform the value that was resolved or the reason that was rejected.
 * Each call to `then` returns a new Promise of that transformed value; i.e., a Promise
 * that is resolved with the callback return value or rejected with any error thrown by
 * the callback.
 *
 * ## Basic Usage
 *
 *      this.companyService.loadCompanies().then(
 *          function(records) {
 *              // Do something with result.
 *          },
 *          function(error) {
 *              // Do something on failure.
 *          }).
 *      always(function() {
 *          // Do something whether call succeeded or failed
 *      });
 *
 * The above code uses the `Promise` returned from the `companyService.loadCompanies()`
 * method and uses `then()` to attach success and failure handlers. Finally, an `always()`
 * method call is chained onto the returned promise. This specifies a callback function
 * that will run whether the underlying call succeeded or failed.
 *
 * See `{@link Ext.Deferred}` for an example of using the returned Promise.
 *
 * [1]: http://wiki.ecmascript.org/doku.php?id=harmony:specification_drafts#april_14_2015_rev_38_final_draft
 *
 * @since 6.0.0
 */
Ext.define('Ext.promise.Promise', function(ExtPromise) {
    var Deferred;

/* eslint-disable indent */
return {
    requires: [
        'Ext.promise.Deferred'
    ],

    statics: {
        /**
         * @property CancellationError
         * @static
         * The type of `Error` propagated by the `{@link #method-cancel}` method. If
         * the browser provides a native `CancellationError` then that type is used. If
         * not, a basic `Error` type is used.
         */
        CancellationError: Ext.global.CancellationError || Error,

        _ready: function() {
            // Our requires are met, so we can cache Ext.promise.Deferred
            Deferred = Ext.promise.Deferred;
        },

        /**
         * Returns a new Promise that will only resolve once all the specified
         * `promisesOrValues` have resolved.
         *
         * The resolution value will be an Array containing the resolution value of each
         * of the `promisesOrValues`.
         *
         * The public API's to use instead of this method are `{@link Ext.Promise#all}`
         * and `{@link Ext.Deferred#all}`.
         *
         * @param {Mixed[]/Ext.promise.Promise[]/Ext.promise.Promise} promisesOrValues An
         * Array of values or Promises, or a Promise of an Array of values or Promises.
         * @return {Ext.promise.Promise} A Promise of an Array of the resolved values.
         *
         * @static
         * @private
         */
        all: function(promisesOrValues) {
            //<debug>
            if (!(Ext.isArray(promisesOrValues) || ExtPromise.is(promisesOrValues))) {
                Ext.raise('Invalid parameter: expected an Array or Promise of an Array.');
            }
            //</debug>

            return ExtPromise.when(promisesOrValues).then(function(promisesOrValues) {
                var deferred = new Deferred(),
                    remainingToResolve = promisesOrValues.length,
                    results = new Array(remainingToResolve),
                    index, promiseOrValue, resolve, i, len;

                if (!remainingToResolve) {
                    deferred.resolve(results);
                }
                else {
                    resolve = function(item, index) {
                        return ExtPromise.when(item).then(function(value) {
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
         * Determines whether the specified value is a Promise (including third-party
         * untrusted Promises or then()-ables), based on the Promises/A specification
         * feature test.
         *
         * @param {Mixed} value A potential Promise.
         * @return {Boolean} `true` if the given value is a Promise, otherwise `false`.
         * @static
         * @private
         */
        is: function(value) {
            return value != null && (typeof value === 'object' || Ext.isFunction(value)) &&
                Ext.isFunction(value.then);
        },

        /**
         * Returns a promise that resolves or rejects as soon as one of the promises in the array
         * resolves or rejects, with the value or reason from that promise.
         * @param {Ext.promise.Promise[]} promises The promises.
         * @return {Ext.promise.Promise} The promise to be resolved when the race completes.
         *
         * @private
         * @static
         * @since 6.5.0
         */
        race: function(promises) {
            var deferred = new Deferred(),
                len = promises.length,
                i;

            //<debug>
            if (!Ext.isArray(promises)) {
                Ext.raise('Invalid parameter: expected an Array.');
            }
            //</debug>

            for (i = 0; i < len; ++i) {
                deferred.resolve(promises[i]);
            }

            return deferred.promise;
        },

        /**
         * Rethrows the specified Error on the next turn of the event loop.
         * @static
         * @private
         */
        rethrowError: function(error) {
            Ext.asap(function() {
                throw error;
            });
        },

        /**
         * Returns a new Promise that either
         *
         *  * Resolves immediately for the specified value, or
         *  * Resolves or rejects when the specified promise (or third-party Promise or
         *    then()-able) is resolved or rejected.
         *
         * The public API's to use instead of this method are `{@link Ext.Promise#resolve}`
         * and `{@link Ext.Deferred#resolved}`.
         *
         * @param {Mixed} value A Promise (or third-party Promise or then()-able)
         * or value.
         * @return {Ext.Promise} A Promise of the specified Promise or value.
         *
         * @static
         * @private
         */
        when: function(value) {
            var deferred = new Deferred();

            deferred.resolve(value);

            return deferred.promise;
        }
    },

    /**
     * @property {Ext.promise.Deferred} Reference to this promise's
     * `{@link Ext.promise.Deferred Deferred}` instance.
     *
     * @readonly
     * @private
     */
    owner: null,

    /**
     * NOTE: {@link Ext.promise.Deferred Deferreds} are the mechanism used to create new
     * Promises.
     * @param {Ext.promise.Deferred} owner The owning `Deferred` instance.
     *
     * @private
     */
    constructor: function(owner) {
        this.owner = owner;
    },

    /**
     * Attaches onFulfilled and onRejected callbacks that will be notified when the future
     * value becomes available.
     *
     * Those callbacks can subsequently transform the value that was fulfilled or the error
     * that was rejected. Each call to `then` returns a new Promise of that transformed
     * value; i.e., a Promise that is fulfilled with the callback return value or rejected
     * with any error thrown by the callback.
     *
     * @param {Function} onFulfilled Optional callback to execute to transform a
     * fulfillment value.
     * @param {Function} onRejected Optional callback to execute to transform a rejection
     * reason.
     * @param {Function} onProgress Optional callback function to be called with progress
     * updates.
     * @param {Object} scope Optional scope for the callback(s).
     * @return {Ext.promise.Promise} Promise that is fulfilled with the callback return
     * value or rejected with any error thrown by the callback.
     */
    then: function(onFulfilled, onRejected, onProgress, scope) {
        var ref;

        if (arguments.length === 1 && Ext.isObject(arguments[0])) {
            ref = arguments[0];
            onFulfilled = ref.success;
            onRejected = ref.failure;
            onProgress = ref.progress;
            scope = ref.scope;
        }

        if (scope) {
            if (onFulfilled) {
                onFulfilled = onFulfilled.bind(scope);
            }

            if (onRejected) {
                onRejected = onRejected.bind(scope);
            }

            if (onProgress) {
                onProgress = onProgress.bind(scope);
            }
        }

        return this.owner.then(onFulfilled, onRejected, onProgress);
    },

    /**
     * Attaches an onRejected callback that will be notified if this Promise is rejected.
     *
     * The callback can subsequently transform the reason that was rejected. Each call to
     * `otherwise` returns a new Promise of that transformed value; i.e., a Promise that
     * is resolved with the original resolved value, or resolved with the callback return
     * value or rejected with any error thrown by the callback.
     *
     * @param {Function} onRejected Callback to execute to transform a rejection reason.
     * @param {Object} scope Optional scope for the callback.
     * @return {Ext.promise.Promise} Promise of the transformed future value.
     *
     * @since 6.5.0
     */
    'catch': function(onRejected, scope) {
        var ref;

        if (arguments.length === 1 && Ext.isObject(arguments[0])) {
            ref = arguments[0];
            onRejected = ref.fn;
            scope = ref.scope;
        }

        if (scope != null) {
            onRejected = onRejected.bind(scope);
        }

        return this.owner.then(null, onRejected);
    },

    /**
     * An alias for the {@link #catch} method. To be used for browsers
     * where catch cannot be used as a method name.
     */
    otherwise: function(onRejected, scope) {
        return this['catch'].apply(this, arguments);  // eslint-disable-line dot-notation
    },

    /**
     * Attaches an onCompleted callback that will be notified when this Promise is completed.
     *
     * Similar to `finally` in `try... catch... finally`.
     *
     * NOTE: The specified callback does not affect the resulting Promise's outcome; any
     * return value is ignored and any Error is rethrown.
     *
     * @param {Function} onCompleted Callback to execute when the Promise is resolved or
     * rejected.
     * @param {Object} scope Optional scope for the callback.
     * @return {Ext.promise.Promise} A new "pass-through" Promise that is resolved with
     * the original value or rejected with the original reason.
     */
    always: function(onCompleted, scope) {
        var ref;

        if (arguments.length === 1 && Ext.isObject(arguments[0])) {
            ref = arguments[0];
            onCompleted = ref.fn;
            scope = ref.scope;
        }

        if (scope != null) {
            onCompleted = onCompleted.bind(scope);
        }

        return this.owner.then(function(value) {
            try {
                onCompleted();
            }
            catch (e) {
                ExtPromise.rethrowError(e);
            }

            return value;
        }, function(reason) {
            try {
                onCompleted();
            }
            catch (e) {
                ExtPromise.rethrowError(e);
            }

            throw reason;
        });
    },

    /**
     * Terminates a Promise chain, ensuring that unhandled rejections will be rethrown as
     * Errors.
     *
     * One of the pitfalls of interacting with Promise-based APIs is the tendency for
     * important errors to be silently swallowed unless an explicit rejection handler is
     * specified.
     *
     * For example:
     *
     *      promise.then(function() {
     *          // logic in your callback throws an error and it is interpreted as a
     *          // rejection. throw new Error("Boom!");
     *      });
     *
     *      // The Error was not handled by the Promise chain and is silently swallowed.
     *
     * This problem can be addressed by terminating the Promise chain with the done()
     * method:
     *
     *      promise.then(function() {
     *          // logic in your callback throws an error and it is interpreted as a
     *          // rejection. throw new Error("Boom!");
     *      }).done();
     *
     *     // The Error was not handled by the Promise chain and is rethrown by done() on
     *     // the next tick.
     *
     * The `done()` method ensures that any unhandled rejections are rethrown as Errors.
     */
    done: function() {
        this.owner.then(null, ExtPromise.rethrowError);
    },

    /**
     * Cancels this Promise if it is still pending, triggering a rejection with a
     * `{@link #CancellationError}` that will propagate to any Promises originating from
     * this Promise.
     *
     * NOTE: Cancellation only propagates to Promises that branch from the target Promise.
     * It does not traverse back up to parent branches, as this would reject nodes from
     * which other Promises may have branched, causing unintended side-effects.
     *
     * @param {Error} reason Cancellation reason.
     */
    cancel: function(reason) {
        if (reason == null) {
            reason = null;
        }

        this.owner.reject(new this.self.CancellationError(reason));
    },

    /**
     * Logs the resolution or rejection of this Promise with the specified category and
     * optional identifier. Messages are logged via all registered custom logger functions.
     *
     * @param {String} identifier An optional identifier to incorporate into the
     * resulting log entry.
     *
     * @return {Ext.promise.Promise} A new "pass-through" Promise that is resolved with
     * the original value or rejected with the original reason.
     */
    log: function(identifier) {
        if (identifier == null) {
            identifier = '';
        }

        return this.owner.then(function(value) {
            Ext.log("" + (identifier || 'Promise') + " resolved with value: " + value);

            return value;
        }, function(reason) {
            Ext.log("" + (identifier || 'Promise') + " rejected with reason: " + reason);

            throw reason;
        });
    }
};
}, function(ExtPromise) {
    ExtPromise._ready();
});
