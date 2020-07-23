Ext.define('Ext.locale.pt_BR.grid.filters.filter.Date', {
    override: 'Ext.grid.filters.filter.Date',
    getFields: function() {
        return {
            lt: { text: 'Antes' },
            gt: { text: 'Depois' },
            eq: { text: 'Em' }
        };
    }
});
