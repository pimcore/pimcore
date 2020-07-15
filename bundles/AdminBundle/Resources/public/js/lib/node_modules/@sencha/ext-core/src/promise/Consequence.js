//
// Ext.promise.Consequence adapted from:
// [DeftJS](https://github.com/deftjs/deftjs5)
// Copyright (c) 2012-2013 [DeftJS Framework Contributors](http://deftjs.org)
// Open source under the [MIT License](http://en.wikipedia.org/wiki/MIT_License).
//

/**
 * Consequences are used internally by a Deferred to capture and notify callbacks, and
 * propagate their transformed results as fulfillment or rejection.
 *
 * Developers never directly interact with a Consequence.
 *
 * A Consequence forms a chain between two Deferreds, where the result of the first
 * Deferred is transformed by the corresponding callback before being applied to the
 * second Deferred.
 *
 * Each time a Deferred's `then` method is called, it creates a new Consequence that will
 * be triggered once its originating Deferred has been fulfilled or rejected. A Consequence
 * captures a pair of optional onFulfilled and onRejected callbacks.
 *
 * Each Consequence has its own Deferred (which in turn has a Promise) that is resolved or
 * rejected when the Consequence is triggered. When a Consequence is triggered by its
 * originating Deferred, it calls the corresponding callback and propagates the transformed
 * result to its own Deferred; resolved with the callback return value or rejected with any
 * error thrown by the callback.
 *
 * @since 6.0.0
 * @private
 */
Ext.define('Ext.promise.Consequence', function(Consequence) { return { // eslint-disable-line brace-style, max-len
    /**
     * @property {Ext.promise.Promise}
     * Promise of the future value of this Consequence.
     */
    promise: null,

    /**
     * @property {Ext.promise.Deferred} deferred Internal Deferred for this Consequence.
     *
     * @private
     */
    deferred: null,

    /**
     * @property {Function} onFulfilled Callback to execute when this Consequence is triggered
     * with a fulfillment value.
     *
     * @private
     */
    onFulfilled: null,

    /**
     * @property {Function} onRejected Callback to execute when this Consequence is triggered
     * with a rejection reason.
     *
     * @private
     */
    onRejected: null,

    /**
     * @property {Function} onProgress Callback to execute when this Consequence is updated
     * with a progress value.
     *
     * @private
     */
    onProgress: null,

    /**
     * @param {Function} onFulfilled Callback to execute to transform a fulfillment value.
     * @param {Function} onRejected Callback to execute to transform a rejection reason.
     * @param {Function} onProgress Callback to execute to transform a progress value.
     */
    constructor: function(onFulfilled, onRejected, onProgress) {
        var me = this;

        me.onFulfilled = onFulfilled;
        me.onRejected = onRejected;
        me.onProgress = onProgress;
        me.deferred = new Ext.promise.Deferred();
        me.promise = me.deferred.promise;
    },

    /**
     * Trigger this Consequence with the specified action and value.
     *
     * @param {String} action Completion action (i.e. fulfill or reject).
     * @param {Mixed} value Fulfillment value or rejection reason.
     */
    trigger: function(action, value) {
        var me = this,
            deferred = me.deferred;

        switch (action) {
            case 'fulfill':
                me.propagate(value, me.onFulfilled, deferred, deferred.resolve);
                break;

            case 'reject':
                me.propagate(value, me.onRejected, deferred, deferred.reject);
                break;
        }
    },

    /**
     * Update this Consequence with the specified progress value.
     *
     * @param {Mixed} progress Progress value.
     */
    update: function(progress) {
        if (Ext.isFunction(this.onProgress)) {
            progress = this.onProgress(progress);
        }

        this.deferred.update(progress);
    },

    /**
     * Transform and propagate the specified value using the
     * optional callback and propagate the transformed result.
     *
     * @param {Mixed} value Value to transform and/or propagate.
     * @param {Function} [callback] Callback to use to transform the value.
     * @param {Function} deferred Deferred to use to propagate the value, if no callback
     * was specified.
     * @param {Function} deferredMethod Deferred method to call to propagate the value,
     * if no callback was specified.
     *
     * @private
     */
    propagate: function(value, callback, deferred, deferredMethod) {
        if (Ext.isFunction(callback)) {
            this.schedule(function() {
                try {
                    deferred.resolve(callback(value));
                }
                catch (e) {
                    deferred.reject(e);
                }
            });
        }
        else {
            deferredMethod.call(this.deferred, value);
        }
    },

    /**
     * Schedules the specified callback function to be executed on the next turn of the
     * event loop.
     *
     * @param {Function} callback Callback function.
     *
     * @private
     */
    schedule: function(callback) {
        var n = Consequence.queueSize++;

        Consequence.queue[n] = callback;

        if (!n) { // if (queue was empty)
            Ext.asap(Consequence.dispatch);
        }
    },

    statics: {
        /**
         * @property {Function[]} queue The queue of callbacks pending. This array is never
         * shrunk to reduce GC thrash but instead its elements will be set to `null`.
         *
         * @private
         */
        queue: new Array(10000),

        /**
         * @property {Number} queueSize The number of callbacks in the `queue`.
         *
         * @private
         */
        queueSize: 0,

        /**
         * This method drains the callback queue and calls each callback in order.
         *
         * @private
         */
        dispatch: function() {
            var queue = Consequence.queue,
                fn, i;

            // The queue could grow on each call, so we cannot cache queueSize here.
            for (i = 0; i < Consequence.queueSize; ++i) {
                fn = queue[i];
                queue[i] = null; // release our reference on the callback
                fn();
            }

            Consequence.queueSize = 0;
        }
    }
};
}
//<debug>
, function(Consequence) { // eslint-disable-line comma-style
    Consequence.dispatch.$skipTimerCheck = true;
}
//</debug>
);
