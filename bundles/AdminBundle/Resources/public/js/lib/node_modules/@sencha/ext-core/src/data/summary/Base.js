/**
 * The base class for calculating data summaries. The summary is calculated using the 
 * {@link #method!calculate} method. This is overridden in subclasses.
 *
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Base', {
    mixins: [
        'Ext.mixin.Factoryable'
    ],

    alias: 'data.summary.base',  // also configures Factoryable

    isAggregator: true,

    factoryConfig: {
        defaultType: 'base',
        cacheable: true
    },

    constructor: function(config) {
        var calculate = config && config.calculate;

        if (calculate) {
            config = Ext.apply({}, config);
            delete config.calculate;
            this.calculate = calculate;
        }

        this.initConfig(config);
    },

    /**
     * This method calculates the summary value of the given records.
     * @param {Ext.data.Model[]/Object[]} records The records to aggregate.
     * @param {String} property The property to aggregate on.
     * @param {String} root The root to extra the data from.
     * @param {Number} begin The starting index to calculate from.
     * @param {Number} end The index at which to stop calculating. The item at this
     * index will *not* be included in the calculation.
     *
     * @return {Object} The calculated summary value.
     * @method calculate
     */

    /**
     * Extract the underlying value from the data object.
     * @param {Ext.data.Model} record The record.
     * @param {String} property The property to extract.
     * @param {String} root The root on the data object.
     * @return {Object} The value.
     *
     * @protected
     */
    extractValue: function(record, property, root) {
        var ret;

        if (record) {
            if (root) {
                record = record[root];
            }

            ret = record[property];
        }

        return ret;
    }
}, function() {
    Ext.Factory.on('dataSummary', function(factory, config) {
        if (typeof config === 'function') {
            return factory({
                calculate: config
            });
        }
        // by not returning anything, the normal factory logic is applied
    });
});
