/**
 * @class Ext.chart.axis.Time3D
 */
Ext.define('Ext.chart.axis.Time3D', {
    extend: 'Ext.chart.axis.Numeric3D',
    alias: 'axis.time3d',
    type: 'time3d',
    requires: [
        'Ext.chart.axis.layout.Continuous',
        'Ext.chart.axis.segmenter.Time'
    ],
    config: {
        /**
         * @cfg {String/Boolean} dateFormat
         * Indicates the format the date will be rendered on.
         * For example: 'M d' will render the dates as 'Jan 30', etc.
         */
        dateFormat: null,

        /**
         * @cfg {Date} fromDate The starting date for the time axis.
         */
        fromDate: null,

        /**
         * @cfg {Date} toDate The ending date for the time axis.
         */
        toDate: null,

        layout: 'continuous',

        segmenter: 'time',

        aggregator: 'time'
    },

    updateDateFormat: function(format) {
        this.setRenderer(function(axis, date) {
            return Ext.Date.format(new Date(date), format);
        });
    },

    updateFromDate: function(date) {
        this.setMinimum(+date);
    },

    updateToDate: function(date) {
        this.setMaximum(+date);
    },

    getCoordFor: function(value) {
        if (Ext.isString(value)) {
            value = new Date(value);
        }

        return +value;
    }
});
