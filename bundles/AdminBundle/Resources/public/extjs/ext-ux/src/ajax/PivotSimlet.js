/**
 * Pivot Simlet does remote pivot calculations.
 * Filtering the pivot results doesn't work.
 */
Ext.define('Ext.ux.ajax.PivotSimlet', {
    extend: 'Ext.ux.ajax.JsonSimlet',
    alias: 'simlet.pivot',

    lastPost: null, // last Ajax params sent to this simlet
    lastResponse: null, // last JSON response produced by this simlet
    keysSeparator: '',
    grandTotalKey: '',

    doPost: function(ctx) {
        var me = this,
            ret = me.callParent(arguments); // pick up status/statusText

        me.lastResponse = me.processData(me.getData(ctx), Ext.decode(ctx.xhr.body));
        ret.responseText = Ext.encode(me.lastResponse);

        return ret;
    },

    processData: function(data, params) {
        var me = this,
            len = data.length,
            response = {
                success: true,
                leftAxis: [],
                topAxis: [],
                results: []
            },
            leftAxis = new Ext.util.MixedCollection(),
            topAxis = new Ext.util.MixedCollection(),
            results = new Ext.util.MixedCollection(),
            i, j, k, leftKeys, topKeys, item, agg;

        me.lastPost = params;
        me.keysSeparator = params.keysSeparator;
        me.grandTotalKey = params.grandTotalKey;

        for (i = 0; i < len; i++) {
            leftKeys = me.extractValues(data[i], params.leftAxis, leftAxis);
            topKeys = me.extractValues(data[i], params.topAxis, topAxis);

            // add record to grand totals
            me.addResult(data[i], me.grandTotalKey, me.grandTotalKey, results);

            for (j = 0; j < leftKeys.length; j++) {
                // add record to col grand totals
                me.addResult(data[i], leftKeys[j], me.grandTotalKey, results);

                // add record to left/top keys pair
                for (k = 0; k < topKeys.length; k++) {
                    me.addResult(data[i], leftKeys[j], topKeys[k], results);
                }
            }

            // add record to row grand totals
            for (j = 0; j < topKeys.length; j++) {
                me.addResult(data[i], me.grandTotalKey, topKeys[j], results);
            }
        }

        // extract items from their left/top collections and build the json response
        response.leftAxis = leftAxis.getRange();
        response.topAxis = topAxis.getRange();

        len = results.getCount();

        for (i = 0; i < len; i++) {
            item = results.getAt(i);
            item.values = {};

            for (j = 0; j < params.aggregate.length; j++) {
                agg = params.aggregate[j];

                item.values[agg.id] = me[agg.aggregator](
                    item.records, agg.dataIndex, item.leftKey, item.topKey
                );
            }

            delete(item.records);
            response.results.push(item);
        }

        leftAxis.clear();
        topAxis.clear();
        results.clear();

        return response;
    },

    getKey: function(value) {
        var me = this;

        me.keysMap = me.keysMap || {};

        if (!Ext.isDefined(me.keysMap[value])) {
            me.keysMap[value] = Ext.id();
        }

        return me.keysMap[value];
    },

    extractValues: function(record, dimensions, col) {
        var len = dimensions.length,
            keys = [],
            j, key, item, dim;

        key = '';

        for (j = 0; j < len; j++) {
            dim = dimensions[j];
            key += (j > 0 ? this.keysSeparator : '') + this.getKey(record[dim.dataIndex]);
            item = col.getByKey(key);

            if (!item) {
                item = col.add(key, {
                    key: key,
                    value: record[dim.dataIndex],
                    dimensionId: dim.id
                });
            }

            keys.push(key);
        }

        return keys;
    },

    addResult: function(record, leftKey, topKey, results) {
        var item = results.getByKey(leftKey + '/' + topKey);

        if (!item) {
            item = results.add(leftKey + '/' + topKey, {
                leftKey: leftKey,
                topKey: topKey,
                records: []
            });
        }

        item.records.push(record);
    },

    sum: function(records, measure, rowGroupKey, colGroupKey) {
        var length = records.length,
            total = 0,
            i;

        for (i = 0; i < length; i++) {
            total += Ext.Number.from(records[i][measure], 0);
        }

        return total;
    },

    avg: function(records, measure, rowGroupKey, colGroupKey) {
        var length = records.length,
            total = 0,
            i;

        for (i = 0; i < length; i++) {
            total += Ext.Number.from(records[i][measure], 0);
        }

        return length > 0 ? (total / length) : 0;
    },

    min: function(records, measure, rowGroupKey, colGroupKey) {
        var data = [],
            length = records.length,
            i, v;

        for (i = 0; i < length; i++) {
            data.push(records[i][measure]);
        }

        v = Ext.Array.min(data);

        return v;
    },

    max: function(records, measure, rowGroupKey, colGroupKey) {
        var data = [],
            length = records.length,
            i, v;

        for (i = 0; i < length; i++) {
            data.push(records[i][measure]);
        }

        v = Ext.Array.max(data);

        return v;
    },

    count: function(records, measure, rowGroupKey, colGroupKey) {
        return records.length;
    },

    variance: function(records, measure, rowGroupKey, colGroupKey) {
        var me = Ext.pivot.Aggregators,
            length = records.length,
            avg = me.avg.apply(me, arguments),
            total = 0,
            i;

        if (avg > 0) {
            for (i = 0; i < length; i++) {
                total += Math.pow(Ext.Number.from(records[i][measure], 0) - avg, 2);
            }
        }

        return (total > 0 && length > 1) ? (total / (length - 1)) : 0;
    },

    varianceP: function(records, measure, rowGroupKey, colGroupKey) {
        var me = Ext.pivot.Aggregators,
            length = records.length,
            avg = me.avg.apply(me, arguments),
            total = 0,
            i;

        if (avg > 0) {
            for (i = 0; i < length; i++) {
                total += Math.pow(Ext.Number.from(records[i][measure], 0) - avg, 2);
            }
        }

        return (total > 0 && length > 0) ? (total / length) : 0;
    },

    stdDev: function(records, measure, rowGroupKey, colGroupKey) {
        var me = Ext.pivot.Aggregators,
            v = me.variance.apply(me, arguments);

        return v > 0 ? Math.sqrt(v) : 0;
    },

    stdDevP: function(records, measure, rowGroupKey, colGroupKey) {
        var me = Ext.pivot.Aggregators,
            v = me.varianceP.apply(me, arguments);

        return v > 0 ? Math.sqrt(v) : 0;
    }

});
