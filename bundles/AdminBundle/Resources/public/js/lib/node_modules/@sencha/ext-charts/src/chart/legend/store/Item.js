/**
 * The data model for legend items.
 */
Ext.define('Ext.chart.legend.store.Item', {
    extend: 'Ext.data.Model',

    fields: [
        'id',
        'name',      // The series title.
        'mark',      // The color of the series.
        'disabled',  // The state of the series.
        'series',    // A reference to the series instance.
        // A sprite index, e.g. for stacked or pie series.
        // For such series an individual component of the series
        // is hidden or shown when the legend item is toggled.
        'index'
    ]
});
