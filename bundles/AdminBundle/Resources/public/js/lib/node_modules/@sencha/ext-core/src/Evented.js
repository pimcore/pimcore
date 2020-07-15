/**
 * @private
 */
Ext.define('Ext.Evented', {
    alternateClassName: 'Ext.EventedBase',

    mixins: [
        'Ext.mixin.Observable'
    ],

    initialized: false,

    constructor: function(config) {
        // Base constructor is overriden for testing
        //<debug>
        this.callParent();
        //</debug>

        this.mixins.observable.constructor.call(this, config);
        this.initialized = true;
    },

    onClassExtended: function(cls, data) {
        if (!data.hasOwnProperty('eventedConfig')) {
            return;
        }

        /* eslint-disable-next-line vars-on-top */
        var config = data.config,
            eventedConfig = data.eventedConfig,
            name, cfg;

        if (config) {
            Ext.applyIf(config, eventedConfig);
        }
        else {
            cls.addConfig(eventedConfig);
        }

        /*
         * These are generated setters for eventedConfig
         *
         * If the component is initialized, it invokes fireAction to fire the event as well,
         * which indicate something has changed. Otherwise, it just executes the action
         * (happens during initialization)
         *
         * This is helpful when we only want the event to be fired for subsequent changes.
         * Also it's a major performance improvement for instantiation when fired events
         * are mostly useless since there's no listeners
         */

        // TODO: Move this into Observable
        for (name in eventedConfig) {
            if (eventedConfig.hasOwnProperty(name)) {
                cfg = Ext.Config.get(name);
                data[cfg.names.set] = cfg.eventedSetter || cfg.getEventedSetter();
            }
        }
    }
});
