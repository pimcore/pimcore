Ext.define('Ext.ux.gauge.needle.Wedge', {
    extend: 'Ext.ux.gauge.needle.Abstract',
    alias: 'gauge.needle.wedge',

    config: {
        path: function(ir, or) {
            return or - ir > 10 ? "M-4," + ir + " L0," + or + " L4," + ir + " Z" : '';
        }
    }
});
