/**
 * This class manages a set of numeric spans (2-element arrays marking begin and end
 * points). The method of this class coalesce and split spans as necessary to store
 * the fewest possible pairs needed to represent the covered (one-dimensional) area.
 *
 * @private
 * @since 6.5.0
 */
Ext.define('Ext.util.Spans', {
    isSpans: true,

    constructor: function() {
        this.spans = this.spans || [];
    },

    /**
     * Clears all spans.
     * @return {Ext.util.Spans} This Spans object.
     */
    clear: function() {
        this.spans.length = 0;

        return this;
    },

    /**
     * Adds a new span to the current set of spans. This will coalesce adjacent spans
     * as necessary to store the minimum number of spans possible.
     *
     * @param {Number/Number[]} begin Either the beginning of the span or a 2-element
     * array of `[begin,end]`.
     * @param {Number} [end] If `begin` is just the position, the second argument is
     * the end of the span to add. This value is exclusive of the span, that is it
     * marks the first position beyond the span. This ensures that `end - begin` is
     * the length of the span.
     * @return {Boolean} `true` if the new span changes this object, `false` if the
     * span was already in the set.
     */
    add: function(begin, end) {
        if (end === undefined) {
            if (typeof begin === 'number') {
                end = begin + 1;
            }
            else {
                end = begin[1];
                begin = begin[0];
            }
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            spans = me.spans,
            b, e, first, last, span;

        first = me.bisect(begin);

        if (first) {  // if (there is a previous span)
            span = spans[first - 1];
            b = span[0];
            e = span[1];

            if (begin <= e) {
                // This new span touches the previous one, but perhaps this new
                // span is contained inside the previous span...
                if (end <= e) {
                    return false;  // no change
                }

                // The new begin touches the previous span, but the new end goes
                // beyond it, so we extend it by replacing it.
                begin = b;
                spans.splice(--first, 1);
            }
        }

        // Now there is either no previous span, or if there was one, it does
        // not touch the new one.
        last = me.bisect(end);

        if (last > first) {
            // If we are replacing any spans, make sure the new "end" is at
            // least as large as the end of the last span we are replacing.
            span = spans[last - 1];
            end = Math.max(end, span[1]);
        }

        if (last < spans.length) {
            span = spans[last];

            // The span beyond our new span may be touching the end of the
            // new span, in which case we need to coalesce there as well.
            // Since we are removing it, we need to expand "end" to include
            // this additional span.
            if (end === span[0]) {
                end = span[1];
                ++last;
            }
        }

        spans.splice(first, last - first, [begin, end]);

        return true;
    },

    /**
     * Returns `true` if the given span is fully in the current set of spans.
     * @param {Number/Number[]} begin Either the beginning of the span or a 2-element
     * array of `[begin,end]`.
     * @param {Number} [end] If `begin` is just the position, the second argument is
     * the end of the span to add. This value is exclusive of the span, that is it
     * marks the first position beyond the span. This ensures that `end - begin` is
     * the length of the span.
     * @return {Boolean}
     */
    contains: function(begin, end) {
        if (end === undefined) {
            if (typeof begin === 'number') {
                end = begin + 1;
            }
            else {
                end = begin[1];
                begin = begin[0];
            }
        }

        // eslint-disable-next-line vars-on-top
        var spans = this.spans,
            index = this.bisect(begin),
            ret = false,
            e, span;

        if (index && begin < (e = spans[index - 1][1])) {
            ret = end <= e;
        }
        else if (index < spans.length) {
            span = spans[index];
            ret = span[0] <= begin && end <= span[1];
        }

        return ret;
    },

    /**
     * Calls the passed function for every integer in every span.
     * @param {Function} fn The function to call. Returning `false` will abort the operation.
     * @param {Mixed} scope The scope (`this` reference) in which the function will execute.
     */
    each: function(fn, scope) {
        var spans = this.spans,
            len = spans.length,
            i, span, j;

        for (i = 0; i < len; i++) {
            span = spans[i];

            for (j = span[0]; j < span[1]; j++) {
                if (fn.call(scope || this, i) === false) {
                    return;
                }
            }
        }
    },

    /**
     * Returns `true` if the specified span intersects with the current set of spans.
     *
     * @param {Number/Number[]} begin Either the beginning of the span or a 2-element
     * array of `[begin,end]`.
     * @param {Number} [end] If `begin` is just the position, the second argument is
     * the end of the span to add. This value is exclusive of the span, that is it
     * marks the first position beyond the span. This ensures that `end - begin` is
     * the length of the span.
     * @return {Boolean}
     */
    intersects: function(begin, end) {
        if (end === undefined) {
            if (typeof begin === 'number') {
                end = begin + 1;
            }
            else {
                end = begin[1];
                begin = begin[0];
            }
        }

        // eslint-disable-next-line vars-on-top
        var spans = this.spans,
            index = this.bisect(begin),
            ret = false;

        if (index && begin < spans[index - 1][1]) {
            ret = true;
        }
        else if (index < spans.length) {
            ret = spans[index][0] < end;
        }

        return ret;
    },

    /**
     * Removes a span from the current set of spans. This will coalesce adjacent spans
     * as necessary to store the minimum number of spans possible.
     *
     * @param {Number/Number[]} begin Either the beginning of the span or a 2-element
     * array of `[begin,end]`.
     * @param {Number} [end] If `begin` is just the position, the second argument is
     * the end of the span to add. This value is exclusive of the span, that is it
     * marks the first position beyond the span. This ensures that `end - begin` is
     * the length of the span.
     * @return {Boolean} `true` if removing the span changes this object, `false` if the
     * span was not in the set.
     */
    remove: function(begin, end) {
        if (end === undefined) {
            if (typeof begin === 'number') {
                end = begin + 1;
            }
            else {
                end = begin[1];
                begin = begin[0];
            }
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            spans = me.spans,
            first = me.bisect(begin),
            ret = false,
            last, span, tmp;

        if (first) {
            span = spans[first - 1];
            tmp = span[1];

            if (begin < tmp) {
                span[1] = begin;

                if (end < tmp) {
                    spans.splice(first, 0, [end, tmp]);

                    return true;
                }

                ret = true;
            }
        }

        last = me.bisect(end);

        if (first < last) {
            ret = true;
            span = spans[last - 1];

            if (end < span[1]) {
                span[0] = end;
                --last;
            }

            last -= first;

            if (last) {
                spans.splice(first, last);
            }
        }

        return ret;
    },

    /**
     * Returns an object that holds the current state and can be passed back later
     * to `unstash` to restore that state.
     * @return {Object}
     */
    stash: function() {
        return this.spans.slice();
    },

    /**
     * Takes an object a state object returned by `stash` and makes that the current
     * state.
     * @return {Ext.util.Spans} This Spans object.
     */
    unstash: function(pickle) {
        this.spans = pickle;

        return this;
    },

    /**
     * @return {Number} the number of integer locations covered by all the spans.
     */
    getCount: function() {
        var spans = this.spans,
            len = spans.length,
            result = 0,
            i, span;

        for (i = 0; i < len; i++) {
            span = spans[i];
            result += span[1] - span[0];
        }

        return result;
    },

    privates: {
        bisect: function(value) {
            return Ext.Number.bisectTuples(this.spans, value, 0);
        }
    }
});
