Ext.define("Ext.locale.it.grid.filters.filter.Date", {
    override: "Ext.grid.filters.filter.Date",

    getFields: function() {
        return {
            lt: { text: 'Prima del' },
            gt: { text: 'Dopo il' },
            eq: { text: 'Il giorno' }
        };
    }
});
