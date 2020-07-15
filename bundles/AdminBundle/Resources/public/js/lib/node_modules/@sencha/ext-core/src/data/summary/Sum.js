/**
 * Calculates the sum for a set of data.
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Sum', {
    extend: 'Ext.data.summary.Base',

    alias: 'data.summary.sum',

    calculate: function(records, property, root, begin, end) {
        var n = end - begin,
            i, sum, v;

        for (i = 0; i < n; ++i) {
            v = this.extractValue(records[begin + i], property, root);
            sum = i ? sum + v : v;
        }

        return sum;
    }
});
