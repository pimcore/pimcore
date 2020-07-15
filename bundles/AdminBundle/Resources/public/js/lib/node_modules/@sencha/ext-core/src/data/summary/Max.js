/**
 * Calculates the maximum for a set of data.
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Max', {
    extend: 'Ext.data.summary.Base',

    alias: 'data.summary.max',

    calculate: function(records, property, root, begin, end) {
        var max = this.extractValue(records[begin], property, root),
            i, v;

        for (i = begin; i < end; ++i) {
            v = this.extractValue(records[i], property, root);

            if (v > max) {
                max = v;
            }
        }

        return max;
    }
});
