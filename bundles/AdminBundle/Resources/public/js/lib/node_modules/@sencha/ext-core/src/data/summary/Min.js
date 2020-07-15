/**
 * Calculates the minimum for a set of data.
 * @since 6.5.0
 */
Ext.define('Ext.data.summary.Min', {
    extend: 'Ext.data.summary.Base',

    alias: 'data.summary.min',

    calculate: function(records, property, root, begin, end) {
        var min = this.extractValue(records[begin], property, root),
            i, v;

        for (i = begin; i < end; ++i) {
            v = this.extractValue(records[i], property, root);

            if (v < min) {
                min = v;
            }
        }

        return min;
    }
});
