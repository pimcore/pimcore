/**
 * This class manages a pending Ajax request. Instances of this type are created by the
 * `{@link Ext.data.Connection#request}` method.
 * @since 6.0.0
 */
Ext.define('Ext.data.request.Base', {
    requires: [
        'Ext.Deferred'
    ],

    mixins: [
        'Ext.mixin.Factoryable'
    ],

    // Since this class is abstract, we don't have an alias of our own for Factoryable
    // to use.
    factoryConfig: {
        type: 'request',
        defaultType: 'ajax' // this is the default deduced from the alias
    },

    result: null,

    success: null,

    timer: null,

    constructor: function(config) {
        var me = this;

        // ownerConfig contains default values for config options
        // applicable to every Request spawned by that owner;
        // however the values can be overridden in the options
        // object passed to owner's request() method.
        Ext.apply(me, config.options || {}, config.ownerConfig);

        me.id = ++Ext.data.Connection.requestId;
        me.owner = config.owner;
        me.options = config.options;
        me.requestOptions = config.requestOptions;
    },

    /**
     * Start the request.
     */
    start: function() {
        var me = this,
            timeout = me.getTimeout();

        if (timeout && me.async) {
            me.timer = Ext.defer(me.onTimeout, timeout, me);
        }
    },

    abort: function() {
        var me = this;

        me.clearTimer();

        if (!me.timedout) {
            me.aborted = true;
        }

        me.abort = Ext.emptyFn;
    },

    createDeferred: function() {
        var me = this,
            result = me.result,
            d = new Ext.Deferred();

        if (me.completed) {
            if (me.success) {
                d.resolve(result);
            }
            else {
                d.reject(result);
            }
        }

        me.deferred = d;

        return d;
    },

    getDeferred: function() {
        return this.deferred || this.createDeferred();
    },

    getPromise: function() {
        return this.getDeferred().promise;
    },

    /**
     * @method then
     * Returns a new promise resolving to the value of the called method.
     * @param {Function} success Called when the Promise is fulfilled.
     * @param {Function} failure Called when the Promise is rejected.
     * @returns {Ext.promise.Promise}
     */
    then: function() {
        var promise = this.getPromise();

        return promise.then.apply(promise, arguments);
    },

    /**
     * @method isLoading
     * Determines whether this request is in progress.
     *
     * @return {Boolean} `true` if this request is in progress, `false` if complete.
     */

    onComplete: function() {
        var me = this,
            deferred = me.deferred,
            result = me.result;

        me.clearTimer();

        if (deferred) {
            if (me.success) {
                deferred.resolve(result);
            }
            else {
                deferred.reject(result);
            }
        }

        me.completed = true;
    },

    onTimeout: function() {
        var me = this;

        me.timedout = true;
        me.timer = null;

        me.abort(true);
    },

    getTimeout: function() {
        return this.timeout;
    },

    clearTimer: function() {
        this.timer = Ext.undefer(this.timer);
    },

    destroy: function() {
        var me = this;

        me.abort();

        me.owner = me.options = me.requestOptions = me.result = null;

        me.callParent();
    },

    privates: {
        /**
         * Creates the exception object
         * @param {Object} request
         * @private
         */
        createException: function() {
            var me = this,
                result;

            result = {
                request: me,
                requestId: me.id,
                status: me.aborted ? -1 : 0,
                statusText: me.aborted ? 'transaction aborted' : 'communication failure',
                getResponseHeader: me._getHeader,
                getAllResponseHeaders: me._getHeaders
            };

            if (me.aborted) {
                result.aborted = true;
            }

            if (me.timedout) {
                result.timedout = true;
            }

            return result;
        },

        _getHeader: function(name) {
            var headers = this.headers;

            return headers && headers[name.toLowerCase()];
        },

        _getHeaders: function() {
            return this.headers;
        }
    }
});
