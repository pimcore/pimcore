Ext.define('Ext.ux.gauge.needle.Spike', {
    extend: 'Ext.ux.gauge.needle.Abstract',
    alias: 'gauge.needle.spike',

    config: {
        path: function(ir, or) {
            return or - ir > 10
                ? "M0," + (ir + 5) + " L-4," + ir + " L0," + or + " L4," + ir + " Z"
                : '';
        }
    }
});
