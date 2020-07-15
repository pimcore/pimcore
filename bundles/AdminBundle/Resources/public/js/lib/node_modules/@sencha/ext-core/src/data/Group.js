/**
 * Encapsulates a group of records. Can provide a
 * {@link #getSummaryRecord} summary record.
 *
 * @since 6.5.0
 */
Ext.define('Ext.data.Group', {
    extend: 'Ext.util.Group',

    isDataGroup: true,

    store: null,

    /**
     * Returns the summary results for the group.
     * @return {Ext.data.Model}
     */
    getSummaryRecord: function() {
        var me = this,
            summaryRecord = me.summaryRecord,
            store = me.store,
            generation = store.getData().generation,
            M, T;

        if (!summaryRecord) {
            M = store.getModel();
            T = M.getSummaryModel();
            me.summaryRecord = summaryRecord = new T();
        }

        if (!summaryRecord.isRemote && summaryRecord.summaryGeneration !== generation) {
            summaryRecord.calculateSummary(me.items);
            summaryRecord.summaryGeneration = generation;
        }

        return summaryRecord;
    }
});
