/**
 * @class Ext.chart.axis.layout.Discrete
 * @extends Ext.chart.axis.layout.Layout
 *
 * Simple processor for data that cannot be interpolated.
 */
Ext.define('Ext.chart.axis.layout.Discrete', {
    extend: 'Ext.chart.axis.layout.Layout',
    alias: 'axisLayout.discrete',
    isDiscrete: true,

    processData: function() {
        var me = this,
            axis = me.getAxis(),
            seriesList = axis.boundSeries,
            direction = axis.getDirection(),
            i, ln, series;

        me.labels = [];
        me.labelMap = {};

        for (i = 0, ln = seriesList.length; i < ln; i++) {
            series = seriesList[i];

            if (series['get' + direction + 'Axis']() === axis) {
                series['coordinate' + direction]();
            }
        }
        // About the labels on Category axes (aka. axes with a Discrete layout)...
        //
        // When the data set from the store changes, series.processData() is called, which does
        // its thing at the series level and then calls series.updateLabelData() to update
        // the labels in the sprites that belong to the series. At the same time,
        // series.processData() calls axis.processData(), which also does its thing but at the axis
        // level, and also needs to update the labels for the sprite(s) that belong to the axis.
        // This is not that simple, however. So how are the axis labels rendered?
        // First, axis.sprite.Axis.render() calls renderLabels() which obtains the majorTicks
        // from the  axis.layout and iterate() through them. The majorTicks are an object returned
        // by snapEnds() below which provides a getLabel() function that returns the label
        // from the axis.layoutContext.data array. So now the question is: how are the labels
        // transferred from the axis.layout to the axis.layoutContext?
        // The easy response is: it's in calculateLayout() below. The issue is to call
        // calculateLayout() because it takes in an axis.layoutContext that can only be created
        // in axis.sprite.Axis.layoutUpdater(), which is a private "updater" function that is
        // called by all the sprite's "triggers". Of course, we don't want to call layoutUpdater()
        // directly from here, so instead we update the sprite's data attribute, which sets
        // the trigger which calls layoutUpdater() which calls calculateLayout() etc...
        // Note that the sprite's data attribute could be set to any value and it would still result
        // in the   trigger we need. For consistency, however, it is set to the labels.

        axis.getSprites()[0].setAttributes({ data: me.labels });
        me.fireEvent('datachange', me.labels);
    },

    /**
     * @method calculateLayout
     * @inheritdoc
     */
    calculateLayout: function(context) {
        context.data = this.labels;
        this.callParent([context]);
    },

    /**
     * @method calculateMajorTicks
     * @inheritdoc
     */
    calculateMajorTicks: function(context) {
        var me = this,
            attr = context.attr,
            data = context.data,
            range = attr.max - attr.min,
            viewMin = attr.min + range * attr.visibleMin,
            viewMax = attr.min + range * attr.visibleMax,
            out;

        out = me.snapEnds(context, Math.max(0, attr.min), Math.min(attr.max, data.length - 1), 1);

        if (out) {
            me.trimByRange(context, out, viewMin, viewMax);
            context.majorTicks = out;
        }
    },

    /**
     * @method snapEnds
     * @inheritdoc
     */
    snapEnds: function(context, min, max, estStepSize) {
        var data = context.data,
            steps;

        estStepSize = Math.ceil(estStepSize);
        steps = Math.floor((max - min) / estStepSize);

        return {
            min: min,
            max: max,
            from: min,
            to: steps * estStepSize + min,
            step: estStepSize,
            steps: steps,
            unit: 1,
            getLabel: function(currentStep) {
                return data[this.from + this.step * currentStep];
            },
            get: function(currentStep) {
                return this.from + this.step * currentStep;
            }
        };
    },

    /**
     * @method trimByRange
     * @inheritdoc
     */
    trimByRange: function(context, out, trimMin, trimMax) {
        var unit = out.unit,
            beginIdx = Math.ceil((trimMin - out.from) / unit) * unit,
            endIdx = Math.floor((trimMax - out.from) / unit) * unit,
            begin = Math.max(0, Math.ceil(beginIdx / out.step)),
            end = Math.min(out.steps, Math.floor(endIdx / out.step));

        if (end < out.steps) {
            out.to = end;
        }

        if (out.max > trimMax) {
            out.max = out.to;
        }

        if (out.from < trimMin && out.step > 0) {
            out.from = out.from + begin * out.step * unit;

            while (out.from < trimMin) {
                begin++;
                out.from += out.step * unit;
            }
        }

        if (out.min < trimMin) {
            out.min = out.from;
        }

        out.steps = end - begin;
    },

    getCoordFor: function(value, field, idx, items) {
        this.labels.push(value);

        return this.labels.length - 1;
    }
});
