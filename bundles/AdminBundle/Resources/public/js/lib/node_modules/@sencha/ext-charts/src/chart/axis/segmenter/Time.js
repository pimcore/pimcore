/**
 * @class Ext.chart.axis.segmenter.Time
 * @extends Ext.chart.axis.segmenter.Segmenter
 * 
 * Time data type.
 */
Ext.define('Ext.chart.axis.segmenter.Time', {
    extend: 'Ext.chart.axis.segmenter.Segmenter',
    alias: 'segmenter.time',

    config: {
        /**
         * @cfg {Object} step
         * @cfg {String} step.unit The unit of the step (Ext.Date.DAY, Ext.Date.MONTH, etc).
         * @cfg {Number} step.step The number of units for the step (1, 2, etc).
         * If specified, will override the result of {@link #preferredStep}.
         * For example:
         *     
         *     step: {
         *         unit: Ext.Date.HOUR,
         *         step: 1
         *     }
         */
        step: null
    },

    renderer: function(value, context) {
        var ExtDate = Ext.Date;

        switch (context.majorTicks.unit) {
            case 'y':
                return ExtDate.format(value, 'Y');

            case 'mo':
                return ExtDate.format(value, 'Y-m');

            case 'd':
                return ExtDate.format(value, 'Y-m-d');
        }

        return ExtDate.format(value, 'Y-m-d\nH:i:s');
    },

    from: function(value) {
        return new Date(value);
    },

    diff: function(min, max, unit) {
        if (isFinite(min)) {
            min = new Date(min);
        }

        if (isFinite(max)) {
            max = new Date(max);
        }

        return Ext.Date.diff(min, max, unit);
    },

    updateStep: function() {
        var axis = this.getAxis();

        if (axis && !this.isConfiguring) {
            axis.performLayout();
        }
    },

    align: function(date, step, unit) {
        if (unit === 'd' && step >= 7) {
            date = Ext.Date.align(date, 'd', step);
            date.setDate(date.getDate() - date.getDay() + 1);

            return date;
        }
        else {
            return Ext.Date.align(date, unit, step);
        }
    },

    add: function(value, step, unit) {
        return Ext.Date.add(new Date(value), unit, step);
    },

    timeBuckets: [
        {
            unit: Ext.Date.YEAR,
            steps: [1, 2, 5, 10, 20, 50, 100, 200, 500]
        },
        {
            unit: Ext.Date.MONTH,
            steps: [1, 3, 6]
        },
        {
            unit: Ext.Date.DAY,
            steps: [1, 7, 14]
        },
        {
            unit: Ext.Date.HOUR,
            steps: [1, 6, 12]
        },
        {
            unit: Ext.Date.MINUTE,
            steps: [1, 5, 15, 30]
        },
        {
            unit: Ext.Date.SECOND,
            steps: [1, 5, 15, 30]
        },
        {
            unit: Ext.Date.MILLI,
            steps: [1, 2, 5, 10, 20, 50, 100, 200, 500]
        }
    ],

    /**
     * @private
     * Takes a time interval and figures out what is the smallest nice number of which
     * units (years, months, days, etc.) that can fully encompass that interval.
     * @param {Date} min
     * @param {Date} max
     * @return {Object}
     * @return {String} return.unit The unit.
     * @return {Number} return.step The number of units.
     */
    getTimeBucket: function(min, max) {
        var buckets = this.timeBuckets,
            unit, unitCount,
            steps, step,
            result,
            i, j;

        for (i = 0; i < buckets.length; i++) {
            unit = buckets[i].unit;
            unitCount = this.diff(min, max, unit);

            if (unitCount > 0) {
                steps = buckets[i].steps;

                for (j = 0; j < steps.length; j++) {
                    step = steps[j];

                    if (unitCount <= step) {
                        break;
                    }
                }

                result = {
                    unit: unit,
                    step: step
                };
                break;
            }
        }

        // If the interval is smaller then one millisecond ...
        if (!result) {
            // ... we can't go smaller than one millisecond.
            result = {
                unit: Ext.Date.MILLI,
                step: 1
            };
        }

        return result;
    },

    preferredStep: function(min, estStepSize) {
        var step = this.getStep();

        return step
            ? step
            : this.getTimeBucket(
                new Date(+min),
                new Date(+min + Math.ceil(estStepSize))
            );
    }
});
