Ext.define('Ext.ux.gauge.needle.Arrow', {
    extend: 'Ext.ux.gauge.needle.Abstract',
    alias: 'gauge.needle.arrow',

    config: {
        path: function(ir, or) {
            return or - ir > 30
                ? "M0," + (ir + 5) + " L-4," + ir + " L-4," + (ir + 10) + " L-1," +
                  (ir + 15) + " L-1," + (or - 7) + " L-5," + (or - 10) + " L0," + or +
                  " L5," + (or - 10) + " L1," + (or - 7) + " L1," + (ir + 15) +
                  " L4," + (ir + 10) + " L4," + ir + " Z"
                : '';
        }
    }
});
