/**
 * @private
 */
Ext.define('Ext.mixin.ConfigState', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'configstate'
    },

    alternateStateConfig: '',

    toggleConfigState: function(isAlternate) {
        var me = this,
            state = me.capturedConfigState,
            cfg = me.getConfig(me.alternateStateConfig),
            key;

        if (!cfg) {
            return;
        }

        if (isAlternate) {
            state = {};

            for (key in cfg) {
                state[key] = me.getConfig(key);
            }

            me.capturedConfigState = state;
            me.setConfig(cfg);
            // Capture
        }
        else if (!me.isConfiguring && state) {
            me.setConfig(state);
            delete me.capturedConfigState;
        }
    }
});
