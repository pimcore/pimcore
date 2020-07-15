/**
 * Calculates the count for a set of data.
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Count', {
    extend: 'Ext.data.summary.Base',

    alias: 'data.summary.count',

    calculate: function(records, property, root, begin, end) {
        return end - begin;
    }
});
