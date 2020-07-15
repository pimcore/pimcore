/**
 * @abstract
 * @class Ext.chart.axis.layout.Layout
 *
 * Interface used by Axis to process its data into a meaningful layout.
 */
Ext.define('Ext.chart.axis.layout.Layout', {
    mixins: {
        observable: 'Ext.mixin.Observable'
    },
    config: {
        /**
         * @cfg {Ext.chart.axis.Axis} axis The axis that the Layout is bound.
         */
        axis: null
    },

    constructor: function(config) {
        this.mixins.observable.constructor.call(this, config);
    },

    /**
     * Processes the data of the series bound to the axis.
     * @param {Ext.chart.series.Series} series The bound series.
     */
    processData: function(series) {
        var me = this,
            axis = me.getAxis(),
            direction = axis.getDirection(),
            boundSeries = axis.boundSeries,
            i, ln;

        if (series) {
            series['coordinate' + direction]();
        }
        else {
            for (i = 0, ln = boundSeries.length; i < ln; i++) {
                boundSeries[i]['coordinate' + direction]();
            }
        }
    },

    /**
     * Calculates the position of major ticks for the axis.
     * @param {Object} context
     */
    calculateMajorTicks: function(context) {
        var me = this,
            attr = context.attr,
            range = attr.max - attr.min,
            zoom = range / Math.max(1, attr.length) * (attr.visibleMax - attr.visibleMin),
            viewMin = attr.min + range * attr.visibleMin,
            viewMax = attr.min + range * attr.visibleMax,
            estStepSize = attr.estStepSize * zoom,
            majorTicks = me.snapEnds(context, attr.min, attr.max, estStepSize);

        if (majorTicks) {
            me.trimByRange(context, majorTicks, viewMin, viewMax);
            context.majorTicks = majorTicks;
        }
    },

    /**
     * Calculates the position of sub ticks for the axis.
     * @param {Object} context
     */
    calculateMinorTicks: function(context) {
        if (this.snapMinorEnds) {
            context.minorTicks = this.snapMinorEnds(context);
        }
    },

    /**
     * Calculates the position of tick marks for the axis.
     * @param {Object} context
     * @return {*}
     */
    calculateLayout: function(context) {
        var me = this,
            attr = context.attr;

        if (attr.length === 0) {
            return null;
        }

        if (attr.majorTicks) {
            me.calculateMajorTicks(context);

            if (attr.minorTicks) {
                me.calculateMinorTicks(context);
            }
        }
    },

    /**
     * @method
     * Snaps the data bound to the axis to meaningful tick marks.
     * @param {Object} context
     * @param {Number} min
     * @param {Number} max
     * @param {Number} estStepSize
     */
    snapEnds: Ext.emptyFn,

    /**
     * Trims the layout of the axis by the defined minimum and maximum.
     * @param {Object} context
     * @param {Object} ticks Ticks object (e.g. major ticks) to be modified.
     * @param {Number} trimMin
     * @param {Number} trimMax
     */
    trimByRange: function(context, ticks, trimMin, trimMax) {
        var segmenter = context.segmenter,
            unit = ticks.unit,
            beginIdx = segmenter.diff(ticks.from, trimMin, unit),
            endIdx = segmenter.diff(ticks.from, trimMax, unit),
            begin = Math.max(0, Math.ceil(beginIdx / ticks.step)),
            end = Math.min(ticks.steps, Math.floor(endIdx / ticks.step));

        if (end < ticks.steps) {
            ticks.to = segmenter.add(ticks.from, end * ticks.step, unit);
        }

        if (ticks.max > trimMax) {
            ticks.max = ticks.to;
        }

        if (ticks.from < trimMin) {
            ticks.from = segmenter.add(ticks.from, begin * ticks.step, unit);

            while (ticks.from < trimMin) {
                begin++;
                ticks.from = segmenter.add(ticks.from, ticks.step, unit);
            }
        }

        if (ticks.min < trimMin) {
            ticks.min = ticks.from;
        }

        ticks.steps = end - begin;
    }
});
