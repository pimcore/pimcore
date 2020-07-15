/**
 * @class Ext.chart.axis.segmenter.Numeric
 * @extends Ext.chart.axis.segmenter.Segmenter
 * 
 * Numeric data type.
 */
Ext.define('Ext.chart.axis.segmenter.Numeric', {
    extend: 'Ext.chart.axis.segmenter.Segmenter',
    alias: 'segmenter.numeric',
    isNumeric: true,

    renderer: function(value, context) {
        return value.toFixed(Math.max(0, context.majorTicks.unit.fixes));
    },

    diff: function(min, max, unit) {
        return Math.floor((max - min) / unit.scale);
    },

    align: function(value, step, unit) {
        var scaledStep = unit.scale * step;

        return Math.floor(value / scaledStep) * scaledStep;
    },

    add: function(value, step, unit) {
        return value + step * unit.scale;
    },

    preferredStep: function(min, estStepSize) {
        // Getting an order of magnitude of the estStepSize with a common logarithm.
        var order = Math.floor(Math.log(estStepSize) * Math.LOG10E),
            scale = Math.pow(10, order);

        estStepSize /= scale;

        if (estStepSize < 2) {
            estStepSize = 2;
        }
        else if (estStepSize < 5) {
            estStepSize = 5;
        }
        else if (estStepSize < 10) {
            estStepSize = 10;
            order++;
        }

        return {
            unit: {
                // When passed estStepSize is less than 1, its order of magnitude
                // is equal to -number_of_leading_zeros in the estStepSize.
                fixes: -order, // Number of fractional digits.
                scale: scale
            },
            step: estStepSize
        };
    },

    leadingZeros: function(n) {
        // For example:
        // leadingZeros(0.2) is 1,
        // leadingZeros(-0.01) is 2.
        return -Math.floor(Ext.Number.log10(Math.abs(n)));
    },

    /**
     * Wraps the provided estimated step size of a range without altering it into a step size
     * object.
     *
     * @param {*} min The start point of range.
     * @param {*} estStepSize The estimated step size.
     * @return {Object} Return the step size by an object of step x unit.
     * @return {Number} return.step The step count of units.
     * @return {Object} return.unit The unit.
     */
    exactStep: function(min, estStepSize) {
        var stepZeros = this.leadingZeros(estStepSize),
            scale = Math.pow(10, stepZeros);

        return {
            unit: {
                // add one decimal point if estStepSize is not a multiple of scale
                fixes: stepZeros + (estStepSize % scale === 0 ? 0 : 1),
                // Swap scale & step, if the estStepSize < 1,
                // or 'diff' method will give us rounding errors.
                scale: estStepSize < 1 ? estStepSize : 1
            },
            step: estStepSize < 1 ? 1 : estStepSize
        };
    },

    adjustByMajorUnit: function(step, scale, range) {
        var min = range[0],
            max = range[1],
            increment = step * scale,
            remainder, multiplier;

        multiplier = Math.max(1 / (min || 1), 1 / (increment || 1));
        multiplier = multiplier > 1 ? multiplier : 1;
        remainder = ((min * multiplier) % (increment * multiplier)) / multiplier;

        if (remainder !== 0) {
            range[0] = min - remainder + (min < 0 ? -increment : 0);
        }

        multiplier = Math.max(1 / (max || 1), 1 / (increment || 1));
        multiplier = multiplier > 1 ? multiplier : 1;
        remainder = ((max * multiplier) % (increment * multiplier)) / multiplier;

        if (remainder !== 0) {
            range[1] = max - remainder + (max > 0 ? increment : 0);
        }
    }
});
