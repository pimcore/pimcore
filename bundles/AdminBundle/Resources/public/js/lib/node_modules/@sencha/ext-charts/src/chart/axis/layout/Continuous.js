/**
 * @class Ext.chart.axis.layout.Continuous
 * @extends Ext.chart.axis.layout.Layout
 * 
 * Processor for axis data that can be interpolated.
 */
Ext.define('Ext.chart.axis.layout.Continuous', {
    extend: 'Ext.chart.axis.layout.Layout',
    alias: 'axisLayout.continuous',
    isContinuous: true,

    config: {
        adjustMinimumByMajorUnit: false,
        adjustMaximumByMajorUnit: false
    },

    getCoordFor: function(value, field, idx, items) {
        return +value;
    },

    /**
     * @method snapEnds
     * @inheritdoc
     */
    snapEnds: function(context, min, max, estStepSize) {
        var segmenter = context.segmenter,
            axis = this.getAxis(),
            noAnimation = !axis.spriteAnimationCount,
            majorTickSteps = axis.getMajorTickSteps(),
            // if specific number of steps requested and the segmenter supports such segmentation
            bucket = majorTickSteps && segmenter.exactStep
                ? segmenter.exactStep(min, (max - min) / majorTickSteps)
                : segmenter.preferredStep(min, estStepSize),
            unit = bucket.unit,
            step = bucket.step,
            diffSteps = segmenter.diff(min, max, unit),
            steps = (majorTickSteps || diffSteps) + 1,
            from;

        // If 'majorTickSteps' config of the axis is set (is not 0), it means that
        // we want to split the range at that number of equal intervals (segmenter.exactStep),
        // and don't care if the resulting ticks are at nice round values or not.
        // So 'from' (aligned) step is equal to 'min' (unaligned step).
        // And 'to' is equal to 'max'.
        //
        // Another case where this is possible, is when the range between 'min' and
        // 'max' can be represented by n steps, where n is an integer.
        // For example, if the data values are [7, 17, 27, 37, 47], the data step is 10
        // and, if the calculated tick step (segmenter.preferredStep) is also 10,
        // there is no need to segmenter.align the 'min' to 0, so that the ticks are at
        // [0, 10, 20, 30, 40, 50], because the data points are already perfectly
        // spaced, so the ticks can be exactly at the data points without runing the
        // aesthetics.
        //
        // The 'noAnimation' check is required to prevent EXTJS-25413 from happening.
        // The segmentation described above is ideal for a static chart, but produces
        // unwanted effects during animation.
        if (majorTickSteps || (noAnimation && +segmenter.add(min, diffSteps, unit) === max)) {
            from = min;
        }
        else {
            from = segmenter.align(min, step, unit);
        }

        return {
            // min/max are NOT aligned to step
            min: segmenter.from(min),
            max: segmenter.from(max),

            // from/to are aligned to step
            from: from,
            to: segmenter.add(from, steps, unit),

            step: step,
            steps: steps,
            unit: unit,
            get: function(currentStep) {
                return segmenter.add(this.from, this.step * currentStep, this.unit);
            }
        };
    },

    snapMinorEnds: function(context) {
        var majorTicks = context.majorTicks,
            minorTickSteps = this.getAxis().getMinorTickSteps(),
            segmenter = context.segmenter,
            min = majorTicks.min,
            max = majorTicks.max,
            from = majorTicks.from,
            unit = majorTicks.unit,
            step = majorTicks.step / minorTickSteps,
            scaledStep = step * unit.scale,
            fromMargin = from - min,
            offset = Math.floor(fromMargin / scaledStep),
            extraSteps = offset + Math.floor((max - majorTicks.to) / scaledStep) + 1,
            steps = majorTicks.steps * minorTickSteps + extraSteps;

        return {
            min: min,
            max: max,
            from: min + fromMargin % scaledStep,
            to: segmenter.add(from, steps * step, unit),
            step: step,
            steps: steps,
            unit: unit,
            get: function(current) {
                // don't render minor tick in major tick position
                return (current % minorTickSteps + offset + 1 !== 0)
                    ? segmenter.add(this.from, this.step * current, unit)
                    : null;
            }
        };
    }
});
