// @tag dom,core

/* eslint-disable max-len */
/**
 * An Identifiable mixin.
 * @private
 */
Ext.define('Ext.mixin.Identifiable', function(Identifiable) { return { // eslint-disable-line brace-style
/* eslint-enable max-len */
    isIdentifiable: true,

    mixinId: 'identifiable',

    /**
     * Retrieves the `id`. This method Will auto-generate an id if one has not already
     * been configured.
     * @return {String} id
     */
    getId: function() {
        var me = this,
            id = me.id,
            cfg;

        if (!(id || id === 0)) {
            cfg = me.initialConfig;

            // The id config can be requested so early (e.g., by stateful) that the
            // property has not been put on the instance yet.
            if (cfg && cfg.id) {
                id = cfg.id;
            }
            else {
                id = me.generateAutoId();
                me.autoGenId = true;
            }

            me.setId(id);
        }

        me.getId = Identifiable._getId;

        return id;
    },

    setId: function(id) {
        // The double assignment here and in setId is intentional to workaround a JIT
        // issue that prevents me.id from being assigned in random scenarios. The issue
        // occurs on 4th gen iPads and lower, possibly other older iOS devices.
        // See EXTJS-16494.
        this.id = this.id = id;
    },

    privates: {
        statics: {
            _idCleanRe: /\.|[^\w-]/g,
            uniqueIds: {},

            _getId: function() {
                return this.id;
            }
        },

        defaultIdPrefix: 'ext-',

        defaultIdSeparator: '-',

        id: null,

        /**
         * @property {Boolean} autoGenId
         * `true` indicates an `id` was auto-generated rather than provided by configuration.
         * @private
         * @since 6.7.0
         */
        autoGenId: false,

        generateAutoId: function() {
            var me = this,
                prototype = me.self.prototype,
                sep = me.defaultIdSeparator,
                uniqueIds = Identifiable.uniqueIds,
                cleanRe, defaultIdPrefix, prefix, xtype;

            if (!prototype.hasOwnProperty('identifiablePrefix')) {
                cleanRe = Identifiable._idCleanRe;
                defaultIdPrefix = me.defaultIdPrefix;
                xtype = me.xtype;

                if (xtype) {
                    prefix = defaultIdPrefix + xtype.replace(cleanRe, sep) + sep;
                }
                else if (!(prefix = prototype.$className)) {
                    prefix = defaultIdPrefix + 'anonymous' + sep;
                }
                else {
                    prefix = prefix.replace(cleanRe, sep).toLowerCase() + sep;
                }

                prototype.identifiablePrefix = prefix;
            }

            prefix = me.identifiablePrefix;

            if (!uniqueIds.hasOwnProperty(prefix)) {
                uniqueIds[prefix] = 0;
            }

            return prefix + (++uniqueIds[prefix]);
        }
    } // privates
};
});
