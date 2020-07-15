/**
 * @private
 */
Ext.define('Ext.chart.Util', {
    singleton: true,

    /**
     * @private
     * Given a `range` array of two (min/max) numbers and an arbitrary array of numbers
     * (`data`), updates the given `range`, if the range of `data` exceeds it.
     * Typically, one would start with the `[NaN, NaN]` range array instance, call the
     * method on multiple datasets with that range instance, then validate it with
     * {@link #validateRange}.
     * @param {Number[]} range
     * @param {Number[]} data
     */
    expandRange: function(range, data) {
        var length = data.length,
            min = range[0],
            max = range[1],
            i, value;

        for (i = 0; i < length; i++) {
            value = data[i];

            // `null` is a "finite" number in JavaScript
            // and greater than any negative number.
            if (value == null || !isFinite(value)) {
                continue;
            }

            if (value < min || !isFinite(min)) {
                min = value;
            }

            if (value > max || !isFinite(max)) {
                max = value;
            }
        }

        range[0] = min;
        range[1] = max;
    },

    defaultRange: [0, 1],

    /**
     * @private
     * Makes sure the range exists, is not zero, and its min/max values are finite numbers.
     * If this is not the case, the values from the provided `defaultRange`
     * are used.
     *
     * The range to validate. Never modified.
     * @param {Number[]} range
     * The default range to use, if the given range is not a valid data structure,
     * if both values are infinities, or if both values are the same and dangerously
     * close to either infinity (which makes expansion of the range by the value of
     * `padding` impossible).
     * If only a single value is infinity, the other value will be derived
     * from the finite value by incrementing/decrementing it by the span
     * of the default range towards the infinity.
     * For example, if the `defaultRange` is `[0, 1]`, we have:
     *
     *     [5, Infinity]   --> [5, 6]
     *     [3, -Infinity]  --> [2, 3]
     *     [-Infinity, -5] --> [-6, -5]
     *     [-3, -Infinity] --> [-4, -3]
     *
     * @param {Number[]} [defaultRange=[0, 1]]
     * A non-negative padding to use in case of identical min/max.
     * Note that the range span is not guaranteed to be `padding * 2` in this case,
     * if min/max are close to MIN_SAFE_INTEGER/MAX_SAFE_INTEGER.
     * @param {Number} [padding=0.5]
     * @return {Number[]}
     */
    validateRange: function(range, defaultRange, padding) {
        var isFin0, isFin1;

        defaultRange = defaultRange || this.defaultRange.slice();

        if (!(padding === 0 || padding > 0)) {
            padding = 0.5;
        }

        if (!range || range.length !== 2) {
            return defaultRange;
        }

        range = [range[0], range[1]];

        if (!range[0]) {
            range[0] = 0;
        }

        if (!range[1]) {
            range[1] = 0;
        }

        if (padding && range[0] === range[1]) {
            range = [
                range[0] - padding,
                range[0] + padding
            ];

            // In case the range values are at Infinity, the expansion above by the value
            // of 'padding' won't do us much good, so we still have to fall back to the
            // 'defaultRange'.
            if (range[0] === range[1]) {
                return defaultRange;
            }
        }

        // Same sign infinities are ruled out at this point.

        isFin0 = isFinite(range[0]);
        isFin1 = isFinite(range[1]);

        if (!isFin0 && !isFin1) {
            return defaultRange;
        }
        // Different sign infinities are ruled out at this point.

        if (isFin0 && !isFin1) {
            range[1] = range[0] +
                       Ext.Number.sign(range[1]) * (defaultRange[1] - defaultRange[0]);
        }
        else if (isFin1 && !isFin0) {
            range[0] = range[1] +
                       Ext.Number.sign(range[0]) * (defaultRange[1] - defaultRange[0]);
        }

        // All infinities are ruled out at this point.

        return [
            Math.min(range[0], range[1]),
            Math.max(range[0], range[1])
        ];
    },

    applyAnimation: function(animation, oldAnimation) {
        if (!animation) {
            animation = {
                duration: 0
            };
        }
        else if (animation === true) {
            animation = {
                easing: 'easeInOut',
                duration: 500
            };
        }

        return oldAnimation ? Ext.apply({}, animation, oldAnimation) : animation;
    }
});
