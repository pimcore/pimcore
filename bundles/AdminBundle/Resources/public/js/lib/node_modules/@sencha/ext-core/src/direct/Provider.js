/**
 * Ext.direct.Provider is an abstract class meant to be extended.
 *
 * For example Ext JS implements the following subclasses:
 *
 *     Provider
 *     |
 *     +---JsonProvider
 *         |
 *         +---PollingProvider
 *         |
 *         +---RemotingProvider
 *
 * @abstract
 */
Ext.define('Ext.direct.Provider', {
    alias: 'direct.provider',

    mixins: [
        'Ext.mixin.Observable'
    ],

    requires: [
        'Ext.direct.Manager'
    ],

    isProvider: true,
    $configPrefixed: false,
    $configStrict: false,

    /* eslint-disable max-len */
    /**
     * @cfg {String} id
     * The unique id of the provider (defaults to an {@link Ext#id auto-assigned id}).
     * You should assign an id if you need to be able to access the provider later and you do
     * not have an object reference available, for example:
     *
     *      Ext.direct.Manager.addProvider({
     *          type: 'polling',
     *          url:  'php/poll.php',
     *          id:   'poll-provider'
     *      });
     *      
     *      var p = {@link Ext.direct.Manager}.{@link Ext.direct.Manager#getProvider getProvider}('poll-provider');
     *      
     *      p.disconnect();
     *
     */
    /* eslint-enable max-len */

    /**
     * @cfg {String[]} relayedEvents
     * List of Provider events that should be relayed by {@link Ext.direct.Manager}.
     * 'data' event is always relayed.
     */

    config: {
        /**
         * @cfg {Object} [headers]
         * An object containing default headers for every Ajax request made by this Provider.
         */
        headers: undefined
    },

    /**
     * @event connect
     * Fires when the Provider connects to the server-side
     *
     * @param {Ext.direct.Provider} provider The {@link Ext.direct.Provider Provider}.
     */

    /**
     * @event disconnect
     * Fires when the Provider disconnects from the server-side
     *
     * @param {Ext.direct.Provider} provider The {@link Ext.direct.Provider Provider}.
     */

    /**
     * @event data
     * Fires when the Provider receives data from the server-side. This event is fired
     * for valid responses as well as for exceptions.
     *
     * @param {Ext.direct.Provider} provider The {@link Ext.direct.Provider Provider} instance.
     * @param {Ext.direct.Event} e The {@link Ext.direct.Event} that occurred.
     */

    /**
     * @event exception
     * Fires when the Provider receives an exception from the server-side. This event is *not*
     * fired for valid responses.
     *
     * @param {Ext.direct.Provider} provider The {@link Ext.direct.Provider Provider} instance.
     * @param {Ext.direct.Event} e The {@link Ext.direct.Event Exception event} that occured.
     */

    subscribers: 0,

    constructor: function(config) {
        var me = this;

        me.mixins.observable.constructor.call(me, config);

        me.requests = {};

        if (me.id == null) {
            me.id = Ext.id(null, 'provider-');
        }
    },

    destroy: function() {
        var me = this;

        me.disconnect(true);
        me.callParent();
    },

    /**
     * Returns whether or not the server-side is currently connected.
     */
    isConnected: function() {
        return this.subscribers > 0;
    },

    /**
     * Connect the provider and start its service.
     * Provider will fire `connect` event upon successful connection.
     */
    connect: function() {
        var me = this;

        if (me.subscribers === 0) {
            me.doConnect();
            me.fireEventArgs('connect', [me]);
        }

        me.subscribers++;
    },

    /**
     * @method
     *
     * Do connection setup. This is a template method.
     * @template
     * @protected
     */
    doConnect: Ext.emptyFn,

    /**
     * Disconnect the provider and stop its service.
     * Provider will fire `disconnect` event upon successful disconnect.
     */
    disconnect: function(/* */ force) {
        var me = this;

        if (me.subscribers > 0 || force) {
            if (force) {
                me.subscribers = 0;
            }
            else {
                me.subscribers--;
            }

            if (me.subscribers === 0) {
                me.doDisconnect();
                me.fireEventArgs('disconnect', [me]);
            }
        }
    },

    /**
     * @method
     *
     * Do connection teardown. This is a template method.
     * @template
     * @protected
     */
    doDisconnect: function() {
        var requests = this.requests,
            request, id;

        for (id in requests) {
            request = requests[id];
            request.abort();
        }

        this.requests = {};
    },

    /**
     * Send the Ajax request
     *
     * @param {Object} params Ajax request parameters
     *
     * @private
     */
    sendAjaxRequest: function(params) {
        var request = Ext.Ajax.request(params);

        if (request && request.id) {
            this.requests[request.id] = request;
        }

        return request;
    },

    /**
     * Ajax request callback
     *
     * @private
     */
    onData: function(options, success, response) {
        if (response && response.request) {
            delete this.requests[response.request.id];
        }
    },

    inheritableStatics: {
        /**
         * @method
         *
         * Check if the passed configuration object contains enough
         * information to construct a Provider.
         *
         * @param {Object} config
         *
         * @return {Boolean} `true` if config is sufficient, `false` otherwise.
         * @static
         * @inheritable
         */
        checkConfig: Ext.returnFalse
    },

    onClassExtended: function(cls, data, hooks) {
        if (data.type) {
            Ext.direct.Manager.addProviderClass(data.type, cls);
        }
    }
});
