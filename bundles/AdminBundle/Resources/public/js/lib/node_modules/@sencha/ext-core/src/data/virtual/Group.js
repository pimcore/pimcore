/**
 * This class contains remote group information about virtual stores and provides
 * a similar interface to `Ext.data.Group`.
 * @since 6.5.0
 */
Ext.define('Ext.data.virtual.Group', {
    isVirtualGroup: true,

    firstRecords: null,
    id: '',
    summaryRecord: null,

    constructor: function(key) {
        this.id = key;
        this.firstRecords = [];
    },

    first: function() {
        return this.firstRecords[0] || null;
    },

    getGroupKey: function() {
        return this.id;
    },

    getSummaryRecord: function() {
        return this.summaryRecord;
    }
});
