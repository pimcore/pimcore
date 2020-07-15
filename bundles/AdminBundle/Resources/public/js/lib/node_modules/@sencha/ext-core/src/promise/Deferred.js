/*
 Ext.promise.Deferred adapted from:
 [DeftJS](https://github.com/deftjs/deftjs5)
 Copyright (c) 2012-2013 [DeftJS Framework Contributors](http://deftjs.org)
 Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
 */

/**
 * Deferreds are the mechanism used to create new Promises. A Deferred has a single
 * associated Promise that can be safely returned to external consumers to ensure they do
 * not interfere with the resolution or rejection of the deferred operation.
 *
 * A Deferred is typically used within the body of a function that performs an asynchronous
 * operation. When that operation succeeds, the Deferred should be resolved; if that
 * operation fails, the Deferred should be rejected.
 *
 * Each Deferred has an associated Promise. A Promise delegates `then` calls to its
 * Deferred's `then` method. In this way, access to Deferred operations are divided between
 * producer (Deferred) and consumer (Promise) roles.
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
 * @private
 * @since 6.0.0
 */
Ext.define('Ext.promise.Deferred', {
    requires: [
        'Ext.promise.Consequence'
    ],

    /**
     * @property {Ext.promise.Promise} promise Promise of the future value of this Deferred.
     */
    promise: null,

    /**
     * @property {Ext.promise.Consequence[]} consequences Pending Consequences chained
     * to this Deferred.
     *
     * @private
     */
    consequences: [],

    /**
     * @property {Boolean} completed Indicates whether this Deferred has been completed.
     *
     * @private
     */
    completed: false,

    /**
     * @property {String} completeAction The completion action (i.e. 'fulfill' or 'reject').
     *
     * @private
     */
    completionAction: null,

    /**
     * @property {Mixed} completionValue The completion value (i.e. resolution value
     * or rejection error).
     *
     * @private
     */
    completionValue: null,

    constructor: function() {
        var me = this;

        me.promise = new Ext.promise.Promise(me);
        me.consequences = [];
        me.completed = false;
        me.completionAction = null;
        me.completionValue = null;
    },

    /**
     * Used to specify onFulfilled and onRejected callbacks that will be
     * notified when the future value becomes available.
     *
     * Those callbacks can subsequently transform the value that was
     * fulfilled or the error that was rejected. Each call to `then`
     * returns a new Promise of that transformed value; i.e., a Promise
     * that is fulfilled with the callback return value or rejected with
     * any error thrown by the callback.
     *
     * @param {Function} [onFulfilled] Callback to execute to transform a fulfillment value.
     * @param {Function} [onRejected] Callback to execute to transform a rejection reason.
     * @param {Function} [onProgress] Callback to execute to transform a progress value.
     *
     * @return Promise that is fulfilled with the callback return value or rejected with
     * any error thrown by the callback.
     */
    then: function(onFulfilled, onRejected, onProgress) {
        var me = this,
            consequence = new Ext.promise.Consequence(onFulfilled, onRejected, onProgress);

        if (me.completed) {
            consequence.trigger(me.completionAction, me.completionValue);
        }
        else {
            me.consequences.push(consequence);
        }

        return consequence.promise;
    },

    /**
     * Resolve this Deferred with the (optional) specified value.
     *
     * If called with a then-able (i.e.a Function or Object with a `then`
     * function, such as another Promise) it assimilates the then-able's
     * result; the Deferred provides its own `resolve` and `reject` methods
     * as the onFulfilled or onRejected arguments in a call to that
     * then-able's `then` function.  If an error is thrown while calling
     * the then-able's `then` function (prior to any call back to the
     * specified `resolve` or `reject` methods), the Deferred rejects with
     * that error. If a Deferred's `resolve` method is called with its own
     * Promise, it rejects with a TypeError.
     *
     * Once a Deferred has been fulfilled or rejected, it is considered to be complete
     * and subsequent calls to `resolve` or `reject` are ignored.
     *
     * @param {Mixed} value Value to resolve as either a fulfillment value or rejection
     * reason.
     */
    resolve: function(value) {
        var me = this,
            isHandled, thenFn;

        if (me.completed) {
            return;
        }

        try {
            if (value === me.promise) {
                throw new TypeError('A Promise cannot be resolved with itself.');
            }

            if (value != null && (typeof value === 'object' || Ext.isFunction(value)) &&
                        Ext.isFunction(thenFn = value.then)) {
                isHandled = false;

                try {
                    thenFn.call(value, function(value) {
                        if (!isHandled) {
                            isHandled = true;
                            me.resolve(value);
                        }
                    }, function(error) {
                        if (!isHandled) {
                            isHandled = true;
                            me.reject(error);
                        }
                    });
                }
                catch (e1) {
                    if (!isHandled) {
                        me.reject(e1);
                    }
                }
            }
            else {
                me.complete('fulfill', value);
            }
        }
        catch (e2) {
            me.reject(e2);
        }
    },

    /**
     * Reject this Deferred with the specified reason.
     *
     * Once a Deferred has been rejected, it is considered to be complete
     * and subsequent calls to `resolve` or `reject` are ignored.
     *
     * @param {Error} reason Rejection reason.
     */
    reject: function(reason) {
        if (this.completed) {
            return;
        }

        this.complete('reject', reason);
    },

    /**
     * Updates progress for this Deferred, if it is still pending, triggering it to
     * execute the `onProgress` callback and propagate the resulting transformed progress
     * value to Deferreds that originate from this Deferred.
     *
     * @param {Mixed} progress The progress value.
     */
    update: function(progress) {
        var consequences = this.consequences,
            consequence, i, len;

        if (this.completed) {
            return;
        }

        for (i = 0, len = consequences.length; i < len; i++) {
            consequence = consequences[i];
            consequence.update(progress);
        }
    },

    /**
     * Complete this Deferred with the specified action and value.
     *
     * @param {String} action Completion action (i.e. 'fufill' or 'reject').
     * @param {Mixed} value Fulfillment value or rejection reason.
     *
     * @private
     */
    complete: function(action, value) {
        var me = this,
            consequences = me.consequences,
            consequence, i, len;

        me.completionAction = action;
        me.completionValue = value;
        me.completed = true;

        for (i = 0, len = consequences.length; i < len; i++) {
            consequence = consequences[i];
            consequence.trigger(me.completionAction, me.completionValue);
        }

        me.consequences = null;
    }
});
