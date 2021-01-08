Ext.define('Ext.ux.gauge.needle.Rectangle', {
    extend: 'Ext.ux.gauge.needle.Abstract',
    alias: 'gauge.needle.rectangle',

    config: {
        path: function(ir, or) {
            return or - ir > 10
                ? "M-2," + ir + " L2," + ir + " L2," + or + " L-2," + or + " Z"
                : '';
        }
    }
});
