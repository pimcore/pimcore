/**
 * Calculates the average for a set of data.
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Average', {
    extend: 'Ext.data.summary.Sum',

    alias: 'data.summary.average',

    calculate: function(records, property, root, begin, end) {
        var len = end - begin,
            value;

        if (len > 0) {
            value = this.callParent([records, property, root, begin, end]) / len;
        }

        return value;
    }
});
