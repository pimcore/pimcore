/**
 * Discrete processor that combines duplicate data points.
 */
Ext.define('Ext.chart.axis.layout.CombineDuplicate', {
    extend: 'Ext.chart.axis.layout.Discrete',
    alias: 'axisLayout.combineDuplicate',

    getCoordFor: function(value, field, idx, items) {
        var result;

        if (!(value in this.labelMap)) {
            result = this.labelMap[value] = this.labels.length;

            this.labels.push(value);

            return result;
        }

        return this.labelMap[value];
    }
});
