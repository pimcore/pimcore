/**
 * @private
 */
Ext.define('Ext.util.BasicFilter', {
    isFilter: true,

    config: {
        /**
         * @cfg {String} id
         * An identifier by which this Filter is known, for example, as a member of a
         * {@link Ext.data.Store#cfg-filters Store's filters collection}.
         *
         * Identified filters are manageable in such collections because they can be found
         * or removed using their `id`.
         */
        id: null,

        /**
         * @cfg {Boolean} disabled
         * Setting this property to `true` disables this individual filter.
         */
        disabled: false,

        /**
         * @cfg {Function} serializer
         * A function to post-process any serialization. Accepts the serialized filter
         * containing `property`, `value` and `operator` properties, and may either
         * mutate it, or return a completely new representation. Returning a falsy
         * value does not modify the representation.
         * @since 6.2.0
         */
        serializer: null
    },

    /**
     * @property {Number} generation
     * this property is a Mutation counter which is incremented whenever the filter changes
     * in a way that may change either its serialized form or its result.
     * @readonly
     * @since 6.5.0
     */
    generation: 0,

    /**
     * Initializes a filter.
     * @param {Object} config The config object
     */
    constructor: function(config) {
        this.initConfig(config);
    },

    updateDisabled: function() {
        // Developers may use this to see if a filter has changed in ways that must cause
        // a reevaluation of filtering
        if (!this.isConfiguring) {
            ++this.generation;
        }
    }

    /**
     * @method filter
     * @param {Object} item
     * @return {Boolean}
     */

    /**
     * @method serialize
     * Returns this filter's serialized state. This is used when transmitting this filter
     * to a server.
     * @return {Object}
     */

    /**
     * Serialize this filter into the `out` array (if possible).
     * @param {Array} out The array of simple and-able filters.
     * @return {Boolean} `true` if any saved filters require encoding
     * @method serializeTo
     * @private
     */
});
